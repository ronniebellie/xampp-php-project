<?php
require __DIR__ . '/../includes/numerical_math.php';
$checks=0;
function near($a,$b): void { global $checks; $checks++; if (!is_finite($a)||abs($a-$b)>1e-6) throw new RuntimeException("$a != $b"); }
function invalid(callable $fn): void { global $checks; $checks++; try {$fn();} catch (DomainException $e) {return;} throw new RuntimeException('Expected mathematical-domain rejection'); }
near(round(rb_math_annuity(30000,.05,25,true),2),422818.34);
near(rb_math_annuity(100,.01,12),1268.2503013197);
near(rb_math_annuity(100,0,12),1200);near(rb_math_annuity(100,0,12,true),1200);
near(rb_math_pv(12000,0,10),12000);
invalid(fn()=>rb_math_periods(1000,2000,-.05,1));
near(rb_math_periods(1000,500,-.05,1),log(.5)/log(.95));
near(rb_math_periods(1000,1000,0,12),0);
foreach ([-1,-1.1,NAN,INF] as $r) {invalid(fn()=>rb_math_pv(1000,$r,2));invalid(fn()=>rb_math_annuity(100,$r,12));}
invalid(fn()=>rb_math_annuity(100,.01,1.5));invalid(fn()=>rb_math_pv(1e308,-.9,1000));
near(rb_math_growing(100,0,0,12,true),1200);near(rb_math_growing(100,0,0,12,false),1200);
invalid(fn()=>rb_math_growing(100,-1,0,12,true));
foreach([0,.05,-.05] as $rate)foreach([$rate,$rate+1e-14] as $growth){
    $pv=0.0;$fv=0.0;$payment=100.0;
    for($period=1;$period<=12;$period++){$pv+=$payment/pow(1+$rate,$period);$fv=$fv*(1+$rate)+$payment;$payment*=1+$growth;}
    near(rb_math_growing(100,$rate,$growth,12,true),$pv);near(rb_math_growing(100,$rate,$growth,12,false),$fv);
}
// Execute actual POST controllers in isolated CLI processes, excluding only
// session bootstrap and HTML rendering. No HTTP, database or session files.
function controller(string $page, array $post): array {
    $file=__DIR__.'/../time-value-of-money/'.$page.'/index.php';
    $source=explode('?>',file_get_contents($file))[0];
    $source=str_replace(['<?php', "require_once \$_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';", 'rb_session_start();', '__DIR__'],['','','',var_export(dirname($file),true)],$source);
    $code='$_POST='.var_export($post,true).'; $_GET=[]; $_SESSION=[]; $_SERVER["REQUEST_METHOD"]="POST"; register_shutdown_function(function(){echo json_encode(["session"=>$_SESSION,"errors"=>$GLOBALS["errors"]??[],"result"=>$GLOBALS["result"]??$GLOBALS["resultRatePct"]??null],JSON_THROW_ON_ERROR);});'.$source;
    $proc=proc_open([PHP_BINARY,'-r',$code],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
    $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
    if(proc_close($proc)!==0||$err!=='')throw new RuntimeException($err.' '.$out);
    return json_decode($out,true,512,JSON_THROW_ON_ERROR);
}
foreach(['future-value-annuity','present-value-annuity'] as $page){
    $out=controller($page,['pmt'=>'100','rate'=>'0','years'=>'1','compound'=>'12']);
    near($out['result']??array_values($out['session'])[0],1200);
    $out=controller($page,['pmt'=>'100','rate'=>'-1200','years'=>'1','compound'=>'12']);
    $checks++;if(!$out['errors']||$out['result']!==null||$out['session'])throw new RuntimeException('Invalid annuity returned result');
}
near(controller('present-value',['fv'=>'12000','rate'=>'0','years'=>'10','compound'=>'12'])['session']['pv_single_result'],12000);
near(controller('loan-payment',['principal'=>'12000','rate'=>'0','years'=>'1','compound'=>'12'])['session']['loan_payment_result'],1000);
$out=controller('number-of-periods',['pv'=>'1000','fv'=>'2000','rate'=>'-5','compound'=>'1']);
$checks++;if(!$out['errors']||$out['session'])throw new RuntimeException('Unattainable duration accepted');
near(controller('interest-rate',['pv'=>'1e-20','fv'=>'1.1e-20','years'=>'1','compound'=>'1'])['result'],10);
foreach(['pv','fv'] as $mode){
    $out=controller('growing-annuity',['pmt1'=>'100','rate'=>'0','growth'=>'0','years'=>'1','compound'=>'12','mode'=>$mode]);
    near($out['session']['growing_annuity_result']['value'],1200);
}
// Exercise the real export formatting sections with reordered claiming ages.
$a=70;$b=62;$c=67;
$dataA=[['age'=>70,'monthlyBenefit'=>100,'cumulativeTotal'=>1200/pow(1.05,9)]];
$dataB=[['age'=>62,'monthlyBenefit'=>100,'cumulativeTotal'=>1200/1.05]];
$dataC=[['age'=>67,'monthlyBenefit'=>100,'cumulativeTotal'=>1200/pow(1.05,6)]];
$source=file_get_contents(__DIR__.'/../api/export_ss_csv.php');
$body=substr($source,strpos($source,'$out = fopen'));
$body=substr($body,0,strpos($body,'exit;'));
ob_start();eval($body);$csv=ob_get_clean();$lines=explode("\n",trim($csv));
$checks++;if(!str_contains($lines[0],'PV at age 62')||str_getcsv($lines[1])[0]!=='62')throw new RuntimeException('CSV valuation/age ordering mismatch');
near((float)str_replace(',','',str_getcsv($lines[1])[4]),round(1200/1.05,2));
$data=['dataA'=>$dataA,'dataB'=>$dataB,'dataC'=>$dataC];
$source=file_get_contents(__DIR__.'/../api/generate_ss_pdf.php');
$body=substr($source,strpos($source,'$dataA ='));
$body=substr($body,0,strpos($body,"\$pdf->SetFont"));eval($body);
$checks++;if(strpos($tableHtml,'<td>62</td>')>strpos($tableHtml,'<td>70</td>')||!str_contains($tableHtml,'$1,143'))throw new RuntimeException('PDF table valuation/age ordering mismatch');
echo "Phase 4 PHP numerical tests passed ($checks checks).\n";
