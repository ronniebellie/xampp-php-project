<?php
/** Private CLI verifier: copies schema into connection-local TEMPORARY tables only. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
$root = realpath($argv[1] ?? dirname(__DIR__));
if (!$root) throw new RuntimeException('Missing source');
$config = require '/etc/ronbelisle/config.php';
$d=$config['db'];mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db=new mysqli($d['host'],$d['user'],$d['pass'],$d['name']);unset($d,$config);
$db->set_charset('utf8mb4');$db->query("SET time_zone = '+00:00'");
require $root.'/includes/journey_plan_store.php';
require $root.'/includes/journey_stripe_sync.php';
require $root.'/includes/journey_billing_portal.php';
$checks=0;function jcheck(bool $ok,string $message):void {global $checks;if(!$ok)throw new RuntimeException($message);$checks++;}
$tables=['users','user_product_subscriptions','journey_plans','journey_plan_versions','stripe_webhook_events'];$created=[];
try {
 foreach($tables as $table){
  $definition=$db->query("SHOW CREATE TABLE `$table`")->fetch_assoc()['Create Table'];
  $lines=explode("\n",$definition);$lines=array_values(array_filter($lines,static fn($line)=>!str_contains($line,'CONSTRAINT ')));
  $definition=preg_replace('/,\n\)/',"\n)",implode("\n",$lines));
  $db->query(preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$definition,1));$created[]=$table;
 }
 $db->query("INSERT INTO users (id,email,password_hash,full_name,subscription_status) VALUES (1,'journey-one@example.invalid','synthetic','Fixture One','free'),(2,'journey-two@example.invalid','synthetic','Fixture Two','free')");
 $GLOBALS['conn']=$db;$_SESSION=['user_id'=>1];jcheck(journey_plan_session_user_id()===1,'Valid session owner');$_SESSION['user_id']=999;jcheck(journey_plan_session_user_id()===0,'Deleted session owner rejected');
 jcheck(journey_webhook_event_claim($db,'evt_fixture','customer.subscription.updated',time(),false)==='claimed','Webhook claimed');
 jcheck(journey_webhook_event_claim($db,'evt_fixture','customer.subscription.updated',time(),false)==='in_progress','Concurrent worker excluded');
 $db->query("UPDATE stripe_webhook_events SET updated_at=DATE_SUB(CURRENT_TIMESTAMP,INTERVAL 11 MINUTE) WHERE stripe_event_id='evt_fixture'");
 jcheck(journey_webhook_event_claim($db,'evt_fixture','customer.subscription.updated',time(),false)==='reclaimed','Crashed worker retry recovered');
 journey_webhook_event_mark($db,'evt_fixture','processed');jcheck(journey_webhook_event_claim($db,'evt_fixture','customer.subscription.updated',time(),false)==='already_processed','Finished webhook deduplicated');
 journey_price_id_overrides_set(['monthly'=>'price_JOURNEY_FIXTURE']);$now=time();
 $sub=['id'=>'sub_JOURNEY_FIXTURE','customer'=>'cus_JOURNEY_FIXTURE','status'=>'active','current_period_end'=>$now+86400,'items'=>['data'=>[['price'=>['id'=>'price_JOURNEY_FIXTURE']]]]];
 jcheck(journey_sync_subscription_row($db,$sub,1,200,$now)['ok'],'Subscription insert');
 jcheck(has_journey_premium_access($db,1,$now),'Active DB access');jcheck(!has_journey_premium_access($db,2,$now),'Entitlement ownership');
 jcheck(!has_journey_premium_access($db,1,$now+86400),'Active period expiry without webhook');
 $payload=['schemaVersion'=>1,'progress'=>['records'=>['synthetic'=>['value'=>0]]],'calculators'=>[]];
 $first=journey_plan_import($db,1,$payload,'2026-09-14T10:00:00Z');jcheck($first['ok'],'First import');
 jcheck(journey_plan_fetch($db,2)===null,'Plan owner isolation');
 jcheck(journey_plan_import($db,1,$payload,'2026-09-14T11:00:00Z')['error']==='already_exists','Import never overwrites');
 $secondPayload=$payload;$secondPayload['progress']['records']['synthetic']['value']=1;
 $second=journey_plan_save($db,1,$secondPayload,'2020-01-01T00:00:00Z','manual',false,$first['plan']['revision']);jcheck($second['ok'],'Version-based write independent of client clock');
 jcheck(journey_plan_save($db,1,$payload,'2099-01-01T00:00:00Z','manual',true,$first['plan']['revision'])['error']==='conflict','Stale future clock and force cannot overwrite');
 jcheck(journey_plan_fetch($db,1)['payload']['progress']['records']['synthetic']['value']===1,'Conflict preserves values');
 jcheck((int)$db->query('SELECT COUNT(*) n FROM journey_plan_versions')->fetch_assoc()['n']===2,'Only successful writes create versions');
 $revision=$second['plan']['revision'];for($i=2;$i<27;$i++){$next=$payload;$next['progress']['records']['synthetic']['value']=$i;$saved=journey_plan_save($db,1,$next,null,'manual',false,$revision);jcheck($saved['ok'],'Retention write');$revision=$saved['plan']['revision'];}
 jcheck((int)$db->query('SELECT COUNT(*) n FROM journey_plan_versions')->fetch_assoc()['n']===20,'Version history bounded to twenty');
 $sub['status']='trialing';$sub['trial_end']=$now+30;journey_sync_subscription_row($db,$sub,1,210,$now);
 jcheck(has_journey_premium_access($db,1,$now),'Trial begins');jcheck(!has_journey_premium_access($db,1,$now+30),'Trial ends despite longer period');
 $sub['status']='past_due';journey_sync_subscription_row($db,$sub,1,220,$now);
 jcheck(!has_journey_premium_access($db,1,$now),'Payment failure revokes');jcheck(journey_manageable_subscription($db,1)!==null,'Payment recovery remains available');
 $sub['status']='active';journey_sync_subscription_row($db,$sub,1,230,$now);jcheck(has_journey_premium_access($db,1,$now),'Payment recovery restores');
 $sub['cancel_at_period_end']=true;journey_sync_subscription_row($db,$sub,1,240,$now);jcheck(has_journey_premium_access($db,1,$now),'Scheduled cancellation grace');jcheck(!has_journey_premium_access($db,1,$now+86400),'Cancellation expiry');
 $sub['status']='canceled';$sub['current_period_end']=$now-1;journey_sync_subscription_row($db,$sub,1,300,$now);jcheck(!has_journey_premium_access($db,1,$now),'Ended subscription');
 $sub['status']='active';$sub['current_period_end']=$now+86400;journey_sync_subscription_row($db,$sub,2,100,$now);
 jcheck(!has_journey_premium_access($db,1,$now),'Stale event cannot restore access');jcheck(!has_journey_premium_access($db,2,$now),'Hint cannot transfer ownership');
 jcheck(journey_plan_can_read($db,1),'Former Premium retains read-only plan');jcheck(!journey_plan_can_write($db,1),'Former Premium cannot write');
 $portal=journey_build_portal_session_params('cus_JOURNEY_FIXTURE','sub_JOURNEY_FIXTURE');jcheck(!isset($portal['flow_data']),'General billing recovery portal');
 echo "Journey SQL temporary-fixture verification passed ($checks checks).\n";
} finally {foreach(array_reverse($created) as $table)$db->query("DROP TEMPORARY TABLE `$table`");$db->close();}
