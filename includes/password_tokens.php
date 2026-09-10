<?php
/** Password-state-bound tokens. Changing the password invalidates every outstanding link. */
function rb_password_token_create(string $email, ?string $passwordHash, string $purpose, string $secret, int $ttl = 86400): string
{
    if ($secret === '' || $secret === 'replace-with-random-secret-32chars' || $ttl < 1) {
        throw new InvalidArgumentException('Password token configuration is invalid.');
    }
    $payload = 'v2.' . base64_encode($email) . '.' . (time() + $ttl);
    return $payload . '.' . hash_hmac('sha256', $purpose . "\0" . $payload . "\0" . ($passwordHash ?? ''), $secret);
}

/** Untrusted identity, used only to look up the current password state before verification. */
function rb_password_token_email(string $token): ?string
{
    $parts = explode('.', $token);
    if (count($parts) !== 4 || $parts[0] !== 'v2' || !ctype_digit($parts[2])
        || strlen($parts[2]) > 10 || !preg_match('/^[a-f0-9]{64}$/D', $parts[3])) return null;
    $email = base64_decode($parts[1], true);
    return is_string($email) && strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
}

function rb_password_token_verify(string $token, ?string $passwordHash, string $purpose, string $secret, ?int $now = null): bool
{
    if ($secret === '' || $secret === 'replace-with-random-secret-32chars' || rb_password_token_email($token) === null) return false;
    $parts = explode('.', $token);
    if ((int) $parts[2] <= ($now ?? time())) return false;
    $payload = implode('.', array_slice($parts, 0, 3));
    return hash_equals(hash_hmac('sha256', $purpose . "\0" . $payload . "\0" . ($passwordHash ?? ''), $secret), $parts[3]);
}

/** Conditional update prevents concurrent redemptions from both succeeding. Table is never request-derived. */
function rb_password_token_redeem($conn, string $table, int $id, ?string $oldHash, string $newHash): bool
{
    if (!in_array($table, ['users', 'calcforadvisors_subscribers'], true)) throw new InvalidArgumentException('Invalid account table.');
    $active = $table === 'calcforadvisors_subscribers' ? " AND status = 'active'" : '';
    $stmt = $conn->prepare("UPDATE {$table} SET password_hash = ? WHERE id = ? AND BINARY password_hash <=> BINARY ?" . $active);
    $stmt->bind_param('sis', $newHash, $id, $oldHash);
    $stmt->execute();
    $updated = $stmt->affected_rows === 1;
    $stmt->close();
    return $updated;
}
