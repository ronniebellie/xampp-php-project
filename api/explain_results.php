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
error_reporting(0);
ini_set('display_errors', 0);
session_start();

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
    return !empty($_SESSION['calcforadvisors_subscriber_id'])
        && in_array($_SESSION['calcforadvisors_plan'] ?? '', ['monthly', 'annual'], true);
}

/**
 * Monthly cap helpers (best-effort). On any DB problem we return a "cannot
 * confirm" result so the caller falls back to the cheap model — failing safe
 * toward lower cost rather than risking uncapped Fable 5 spend.
 */
function fable5_under_cap($conn, $subscriber_id) {
    if (!$conn) return false;
    $period = date('Ym');
    $ok = @$conn->query(
        "CREATE TABLE IF NOT EXISTS ai_explain_usage (" .
        "subscriber_id INT NOT NULL, period CHAR(6) NOT NULL, " .
        "used INT NOT NULL DEFAULT 0, PRIMARY KEY (subscriber_id, period))"
    );
    if ($ok === false) return false;
    $stmt = @$conn->prepare("SELECT used FROM ai_explain_usage WHERE subscriber_id = ? AND period = ?");
    if (!$stmt) return false;
    $stmt->bind_param("is", $subscriber_id, $period);
    if (!$stmt->execute()) { $stmt->close(); return false; }
    $used = 0;
    $stmt->bind_result($used);
    $stmt->fetch();
    $stmt->close();
    return ((int) $used) < FABLE5_MONTHLY_CAP;
}

function fable5_record_usage($conn, $subscriber_id) {
    if (!$conn) return;
    $period = date('Ym');
    $stmt = @$conn->prepare(
        "INSERT INTO ai_explain_usage (subscriber_id, period, used) VALUES (?, ?, 1) " .
        "ON DUPLICATE KEY UPDATE used = used + 1"
    );
    if (!$stmt) return;
    $stmt->bind_param("is", $subscriber_id, $period);
    @$stmt->execute();
    $stmt->close();
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
    if (fable5_under_cap($conn ?? null, $advisor_sid)) {
        $use_fable = true;
    }
}

$explanation = null;

if ($use_fable) {
    $result = call_anthropic(FABLE5_MODEL, $messages, $max_tokens, FABLE5_EFFORT);
    if ($result['ok']) {
        fable5_record_usage($conn ?? null, $advisor_sid);
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
        die(json_encode(['error' => $result['error']]));
    }
    $explanation = $result['text'];
}

header('Content-Type: application/json');
echo json_encode(['explanation' => $explanation]);
