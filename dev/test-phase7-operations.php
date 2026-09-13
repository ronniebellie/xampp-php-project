<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/api_resources.php';
require_once __DIR__.'/../includes/calculator_catalog.php';
require_once __DIR__.'/../includes/report_pdf.php';
$checks=0;
function p7check(bool $ok,string $message):void {global $checks;if(!$ok)throw new RuntimeException($message);$checks++;}
function p7reject(callable $fn,string $message):void {try{$fn();}catch(Throwable $e){p7check(true,$message);return;}p7check(false,$message);}
p7check(rb_bounded_json('{"zero":0}',10)===['zero'=>0],'valid zero JSON');
foreach([
 ['REQUEST_METHOD'=>'GET'],
 ['REQUEST_METHOD'=>'POST','CONTENT_TYPE'=>'text/plain'],
 ['REQUEST_METHOD'=>'POST','CONTENT_TYPE'=>'application/json','CONTENT_LENGTH'=>101],
] as $i=>$server){
 $code='require '.var_export(realpath(__DIR__.'/../includes/api_resources.php'),true).'; $_SERVER='.var_export($server,true).'; register_shutdown_function(function(){echo "|".http_response_code();}); rb_read_api_json(100);';
 $output=shell_exec(escapeshellarg(PHP_BINARY).' -r '.escapeshellarg($code));
 p7check(str_ends_with((string)$output,'|'.[405,415,413][$i]),'request method/type/size status');
}
p7reject(fn()=>(new RbReportPdf())->Error('synthetic private diagnostic'),'TCPDF errors throw instead of public exit');
foreach(['[]','null','false','{bad'] as $body)p7reject(fn()=>rb_bounded_json($body,100),'invalid JSON object');
p7reject(fn()=>rb_bounded_json('{"a":"123"}',5),'body bound');
p7check(rb_ai_data(['results_summary'=>'0'])['results_summary']==='0','zero summary valid');
foreach([null,[],3,''] as $value)p7reject(fn()=>rb_ai_data(['results_summary'=>$value]),'summary type');
foreach([
 ['results_summary'=>str_repeat('a',8001)],
 ['results_summary'=>'x','conversation'=>[['role'=>'system','content'=>'inject']]],
 ['results_summary'=>'x','conversation'=>array_fill(0,21,['role'=>'user','content'=>'x'])],
 ['results_summary'=>'x','conversation'=>array_fill(0,9,['role'=>'user','content'=>str_repeat('x',4000)])],
 ['results_summary'=>'x','follow_up_question'=>str_repeat('a',2001)],
] as $data)p7reject(fn()=>rb_ai_data($data),'AI bounds');
p7check(rb_pdf_data(['rate'=>0,'label'=>'A & B','html'=>'<img src="http://example.invalid/x">text'])===['rate'=>0,'label'=>'A & B','html'=>'text'],'PDF plain text and zero');
p7reject(fn()=>rb_pdf_data(['rows'=>array_fill(0,1001,1)]),'PDF rows');
p7reject(fn()=>rb_pdf_data(['rate'=>INF]),'PDF nonfinite number');
p7reject(fn()=>rb_pdf_data(['text'=>str_repeat('x',16385)]),'PDF text');
p7reject(fn()=>rb_pdf_data(['rows'=>array_fill(0,1000,array_fill(0,51,0))]),'PDF total work');
$png='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aX1cAAAAASUVORK5CYII=';
p7check(strlen(rb_png_bytes($png))>0,'valid PNG');
foreach(['data:image/svg+xml;base64,PHN2Zz4=','data:image/png;base64,!!!!','https://example.invalid/chart.png'] as $value)p7reject(fn()=>rb_png_bytes($value),'PNG input format');
$bytes=rb_png_bytes($png);$bytes=substr_replace($bytes,pack('N',100000),16,4);
p7reject(fn()=>rb_png_bytes('data:image/png;base64,'.base64_encode($bytes)),'PNG decompression dimensions');
$path=rb_pdf_chart_file($png);p7check(is_file($path)&&file_get_contents($path)===rb_png_bytes($png),'single actual temp path');unlink($path);
// Real child process proves cleanup on normal exit, not merely a source assertion.
$cmd=escapeshellarg(PHP_BINARY).' -r '.escapeshellarg('require '.var_export(realpath(__DIR__.'/../includes/api_resources.php'),true).'; echo rb_pdf_chart_file('.var_export($png,true).');');
$childPath=shell_exec($cmd);p7check(is_string($childPath)&&str_contains($childPath,'rb_chart_')&&!file_exists($childPath),'shutdown chart cleanup');
$dir=sys_get_temp_dir().'/phase7-rate-'.bin2hex(random_bytes(8));
try {
 $lease=rb_ai_lease('cfa:1',$dir,100);p7check(is_resource($lease),'first lease');
 p7check(rb_ai_lease('cfa:1',$dir,100)===null,'concurrency denied');
 $other=rb_ai_lease('cfa:2',$dir,100);p7check(is_resource($other),'account isolation');fclose($other);fclose($lease);
 for($i=1;$i<6;$i++){$lease=rb_ai_lease('cfa:1',$dir,100);p7check(is_resource($lease),'burst within limit');fclose($lease);}
 p7check(rb_ai_lease('cfa:1',$dir,159)===null,'burst denied');
 $lease=rb_ai_lease('cfa:1',$dir,160);p7check(is_resource($lease),'window resets');fclose($lease);
 p7reject(fn()=>rb_ai_lease('../bad',$dir,160),'invalid owner');
} finally {foreach(glob($dir.'/*.json')?:[] as $file)unlink($file);if(is_dir($dir))rmdir($dir);}
p7check(!rb_ai_reserve_monthly(null,1,100),'unavailable monthly ledger fails safe');
$root=dirname(__DIR__);
foreach(glob($root.'/api/generate_*pdf.php') as $file){if(str_contains($file,'journey'))continue;$source=file_get_contents($file);p7check(str_contains($source,'rb_read_api_json(8388608)')&&str_contains($source,'rb_pdf_data($data)')&&!str_contains($source,'tempnam('),'all calculator PDFs bounded and cleaned');}
$ai=file_get_contents($root.'/api/explain_results.php');
foreach(['ss'=>'$fra =','ss_summary'=>'$life =','ss_early_exit'=>'$scenarios =','ss_survivor'=>'$r =','retirement_plan'=>'$summary ='] as $endpoint=>$alias){
 $source=file_get_contents($root.'/api/generate_'.$endpoint.'_pdf.php');
 p7check(strpos($source,'rb_pdf_data($data)')<strpos($source,$alias),'sanitize before report aliases');
}
p7check(!str_contains($ai,"empty(\$data['results_summary'])"),'zero summary not rejected downstream');
p7check(str_contains($ai,'rb_csrf_validate')&&str_contains($ai,'session_write_close')&&!str_contains($ai,'CREATE TABLE'),'AI CSRF/session/no DDL');
ob_start();include $root.'/sitemap.php';$xml=ob_get_clean();
p7check(!str_contains($xml,'<lastmod>'),'no invented sitemap dates');
foreach(rb_calculator_catalog() as $calc)if($calc['active'])p7check(substr_count($xml,'https://ronbelisle.com'.$calc['route'].'</loc>')===1,'catalog sitemap route '.$calc['route']);
foreach(['/.htaccess','/calcforadvisors/.htaccess'] as $file)p7check(str_contains(file_get_contents($root.$file),"object-src 'none'; frame-ancestors 'self'"),'compatible CSP');
$_SERVER['HTTP_HOST']='ronbelisle.com';$_SERVER['REQUEST_URI']='/ss-survivor-impact/';
$ld_name='stale name';$ld_description='stale description';ob_start();include $root.'/includes/json-ld-softwareapp.php';$ld=ob_get_clean();
p7check(str_contains($ld,'Social Security Survivor Impact')&&!str_contains($ld,'stale name'),'catalog JSON-LD');
$_SERVER['REQUEST_URI']='/synthetic-not-catalog/';$ld_name='</script><img src=x>';$ld_description='plain';ob_start();include $root.'/includes/json-ld-softwareapp.php';$ld=ob_get_clean();
p7check(substr_count($ld,'</script>')===1&&!str_contains($ld,'<img'),'JSON-LD script breakout prevented');
echo "Phase 7 operational tests passed ($checks checks).\n";
