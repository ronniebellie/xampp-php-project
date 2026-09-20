<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/pas_stress_report.php';
$d=json_decode(file_get_contents($argv[1]??__DIR__.'/fixtures/pas-stress-report.json'),true,512,JSON_THROW_ON_ERROR);
rb_pas_stress_validate($d);
$out=fopen('php://temp','w+');rb_pas_stress_csv($out,$d);rewind($out);$csv=stream_get_contents($out);fclose($out);
foreach(['Retirement Stress Test','Simulation count','Seed','PAS volatility','Inflation','Survival','Conservative','Correlation','not predictions or guarantees'] as $text)if(!str_contains($csv,$text))throw new RuntimeException('Missing report field '.$text);
$lines=array_map('str_getcsv',explode("\n",trim($csv)));
$median=array_values(array_filter($lines,fn($r)=>($r[0]??'')==='Median ending balance ($)'))[0];
if(abs((float)$median[1]-$d['pas']['ending']['p50'])>.00001)throw new RuntimeException('Exported median mismatch');
$annual=array_values(array_filter($lines,fn($r)=>str_starts_with($r[0]??'','End of ')||str_starts_with($r[0]??'','Start of ')));
if(count($annual)!==count($d['annual']))throw new RuntimeException('Annual export row mismatch');
foreach($annual as $i=>$r)if(abs((float)$r[15]-$d['annual'][$i]['buckets'][1]['p50'])>.00001)throw new RuntimeException('Annual bucket mismatch');
$bad=$d;unset($bad['pas']);try{rb_pas_stress_validate($bad);throw new RuntimeException('Invalid report accepted');}catch(InvalidArgumentException $expected){}
$pdf=rb_pas_stress_pdf($d);if(!str_starts_with($pdf,'%PDF-'))throw new RuntimeException('PDF generation failed');
if(isset($argv[2])){file_put_contents($argv[2].'.pdf',$pdf);file_put_contents($argv[2].'.csv',$csv);}
echo "PASS Stress Test PDF/CSV rendering, assumptions, numerical summaries, annual rows and invalid report rejection.\n";
