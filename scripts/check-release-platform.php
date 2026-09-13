<?php
/** Deployment-only check. Never add a project manifest to the public release.
 * Usage: php check-release-platform.php RELEASE BASE64_COMMITTED_COMPOSER_JSON
 * COMPOSER_BIN may select the installed Composer executable/wrapper.
 */
declare(strict_types=1);

$scratch = null;
try {
    if ($argc !== 3) throw new RuntimeException('Require release path and committed root requirements.');
    $release = realpath($argv[1]);
    if ($release === false || !is_dir($release)) throw new RuntimeException('Release directory is missing.');
    $vendor = $release . '/vendor';
    if (!is_file($vendor . '/autoload.php')) throw new RuntimeException('Packaged autoloader is missing.');
    $installed = json_decode((string) file_get_contents($vendor . '/composer/installed.json'), true, 512, JSON_THROW_ON_ERROR);
    if (empty($installed['packages']) || !is_array($installed['packages']) || ($installed['dev'] ?? null) !== false) {
        throw new RuntimeException('Require nonempty production installed-package metadata.');
    }
    $decoded = base64_decode($argv[2], true);
    if ($decoded === false) throw new RuntimeException('Invalid encoded root requirements.');
    $root = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($root) || !isset($root['require']) || !is_array($root['require'])) throw new RuntimeException('Missing committed require map.');
    foreach ($root['require'] as $name => $constraint) {
        if (!is_string($name) || !is_string($constraint)) throw new RuntimeException('Malformed committed requirement.');
    }

    // Only the require map is copied, not config.platform, plugins, scripts,
    // repositories, credentials, or any other project settings.
    $scratch = tempnam(sys_get_temp_dir(), 'rb-platform-');
    if ($scratch === false || !unlink($scratch) || !mkdir($scratch, 0700)) throw new RuntimeException('Cannot create private verification directory.');
    mkdir($scratch . '/home', 0700);
    $project = ['name' => 'ronbelisle/release-platform-verification', 'require' => (object) $root['require'], 'config' => ['vendor-dir' => $vendor]];
    file_put_contents($scratch . '/composer.json', json_encode($project, JSON_THROW_ON_ERROR));
    $env = getenv();
    $env['COMPOSER'] = $scratch . '/composer.json';
    $env['COMPOSER_HOME'] = $scratch . '/home';
    $env['COMPOSER_VENDOR_DIR'] = $vendor;
    $env['COMPOSER_DISABLE_NETWORK'] = '1';
    $env['COMPOSER_ALLOW_SUPERUSER'] = '1';
    $env['COMPOSER_ROOT_VERSION'] = '1.0.0';
    unset($env['COMPOSER_IGNORE_PLATFORM_REQS'], $env['COMPOSER_IGNORE_PLATFORM_REQ']);
    $command = [getenv('COMPOSER_BIN') ?: 'composer', '--no-plugins', '--no-scripts', '--no-interaction', '--working-dir=' . $scratch, 'check-platform-reqs', '--no-dev', '--format=json'];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $scratch, $env);
    if (!is_resource($process)) throw new RuntimeException('Cannot start Composer verification.');
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $status = proc_close($process);
    fwrite(STDOUT, $output); fwrite(STDERR, $errors);
    if ($status !== 0) throw new RuntimeException('Composer rejected the runtime platform (exit ' . $status . ').');
    $results = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($results) || !$results) throw new RuntimeException('No platform requirements were verified.');
    foreach ($results as $result) {
        if (($result['status'] ?? null) !== 'success') throw new RuntimeException('Unverified platform requirement.');
    }
    echo "PASS packaged dependency/platform requirements; public release unchanged.\n";
    $status = 0;
} catch (Throwable $error) {
    fwrite(STDERR, 'ERROR: ' . $error->getMessage() . "\n");
    $status = 1;
} finally {
    // This tree is newly created private scratch, never the release/vendor tree.
    if ($scratch !== null && is_dir($scratch)) {
        $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($scratch, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) rmdir($item->getPathname());
            else unlink($item->getPathname());
        }
        rmdir($scratch);
    }
}
exit($status);
