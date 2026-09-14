<?php
declare(strict_types=1);
$root=dirname(__DIR__);
require $root.'/vendor/autoload.php';
require $root.'/includes/journey_summary_pdf.php';
require $root.'/includes/journey_checkout.php';
require $root.'/includes/journey_feedback.php';
require $root.'/includes/journey_billing_portal.php';
require $root.'/includes/auth_rate_limit.php';
$checks=0;function checkJ(bool $condition,string $message):void{global $checks;if(!$condition)throw new RuntimeException($message);$checks++;}
$now=1800000000;
foreach(['active','trialing','canceled_grace'] as $status){
 $row=['entitlement_status'=>$status,'current_period_end'=>$now+100,'trial_end'=>$now+50];
 checkJ(journey_stored_entitlement_allows_access($row,$now),$status.' current');
 checkJ(!journey_stored_entitlement_allows_access($row,$now+100),$status.' expires');
 checkJ(!journey_stored_entitlement_allows_access(['entitlement_status'=>$status],$now),$status.' missing deadline');
}
foreach(['past_due','unpaid','paused','canceled','incomplete','incomplete_expired'] as $status)checkJ(!journey_stored_entitlement_allows_access(['entitlement_status'=>$status,'current_period_end'=>$now+100],$now),$status.' denied');
checkJ(journey_feedback_sanitize_page_url('https://journey.ronbelisle.com/phases/social-security.php?benefit=1234&token=secret#name')==='https://journey.ronbelisle.com/phases/social-security.php','Feedback strips query and fragment');
checkJ(journey_feedback_sanitize_page_url('https://evil.example/')===null,'Feedback host');
checkJ(!isset(journey_build_portal_session_params('cus_fixture','sub_fixture')['flow_data']),'Portal supports recovery');
$keys=['spending-goals','social-security','build-your-plan','stress-test','tax-strategy','survivor-planning'];
$plan=['saved'=>true,'monthlyRetirementSpendingGoal'=>4000,'monthlySocialSecurityAssumption'=>2000,'monthlyOtherDependableIncome'=>500,'monthlyNeededFromRetirementSavings'=>1500,'annualNeededFromRetirementSavings'=>18000,'retirementSavingsBalance'=>450000];
$progress=['records'=>[]];foreach($keys as $key){$progress[$key]=true;$progress['records'][$key]=['saved'=>true,'phase3Snapshot'=>$plan];}
$progress['records']['build-your-plan']=$plan;
$progress['records']['spending-goals']['result']['dataForLaterPhases']=['monthlyRetirementSpendingTarget'=>4000,'monthlyOtherRegularRetirementIncome'=>500];
checkJ(journey_summary_pdf_ready($progress),'Coherent six-phase report');
$bad=$progress;$bad['records']['build-your-plan']['socialSecuritySource']='phase2';checkJ(!journey_summary_pdf_ready($bad),'Missing source benefit rejected');
$bad['records']['social-security']['lastSavedPlanning']=['decisionStatus'=>'provisional','estimatedMonthlyBenefit'=>2000];checkJ(journey_summary_pdf_ready($bad),'Saved source benefit agreement');
$bad['records']['social-security']['lastSavedPlanning']['estimatedMonthlyBenefit']=1000;checkJ(!journey_summary_pdf_ready($bad),'Changed source benefit rejected');
foreach($keys as $key){$bad=$progress;$bad['records'][$key]['needsReview']=true;checkJ(!journey_summary_pdf_ready($bad),'Stale phase rejected '.$key);}
$bad=$progress;$bad['records']['build-your-plan']['annualNeededFromRetirementSavings']=1;checkJ(!journey_summary_pdf_ready($bad),'Annual mismatch rejected');
$bad=$progress;$bad['records']['stress-test']['phase3Snapshot']['retirementSavingsBalance']=10;checkJ(!journey_summary_pdf_ready($bad),'Cross-phase mismatch rejected');
$data=journey_summary_pdf_extract($progress);checkJ($data['overview']['fromInvestments']===1500.0 && $data['phase3']['annualFromSavings']===18000,'Report numerical agreement');
checkJ(journey_summary_pdf_money(INF)==='—','Nonfinite report value');
$dir=sys_get_temp_dir().'/journey-rate-'.bin2hex(random_bytes(6));
try{for($i=0;$i<3;$i++)checkJ(rb_auth_rate_allow('fixture-account',3,60,$dir,$now),'Admission');checkJ(!rb_auth_rate_allow('fixture-account',3,60,$dir,$now),'Rate cap');checkJ(rb_auth_rate_allow('other-account',3,60,$dir,$now),'Independent key');checkJ(rb_auth_rate_allow('fixture-account',3,60,$dir,$now+60),'Rate expiry');}finally{foreach(glob($dir.'/*')?:[] as $file)unlink($file);if(is_dir($dir))rmdir($dir);}
foreach(['login','register'] as $name){$html=file_get_contents($root.'/auth/'.$name.'.php');checkJ(str_contains($html,'rb_csrf_validate')&&str_contains($html,'rb_csrf_field()'),'Auth CSRF '.$name);checkJ(str_contains($html,'rb_auth_rate_allow'),'Auth throttling '.$name);}
$api=file_get_contents($root.'/api/generate_journey_summary_pdf.php');checkJ(str_contains($api,'journey_plan_require_csrf($body)')&&str_contains($api,'session_write_close()'),'PDF CSRF/session');
echo "Journey security/report regression passed ($checks checks).\n";
