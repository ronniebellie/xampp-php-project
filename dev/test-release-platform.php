<?php
/** Offline deployment contract: composer.json must remain absent in releases. */
declare(strict_types=1);
$base = sys_get_temp_dir() . '/rb-platform-test-' . bin2hex(random_bytes(8));
mkdir($base, 0700); mkdir($base . '/vendor', 0700); mkdir($base . '/vendor/composer', 0700);
mkdir($base . '/vendor/fixture', 0700); mkdir($base . '/vendor/fixture/package', 0700);
$checks = 0;
try {
    file_put_contents($base . '/vendor/autoload.php', '<?php return true;');
    // Deliberately insufficient generated check; must not bypass full metadata.
    file_put_contents($base . '/vendor/composer/platform_check.php', '<?php return true;');
    $metadata = ['packages' => [['name' => 'fixture/package', 'version' => '1.0.0', 'version_normalized' => '1.0.0.0', 'type' => 'library', 'require' => ['php' => '>=7.1', 'ext-json' => '*']]], 'dev' => false, 'dev-package-names' => []];
    $run = static function (array $data, array $root, bool $success) use ($base, &$checks): void {
        file_put_contents($base . '/vendor/composer/installed.json', json_encode($data));
        $before = hash_file('sha256', $base . '/vendor/composer/installed.json');
        $proc = proc_open([PHP_BINARY, __DIR__ . '/../scripts/check-release-platform.php', $base, base64_encode(json_encode($root))], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $out = stream_get_contents($pipes[1]); fclose($pipes[1]);
        $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
        $status = proc_close($proc);
        if (($status === 0) !== $success) throw new RuntimeException("Unexpected verification status: $out $err");
        if (file_exists($base . '/composer.json') || file_exists($base . '/composer.lock') || $before !== hash_file('sha256', $base . '/vendor/composer/installed.json')) throw new RuntimeException('Public artifact was changed');
        $checks++;
    };
    $root = ['require' => ['fixture/package' => '1.0.0']];
    $run($metadata, $root, true);
    $missing = $metadata; $missing['packages'][0]['require']['ext-rb-definitely-missing'] = '*';
    $run($missing, $root, false);
    $badPhp = $metadata; $badPhp['packages'][0]['require']['php'] = '>=999';
    $run($badPhp, $root, false);
    $badExtension = $metadata; $badExtension['packages'][0]['require']['ext-json'] = '>=999';
    $run($badExtension, $root, false);
    $run($metadata, ['require' => ['php' => '>=999'], 'config' => ['platform' => ['php' => '999.0']]], false);
    $run(['packages' => [], 'dev' => false], $root, false);
    $dev = $metadata; $dev['dev'] = true; $run($dev, $root, false);
    $run($metadata, [], false);
    $deploy = file_get_contents(__DIR__ . '/../deploy.sh');
    if (!str_contains($deploy, 'php "$base/incoming/$release_id.platform-check.php" "$release" "$root_requirements_b64"') || strpos($deploy, 'php "$base/incoming/$release_id.platform-check.php"') > strpos($deploy, 'mv -Tf "$next_link" "$current"')) throw new RuntimeException('Deployment must check before activation');
    $checks++;
    echo "Release platform verification tests passed ($checks checks).\n";
} finally {
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($items as $item) { if ($item->isDir()) rmdir($item->getPathname()); else unlink($item->getPathname()); }
    rmdir($base);
}
