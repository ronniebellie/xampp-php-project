<?php
/**
 * Journey plan cloud storage helpers (Milestone 5 / R1 — P1).
 * Database is authoritative for Journey Premium users; APIs enforce access rules.
 */

declare(strict_types=1);

if (defined('RB_JOURNEY_PLAN_STORE_LOADED')) {
    return;
}
define('RB_JOURNEY_PLAN_STORE_LOADED', 1);

require_once __DIR__ . '/journey_checkout.php';
require_once __DIR__ . '/csrf.php';

/** Current Journey plan JSON schema version for cloud payloads. */
const JOURNEY_PLAN_PAYLOAD_SCHEMA_VERSION = 1;

/** Soft max payload size (bytes) after JSON encode. */
const JOURNEY_PLAN_PAYLOAD_MAX_BYTES = 1572864; // 1.5 MiB

/** Retain this many newest versions per plan. */
const JOURNEY_PLAN_VERSION_RETENTION = 20;

/**
 * Whether cloud Journey APIs are enabled.
 * Set RB_JOURNEY_CLOUD_SAVE_ENABLED=0 to disable without dropping tables.
 */
function journey_cloud_save_enabled(): bool
{
    $raw = getenv('RB_JOURNEY_CLOUD_SAVE_ENABLED');
    if ($raw === false || $raw === '') {
        return true;
    }
    $normalized = strtolower(trim((string) $raw));
    return !in_array($normalized, ['0', 'false', 'off', 'no'], true);
}

/**
 * @return list<string>
 */
function journey_plan_cors_allowed_origins(): array
{
    return [
        'https://journey.ronbelisle.com',
        'http://journey.ronbelisle.com',
    ];
}

function journey_plan_apply_cors_headers(): void
{
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? trim((string) $_SERVER['HTTP_ORIGIN']) : '';
    if (in_array($origin, journey_plan_cors_allowed_origins(), true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    }
    header('Access-Control-Allow-Methods: GET, PUT, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Accept, Content-Type, X-CSRF-Token');
    header('Cache-Control: no-store');
}

function journey_plan_handle_options_preflight(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        journey_plan_apply_cors_headers();
        http_response_code(204);
        exit;
    }
}

/**
 * @param array<string,mixed> $payload
 */
function journey_plan_json_response(array $payload, int $status = 200): void
{
    journey_plan_apply_cors_headers();
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function journey_plan_tables_ready(mysqli $conn): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    $result = $conn->query("SHOW TABLES LIKE 'journey_plans'");
    $ready = $result && $result->num_rows > 0;
    if ($result instanceof mysqli_result) {
        $result->free();
    }
    return $ready;
}

function journey_plan_session_user_id(): int
{
    if (!isset($_SESSION['user_id'])) {
        return 0;
    }
    return (int) $_SESSION['user_id'];
}

function journey_plan_can_write(mysqli $conn, int $userId): bool
{
    return $userId > 0 && has_journey_premium_access($conn, $userId);
}

