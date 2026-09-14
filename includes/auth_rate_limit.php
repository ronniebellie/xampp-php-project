<?php
declare(strict_types=1);
/** Private bounded disk ledger. No names, email addresses or IPs are stored. */
function rb_auth_rate_allow(string $key, int $limit = 20, int $window = 300, ?string $directory = null, ?int $now = null): bool
{
    $directory ??= sys_get_temp_dir() . '/rb-auth-attempts';
    $now ??= time();
    if ((!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) || is_link($directory)) return false;
    $hash = hash('sha256', $key);
    // 256 files, at most 128 active keys per file; anonymous traffic cannot create unbounded files.
    $path = $directory . '/' . substr($hash, 0, 2) . '.json';
    if (is_link($path)) return false;
    $file = @fopen($path, 'c+');
    if (!$file) return false;
    try {
        if (!flock($file, LOCK_EX)) return false;
        $raw = stream_get_contents($file, 65537);
        if ($raw === false || strlen($raw) > 65536) return false;
        $state = $raw === '' ? [] : json_decode($raw, true);
        if (!is_array($state)) return false;
        foreach ($state as $id => $entry) if (!is_array($entry) || ($entry['until'] ?? 0) <= $now) unset($state[$id]);
        if (!isset($state[$hash])) {
            if (count($state) >= 128) return false;
            $state[$hash] = ['until' => $now + $window, 'used' => 0];
        }
        if ($state[$hash]['used'] >= $limit) return false;
        $state[$hash]['used']++;
        $json = json_encode($state);
        rewind($file);
        return is_string($json) && ftruncate($file, 0) && fwrite($file, $json) === strlen($json) && fflush($file);
    } finally { fclose($file); }
}
