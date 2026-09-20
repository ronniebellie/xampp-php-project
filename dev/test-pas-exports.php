<?php
// Exercise the real report rendering code with synthetic data, without accounts or a database.
declare(strict_types=1);
$root = dirname(__DIR__);
$data = isset($argv[1]) ? json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR) : [
    'portfolioValue' => 1000, 'years' => 1, 'returnRate' => 0, 'pasFee' => 0, 'targetDateFee' => 0,
    'withdrawalPct' => 10, 'timelineStartYear' => 2030, 'withdrawalsStartYear' => 2030,
    'allocation' => ['conservative' => 2, 'moderate' => 3, 'aggressive' => 95],
    'pasData' => [['year' => 0, 'balance' => 1000], ['year' => 1, 'balance' => 900, 'withdrawal' => 100]],
    'targetData' => [
        ['year' => 0, 'calendarYear' => 2030, 'balance' => 1000, 'withdrawal' => 0,
         'buckets' => ['conservative' => 20, 'moderate' => 30, 'aggressive' => 950]],
        ['year' => 1, 'calendarYear' => 2030, 'balance' => 900, 'withdrawal' => 100,
         'buckets' => ['conservative' => 0, 'moderate' => 0, 'aggressive' => 900]]
    ]
];
require_once $root . '/includes/report_csv.php';
$data = rb_pdf_data($data);
$source = file_get_contents($root . '/api/export_pas_csv.php');
$start = strpos($source, '$pRows =');
$end = strpos($source, 'fclose($out);') + strlen('fclose($out);');
$csvCode = substr($source, $start, $end - $start);
// The endpoint ends its request buffer; provide that buffer inside the capture.
ob_start(); ob_start(); eval($csvCode); $csv = ob_get_clean();
if (!str_contains($csv, 'Conservative Balance') || !str_contains($csv, 'Depleted: 2030')) throw new RuntimeException('Missing CSV bucket details');
$rows = array_map('str_getcsv', explode("\n", trim($csv)));
$table = array_values(array_filter($rows, fn($row) => count($row) === 20 && is_numeric($row[0])));
if (count($table) !== count($data['targetData'])) throw new RuntimeException('CSV row count mismatch');
foreach ($table as $i => $row) {
    foreach ([11 => 'conservative', 12 => 'moderate', 13 => 'aggressive'] as $col => $key) {
        if ($row[$col] !== number_format($data['targetData'][$i]['buckets'][$key], 2)) throw new RuntimeException('CSV bucket mismatch');
    }
}
require_once $root . '/vendor/autoload.php';
$source = file_get_contents($root . '/api/generate_pas_pdf.php');
$start = strpos($source, "require_once __DIR__ . '/../includes/report_pdf.php';");
$end = strpos($source, '} catch (Throwable $e)', $start);
$pdfCode = str_replace('__DIR__', var_export($root . '/api', true), substr($source, $start, $end - $start));
eval($pdfCode);
if (!str_starts_with($pdfBytes, '%PDF-')) throw new RuntimeException('Invalid PDF');
if (isset($argv[2])) {file_put_contents($argv[2] . '.pdf', $pdfBytes); file_put_contents($argv[2] . '.csv', $csv);}
fwrite(STDOUT, "PAS real CSV and PDF rendering passed: matching annual bucket rows and depletion metadata.\n");
