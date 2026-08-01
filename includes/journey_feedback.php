<?php
/**
 * Journey product feedback — store + admin helpers (no email notifications).
 */

if (defined('JOURNEY_FEEDBACK_LOADED')) {
    return;
}
define('JOURNEY_FEEDBACK_LOADED', 1);

require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/journey_entitlement.php';
require_once __DIR__ . '/journey_checkout.php';

const JOURNEY_FEEDBACK_MAX_TEXT = 5000;
const JOURNEY_FEEDBACK_MAX_URL = 1024;
const JOURNEY_FEEDBACK_MAX_UA = 512;
const JOURNEY_FEEDBACK_LIST_LIMIT = 50;

/**
 * @return list<string>
 */
function journey_feedback_known_phases(): array
{
    return [
        'spending-goals',
        'social-security',
        'build-your-plan',
        'stress-test',
        'tax-strategy',
        'survivor-planning',
        'continue-to-phase-2',
        'retirement-spending-plan',
        'home',
        'feedback',
        'premium-plan',
        'premium-success',
        'premium-checkout',
    ];
}

function journey_feedback_normalize_phase(?string $phase): ?string
{
    $phase = strtolower(trim((string) $phase));
    if ($phase === '') {
        return null;
    }
    if (in_array($phase, journey_feedback_known_phases(), true)) {
        return $phase;
    }
    if (preg_match('/^[a-z0-9][a-z0-9_-]{0,62}$/', $phase)) {
        return $phase;
    }
    return null;
}

function journey_feedback_infer_phase_from_url(?string $url): ?string
{
    $url = trim((string) $url);
    if ($url === '') {
        return null;
    }
    $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
    $map = [
        '/phases/spending-goals.php' => 'spending-goals',
        '/phases/social-security.php' => 'social-security',
        '/phases/build-your-plan.php' => 'build-your-plan',
        '/phases/stress-test.php' => 'stress-test',
        '/phases/tax-strategy.php' => 'tax-strategy',
        '/phases/survivor-planning.php' => 'survivor-planning',
        '/phases/continue-to-phase-2.php' => 'continue-to-phase-2',
        '/calculators/retirement-spending-plan/' => 'retirement-spending-plan',
        '/calculators/retirement-spending-plan' => 'retirement-spending-plan',
        '/feedback.php' => 'feedback',
        '/premium/journey.php' => 'premium-plan',
        '/premium/journey-success.php' => 'premium-success',
        '/premium/journey-checkout.php' => 'premium-checkout',
        '/' => 'home',
        '/index.php' => 'home',
    ];
    if (isset($map[$path])) {
        return $map[$path];
    }
    foreach ($map as $needle => $phase) {
        if ($needle !== '/' && strpos($path, $needle) === 0) {
            return $phase;
        }
    }
    return null;
}

function journey_feedback_sanitize_page_url(?string $url): ?string
{
    $url = trim((string) $url);
    if ($url === '') {
        return null;
    }
    if (strlen($url) > JOURNEY_FEEDBACK_MAX_URL) {
        $url = substr($url, 0, JOURNEY_FEEDBACK_MAX_URL);
    }
    if (!preg_match('#^https?://#i', $url)) {
        return null;
    }
    $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
    if (!in_array($host, ['journey.ronbelisle.com', 'ronbelisle.com', 'www.ronbelisle.com'], true)) {
        return null;
    }
    return $url;
}

function journey_feedback_tables_ready(mysqli $conn): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    $result = $conn->query("SHOW TABLES LIKE 'journey_feedback'");
    $ready = $result && $result->num_rows > 0;
    if ($result instanceof mysqli_result) {
        $result->free();
    }
    return $ready;
}

/**
 * @param array{
 *   trying_to_do?:string,
 *   what_happened?:string,
 *   email?:?string,
 *   page_url?:?string,
 *   journey_phase?:?string,
 *   user_agent?:?string,
 *   user_id?:int,
 *   is_signed_in?:bool|int,
 *   is_premium?:bool|int
 * } $input
 * @return array{ok:bool,id?:int,error?:string,message?:string}
 */
