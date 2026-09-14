<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/report_csv.php';
$n=0;function csvcheck(bool $ok):void{global $n;if(!$ok)throw new RuntimeException('CSV boundary failed');$n++;}
foreach(['=HYPERLINK("https://example.invalid")','+cmd','-cmd','@SUM(A1)','  =1+1',"\t+1"] as $value)csvcheck(str_starts_with(rb_csv_cell($value),"'"));
foreach([0,-123,-0.25,'-1,234.50','123.5','plain'] as $value)csvcheck(rb_csv_cell($value)===$value);
$stream=fopen('php://memory','w+');rb_csv_context($stream,'Synthetic Calculator',['balance'=>600000,'context'=>['rate'=>0,'email'=>'private@example.invalid','token'=>'secret']]);rb_csv_row($stream,['year','=1+1',0]);rewind($stream);$text=stream_get_contents($stream);csvcheck(str_contains($text,'600000'));csvcheck(str_contains($text,'rate,0'));csvcheck(!str_contains($text,'private')&&!str_contains($text,'secret'));csvcheck(str_contains($text,'RonBelisle.com'));csvcheck(str_contains($text,"'=1+1"));fclose($stream);
foreach(glob(__DIR__.'/../api/export_*_csv.php') as $file){$s=file_get_contents($file);csvcheck(str_contains($s,'rb_read_api_json(2097152)'));csvcheck(str_contains($s,'session_write_close'));csvcheck(str_contains($s,'rb_csv_context'));csvcheck(!str_contains($s,'fputcsv('));}
echo "Consumer CSV formula, privacy, context and resource boundaries passed ($n checks).\n";
