<?php
/**
 * Explain Results API – Premium feature
 * Accepts calculator results, explains them in plain language.
 * Supports follow-up questions when conversation + follow_up_question are sent.
 * Requires: logged-in session + premium subscription.
 *
 * Model tiering:
 *   - Advisor tier (calcforadvisors paid: monthly/annual) -> Claude Fable 5,
 *     but only when an Anthropic key is configured AND the advisor is under their
 *     monthly cap. Otherwise it transparently falls back to OpenAI.
 *   - Everyone else (ronbelisle consumer premium) -> OpenAI gpt-4o-mini.
 *
 * Cost safety: with no Anthropic key configured, Fable 5 stays dormant and the
 * feature behaves exactly as before (OpenAI only). A per-advisor monthly cap
 * (FABLE5_MONTHLY_CAP) prevents runaway spend; over the cap we fall back to OpenAI.
 */
require_once __DIR__ . '/../includes/api_resources.php';
rb_api_errors();
error_reporting(0);
ini_set('display_errors', 0);
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();

require_once __DIR__ . '/../includes/db_config.php';

// Provider config (both optional). OpenAI is the baseline/fallback; Anthropic powers Fable 5.
require_once __DIR__ . '/../includes/openai_config.php';
require_once __DIR__ . '/../includes/anthropic_config.php';

// Default consumer model + Fable 5 tuning knobs (override in config if desired).
if (!defined('OPENAI_EXPLAIN_MODEL')) define('OPENAI_EXPLAIN_MODEL', 'gpt-4o-mini');
if (!defined('FABLE5_MODEL'))        define('FABLE5_MODEL', 'claude-fable-5');
// Effort controls how much (billed) thinking Fable 5 does. Plain-language
// explanations don't need deep reasoning, so keep this low/medium for cost control.
if (!defined('FABLE5_EFFORT'))       define('FABLE5_EFFORT', 'low');
// Max explanations (initial + follow-ups) a single advisor can run per calendar
// month on Fable 5 before we fall back to the cheap model. Tune to taste.
if (!defined('FABLE5_MONTHLY_CAP'))  define('FABLE5_MONTHLY_CAP', 100);

function explain_openai_configured() {
    return defined('OPENAI_API_KEY') && OPENAI_API_KEY !== '' && strpos(OPENAI_API_KEY, 'sk-your-') !== 0;
}

function explain_anthropic_configured() {
    return defined('ANTHROPIC_API_KEY') && ANTHROPIC_API_KEY !== '' && strpos(ANTHROPIC_API_KEY, 'sk-ant-your-') !== 0;
}

// Need at least one working provider.
if (!explain_openai_configured() && !explain_anthropic_configured()) {
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

/** Is this request from a paid calcforadvisors advisor (the Fable 5 tier)? */
function explain_is_advisor_tier() {
    return (get_scenario_owner()['type'] ?? null) === 'cfa';
}

// Parse JSON input

$data = rb_read_api_json(65536);
try { $data=rb_ai_data($data); } catch(Throwable $e) { rb_api_error(400,'Invalid or oversized explanation request'); }
require_once __DIR__.'/../includes/csrf.php';
$token=$_SERVER['HTTP_X_CSRF_TOKEN']??null;
if(!is_string($token)||!rb_csrf_validate($token)) rb_api_error(403,'Reload the calculator and try again.');
$owner=get_scenario_owner();
if(!$owner) rb_api_error(403,'Premium account required');
$lease=rb_ai_lease($owner['type'].':'.$owner['id']);
if(!$lease){header('Retry-After: 60');rb_api_error(429,'Please wait before requesting another explanation.');}
register_shutdown_function(static function()use($lease){if(is_resource($lease))fclose($lease);});


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

// Follow-up requests from the shared modal retain the result summary, not custom mode fields.
$is_pas_stress = $calculator_type === 'vanguard-pas-vs-target-date'
    && (($data['analysis_mode'] ?? '') === 'stress' || str_starts_with($results_summary, 'Retirement Stress Test.'));

// Build prompt
$system_prompt = "You are a helpful financial planning assistant. Explain the user's calculator results in plain language. Be clear, educational, and supportive. Do not give specific investment or legal advice. Keep the tone friendly and professional. Do NOT say things like \"feel free to ask\" in your text—the interface provides a follow-up question box. End responses with a neutral closing sentence (e.g., \"This explanation is for educational purposes only.\").";

if ($calculator_type === 'vanguard-pas-vs-target-date' && !$is_pas_stress) {
    $system_prompt .= " For this calculator, Total Opportunity Cost is the grand total over the timeline—the sum of Direct Fee Difference plus compounding/withdrawal effects. Never describe Total Opportunity Cost as an extra cost in addition to those components (that would double-count). When you mention opportunity cost, clearly state that the total equals the modeled fee difference plus signed compounding/withdrawal effects. The latter are not fees and may be negative. Withdrawals use Conservative, Moderate, then Aggressive without replenishment; identical bucket returns do not create additional investment performance.";
}

if ($calculator_type === 'vanguard-pas-vs-target-date' && $is_pas_stress) {
    $system_prompt .= " This is Retirement Stress Test mode, not Simple Projection. Treat Monte Carlo outcomes as hypothetical illustrations, never predictions or guarantees. Discuss assumptions, correlated market variability, inflation-adjusted withdrawals, fees, sequence risk, spending survival and ending-balance uncertainty separately. Differences in expected return or volatility are not fees. Do not apply the deterministic opportunity-cost identity to unrelated medians. Depletion and failure years are conditional on those events occurring. Explain no-replenishment sequential bucket spending without claiming it eliminates sequence risk. Do not declare either strategy better based on one statistic. Defaults are generic assumptions, not Vanguard forecasts.";
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

    if ($calculator_type === 'vanguard-pas-vs-target-date' && !$is_pas_stress) {
        $user_prompt .= "\n\nExplain the signed ending-balance difference as the direct fee difference plus compounding and withdrawal effects. Do not call all of it fees or imply the total is additional to its components. Use the supplied bucket depletion and withdrawal assumptions.";
    }

    $messages[] = ['role' => 'user', 'content' => $user_prompt];
}

$max_tokens = $is_follow_up ? 1000 : 1500;

/**
 * Call OpenAI Chat Completions. Returns ['ok'=>bool, 'text'=>string, 'error'=>string].
 */
function call_openai($model, $messages, $max_tokens) {
    $payload = [
        'model' => $model,
        'messages' => $messages,
        'max_tokens' => $max_tokens,
        'temperature' => 0.6,
    ];
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        return ['ok' => false, 'text' => '', 'error' => 'Could not reach AI service: ' . $curl_err];
    }
    $decoded = json_decode($response, true);
    if ($http_code !== 200 || !isset($decoded['choices'][0]['message']['content'])) {
        $msg = 'AI service error';
        if (isset($decoded['error']['message'])) {
            $msg = $decoded['error']['message'];
        } elseif (!empty($response)) {
            $msg = substr(strip_tags($response), 0, 200);
        }
        return ['ok' => false, 'text' => '', 'error' => $msg];
    }
    return ['ok' => true, 'text' => trim($decoded['choices'][0]['message']['content']), 'error' => ''];
}

