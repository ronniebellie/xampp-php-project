<?php
/**
 * CalcForAdvisors 2.0 public portal presentation foundation.
 *
 * Production subscriber data has not been audited and the Phase 2 migration
 * has not been run. Reads must support legacy and proposed schemas.
 */
declare(strict_types=1);

require_once __DIR__ . '/calculator_catalog.php';
require_once __DIR__ . '/calcforadvisors_entitlement.php';

function cfa_public_http_url(string $value): string
{
    if ($value === '' || !filter_var($value, FILTER_VALIDATE_URL)) return '';
    return in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true) ? $value : '';
}

function cfa_public_portal_profile(array $row): array
{
    $text = static fn(string $key): string => trim((string) ($row[$key] ?? ''));
    $email = $text('public_email');
    return [
        'id' => (int) ($row['id'] ?? 0),
        'portal_slug' => cfa_normalize_portal_slug($text('portal_slug') ?: $text('trial_slug')),
        'firm_name' => $text('firm_name'), 'advisor_name' => $text('advisor_name'),
        'logo_url' => cfa_public_http_url($text('logo_url')),
        'banner_url' => cfa_public_http_url($text('banner_url')),
        'public_email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '',
        'phone' => $text('phone'), 'website_url' => cfa_public_http_url($text('website_url')),
        'disclosure_text' => $text('disclosure_text'),
    ];
}

/** @return array{state:string,profile:?array,entitlement:?array} */
function cfa_resolve_public_portal(string $rawSlug, callable $loader): array
{
    $validation = cfa_validate_portal_slug($rawSlug);
    if (!$validation['ok'] || $validation['slug'] !== strtolower(trim($rawSlug))) {
        return ['state' => 'invalid', 'profile' => null, 'entitlement' => null];
    }
    $row = $loader($validation['slug']);
    if (!is_array($row)) return ['state' => 'invalid', 'profile' => null, 'entitlement' => null];
    $entitlement = cfa_evaluate_advisor_entitlement($row);
    return [
        'state' => $entitlement['portal_available'] ? 'available' : 'unavailable',
        'profile' => cfa_public_portal_profile($row), 'entitlement' => $entitlement,
    ];
}

/** Read a portal without requiring the Phase 2 migration. */
function cfa_load_public_portal(mysqli $conn, string $slug): ?array
{
    $columns = [];
    $result = $conn->query('SHOW COLUMNS FROM calcforadvisors_subscribers');
    if (!$result) return null;
    while ($column = $result->fetch_assoc()) $columns[(string) $column['Field']] = true;
    $wanted = ['id','plan','status','created_at','trial_slug','portal_slug','firm_name','advisor_name','logo_url','banner_url','public_email','phone','website_url','disclosure_text','stripe_subscription_status','trial_ends_at','access_ends_at','past_due_started_at'];
    $selected = array_values(array_filter($wanted, static fn(string $name): bool => isset($columns[$name])));
    if (!$selected || (!isset($columns['portal_slug']) && !isset($columns['trial_slug']))) return null;
    $where = isset($columns['portal_slug']) && isset($columns['trial_slug'])
        ? '(portal_slug = ? OR ((portal_slug IS NULL OR portal_slug = \'\') AND trial_slug = ?))'
        : (isset($columns['portal_slug']) ? 'portal_slug = ?' : 'trial_slug = ?');
    $stmt = $conn->prepare('SELECT ' . implode(', ', $selected) . " FROM calcforadvisors_subscribers WHERE {$where} LIMIT 1");
    if (!$stmt) return null;
    if (substr_count($where, '?') === 2) $stmt->bind_param('ss', $slug, $slug);
    else $stmt->bind_param('s', $slug);
    if (!$stmt->execute()) { $stmt->close(); return null; }
    $res = $stmt->get_result(); $row = $res ? $res->fetch_assoc() : null; $stmt->close();
    return is_array($row) ? $row : null;
}

function cfa_advisor_calculator(string $id): ?array
{
    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $id)) return null;
    $calculator = rb_calculator_by_id($id);
    return $calculator && $calculator['active'] === true && $calculator['advisor'] === true ? $calculator : null;
}

/** @return list<string> */
function cfa_calculator_badges(array $calculator): array
{
    $labels = ['save' => 'Save', 'compare' => 'Compare', 'pdf' => 'PDF', 'ai' => 'AI Explain'];
    $badges = [];
    foreach ($labels as $feature => $label) if (($calculator[$feature] ?? false) === true) $badges[] = $label;
    return $badges;
}

function cfa_calculator_embed_url(array $calculator, string $base = 'https://ronbelisle.com'): string
{
    $base = rtrim(cfa_public_http_url($base), '/');
    return ($base ?: 'https://ronbelisle.com') . $calculator['route'] . '?embed=1';
}

function cfa_portal_path(string $slug): string
{
    $validation = cfa_validate_portal_slug($slug);
    return $validation['ok'] ? '/p/' . rawurlencode($validation['slug']) : '/';
}