function journey_feedback_store(mysqli $conn, array $input): array
{
    if (!journey_feedback_tables_ready($conn)) {
        return ['ok' => false, 'error' => 'schema_missing', 'message' => 'Feedback storage is not ready.'];
    }

    $trying = trim((string) ($input['trying_to_do'] ?? ''));
    $happened = trim((string) ($input['what_happened'] ?? ''));
    if ($trying === '' || $happened === '') {
        return ['ok' => false, 'error' => 'missing_fields', 'message' => 'Please complete both feedback fields.'];
    }
    if (mb_strlen($trying) > JOURNEY_FEEDBACK_MAX_TEXT || mb_strlen($happened) > JOURNEY_FEEDBACK_MAX_TEXT) {
        return ['ok' => false, 'error' => 'too_long', 'message' => 'Feedback is too long. Please shorten it.'];
    }

    $emailRaw = trim((string) ($input['email'] ?? ''));
    $email = '';
    if ($emailRaw !== '') {
        if (!filter_var($emailRaw, FILTER_VALIDATE_EMAIL) || strlen($emailRaw) > 255) {
            return ['ok' => false, 'error' => 'invalid_email', 'message' => 'Please enter a valid email address.'];
        }
        $email = $emailRaw;
    }

    $userId = isset($input['user_id']) ? (int) $input['user_id'] : 0;
    $isSignedIn = (!empty($input['is_signed_in']) || $userId > 0) ? 1 : 0;
    $isPremium = !empty($input['is_premium']) ? 1 : 0;

    $pageUrl = journey_feedback_sanitize_page_url($input['page_url'] ?? null);
    $phase = journey_feedback_normalize_phase(isset($input['journey_phase']) ? (string) $input['journey_phase'] : null);
    if ($phase === null) {
        $phase = journey_feedback_infer_phase_from_url($pageUrl);
    }

    $ua = trim((string) ($input['user_agent'] ?? ''));
    if (strlen($ua) > JOURNEY_FEEDBACK_MAX_UA) {
        $ua = substr($ua, 0, JOURNEY_FEEDBACK_MAX_UA);
    }

    $emailSql = $email !== '' ? $email : null;
    $pageSql = $pageUrl;
    $phaseSql = $phase;
    $uaSql = $ua !== '' ? $ua : null;

    if ($userId > 0) {
        $stmt = $conn->prepare(
            'INSERT INTO journey_feedback
                (user_id, email, trying_to_do, what_happened, page_url, journey_phase,
                 is_signed_in, is_premium, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$stmt) {
            return ['ok' => false, 'error' => 'prepare_failed', 'message' => 'Could not save feedback.'];
        }
        $stmt->bind_param(
            'isssssiis',
            $userId,
            $emailSql,
            $trying,
            $happened,
            $pageSql,
            $phaseSql,
            $isSignedIn,
            $isPremium,
            $uaSql
        );
    } else {
        $stmt = $conn->prepare(
            'INSERT INTO journey_feedback
                (user_id, email, trying_to_do, what_happened, page_url, journey_phase,
                 is_signed_in, is_premium, user_agent)
             VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$stmt) {
            return ['ok' => false, 'error' => 'prepare_failed', 'message' => 'Could not save feedback.'];
        }
        $stmt->bind_param(
            'sssssiis',
            $emailSql,
            $trying,
            $happened,
            $pageSql,
            $phaseSql,
            $isSignedIn,
            $isPremium,
            $uaSql
        );
    }

    if (!$stmt->execute()) {
        $stmt->close();
        error_log('journey_feedback_store: insert failed');
        return ['ok' => false, 'error' => 'insert_failed', 'message' => 'Could not save feedback.'];
    }
    $id = (int) $stmt->insert_id;
    $stmt->close();
    return ['ok' => true, 'id' => $id];
}

function journey_feedback_summary_line(string $text): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    if ($text === '') {
        return '(No summary)';
    }
    if (mb_strlen($text) <= 120) {
        return $text;
    }
    return mb_substr($text, 0, 117) . '…';
}

/**
 * @return list<array<string,mixed>>
 */
