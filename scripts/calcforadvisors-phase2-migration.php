<?php
/**
 * Fixed, CLI-only CalcForAdvisors Phase 2 migration runner.
 *
 * Usage on production (from the reviewed /tmp package):
 *   php scripts/calcforadvisors-phase2-migration.php --preflight
 *   php scripts/calcforadvisors-phase2-migration.php --stage=A
 *   ... B, C, D, E ...
 *   php scripts/calcforadvisors-phase2-migration.php --verify
 *
 * No arbitrary SQL, paths, subscriber IDs, or values are accepted.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n"); exit(2);
}
$allowed = ['--preflight', '--stage=A', '--stage=B', '--stage=C', '--stage=D', '--stage=E', '--verify'];
$command = $argv[1] ?? '';
if ($argc !== 2 || !in_array($command, $allowed, true)) {
    fwrite(STDERR, "Use exactly one of: " . implode(', ', $allowed) . "\n"); exit(2);
}

$appRoot = '/var/www/html';
require $appRoot . '/includes/db_config.php';
if (!isset($conn) || !$conn instanceof mysqli) {
    fwrite(STDERR, "Database bootstrap failed.\n"); exit(2);
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function stop_migration(string $message): void { fwrite(STDERR, "STOP: {$message}\n"); exit(1); }
function scalar(mysqli $db, string $sql) { $r=$db->query($sql)->fetch_row(); return $r[0] ?? null; }
function table_exists(mysqli $db, string $table): bool {
    $s=$db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    $s->bind_param('s',$table); $s->execute(); $v=(int)$s->get_result()->fetch_row()[0]; $s->close(); return $v===1;
}
function column_map(mysqli $db): array {
    $out=[]; $r=$db->query("SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='calcforadvisors_subscribers'");
    while($x=$r->fetch_assoc()) $out[$x['COLUMN_NAME']]=$x; return $out;
}
function index_map(mysqli $db): array {
    $out=[]; $r=$db->query("SELECT INDEX_NAME,NON_UNIQUE,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) cols FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='calcforadvisors_subscribers' GROUP BY INDEX_NAME,NON_UNIQUE");
    while($x=$r->fetch_assoc()) $out[$x['INDEX_NAME']]=$x; return $out;
}
function expected_columns(): array { return [
    'portal_slug'=>['varchar(48)','YES'], 'advisor_name'=>['varchar(255)','YES'],
    'public_email'=>['varchar(255)','YES'], 'phone'=>['varchar(64)','YES'],
    'website_url'=>['varchar(512)','YES'], 'disclosure_text'=>['text','YES'],
    'stripe_subscription_status'=>['varchar(32)','YES'], 'trial_ends_at'=>['datetime','YES'],
    'access_ends_at'=>['datetime','YES'], 'trial_used_at'=>['datetime','YES'],
    'past_due_started_at'=>['datetime','YES'],
]; }
function expected_indexes(): array { return [
    'uk_portal_slug'=>[0,'portal_slug'], 'idx_cfa_stripe_status'=>[1,'stripe_subscription_status'],
    'idx_cfa_access_ends'=>[1,'access_ends_at'],
]; }
function foundation_state(mysqli $db): string {
    $cols=column_map($db); $idx=index_map($db); $present=0; $total=count(expected_columns())+count(expected_indexes());
    foreach(expected_columns() as $n=>$def) if(isset($cols[$n])) { $present++; if(strtolower($cols[$n]['COLUMN_TYPE'])!==$def[0] || $cols[$n]['IS_NULLABLE']!==$def[1]) stop_migration("unexpected definition for column {$n}"); }
    foreach(expected_indexes() as $n=>$def) if(isset($idx[$n])) { $present++; if((int)$idx[$n]['NON_UNIQUE']!==$def[0] || $idx[$n]['cols']!==$def[1]) stop_migration("unexpected definition for index {$n}"); }
    return $present===0?'absent':($present===$total?'complete':'partial');
}
function assert_database(mysqli $db): void {
    if ((string)scalar($db,'SELECT DATABASE()') !== 'ronbelisle_premium') stop_migration('wrong database');
    if (!table_exists($db,'calcforadvisors_subscribers')) stop_migration('subscriber table missing');
}
function assert_legacy_facts(mysqli $db): void {
    if ((int)scalar($db,'SELECT COUNT(*) FROM calcforadvisors_subscribers')!==16) stop_migration('subscriber count drifted');
    $agg=[]; $r=$db->query("SELECT plan,status,COUNT(*) c FROM calcforadvisors_subscribers GROUP BY plan,status");
    while($x=$r->fetch_assoc()) $agg[$x['plan'].'/'.$x['status']]=(int)$x['c'];
    ksort($agg);
    if($agg!==['free/active'=>11,'monthly/active'=>1,'monthly/canceled'=>4]) stop_migration('plan/status aggregate drifted');
    if((int)scalar($db,'SELECT COUNT(*) FROM calcforadvisors_subscribers WHERE id BETWEEN 1 AND 16')!==16) stop_migration('required subscriber IDs drifted');
    if((int)scalar($db,"SELECT COUNT(*) FROM calcforadvisors_subscribers WHERE trial_slug IN ('c75c29de9337e761','b8d02011bda427ee')")!==2) stop_migration('legacy slugs drifted');
    if((int)scalar($db,"SELECT COUNT(*) FROM calcforadvisors_subscribers WHERE trial_slug IS NOT NULL AND trial_slug NOT IN ('c75c29de9337e761','b8d02011bda427ee')")!==0) stop_migration('unexpected legacy slug found');
    $hashes=[
      1=>['9cfd953d3b2919f50c55b853b0c0a870e324260cb966a8765003022585ac4a37','558b302001574440c311b52b2b4c0c7be088cbde3391d32bef67d589df7d491b'],
      2=>['ee2d0b0c6bd86748b666286a50353fc6cad020dcc38f260baa4f39b717d3d53d','6d4d1fd43e82b55ad91d53493f0a50300dedec118499ba007cef9744084ba4bb'],
      3=>['80c3ec4cc57911dd4c7f77fb9a25bee3902160cc81f8a5560424e3292da7c897','0ed78caf7b1667a2f4470db9490c34c3be16d03c75397d7f87f487d3f6d8d8b6'],
      4=>['caab9750bec37605406f535e6d795f3420b92acfcc8f8733f88262eeeb79cb3b','02bcc81dabb0aa6859ad88e806ba44f6b942e167925de3d2e882551d3a67f7c8'],
      13=>['08f5d0332552cb5caff77a20b70a25ab2823bdc8f253769e77644f56526dff2d','675e6d9bf3a8d62b9ba2ef0b647368985bd4b864837c6bfad201c1407c90b8f8'],
    ];
    $s=$db->prepare('SELECT SHA2(stripe_customer_id,256) ch,SHA2(stripe_subscription_id,256) sh FROM calcforadvisors_subscribers WHERE id=?');
    foreach($hashes as $id=>$h){$s->bind_param('i',$id);$s->execute();$x=$s->get_result()->fetch_assoc();if(!$x||!hash_equals($h[0],$x['ch'])||!hash_equals($h[1],$x['sh'])) stop_migration("Stripe identity drift for subscriber {$id}");}
    $s->close();
    $meta=$db->query("SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='calcforadvisors_subscribers'")->fetch_assoc();
    if(!$meta||$meta['ENGINE']!=='InnoDB'||$meta['TABLE_COLLATION']!=='utf8mb4_0900_ai_ci') stop_migration('subscriber engine/collation drifted');
    $id=$db->query("SELECT COLUMN_TYPE,IS_NULLABLE,EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='calcforadvisors_subscribers' AND COLUMN_NAME='id'")->fetch_assoc();
    if(!$id||strtolower($id['COLUMN_TYPE'])!=='int'||$id['IS_NULLABLE']!=='NO'||strpos($id['EXTRA'],'auto_increment')===false) stop_migration('subscriber key drifted');
}
function scenario_structure_ok(mysqli $db): bool {
    if(!table_exists($db,'calcforadvisors_scenarios')) return false;
    $cols=(int)scalar($db,"SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='calcforadvisors_scenarios' AND ((COLUMN_NAME='id' AND COLUMN_TYPE='int') OR (COLUMN_NAME='subscriber_id' AND COLUMN_TYPE='int' AND IS_NULLABLE='NO') OR (COLUMN_NAME='calculator_type' AND COLUMN_TYPE='varchar(64)') OR (COLUMN_NAME='scenario_name' AND COLUMN_TYPE='varchar(255)') OR (COLUMN_NAME='scenario_data' AND COLUMN_TYPE='text') OR COLUMN_NAME IN ('created_at','updated_at'))");
    $fk=(int)scalar($db,"SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='calcforadvisors_scenarios' AND REFERENCED_TABLE_NAME='calcforadvisors_subscribers' AND DELETE_RULE='CASCADE'");
    return $cols===7 && $fk===1;
}
function run_statements(mysqli $db, array $sql): void { foreach($sql as $q) $db->query($q); }

assert_database($conn);

if($command==='--preflight'){
    assert_legacy_facts($conn);
    if(table_exists($conn,'calcforadvisors_scenarios')) stop_migration('scenarios table is no longer absent');
    if(foundation_state($conn)!=='absent') stop_migration('Phase 2 foundation is no longer absent');
    echo "PREFLIGHT PASS: approved 16-row legacy state is unchanged.\n"; exit(0);
}

if($command==='--stage=A'){
    assert_legacy_facts($conn); $state=foundation_state($conn); if($state!=='absent') stop_migration('run A before B');
    if(table_exists($conn,'calcforadvisors_scenarios')) { if(!scenario_structure_ok($conn)) stop_migration('existing scenarios table is incompatible'); echo "A ALREADY COMPLETE\n"; exit; }
    $conn->query("CREATE TABLE calcforadvisors_scenarios (id INT AUTO_INCREMENT PRIMARY KEY,subscriber_id INT NOT NULL,calculator_type VARCHAR(64) NOT NULL,scenario_name VARCHAR(255) NOT NULL,scenario_data TEXT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,INDEX idx_sub_calculator (subscriber_id,calculator_type),CONSTRAINT fk_cfa_scenarios_subscriber FOREIGN KEY (subscriber_id) REFERENCES calcforadvisors_subscribers(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
    if(!scenario_structure_ok($conn)) stop_migration('A postcondition failed; restore backup'); echo "A COMPLETE\n"; exit;
}

if($command==='--stage=B'){
    assert_legacy_facts($conn); if(!scenario_structure_ok($conn)) stop_migration('A not verified'); $state=foundation_state($conn);
    if($state==='complete'){echo "B ALREADY COMPLETE\n";exit;} if($state!=='absent') stop_migration('partial foundation detected; restore backup');
    run_statements($conn,[
      "ALTER TABLE calcforadvisors_subscribers ADD COLUMN portal_slug VARCHAR(48) NULL DEFAULT NULL AFTER trial_slug,ADD COLUMN advisor_name VARCHAR(255) NULL DEFAULT NULL AFTER firm_name,ADD COLUMN public_email VARCHAR(255) NULL DEFAULT NULL AFTER advisor_name,ADD COLUMN phone VARCHAR(64) NULL DEFAULT NULL AFTER public_email,ADD COLUMN website_url VARCHAR(512) NULL DEFAULT NULL AFTER phone,ADD COLUMN disclosure_text TEXT NULL AFTER website_url,ADD COLUMN stripe_subscription_status VARCHAR(32) NULL DEFAULT NULL AFTER status,ADD COLUMN trial_ends_at DATETIME NULL DEFAULT NULL AFTER stripe_subscription_status,ADD COLUMN access_ends_at DATETIME NULL DEFAULT NULL AFTER trial_ends_at,ADD COLUMN trial_used_at DATETIME NULL DEFAULT NULL AFTER access_ends_at,ADD COLUMN past_due_started_at DATETIME NULL DEFAULT NULL AFTER trial_used_at",
      "ALTER TABLE calcforadvisors_subscribers ADD UNIQUE KEY uk_portal_slug (portal_slug),ADD INDEX idx_cfa_stripe_status (stripe_subscription_status),ADD INDEX idx_cfa_access_ends (access_ends_at)"
    ]);
    if(foundation_state($conn)!=='complete') stop_migration('B postcondition failed; restore backup'); echo "B COMPLETE\n"; exit;
}

if(foundation_state($conn)!=='complete'||!scenario_structure_ok($conn)) stop_migration('A/B not verified');

if($command==='--stage=C'){
    assert_legacy_facts($conn);
    if((int)scalar($conn,"SELECT COUNT(*) FROM calcforadvisors_subscribers WHERE plan='free'")!==11) stop_migration('free-trial count drifted');
    $pending=(int)scalar($conn,"SELECT COUNT(*) FROM calcforadvisors_subscribers WHERE plan='free' AND trial_ends_at IS NULL AND trial_used_at IS NULL");
    $done=(int)scalar($conn,"SELECT COUNT(*) FROM calcforadvisors_subscribers WHERE plan='free' AND trial_ends_at=DATE_ADD(created_at,INTERVAL 30 DAY) AND trial_used_at=created_at");
    if($done===11){echo "C ALREADY COMPLETE\n";exit;} if($pending!==11) stop_migration('unexpected existing free-trial values');
    $conn->begin_transaction(); $conn->query("UPDATE calcforadvisors_subscribers SET trial_ends_at=DATE_ADD(created_at,INTERVAL 30 DAY),trial_used_at=created_at WHERE plan='free' AND trial_ends_at IS NULL AND trial_used_at IS NULL");
    if($conn->affected_rows!==11){$conn->rollback();stop_migration('C affected-row mismatch');}$conn->commit(); echo "C COMPLETE\n";exit;
}

if($command==='--stage=D'){
    assert_legacy_facts($conn);
    if((int)scalar($conn,"SELECT COUNT(*) FROM calcforadvisors_subscribers WHERE plan='free' AND trial_ends_at=DATE_ADD(created_at,INTERVAL 30 DAY) AND trial_used_at=created_at")!==11) stop_migration('C not verified');
    $done=(int)scalar($conn,"SELECT COUNT(*) FROM calcforadvisors_subscribers WHERE (id IN (1,2,3,4) AND status='canceled' AND stripe_subscription_status='canceled' AND trial_used_at=created_at AND trial_ends_at IS NULL AND access_ends_at IS NOT NULL AND access_ends_at<UTC_TIMESTAMP()) OR (id=13 AND status='inactive' AND stripe_subscription_status='unresolved' AND trial_used_at=created_at AND trial_ends_at IS NULL AND access_ends_at IS NULL AND past_due_started_at IS NULL)");
    if($done===5){echo "D ALREADY COMPLETE\n";exit;}
    if((int)scalar($conn,"SELECT COUNT(*) FROM calcforadvisors_subscribers WHERE id IN(1,2,3,4,13) AND stripe_subscription_status IS NULL AND trial_ends_at IS NULL AND access_ends_at IS NULL AND trial_used_at IS NULL AND past_due_started_at IS NULL")!==5) stop_migration('unexpected existing reconciliation values');
    $values=[1=>'2026-05-06 21:19:56',2=>'2026-05-06 20:47:42',3=>'2026-05-06 20:55:30',4=>'2026-05-07 12:35:23'];
    $conn->begin_transaction(); $s=$conn->prepare("UPDATE calcforadvisors_subscribers SET stripe_subscription_status='canceled',access_ends_at=?,trial_used_at=created_at WHERE id=? AND status='canceled' AND stripe_subscription_status IS NULL AND access_ends_at IS NULL AND trial_used_at IS NULL");
    foreach($values as $id=>$end){$s->bind_param('si',$end,$id);$s->execute();if($s->affected_rows!==1){$s->close();$conn->rollback();stop_migration("D mismatch for subscriber {$id}");}}$s->close();
    $conn->query("UPDATE calcforadvisors_subscribers SET status='inactive',stripe_subscription_status='unresolved',trial_used_at=created_at WHERE id=13 AND plan='monthly' AND status='active' AND stripe_subscription_status IS NULL AND trial_ends_at IS NULL AND access_ends_at IS NULL AND trial_used_at IS NULL AND past_due_started_at IS NULL");
    if($conn->affected_rows!==1){$conn->rollback();stop_migration('D mismatch for subscriber 13');}$conn->commit(); echo "D COMPLETE\n";exit;
}

if($command==='--stage=E'){
    if((int)scalar($conn,"SELECT COUNT(*) FROM calcforadvisors_subscribers WHERE (id IN(1,2,3,4) AND stripe_subscription_status='canceled' AND access_ends_at<UTC_TIMESTAMP()) OR (id=13 AND status='inactive' AND stripe_subscription_status='unresolved' AND access_ends_at IS NULL)")!==5) stop_migration('D not verified');
    $valid="portal_slug REGEXP '^[a-z0-9][a-z0-9-]{1,46}[a-z0-9]$' AND portal_slug NOT IN ('account','admin','api','assets','billing','calculator','checkout','login','logout','p','portal','pricing','register','robots','sitemap','stripe','success','support','trial','www')";
    if((int)scalar($conn,"SELECT COUNT(*) FROM calcforadvisors_subscribers WHERE portal_slug IS NOT NULL")===16 && (int)scalar($conn,"SELECT COUNT(DISTINCT portal_slug) FROM calcforadvisors_subscribers")===16 && (int)scalar($conn,"SELECT COUNT(*) FROM calcforadvisors_subscribers WHERE {$valid}")===16){echo "E ALREADY COMPLETE\n";exit;}
    if((int)scalar($conn,'SELECT COUNT(*) FROM calcforadvisors_subscribers WHERE portal_slug IS NOT NULL')!==0) stop_migration('unexpected portal_slug state');
    $slugs=[1=>'advisor-1',2=>'advisor-2',3=>'advisor-3',4=>'advisor-4',5=>'advisor-5',6=>'advisor-6',7=>'advisor-7',8=>'advisor-8',9=>'advisor-9',10=>'c75c29de9337e761',11=>'advisor-11',12=>'b8d02011bda427ee',13=>'advisor-13',14=>'advisor-14',15=>'advisor-15',16=>'advisor-16'];
    $conn->begin_transaction();$s=$conn->prepare('UPDATE calcforadvisors_subscribers SET portal_slug=? WHERE id=? AND portal_slug IS NULL');
    foreach($slugs as $id=>$slug){$s->bind_param('si',$slug,$id);$s->execute();if($s->affected_rows!==1){$s->close();$conn->rollback();stop_migration("E mismatch for subscriber {$id}");}}$s->close();$conn->commit();echo "E COMPLETE\n";exit;
}

if($command==='--verify'){
    if(!scenario_structure_ok($conn)||foundation_state($conn)!=='complete') stop_migration('schema verification failed');
    if((int)scalar($conn,'SELECT COUNT(*) FROM calcforadvisors_subscribers')!==16) stop_migration('subscriber count changed');
    if((int)scalar($conn,"SELECT COUNT(*) FROM calcforadvisors_subscribers WHERE plan='free' AND trial_used_at=created_at AND trial_ends_at=DATE_ADD(created_at,INTERVAL 30 DAY) AND trial_ends_at<UTC_TIMESTAMP()")!==11) stop_migration('legacy-trial verification failed');
    if((int)scalar($conn,"SELECT COUNT(*) FROM calcforadvisors_subscribers WHERE id IN(1,2,3,4) AND stripe_subscription_status='canceled' AND access_ends_at<UTC_TIMESTAMP()")!==4) stop_migration('canceled-history verification failed');
    if((int)scalar($conn,"SELECT COUNT(*) FROM calcforadvisors_subscribers WHERE id=13 AND status='inactive' AND stripe_subscription_status='unresolved' AND access_ends_at IS NULL")!==1) stop_migration('unresolved-record verification failed');
    if((int)scalar($conn,'SELECT COUNT(DISTINCT portal_slug) FROM calcforadvisors_subscribers')!==16) stop_migration('slug verification failed');
    if((int)scalar($conn,'SELECT COUNT(*) FROM calcforadvisors_scenarios')!==0) stop_migration('new scenario table unexpectedly contains rows');
    echo "POST-MIGRATION VERIFY PASS\n";exit;
}
