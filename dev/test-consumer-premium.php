<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/consumer_checkout.php';
$n=0;function cpcheck(bool $ok,string $label):void{global $n;if(!$ok)throw new RuntimeException($label);$n++;}
foreach(['cs_live_a123','cs_test_b456','cs_fixture'] as $id)cpcheck(cfa_stripe_id($id,'cs')===$id,'real checkout ID shape');
$now=1800000000;$date=static fn(int $t):DateTimeImmutable=>(new DateTimeImmutable('@'.$t));
$user=['id'=>1,'subscription_status'=>'free'];
cpcheck(!rb_consumer_evaluate($user,null,$date($now))['has_premium'],'free');
$manual=['id'=>1,'subscription_status'=>'premium','stripe_subscription_id'=>null];
cpcheck(rb_consumer_evaluate($manual,null,$date($now))['has_premium'],'manual preserved');
cpcheck(!rb_consumer_evaluate($manual+['subscription_end_date'=>gmdate('Y-m-d H:i:s',$now)],null,$date($now))['has_premium'],'manual expires');
cpcheck(!rb_consumer_evaluate(array_replace($manual,['stripe_subscription_id'=>'sub_1']),null,$date($now))['has_premium'],'legacy stripe flag fails closed');
$row=['id'=>1,'plan'=>'monthly','stripe_subscription_id'=>'sub_1'];
$base=['id'=>'sub_1','customer'=>'cus_1','created'=>$now-100,'current_period_end'=>$now+86400,'items'=>['data'=>[['price'=>['id'=>'price_Cmonthly']]]]];
foreach(['active','trialing','past_due','unpaid','paused','incomplete','incomplete_expired','canceled'] as $status){
 $sub=$base+['status'=>$status,'trial_end'=>$now+3600,'ended_at'=>$now];
 $state=cfa_subscription_state($row,$sub,'monthly',$now,$now);
 $result=rb_consumer_evaluate($user,$state,$date($now));
 cpcheck($result['has_premium']===in_array($status,['active','trialing','past_due'],true),'status '.$status);
 if($status==='active')cpcheck(!rb_consumer_evaluate($user,$state,$date($now+86400))['has_premium'],'active exact expiry');
 if($status==='trialing')cpcheck(!rb_consumer_evaluate($user,$state,$date($now+3600))['has_premium'],'trial exact expiry');
 if($status==='past_due'){
  cpcheck(rb_consumer_evaluate($user,$state,$date($now+7*86400-1))['has_premium'],'grace last second');
  cpcheck(!rb_consumer_evaluate($user,$state,$date($now+7*86400))['has_premium'],'grace expiry');
  $again=cfa_subscription_state($state,$sub,'monthly',$now+86400,$now+86400);
  cpcheck($again['past_due_started_at']===$state['past_due_started_at'],'retry never resets grace');
 }
}
$state=cfa_subscription_state($row,$base+['status'=>'active','cancel_at_period_end'=>true,'cancel_at'=>$now+3600],'annual',$now,$now);
cpcheck(rb_consumer_evaluate($user,$state,$date($now+3599))['has_premium'],'scheduled remains');
cpcheck(!rb_consumer_evaluate($user,$state,$date($now+3600))['has_premium'],'scheduled earlier cancel endpoint');
$trialState=cfa_subscription_state($row,$base+['status'=>'trialing','trial_end'=>$now+86400,'cancel_at'=>$now+3600],'monthly',$now,$now);
cpcheck(!rb_consumer_evaluate($user,$trialState,$date($now+3600))['has_premium'],'trial early cancellation exact expiry');
cpcheck(!rb_consumer_evaluate($user,$row+['stripe_subscription_status'=>'active'],$date($now))['has_premium'],'unsynchronized state denied');
foreach(['https://evil.test/','http://checkout.stripe.com/','https://checkout.stripe.com.evil.test/','https://me@checkout.stripe.com/'] as $url){try{rb_consumer_checkout_url($url);throw new LogicException('accepted');}catch(RuntimeException $e){cpcheck(true,'redirect rejected');}}
cpcheck(rb_consumer_checkout_url('https://checkout.stripe.com/c/pay/cs_test')==='https://checkout.stripe.com/c/pay/cs_test','provider URL');
cpcheck(cfa_subscription_plan($base,['price_Jmonthly'=>'monthly'])===null,'Journey not consumer');
cpcheck(cfa_subscription_plan($base,['price_Cmonthly'=>'monthly'])==='monthly','consumer price recognized');
$two=$base;$two['items']['data'][]=$two['items']['data'][0];cpcheck(cfa_subscription_plan($two,['price_Cmonthly'=>'monthly'])===null,'multi-price rejected');
foreach(['checkout.php','success.php','billing_portal.php'] as $file){$s=file_get_contents(__DIR__.'/../'.$file);cpcheck(str_contains($s,'rb_csrf_validate'),'CSRF '.$file);cpcheck(!str_contains($s,'HTTP_HOST'),'canonical '.$file);cpcheck(!str_contains($s,'getMessage()'),'generic errors '.$file);}
cpcheck(!str_contains(file_get_contents(__DIR__.'/../success.php'),'@mail'),'no repeated welcome side effect');
echo "Consumer Premium pure lifecycle and boundaries passed ($n checks).\n";
