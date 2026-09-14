<?php
/** CLI only: real migration + SQL adapters in a random isolated schema; no Stripe calls. */
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
$root=realpath($argv[1]??dirname(__DIR__));if(!$root)throw new RuntimeException('Source missing');
$cfg=require '/etc/ronbelisle/config.php';$d=$cfg['db'];mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli($d['host'],$d['user'],$d['pass'],$d['name']);unset($d,$cfg);
$userDdl=$db->query('SHOW CREATE TABLE users')->fetch_assoc()['Create Table'];
$schema='rb_consumer_fixture_'.bin2hex(random_bytes(8));$db->query('CREATE DATABASE `'.$schema.'`');
$n=0;function cpdb(bool $ok,string $msg):void{global $n;if(!$ok)throw new RuntimeException($msg);$n++;}
function cpreject(callable $fn,string $msg):void{try{$fn();}catch(RuntimeException $e){cpdb(true,$msg);return;}throw new RuntimeException($msg);}
try {
 $db->select_db($schema);$db->set_charset('utf8mb4');$db->query("SET time_zone='+00:00'");$db->query($userDdl);
 $migration=file_get_contents($argv[2]??$root.'/sql/migrations/20260914_consumer_subscription_reliability.sql');
 foreach(explode(';',$migration) as $sql)if(trim($sql)!=='')$db->query($sql);
 cpdb((int)$db->query('SELECT COUNT(*) n FROM consumer_subscriptions')->fetch_assoc()['n']===0,'migration empty');
 $db->query("INSERT INTO users (id,email,password_hash,full_name,subscription_status) VALUES (1,'one@example.invalid','synthetic','One','free'),(2,'two@example.invalid','synthetic','Two','free'),(3,'manual@example.invalid','synthetic','Manual','premium')");
 $before=$db->query('SELECT * FROM users ORDER BY id')->fetch_all(MYSQLI_ASSOC);
 require $root.'/includes/consumer_checkout.php';$store=new RbConsumerSubscriptionStore($db);$now=time();$prices=['price_Cmonthly'=>'monthly','price_Cannual'=>'annual'];
 $calls=['create'=>0,'customer'=>0];$sessions=[];$subs=[];$lastParams=[];
 $api=[
  'customer'=>function(array $p,string $key)use(&$calls):array{$calls['customer']++;return ['id'=>'cus_'.($p['metadata']['consumer_user_id'])];},
  'create'=>function(array $p,string $key)use(&$calls,&$sessions,&$lastParams):array{$calls['create']++;$lastParams=$p;$id='cs_test_'.($p['client_reference_id']).$calls['create'];return $sessions[$id]=['id'=>$id,'customer'=>$p['customer'],'client_reference_id'=>$p['client_reference_id'],'mode'=>'subscription','status'=>'open','url'=>'https://checkout.stripe.com/c/pay/'.$id];},
  'session'=>function(string $id)use(&$sessions):array{return $sessions[$id]??[];},
  'subscription'=>function(string $id)use(&$subs):array{return $subs[$id]??[];},
 ];
 $url=rb_consumer_start_checkout($store,1,'monthly',$prices,$api,$now);cpdb(str_contains($url,'cs_test_11'),'checkout created');cpdb($lastParams['subscription_data']['trial_end']===$now+7*86400,'seven day trial');
 cpdb(rb_consumer_start_checkout($store,1,'annual',$prices,$api,$now)===$url,'open checkout reused even plan switch');cpdb($calls===['create'=>1,'customer'=>1],'no duplicate provider calls');
 cpdb(!rb_consumer_status($db,1)['has_premium'],'open checkout not premium');cpdb(rb_consumer_status($db,3)['has_premium'],'manual preserved');
 $sub=['id'=>'sub_1','customer'=>'cus_1','created'=>$now-20,'status'=>'trialing','trial_end'=>$now+7*86400,'current_period_end'=>$now+30*86400,'items'=>['data'=>[['price'=>['id'=>'price_Cmonthly']]]]];$subs['sub_1']=$sub;
 $sessions['cs_test_11']=array_replace($sessions['cs_test_11'],['status'=>'complete','subscription'=>'sub_1']);
 cpreject(fn()=>rb_consumer_confirm_checkout($store,2,'cs_test_11',$api,$prices,$now),'foreign return rejected');
 cpdb(rb_consumer_confirm_checkout($store,1,'cs_test_11',$api,$prices,$now)==='processed','owned confirmation');cpdb(rb_consumer_status($db,1)['has_premium'],'trial grants');cpdb(!rb_consumer_status($db,2)['has_premium'],'other account no access');
 cpdb(rb_consumer_confirm_checkout($store,1,'cs_test_11',$api,$prices,$now)==='duplicate','return replay deduplicated');
 $eventId=0;$apply=function(string $status,int $created)use(&$subs,$store,$api,$prices,&$eventId,$now):string{$subs['sub_1']['status']=$status;$event=['id'=>'evt_'.(++$eventId),'type'=>'customer.subscription.updated','created'=>$created,'data'=>['object'=>['id'=>'sub_1']]];return cfa_process_subscription_event($store,$event,$api['subscription'],$prices,$now);};
 cpdb($apply('active',$now+1)==='processed','active');cpdb(rb_consumer_status($db,1)['has_premium'],'active DB');
 cpdb($apply('past_due',$now+2)==='processed','pastdue');$state=$store->subscriber(1);cpdb(rb_consumer_status($db,1)['in_grace'],'grace DB');
 cpdb($apply('past_due',$now+3)==='processed','repeat pastdue');cpdb($store->subscriber(1)['past_due_started_at']===$state['past_due_started_at'],'fixed grace clock');
 cpdb($apply('active',$now+4)==='processed','recovered');cpdb($store->subscriber(1)['past_due_started_at']===null,'grace reset after recovery');
 $subs['sub_1']['ended_at']=$now;cpdb($apply('canceled',$now+5)==='processed','cancel');cpdb(!rb_consumer_status($db,1)['has_premium'],'cancel revoked');
 cpdb(rb_consumer_status($db,1)['billing_customer']==='cus_1','billing after loss');cpdb($apply('active',$now+4)==='stale','stale event');cpdb($apply('active',$now+6)==='terminal','terminal cannot reopen');
 cpdb(rb_consumer_confirm_checkout($store,1,'cs_test_11',$api,$prices,$now+7)==='duplicate','canceled return replay');cpdb(!rb_consumer_status($db,1)['has_premium'],'replay cannot grant');
 $subs['sub_1']['status']='canceled';$sessions['cs_test_11']['status']='expired';
 rb_consumer_start_checkout($store,1,'annual',$prices,$api,$now+8);cpdb(!isset($lastParams['subscription_data']['trial_end']),'resubscribe no new trial');cpdb($calls['customer']===1,'reuse customer');
 // A failed retrieval rolls back the event receipt, leaving the event retryable.
 $event=['id'=>'evt_Retry','type'=>'customer.subscription.updated','created'=>$now+10,'data'=>['object'=>['id'=>'sub_1']]];
 cpreject(fn()=>cfa_process_subscription_event($store,$event,static fn()=>[],$prices,$now),'API failure retry');cpdb(!$store->seen('evt_Retry'),'no receipt on failure');
 $subs['sub_1']['items']['data'][0]['price']['id']='price_Journey';cpdb(cfa_process_subscription_event($store,$event,$api['subscription'],$prices,$now)==='ignored_product','product move');cpdb(!rb_consumer_status($db,1)['has_premium'],'unknown product denies');
 $after=$db->query('SELECT * FROM users ORDER BY id')->fetch_all(MYSQLI_ASSOC);cpdb($before===$after,'original account data unchanged');
 $db->select_db('information_schema');cpdb((int)$db->query("SELECT COUNT(*) n FROM TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='$schema' AND CONSTRAINT_TYPE='FOREIGN KEY'")->fetch_assoc()['n']===2,'migration foreign keys');
 echo "Consumer isolated migration and SQL lifecycle passed ($n checks).\n";
}finally{$db->query('DROP DATABASE `'.$schema.'`');$db->close();}
