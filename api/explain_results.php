<?php
/**
 * Explain Results API – Premium feature
 * Accepts calculator results, calls OpenAI to explain them in plain language.
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

if (strlen($results_summary) > 8000) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'Results summary is too long. Please shorten or summarize.']));
}

// Build prompt
$system_prompt = "You are a helpful financial planning assistant. Explain the user's calculator results in plain language. Use 2–4 short paragraphs. Be clear, educational, and supportive. Do not give specific investment or legal advice. Keep the tone friendly and professional.";

$user_prompt = "A user ran the \"" . $calculator_type . "\" calculator. Here are their results:\n\n" . $results_summary . "\n\nExplain these results in plain language.";

$payload = [
    'model' => 'gpt-4o-mini',
    'messages' => [
        ['role' => 'system', 'content' => $system_prompt],
        ['role' => 'user', 'content' => $user_prompt]
    ],
    'max_tokens' => 600,
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
