<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/calculator_catalog.php';
require_once __DIR__.'/../includes/calcforadvisors_entitlement.php';
$checks=0;
function p6(bool $ok,string $why):void {global $checks;if(!$ok)throw new RuntimeException($why);$checks++;}
ob_start();require __DIR__.'/../calcforadvisors/catalog.php';$catalog=json_decode(ob_get_clean(),true);
p6($catalog['count']===count(rb_advisor_calculators()),'Catalog count derived');
p6(array_column($catalog['calculators'],'id')===array_keys(rb_advisor_calculators()),'Exact advisor set');
$trial=file_get_contents(__DIR__.'/../calcforadvisors/trial.php');
p6(str_contains($trial,'rb_advisor_calculators()')&&!str_contains($trial,'<iframe'),'Catalog trial with no blocked cross-origin iframe');
p6(str_contains($trial,'<a class="card"')&&str_contains($trial,'noopener noreferrer'),'Native keyboard links and opener isolation');
foreach(['index.html','account.php'] as $file)p6(!preg_match('/\b(?:14|16) retirement calculators/',file_get_contents(__DIR__.'/../calcforadvisors/'.$file)),'No hard-coded counts');
$now=new DateTimeImmutable('2026-09-15T00:00:00Z');
p6(!cfa_evaluate_advisor_entitlement(['plan'=>'free','status'=>'active','created_at'=>'2026-08-16 00:00:00'],$now)['portal_available'],'Trial exact endpoint denied');
$compare=file_get_contents(__DIR__.'/../js/compare-scenarios-modal.js');
foreach(['A','B','C'] as $letter)p6(str_contains($compare,'for="compareSelect'.$letter.'"'),'Select label');
p6(!str_contains($compare,'overlay) close();'),'Backdrop closes modal, not browser window');
require_once __DIR__.'/../vendor/autoload.php';require_once __DIR__.'/../includes/report_context.php';
$pdf=new TCPDF();$pdf->setPrintHeader(false);$pdf->setPrintFooter(false);$pdf->setCompression(false);rb_report_context($pdf);$bytes=$pdf->Output('','S');
p6(str_starts_with($bytes,'%PDF-')&&str_contains($bytes,'Report context and assumptions'),'Actual PDF contains context');
foreach(glob(__DIR__.'/../api/generate_*_pdf.php') as $file){if(basename($file)==='generate_journey_summary_pdf.php')continue;p6(str_contains(file_get_contents($file),'rb_report_context($pdf)'),'Every calculator PDF has context');}
echo "Phase 6 workflow/report tests passed ($checks checks).\n";
