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
if (!str_contains($csv, 'Conservative Balance') || !str_contains($csv, 'Bucket sequencing')) throw new RuntimeException('Missing CSV bucket details');
$rows = array_map('str_getcsv', explode("\n", trim($csv)));
$table = array_values(array_filter($rows, fn($row) => count($row) === 24 && is_numeric($row[0])));
if (count($table) !== count($data['targetData'])) throw new RuntimeException('CSV row count mismatch');
foreach ($table as $i => $row) {
    foreach ([1=>'balance',2=>'fee',3=>'totalFees',9=>'withdrawal',20=>'requiredWithdrawal',22=>'shortfall'] as $col=>$key) { if ($row[$col] !== number_format($data['pasData'][$i][$key]??($key==='requiredWithdrawal'?($data['pasData'][$i]['withdrawal']??0):0),2)) throw new RuntimeException('CSV PAS ledger mismatch: '.$key); }
    foreach ([4=>'balance',5=>'fee',6=>'totalFees',10=>'withdrawal',21=>'requiredWithdrawal',23=>'shortfall'] as $col=>$key) { $expected=$data['targetData'][$i][$key]??($key==='requiredWithdrawal'?($data['targetData'][$i]['withdrawal']??0):0); if ($row[$col] !== number_format($expected,2)) throw new RuntimeException('CSV bucket ledger mismatch: '.$key); }
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

// Evaluate the actual pure prompt construction, for both initial and follow-up requests.
$source=file_get_contents($root.'/api/explain_results.php');
$a=strpos($source,'// Build prompt');$b=strpos($source,"$"."messages =",$a);
foreach([false,true] as $is_follow_up) {
    $calculator_type='vanguard-pas-vs-target-date';eval(substr($source,$a,$b-$a));
    foreach(['same gross return','direct difference','inflation-adjusted','unfunded spending','Legacy percentage','fees once'] as $phrase)if(!str_contains($system_prompt,$phrase))throw new RuntimeException('Missing AI cost context');
    if(str_contains($system_prompt,'Monte Carlo')||str_contains($system_prompt,'Total Opportunity Cost'))throw new RuntimeException('Retired AI framing');
}
fwrite(STDOUT,"PAS deterministic AI instructions passed for initial and follow-up prompts.\n");