function journey_feedback_list_recent(mysqli $conn, int $limit = JOURNEY_FEEDBACK_LIST_LIMIT): array
{
    if (!journey_feedback_tables_ready($conn)) {
        return [];
    }
    $limit = max(1, min(100, $limit));
    $sql = "SELECT
                f.id, f.created_at, f.viewed_at, f.user_id, f.email,
                f.trying_to_do, f.what_happened, f.page_url, f.journey_phase,
                f.is_signed_in, f.is_premium,
                u.full_name, u.email AS user_email
            FROM journey_feedback f
            LEFT JOIN users u ON u.id = f.user_id
            ORDER BY f.created_at DESC, f.id DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($res && ($row = $res->fetch_assoc())) {
        $isNew = empty($row['viewed_at']);
        $displayEmail = trim((string) ($row['email'] ?? ''));
        if ($displayEmail === '') {
            $displayEmail = trim((string) ($row['user_email'] ?? ''));
        }
        $displayName = trim((string) ($row['full_name'] ?? ''));
        $rows[] = [
            'id' => (int) ($row['id'] ?? 0),
            'created_at' => $row['created_at'] ?? null,
            'viewed_at' => $row['viewed_at'] ?? null,
            'is_new' => $isNew,
            'status_label' => $isNew ? 'New' : 'Viewed',
            'user_id' => isset($row['user_id']) ? (int) $row['user_id'] : null,
            'full_name' => $displayName,
            'email' => $displayEmail,
            'page_url' => (string) ($row['page_url'] ?? ''),
            'journey_phase' => (string) ($row['journey_phase'] ?? ''),
            'trying_to_do' => (string) ($row['trying_to_do'] ?? ''),
            'what_happened' => (string) ($row['what_happened'] ?? ''),
            'summary' => journey_feedback_summary_line((string) ($row['trying_to_do'] ?? '')),
            'is_signed_in' => !empty($row['is_signed_in']),
            'is_premium' => !empty($row['is_premium']),
        ];
    }
    $stmt->close();
    return $rows;
}

function journey_feedback_count_new(mysqli $conn): int
{
    if (!journey_feedback_tables_ready($conn)) {
        return 0;
    }
    $res = $conn->query('SELECT COUNT(*) AS c FROM journey_feedback WHERE viewed_at IS NULL');
    $row = $res ? $res->fetch_assoc() : null;
    return (int) ($row['c'] ?? 0);
}

/**
 * @return array<string,mixed>|null
 */
function journey_feedback_get(mysqli $conn, int $id): ?array
{
    if ($id <= 0 || !journey_feedback_tables_ready($conn)) {
        return null;
    }
    $stmt = $conn->prepare(
        'SELECT f.*, u.full_name, u.email AS user_email
         FROM journey_feedback f
         LEFT JOIN users u ON u.id = f.user_id
         WHERE f.id = ?
         LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!is_array($row)) {
        return null;
    }
    $isNew = empty($row['viewed_at']);
    $displayEmail = trim((string) ($row['email'] ?? ''));
    if ($displayEmail === '') {
        $displayEmail = trim((string) ($row['user_email'] ?? ''));
    }
    return [
        'id' => (int) ($row['id'] ?? 0),
        'created_at' => $row['created_at'] ?? null,
        'viewed_at' => $row['viewed_at'] ?? null,
        'is_new' => $isNew,
        'status_label' => $isNew ? 'New' : 'Viewed',
        'user_id' => isset($row['user_id']) ? (int) $row['user_id'] : null,
        'full_name' => trim((string) ($row['full_name'] ?? '')),
        'email' => $displayEmail,
        'trying_to_do' => (string) ($row['trying_to_do'] ?? ''),
        'what_happened' => (string) ($row['what_happened'] ?? ''),
        'page_url' => (string) ($row['page_url'] ?? ''),
        'journey_phase' => (string) ($row['journey_phase'] ?? ''),
        'is_signed_in' => !empty($row['is_signed_in']),
        'is_premium' => !empty($row['is_premium']),
        'user_agent' => (string) ($row['user_agent'] ?? ''),
    ];
}

function journey_feedback_mark_viewed(mysqli $conn, int $id): bool
{
    if ($id <= 0 || !journey_feedback_tables_ready($conn)) {
        return false;
    }
    $stmt = $conn->prepare(
        'UPDATE journey_feedback
         SET viewed_at = COALESCE(viewed_at, UTC_TIMESTAMP())
         WHERE id = ?'
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Context for API bootstrap (session-derived; never trust client for auth flags).
 *
 * @return array{user_id:int,email:?string,is_signed_in:bool,is_premium:bool}
 */
function journey_feedback_session_context(mysqli $conn): array
{
    $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    $email = null;
    $signedIn = $userId > 0;
    $premium = false;
    if ($signedIn) {
        $email = trim((string) ($_SESSION['user_email'] ?? ''));
        if ($email === '') {
            $stmt = $conn->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $email = trim((string) ($row['email'] ?? ''));
            }
        }
        if ($email === '') {
            $email = null;
        }
        $premium = has_journey_premium_access($conn, $userId);
    }
    return [
        'user_id' => $userId,
        'email' => $email,
        'is_signed_in' => $signedIn,
        'is_premium' => $premium,
    ];
}
