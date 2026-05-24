<?php
/**
 * Explain Results API – Premium feature
 * Accepts calculator results, calls OpenAI to explain them in plain language.
 * Supports follow-up questions when conversation + follow_up_question are sent.
 * Requires: logged-in session + premium subscription.
 */
error_reporting(0);
ini_set('display_errors', 0);
session_start();

require_once __DIR__ . '/../includes/db_config.php';

// OpenAI config (optional – returns 503 if not configured)
require_once __DIR__ . '/../includes/openai_config.php';

if (!defined('OPENAI_API_KEY') || OPENAI_API_KEY === '' || strpos(OPENAI_API_KEY, 'sk-your-') === 0) {
    header('Content-Type: application/json');
    http_response_code(503);
    die(json_encode(['error' => 'AI Explain feature is not configured on this server']));
}

// Auth: must have Premium access (ronbelisle or calcforadvisors paid)
require_once __DIR__ . '/../includes/has_premium_access.php';
if (!has_premium_access()) {
    header('Content-Type: application/json');
    http_response_code(403);
    die(json_encode(['error' => 'Premium subscription required to use AI Explain']));
}

// Parse JSON input
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || empty($data['results_summary'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'Missing results_summary. Send JSON: { "calculator_type": "...", "results_summary": "..." }']));
}

$calculator_type = isset($data['calculator_type']) ? trim($data['calculator_type']) : 'calculator';
$results_summary = trim($data['results_summary']);
$follow_up_question = isset($data['follow_up_question']) ? trim($data['follow_up_question']) : '';
$conversation = isset($data['conversation']) && is_array($data['conversation']) ? $data['conversation'] : [];

if (strlen($results_summary) > 8000) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'Results summary is too long. Please shorten or summarize.']));
}

$is_follow_up = ($follow_up_question !== '');

if ($is_follow_up) {
    if (strlen($follow_up_question) > 2000) {
        header('Content-Type: application/json');
        http_response_code(400);
        die(json_encode(['error' => 'Follow-up question is too long.']));
    }
    if (count($conversation) > 20) {
        header('Content-Type: application/json');
        http_response_code(400);
        die(json_encode(['error' => 'Too many follow-up messages in this session.']));
    }
}

// Build prompt
$system_prompt = "You are a helpful financial planning assistant. Explain the user's calculator results in plain language. Be clear, educational, and supportive. Do not give specific investment or legal advice. Keep the tone friendly and professional. Do NOT say things like \"feel free to ask\" in your text—the interface provides a follow-up question box. End responses with a neutral closing sentence (e.g., \"This explanation is for educational purposes only.\").";

if ($calculator_type === 'vanguard-pas-vs-target-date') {
    $system_prompt .= " For this calculator, Total Opportunity Cost is the grand total over the timeline—the sum of Direct Fee Difference plus Lost Growth. Never describe Total Opportunity Cost as an extra cost in addition to those components (that would double-count). When you mention opportunity cost, clearly state that the total equals direct fees paid out of pocket plus lost compounding on those fee dollars.";
}

$messages = [['role' => 'system', 'content' => $system_prompt]];

if ($is_follow_up) {
    $system_prompt .= " Answer only the follow-up question using the calculator results and prior explanation. Keep the answer concise (1–3 short paragraphs). Do not invite further chat in prose.";
    $messages[0]['content'] = $system_prompt;

    $context_user = "Calculator: \"" . $calculator_type . "\".\n\nResults:\n\n" . $results_summary;
    $messages[] = ['role' => 'user', 'content' => $context_user];

    foreach ($conversation as $turn) {
        if (!is_array($turn)) continue;
        $role = isset($turn['role']) ? trim($turn['role']) : '';
        $content = isset($turn['content']) ? trim($turn['content']) : '';
        if ($content === '') continue;
        if ($role !== 'user' && $role !== 'assistant') continue;
        if (strlen($content) > 4000) {
            $content = substr($content, 0, 4000);
        }
        $messages[] = ['role' => $role, 'content' => $content];
    }

    $messages[] = ['role' => 'user', 'content' => "Follow-up question: " . $follow_up_question];
} else {
    $user_prompt = "A user ran the \"" . $calculator_type . "\" calculator. Here are their results:\n\n" . $results_summary . "\n\nExplain these results in plain language. Use 2–4 short paragraphs.";

    if ($calculator_type === 'vanguard-pas-vs-target-date') {
        $user_prompt .= "\n\nWhen explaining opportunity cost, use wording like: \"The Total Opportunity Cost of [total] represents the grand total of what you give up over the timeline. This is the sum of two distinct factors: the Direct Fee Difference of [direct] that you pay out of pocket, and [lost growth] in Lost Growth because those fee dollars were removed from the market and couldn't compound.\" Do not imply the user loses the total plus the components separately.";
    }

    $messages[] = ['role' => 'user', 'content' => $user_prompt];
}

$payload = [
    'model' => 'gpt-4o-mini',
    'messages' => $messages,
    'max_tokens' => $is_follow_up ? 400 : 600,
    'temperature' => 0.6
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err = curl_error($ch);
curl_close($ch);

if ($curl_err) {
    header('Content-Type: application/json');
    http_response_code(502);
    die(json_encode(['error' => 'Could not reach AI service: ' . $curl_err]));
}

$decoded = json_decode($response, true);

if ($http_code !== 200 || !isset($decoded['choices'][0]['message']['content'])) {
    $msg = 'AI service error';
    if (isset($decoded['error']['message'])) {
        $msg = $decoded['error']['message'];
    } elseif (!empty($response)) {
        $msg = substr(strip_tags($response), 0, 200);
    }
    header('Content-Type: application/json');
    http_response_code(502);
    die(json_encode(['error' => $msg]));
}

$explanation = trim($decoded['choices'][0]['message']['content']);

header('Content-Type: application/json');
echo json_encode(['explanation' => $explanation]);
