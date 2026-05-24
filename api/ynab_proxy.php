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

$system_prompt =
    'You are an expert financial analyst reviewing a meticulous YNAB budget. ' .
    'Look closely at the relationship between budgeted amounts and activity. ' .
    'Flag any categories showing overspending, evaluate if long-term savings buffers are growing, ' .
    'note anomalies, and provide a concise, plain-English 3-bullet action plan for the month.';

$user_prompt = 'Review this YNAB category data for the current month and provide your analysis:' . "\n\n" . $budget_summary;

$payload = [
    'model' => 'gpt-4o',
    'messages' => [
        ['role' => 'system', 'content' => $system_prompt],
        ['role' => 'user', 'content' => $user_prompt],
    ],
    'max_tokens' => 900,
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
