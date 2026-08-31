<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/calcforadvisors_portal.php';
require_once $root . '/includes/embed_mode.php';
$failures=[]; $checks=0;
$expect=static function(bool $ok,string $message)use(&$failures,&$checks):void{$checks++;if(!$ok)$failures[]=$message;};

$advisor=rb_advisor_calculators(); $groups=rb_advisor_calculators_grouped();
$expect(count($advisor)===16,'Portal source must contain exactly 16 advisor calculators.');
$expect(array_keys($groups)===array_keys(RB_CALCULATOR_ADVISOR_CATEGORIES),'Advisor categories or order differ from catalog.');
$expect(array_sum(array_map('count',$groups))===16,'Grouped advisor count differs from curated set.');
foreach(rb_calculator_catalog() as $id=>$calculator){
    $resolved=cfa_advisor_calculator($id);
    $expect(($calculator['advisor'] === true) === ($resolved !== null), "Advisor filtering mismatch for {$id}.");
    if($resolved){$badges=cfa_calculator_badges($resolved);$expect(!in_array('CSV',$badges,true),"CSV badge rendered for {$id}.");foreach(['save'=>'Save','compare'=>'Compare','pdf'=>'PDF','ai'=>'AI Explain']as$flag=>$label)$expect(in_array($label,$badges,true)===($resolved[$flag]===true),"Badge mismatch for {$id}: {$label}.");}
}
$expect(cfa_advisor_calculator('debt-payoff')===null,'Excluded calculator accepted by wrapper.');
$expect(cfa_advisor_calculator('../retirement-plan')===null,'Malformed calculator ID accepted.');

$active=['portal_slug'=>'sample-advisor','firm_name'=>'Sample Firm','plan'=>'monthly','status'=>'active','stripe_subscription_status'=>'active'];
$expired=$active+['plan'=>'free']; $expired['plan']='free';$expired['stripe_subscription_status']='';$expired['created_at']='2020-01-01 00:00:00';
$available=cfa_resolve_public_portal('sample-advisor',static fn(string $slug):array=>$active);
$unavailable=cfa_resolve_public_portal('sample-advisor',static fn(string $slug):array=>$expired);
$invalid=cfa_resolve_public_portal('../admin',static fn(string $slug):array=>$active);
$expect($available['state']==='available','Active portal did not resolve available.');
$expect($unavailable['state']==='unavailable','Expired portal did not resolve unavailable.');
$expect($invalid['state']==='invalid','Invalid portal slug did not fail safely.');
$expect(cfa_portal_path('../admin')==='/'&&cfa_portal_path('sample-advisor')==='/p/sample-advisor','Portal return path validation failed.');
$expect(rb_safe_calculator_return_url('https://calcforadvisors.ronbelisle.com/p/sample-advisor')!==null,'Future portal return URL rejected.');
$expect(rb_safe_calculator_return_url('https://evil.example/steal')===null,'Arbitrary return URL accepted.');
$expect(rb_safe_calculator_return_url('javascript:alert(1)')===null,'Unsafe return URL accepted.');

$portalSource=file_get_contents($root.'/advisor-portal.php');$wrapperSource=file_get_contents($root.'/advisor-calculator.php');
$expect(strpos($portalSource,'name="robots" content="noindex,nofollow"')!==false,'Portal noindex metadata missing.');
$expect(strpos($portalSource,"X-Robots-Tag: noindex, nofollow")!==false,'Portal robots response header missing.');
$expect(strpos($portalSource,'Powered by RonBelisle.com')!==false,'Portal Powered by branding missing.');
$expect(strpos($wrapperSource,'Powered by RonBelisle.com')!==false,'Wrapper Powered by branding missing.');
$expect(strpos($portalSource,'subscription status')===false&&strpos($portalSource,'past_due')===false,'Public unavailable message exposes billing internals.');

$_GET=[];ob_start();include $root.'/includes/back-link-include.php';$normal=ob_get_clean();
$_GET=['embed'=>'1'];ob_start();include $root.'/includes/back-link-include.php';$embedded=ob_get_clean();
ob_start();include $root.'/includes/footer_simple.php';$embeddedFooter=ob_get_clean();
$expect(strpos($normal,'Return to home page')!==false,'Normal calculator chrome changed unexpectedly.');
$expect($embedded===''&&$embeddedFooter==='','Embed mode did not reduce shared calculator chrome.');

if($failures){fwrite(STDERR,"CalcForAdvisors portal tests failed:\n- ".implode("\n- ",$failures)."\n");exit(1);}echo "CalcForAdvisors portal tests passed ({$checks} checks).\n";
