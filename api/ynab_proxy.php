<?php
/**
 * YNAB Budget Auditor – OpenAI proxy
 *
 * Thin server-side forwarder for chat completions. The client's OpenAI key
 * is passed in the Authorization header; budget text is sent in the POST body.
 * No keys are stored on the server.
 */
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed. Use POST.']));
}

function read_authorization_header() {
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        return trim($_SERVER['HTTP_AUTHORIZATION']);
    }
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, 'Authorization') === 0) {
                return trim($value);
            }
        }
    }
    return '';
}

$auth_header = read_authorization_header();
if (!preg_match('/^Bearer\s+(\S+)\s*$/i', $auth_header, $matches)) {
    http_response_code(401);
    die(json_encode(['error' => 'Missing or invalid Authorization header. Send: Bearer {openai_api_key}']));
}

$openai_key = trim($matches[1]);
if ($openai_key === '' || strpos($openai_key, 'sk-') !== 0) {
    http_response_code(401);
    die(json_encode(['error' => 'Invalid OpenAI API key format.']));
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || empty($data['budget_summary'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Missing budget_summary. Send JSON: { "budget_summary": "..." }']));
}

$budget_summary = trim($data['budget_summary']);

if (strlen($budget_summary) > 12000) {
    http_response_code(400);
    die(json_encode(['error' => 'Budget summary is too long.']));
}

$follow_up_question = isset($data['follow_up_question']) ? trim($data['follow_up_question']) : '';
$conversation = isset($data['conversation']) && is_array($data['conversation']) ? $data['conversation'] : [];
$is_follow_up = ($follow_up_question !== '');

if ($is_follow_up) {
    if (strlen($follow_up_question) > 2000) {
        http_response_code(400);
        die(json_encode(['error' => 'Follow-up question is too long.']));
    }
    if (count($conversation) > 20) {
        http_response_code(400);
        die(json_encode(['error' => 'Too many follow-up messages in this session.']));
    }
}

$system_prompt =
    'You are an expert YNAB (You Need A Budget) analyst reviewing a zero-based budget export. ' .
    'Follow strict YNAB semantics — do not apply generic accounting intuition.' . "\n\n" .
    'CRITICAL YNAB RULES:' . "\n" .
    '1. "Available" is the ONLY indicator of overspending. Flag a category as overspent ONLY when Available is less than $0.00. ' .
    'Never infer overspending from a negative Activity number alone.' . "\n" .
    '2. Activity is this month\'s cash flow in the category: negative Activity usually means spending; positive Activity means inflows or refunds.' . "\n" .
    '3. Credit Card Payments categories (under a "Credit Card Payments" group): negative Activity is COMPLETELY NORMAL and represents regular credit card spending moved from spending categories — NOT a budget deficit. ' .
    'If Available is zero or positive (sufficient to cover the card), treat it as a healthy paid-in-full or on-track credit card workflow. Do NOT flag it as overspending.' . "\n" .
    '4. Regular spending categories (Groceries, Legal & Tax Prep, etc.): overspending occurs only when Available goes negative.' . "\n" .
    '5. When noting concerns, clearly separate true overspending (Available < $0) from normal credit card mechanics.' . "\n\n" .
    'Your tasks: identify categories with Available < $0, evaluate whether long-term savings buffers are growing, note genuine anomalies, ' .
    'and provide a concise, plain-English 3-bullet action plan for the month.';

$messages = [['role' => 'system', 'content' => $system_prompt]];

if ($is_follow_up) {
    $system_prompt .= ' Answer only the follow-up question using the YNAB budget data and prior analysis. ' .
        'Keep the answer concise (1–3 short paragraphs). Maintain strict YNAB semantics. ' .
        'Do not invite further chat in prose.';
    $messages[0]['content'] = $system_prompt;

    $messages[] = [
        'role' => 'user',
        'content' => "YNAB budget data for this audit:\n\n" . $budget_summary,
    ];

    foreach ($conversation as $turn) {
        if (!is_array($turn)) {
            continue;
        }
        $role = isset($turn['role']) ? trim($turn['role']) : '';
        $content = isset($turn['content']) ? trim($turn['content']) : '';
        if ($content === '') {
            continue;
        }
        if ($role !== 'user' && $role !== 'assistant') {
            continue;
        }
        if (strlen($content) > 4000) {
            $content = substr($content, 0, 4000);
        }
        $messages[] = ['role' => $role, 'content' => $content];
    }

    $messages[] = ['role' => 'user', 'content' => 'Follow-up question: ' . $follow_up_question];
} else {
    $user_prompt = 'Review this YNAB category data for the current month and provide your analysis:' . "\n\n" . $budget_summary;
    $messages[] = ['role' => 'user', 'content' => $user_prompt];
}

$payload = [
    'model' => 'gpt-4o',
    'messages' => $messages,
    'max_tokens' => $is_follow_up ? 400 : 900,
    'temperature' => 0.5,
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $openai_key,
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 45,
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err = curl_error($ch);
curl_close($ch);

if ($curl_err) {
    http_response_code(502);
    die(json_encode(['error' => 'Could not reach OpenAI: ' . $curl_err]));
}

$decoded = json_decode($response, true);

if ($http_code !== 200 || !isset($decoded['choices'][0]['message']['content'])) {
    $msg = 'OpenAI service error';
    if (isset($decoded['error']['message'])) {
        $msg = $decoded['error']['message'];
    } elseif (!empty($response)) {
        $msg = substr(strip_tags($response), 0, 200);
    }
    http_response_code($http_code >= 400 && $http_code < 600 ? $http_code : 502);
    die(json_encode(['error' => $msg]));
}

$analysis = trim($decoded['choices'][0]['message']['content']);
echo json_encode(['analysis' => $analysis]);