function journey_plan_has_cloud_row(mysqli $conn, int $userId): bool
{
    if ($userId <= 0 || !journey_plan_tables_ready($conn)) {
        return false;
    }
    $stmt = $conn->prepare('SELECT id FROM journey_plans WHERE user_id = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return is_array($row);
}

/**
 * Load allowed when authenticated and either writable Premium or an existing cloud plan
 * (read-only for past Premium users).
 */
function journey_plan_can_read(mysqli $conn, int $userId): bool
{
    if ($userId <= 0) {
        return false;
    }
    if (journey_plan_can_write($conn, $userId)) {
        return true;
    }
    return journey_plan_has_cloud_row($conn, $userId);
}

function journey_plan_request_csrf_token(): ?string
{
    $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (is_string($header) && $header !== '') {
        return $header;
    }
    return null;
}

/**
 * @return array<string,mixed>|null
 */
function journey_plan_read_json_body(): ?array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function journey_plan_require_csrf(?array $body): void
{
    $token = null;
    if (is_array($body) && isset($body['csrf_token']) && is_string($body['csrf_token'])) {
        $token = $body['csrf_token'];
    }
    if ($token === null || $token === '') {
        $token = journey_plan_request_csrf_token();
    }
    if (!rb_csrf_validate($token)) {
        journey_plan_json_response([
            'success' => false,
            'error' => 'invalid_csrf',
            'message' => 'Missing or invalid CSRF token.',
        ], 403);
    }
}

/**
 * Normalize and validate cloud payload.
 *
 * @param mixed $payload
 * @return array{ok:bool,payload?:array<string,mixed>,error?:string,message?:string,bytes?:int}
 */
function journey_plan_normalize_payload($payload): array
{
    if (!is_array($payload)) {
        return [
            'ok' => false,
            'error' => 'invalid_payload',
            'message' => 'Payload must be a JSON object.',
        ];
    }

    $schemaVersion = isset($payload['schemaVersion'])
        ? (int) $payload['schemaVersion']
        : JOURNEY_PLAN_PAYLOAD_SCHEMA_VERSION;
    if ($schemaVersion < 1 || $schemaVersion > JOURNEY_PLAN_PAYLOAD_SCHEMA_VERSION) {
        return [
            'ok' => false,
            'error' => 'unsupported_schema',
            'message' => 'Unsupported Journey plan schemaVersion.',
        ];
    }

    $progress = $payload['progress'] ?? null;
    if ($progress !== null && !is_array($progress)) {
        return [
            'ok' => false,
            'error' => 'invalid_payload',
            'message' => 'progress must be an object when provided.',
        ];
    }

    $calculators = $payload['calculators'] ?? null;
    if ($calculators !== null && !is_array($calculators)) {
        return [
            'ok' => false,
            'error' => 'invalid_payload',
            'message' => 'calculators must be an object when provided.',
        ];
    }

    $normalized = [
        'schemaVersion' => $schemaVersion,
        'progress' => is_array($progress) ? $progress : new stdClass(),
        'calculators' => is_array($calculators) ? $calculators : new stdClass(),
    ];

    $encoded = json_encode($normalized, JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        return [
            'ok' => false,
            'error' => 'invalid_payload',
            'message' => 'Payload could not be encoded as JSON.',
        ];
    }
    $bytes = strlen($encoded);
    if ($bytes > JOURNEY_PLAN_PAYLOAD_MAX_BYTES) {
        return [
            'ok' => false,
            'error' => 'payload_too_large',
            'message' => 'Journey plan payload exceeds the maximum allowed size.',
            'bytes' => $bytes,
        ];
    }

    // Re-decode so empty objects become arrays for PHP storage consistency.
    $asArray = json_decode($encoded, true);
    if (!is_array($asArray)) {
        return [
            'ok' => false,
            'error' => 'invalid_payload',
            'message' => 'Normalized payload is invalid.',
        ];
    }

    return [
        'ok' => true,
        'payload' => $asArray,
        'bytes' => $bytes,
    ];
}

function journey_plan_parse_client_updated_at(?string $value): ?string
{
    if ($value === null || trim($value) === '') {
        return null;
    }
    try {
        $dt = new DateTimeImmutable($value);
    } catch (Exception $e) {
        return null;
    }
    return $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.v');
}

/**
 * @return array<string,mixed>|null
 */
function journey_plan_fetch(mysqli $conn, int $userId): ?array
{
    if ($userId <= 0 || !journey_plan_tables_ready($conn)) {
        return null;
    }
    $stmt = $conn->prepare(
        'SELECT id, user_id, schema_version, payload, client_updated_at, server_updated_at, created_at, updated_at
         FROM journey_plans WHERE user_id = ? LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!is_array($row)) {
        return null;
    }

    $payload = json_decode((string) ($row['payload'] ?? ''), true);
    if (!is_array($payload)) {
        $payload = [
            'schemaVersion' => (int) ($row['schema_version'] ?? 1),
            'progress' => [],
            'calculators' => [],
        ];
    }

    return [
        'id' => (int) $row['id'],
        'userId' => (int) $row['user_id'],
        'schemaVersion' => (int) $row['schema_version'],
        'payload' => $payload,
        'clientUpdatedAt' => $row['client_updated_at'] !== null
            ? gmdate('c', strtotime((string) $row['client_updated_at'] . ' UTC') ?: time())
            : null,
        'serverUpdatedAt' => gmdate('c', strtotime((string) $row['server_updated_at'] . ' UTC') ?: time()),
        'createdAt' => gmdate('c', strtotime((string) $row['created_at']) ?: time()),
        'updatedAt' => gmdate('c', strtotime((string) $row['updated_at']) ?: time()),
    ];
}

function journey_plan_prune_versions(mysqli $conn, int $planId): void
{
    $keep = JOURNEY_PLAN_VERSION_RETENTION;
    $sql = 'DELETE FROM journey_plan_versions
            WHERE journey_plan_id = ?
              AND id NOT IN (
                SELECT id FROM (
                  SELECT id FROM journey_plan_versions
                  WHERE journey_plan_id = ?
                  ORDER BY created_at DESC, id DESC
                  LIMIT ?
                ) retained
              )';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('iii', $planId, $planId, $keep);
    $stmt->execute();
    $stmt->close();
}

/**
 * @param array<string,mixed> $payload
 * @return array{ok:bool,plan?:array<string,mixed>,error?:string,message?:string,conflict?:array<string,mixed>}
 */
function journey_plan_save(
    mysqli $conn,
    int $userId,
    array $payload,
    ?string $clientUpdatedAtIso,
    string $reason = 'autosave',
    bool $force = false
): array {
    if (!journey_cloud_save_enabled()) {
        return [
            'ok' => false,
            'error' => 'cloud_save_disabled',
            'message' => 'Journey cloud save is temporarily disabled.',
        ];
    }
    if (!journey_plan_tables_ready($conn)) {
        return [
            'ok' => false,
            'error' => 'schema_missing',
            'message' => 'Journey plan tables are not available.',
        ];
    }
    if (!journey_plan_can_write($conn, $userId)) {
        return [
            'ok' => false,
            'error' => 'premium_required',
            'message' => 'Active Journey Premium access is required to save to your account.',
        ];
    }

    $normalized = journey_plan_normalize_payload($payload);
    if (empty($normalized['ok'])) {
        return [
            'ok' => false,
            'error' => $normalized['error'] ?? 'invalid_payload',
            'message' => $normalized['message'] ?? 'Invalid payload.',
        ];
    }

    /** @var array<string,mixed> $cleanPayload */
    $cleanPayload = $normalized['payload'];
    $payloadJson = json_encode($cleanPayload, JSON_UNESCAPED_SLASHES);
    if ($payloadJson === false) {
        return [
            'ok' => false,
            'error' => 'invalid_payload',
            'message' => 'Payload could not be encoded.',
        ];
    }

    $clientUpdatedAt = journey_plan_parse_client_updated_at($clientUpdatedAtIso);
    $existing = journey_plan_fetch($conn, $userId);

    if ($existing && !$force && $clientUpdatedAt !== null && !empty($existing['clientUpdatedAt'])) {
        $incomingTs = strtotime($clientUpdatedAt . ' UTC') ?: 0;
        $existingTs = strtotime((string) $existing['clientUpdatedAt']) ?: 0;
        if ($existingTs > $incomingTs) {
            return [
                'ok' => false,
                'error' => 'conflict',
                'message' => 'A newer Journey plan is already saved to your account.',
                'conflict' => [
                    'serverUpdatedAt' => $existing['serverUpdatedAt'],
                    'clientUpdatedAt' => $existing['clientUpdatedAt'],
                ],
            ];
        }
    }

    $schemaVersion = (int) ($cleanPayload['schemaVersion'] ?? JOURNEY_PLAN_PAYLOAD_SCHEMA_VERSION);
    $reason = preg_replace('/[^a-z_]/', '', strtolower($reason)) ?: 'autosave';
    if (strlen($reason) > 32) {
        $reason = substr($reason, 0, 32);
    }

    $conn->begin_transaction();
    try {
        if ($existing) {
            $planId = (int) $existing['id'];
            if ($clientUpdatedAt === null) {
                $stmt = $conn->prepare(
                    'UPDATE journey_plans
                     SET schema_version = ?, payload = ?, server_updated_at = CURRENT_TIMESTAMP(3)
                     WHERE id = ? AND user_id = ?'
                );
                if (!$stmt) {
                    throw new RuntimeException('prepare_update_failed');
                }
                $stmt->bind_param('isii', $schemaVersion, $payloadJson, $planId, $userId);
            } else {
                $stmt = $conn->prepare(
                    'UPDATE journey_plans
                     SET schema_version = ?, payload = ?, client_updated_at = ?, server_updated_at = CURRENT_TIMESTAMP(3)
                     WHERE id = ? AND user_id = ?'
                );
                if (!$stmt) {
                    throw new RuntimeException('prepare_update_failed');
                }
                $stmt->bind_param('issii', $schemaVersion, $payloadJson, $clientUpdatedAt, $planId, $userId);
            }
            if (!$stmt->execute()) {
                $stmt->close();
                throw new RuntimeException('update_failed');
            }
            $stmt->close();
        } else {
            if ($clientUpdatedAt === null) {
                $stmt = $conn->prepare(
                    'INSERT INTO journey_plans (user_id, schema_version, payload, client_updated_at)
                     VALUES (?, ?, ?, NULL)'
                );
                if (!$stmt) {
                    throw new RuntimeException('prepare_insert_failed');
                }
                $stmt->bind_param('iis', $userId, $schemaVersion, $payloadJson);
            } else {
                $stmt = $conn->prepare(
                    'INSERT INTO journey_plans (user_id, schema_version, payload, client_updated_at)
                     VALUES (?, ?, ?, ?)'
                );
                if (!$stmt) {
                    throw new RuntimeException('prepare_insert_failed');
                }
                $stmt->bind_param('iiss', $userId, $schemaVersion, $payloadJson, $clientUpdatedAt);
            }
            if (!$stmt->execute()) {
                $stmt->close();
                throw new RuntimeException('insert_failed');
            }
            $planId = (int) $conn->insert_id;
            $stmt->close();
        }

        $versionStmt = $conn->prepare(
            'INSERT INTO journey_plan_versions (journey_plan_id, user_id, schema_version, payload, reason)
             VALUES (?, ?, ?, ?, ?)'
        );
        if (!$versionStmt) {
            throw new RuntimeException('prepare_version_failed');
        }
        $versionStmt->bind_param('iiiss', $planId, $userId, $schemaVersion, $payloadJson, $reason);
        if (!$versionStmt->execute()) {
            $versionStmt->close();
            throw new RuntimeException('version_failed');
        }
        $versionStmt->close();

        journey_plan_prune_versions($conn, $planId);
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        return [
            'ok' => false,
            'error' => 'save_failed',
            'message' => 'Could not save Journey plan to your account.',
        ];
    }

    $saved = journey_plan_fetch($conn, $userId);
    if (!$saved) {
        return [
            'ok' => false,
            'error' => 'save_failed',
            'message' => 'Save appeared to succeed but the plan could not be reloaded.',
        ];
    }

    return [
        'ok' => true,
        'plan' => $saved,
    ];
}

/**
 * First-time import only. Never silently overwrites an existing cloud plan.
 *
 * @param array<string,mixed> $payload
 * @return array{ok:bool,plan?:array<string,mixed>,error?:string,message?:string,conflict?:array<string,mixed>}
 */
function journey_plan_import(
    mysqli $conn,
    int $userId,
    array $payload,
    ?string $clientUpdatedAtIso
): array {
    if (journey_plan_has_cloud_row($conn, $userId)) {
        $existing = journey_plan_fetch($conn, $userId);
        return [
            'ok' => false,
            'error' => 'already_exists',
            'message' => 'A Journey plan is already saved to your account. Import will not overwrite it.',
            'conflict' => $existing ? [
                'serverUpdatedAt' => $existing['serverUpdatedAt'],
                'clientUpdatedAt' => $existing['clientUpdatedAt'],
            ] : null,
        ];
    }

    return journey_plan_save($conn, $userId, $payload, $clientUpdatedAtIso, 'import', false);
}