/**
 * Call Anthropic Messages API (Claude Fable 5).
 * Returns ['ok'=>bool, 'text'=>string, 'refusal'=>bool, 'error'=>string].
 * System prompt is a top-level param; messages must be user/assistant only.
 */
function call_anthropic($model, $messages, $max_tokens, $effort) {
    $system = '';
    $turns = [];
    foreach ($messages as $m) {
        if (($m['role'] ?? '') === 'system') {
            $system = $m['content'];
            continue;
        }
        $turns[] = ['role' => $m['role'], 'content' => $m['content']];
    }

    $payload = [
        'model' => $model,
        'max_tokens' => $max_tokens,
        'system' => $system,
        'messages' => $turns,
        'output_config' => ['effort' => $effort],
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . ANTHROPIC_API_KEY,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        return ['ok' => false, 'text' => '', 'refusal' => false, 'error' => 'Could not reach AI service: ' . $curl_err];
    }
    $decoded = json_decode($response, true);
    if ($http_code !== 200 || !isset($decoded['content']) || !is_array($decoded['content'])) {
        $msg = 'AI service error';
        if (isset($decoded['error']['message'])) {
            $msg = $decoded['error']['message'];
        } elseif (!empty($response)) {
            $msg = substr(strip_tags($response), 0, 200);
        }
        return ['ok' => false, 'text' => '', 'refusal' => false, 'error' => $msg];
    }

    // Fable 5 can refuse with HTTP 200 + stop_reason "refusal".
    if (($decoded['stop_reason'] ?? '') === 'refusal') {
        return ['ok' => false, 'text' => '', 'refusal' => true, 'error' => 'Request was declined by the safety system.'];
    }

    // Concatenate text blocks (ignore thinking blocks).
    $text = '';
    foreach ($decoded['content'] as $block) {
        if (($block['type'] ?? '') === 'text' && isset($block['text'])) {
            $text .= $block['text'];
        }
    }
    $text = trim($text);
    if ($text === '') {
        return ['ok' => false, 'text' => '', 'refusal' => false, 'error' => 'Empty response from AI service.'];
    }
    return ['ok' => true, 'text' => $text, 'refusal' => false, 'error' => ''];
}

// Decide provider: Fable 5 for advisors (key present + under cap), else OpenAI.
$use_fable = false;
$advisor_sid = null;
if (explain_is_advisor_tier() && explain_anthropic_configured()) {
    $advisor_sid = (int) $_SESSION['calcforadvisors_subscriber_id'];
    if (rb_ai_reserve_monthly($conn ?? null, $advisor_sid, (int) FABLE5_MONTHLY_CAP)) {
        $use_fable = true;
    }
}

if(session_status()===PHP_SESSION_ACTIVE) session_write_close();
$explanation = null;

if ($use_fable) {
    $result = call_anthropic(FABLE5_MODEL, $messages, $max_tokens, FABLE5_EFFORT);
    if ($result['ok']) {
        // Attempt reserved atomically before the provider call.
        $explanation = $result['text'];
    }
    // On refusal or error, fall through to OpenAI (if available) so the advisor
    // still gets an explanation rather than a hard failure.
}

if ($explanation === null) {
    if (!explain_openai_configured()) {
        header('Content-Type: application/json');
        http_response_code(502);
        die(json_encode(['error' => 'AI service is temporarily unavailable. Please try again.']));
    }
    $result = call_openai(OPENAI_EXPLAIN_MODEL, $messages, $is_follow_up ? 400 : 600);
    if (!$result['ok']) {
        header('Content-Type: application/json');
        http_response_code(502);
        error_log('AI provider request failed');
        die(json_encode(['error' => 'AI service is temporarily unavailable. Please try again.']));
    }
    $explanation = $result['text'];
}

header('Content-Type: application/json');
echo json_encode(['explanation' => $explanation]);
