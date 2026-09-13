<?php
/** Called only by the private server verifier. No persistent data is written. */
function phase7_database_fixture(mysqli $db,string $release):void
{
    require_once $release.'/includes/api_resources.php';
    require_once $release.'/includes/has_premium_access.php';
    require_once $release.'/includes/calcforadvisors_portal.php';
    require_once $release.'/includes/journey_admin_trials.php';
    if (!journey_admin_ensure_signup_reviews_table($db)) throw new RuntimeException('Admin migration ledger missing');
    $tables=['calcforadvisors_subscribers','users','ai_explain_usage'];$created=[];$checks=0;
    $check=static function(bool $ok,string $message)use(&$checks){if(!$ok)throw new RuntimeException($message);$checks++;};
    $savedSession=$_SESSION??[];$savedConn=$GLOBALS['conn']??null;
    try {
        foreach($tables as $table){
            $ddl=$db->query("SHOW CREATE TABLE `$table`")->fetch_assoc()['Create Table'];
            $db->query(preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$ddl,1));$created[]=$table;
        }
        $GLOBALS['conn']=$db;$_SESSION=['calcforadvisors_subscriber_id'=>1,'calcforadvisors_plan'=>'monthly'];
        $db->query("INSERT INTO calcforadvisors_subscribers (id,email,plan,status,created_at,stripe_subscription_status,access_ends_at,cancel_at_period_end) VALUES (1,'phase7@example.invalid','monthly','active',UTC_TIMESTAMP(),'active',DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 DAY),0)");
        $check(has_premium_access()&&get_scenario_owner()===['type'=>'cfa','id'=>1],'Active bridge');
        foreach(['unpaid','paused','incomplete','incomplete_expired'] as $state){
            $stmt=$db->prepare('UPDATE calcforadvisors_subscribers SET stripe_subscription_status=? WHERE id=1');$stmt->bind_param('s',$state);$stmt->execute();$stmt->close();
            $check(!has_premium_access()&&get_scenario_owner()===null,'Revocation despite cached monthly plan: '.$state);
        }
        $db->query("UPDATE calcforadvisors_subscribers SET stripe_subscription_status='canceled',access_ends_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 SECOND) WHERE id=1");
        $check(!has_premium_access()&&get_scenario_owner()===null,'Immediate cancellation');
        $db->query("UPDATE calcforadvisors_subscribers SET stripe_subscription_status='active',cancel_at_period_end=1,access_ends_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 SECOND) WHERE id=1");
        $check(!has_premium_access(),'Expired scheduled cancellation');
        $db->query("UPDATE calcforadvisors_subscribers SET portal_slug='phase7-synthetic',last_stripe_event_created=UNIX_TIMESTAMP() WHERE id=1");
        $portal=cfa_load_public_portal($db,'phase7-synthetic');
        $check($portal!==null&&!cfa_evaluate_advisor_entitlement($portal)['has_premium'],'Public portal respects expired cancellation');
        $db->query('UPDATE calcforadvisors_subscribers SET cancel_at_period_end=0 WHERE id=1');
        $portal=cfa_load_public_portal($db,'phase7-synthetic');
        $check($portal!==null&&!cfa_evaluate_advisor_entitlement($portal)['has_premium'],'Public portal respects verified period expiration');
        $db->query("UPDATE calcforadvisors_subscribers SET cancel_at_period_end=0,access_ends_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 DAY) WHERE id=1");
        $_SESSION['calcforadvisors_plan']='free';$check(has_premium_access(),'Recovered entitlement ignores stale free session');
        $_SESSION=['user_id'=>999];$check(!has_premium_access()&&get_scenario_owner()===null,'Unrelated consumer cannot borrow advisor access');
        for($i=0;$i<3;$i++)$check(rb_ai_reserve_monthly($db,1,3),'Atomic reservation within cap');
        $check(!rb_ai_reserve_monthly($db,1,3),'Cap enforced');
        $row=$db->query('SELECT COUNT(*) AS n,MAX(used) AS used FROM ai_explain_usage')->fetch_assoc();
        $check((int)$row['n']===1&&(int)$row['used']===3,'Unique ledger and bounded count');
        $check(rb_ai_reserve_monthly($db,2,3),'Independent advisor ledger');
        echo "Phase 7 SQL-backed temporary-fixture tests passed ($checks checks).\n";
    } finally {
        $_SESSION=$savedSession;$GLOBALS['conn']=$savedConn;
        foreach(array_reverse($created) as $table)$db->query("DROP TEMPORARY TABLE `$table`");
    }
}
