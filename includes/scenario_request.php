<?php
require_once __DIR__ . '/csrf.php';

const RB_SCENARIO_MAX_BYTES = 1048576;

function rb_scenario_request_error(array $server): ?array
{
    if (($server['REQUEST_METHOD'] ?? '') !== 'POST') return [405, 'POST required'];
    $type = strtolower(trim(explode(';', $server['CONTENT_TYPE'] ?? '')[0]));
    if ($type !== 'application/json') return [415, 'JSON request required'];
    $token = $server['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!is_string($token) || !rb_csrf_validate($token)) return [403, 'The form expired. Reload the page and try again.'];
    if ((int) ($server['CONTENT_LENGTH'] ?? 0) > RB_SCENARIO_MAX_BYTES) return [413, 'Scenario is too large'];
    return null;
}

function rb_scenario_decode(string $body): array
{
    if (strlen($body) > RB_SCENARIO_MAX_BYTES) return [null, [413, 'Scenario is too large']];
    $value = json_decode($body);
    if (json_last_error() !== JSON_ERROR_NONE || !is_object($value) || json_encode($value) === false) return [null, [400, 'Invalid JSON object']];
    return [json_decode($body, true), null];
}

function rb_scenario_data_error(array $data, string $operation): ?array
{
    if ($operation === 'delete') {
        $id = $data['scenario_id'] ?? null;
        if ((!is_int($id) && !(is_string($id) && preg_match('/^[1-9][0-9]*$/D', $id)))
            || filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) return [400, 'Invalid scenario ID'];
        return null;
    }
    $type = $data['calculator_type'] ?? null;
    $name = $data['scenario_name'] ?? null;
    if (!is_string($type) || !preg_match('/^[a-z0-9_-]{1,64}$/D', $type)) return [400, 'Invalid calculator type'];
    if (!is_string($name) || trim($name) === '' || strlen($name) > 255) return [400, 'Scenario name must be 1–255 bytes'];
    if (!isset($data['scenario_data']) || !is_array($data['scenario_data'])) return [400, 'Invalid scenario data'];
    return null;
}

function rb_scenario_fail(array $error): void
{
    http_response_code($error[0]);
    if ($error[0] === 405) header('Allow: POST');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $error[1]]);
    exit;
}

function rb_scenario_read_request(string $operation): array
{
    $error = rb_scenario_request_error($_SERVER);
    if ($error) rb_scenario_fail($error);
    $stream = fopen('php://input', 'rb');
    $body = stream_get_contents($stream, RB_SCENARIO_MAX_BYTES + 1);
    fclose($stream);
    [$data, $error] = rb_scenario_decode($body);
    if ($error) rb_scenario_fail($error);
    $error = rb_scenario_data_error($data, $operation);
    if ($error) rb_scenario_fail($error);
    return $data;
}
