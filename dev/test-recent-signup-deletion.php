<?php
/** Isolated DB regression tests. Requires RB_SIGNUP_TEST_SOCKET pointing to a local test server. */
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/journey_admin_trials.php';
$socket = getenv('RB_SIGNUP_TEST_SOCKET');
if (!$socket) {
    echo "SKIP recent signup deletion: set RB_SIGNUP_TEST_SOCKET for an isolated local MySQL server.\n";
    exit(0);
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli('localhost', getenv('RB_SIGNUP_TEST_USER') ?: 'root', '', 'mysql', 0, $socket);
// Temporary tables shadow persistent names; fixtures never touch application data.
$conn->query("CREATE TEMPORARY TABLE users (id INT PRIMARY KEY, full_name VARCHAR(100), email VARCHAR(255), subscription_status VARCHAR(32) DEFAULT 'free', created_at DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
$conn->query("CREATE TEMPORARY TABLE user_product_subscriptions (id BIGINT PRIMARY KEY, user_id INT, product_key VARCHAR(64), stripe_subscription_id VARCHAR(255) UNIQUE, stripe_customer_id VARCHAR(255), stripe_status VARCHAR(64), entitlement_status VARCHAR(64), trial_start DATETIME, trial_end DATETIME, cancel_at_period_end INT DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
$conn->query("CREATE TEMPORARY TABLE calcforadvisors_subscribers (id INT PRIMARY KEY, firm_name VARCHAR(100), email VARCHAR(255), plan VARCHAR(32), status VARCHAR(32), created_at DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
$conn->query("CREATE TEMPORARY TABLE journey_admin_trial_notifications (id INT PRIMARY KEY, stripe_subscription_id VARCHAR(255) UNIQUE, viewed_at DATETIME, delivery_status VARCHAR(32)) ENGINE=InnoDB");
$conn->query("CREATE TEMPORARY TABLE admin_signup_reviews (source VARCHAR(32), record_id BIGINT, viewed_at DATETIME, PRIMARY KEY(source, record_id)) ENGINE=InnoDB");
function checkSignup(bool $ok, string $message): void {
    if (!$ok) { throw new RuntimeException($message); }
    echo "PASS $message\n";
}
function snapshotSignup(mysqli $conn): array {
    $snapshot = [];
    foreach (['users','user_product_subscriptions','calcforadvisors_subscribers','journey_admin_trial_notifications','admin_signup_reviews'] as $table) {
        $snapshot[$table] = $conn->query("SELECT * FROM $table ORDER BY 1, 2")->fetch_all(MYSQLI_ASSOC);
    }
    return $snapshot;
}
function signupFixture(mysqli $conn): void {
    foreach (['users','user_product_subscriptions','calcforadvisors_subscribers','journey_admin_trial_notifications','admin_signup_reviews'] as $table) { $conn->query("DELETE FROM $table"); }
    $conn->query("INSERT INTO users (id, full_name, email) VALUES (1,'Shared user','shared@example.test'), (2,'Calculator','calc@example.test'), (3,'Keep','keep@example.test')");
    $conn->query("INSERT INTO user_product_subscriptions (id,user_id,product_key,stripe_subscription_id,stripe_status,entitlement_status,trial_start,trial_end) VALUES
        (103,23,'journey','sub_orphan','canceled','canceled','2026-07-30 14:10:10','2026-08-29 14:10:10'),
        (104,1,'journey','sub_shared_a','trialing','trialing','2026-08-01','2026-09-01'),
        (105,1,'journey','sub_shared_b','active','active','2026-08-02','2026-09-02'),
        (106,1,'other','sub_other','active','active','2026-08-03','2026-09-03'),
        (107,1,'journey','sub_no_trial','active','active',NULL,NULL)");
    $conn->query("INSERT INTO calcforadvisors_subscribers (id,firm_name,email,plan,status) VALUES (2,'Advisor','advisor@example.test','monthly','active'),(3,'Keep firm','keep-firm@example.test','annual','active')");
    $conn->query("INSERT INTO journey_admin_trial_notifications (id,stripe_subscription_id) VALUES (1,'sub_orphan'),(2,'sub_shared_a'),(3,'sub_shared_b')");
    $conn->query("INSERT INTO admin_signup_reviews (source,record_id) VALUES ('ronbelisle',1),('ronbelisle',2),('ronbelisle',3),('calcforadvisors',2),('calcforadvisors',3)");
}
$record = static fn(string $s, int $id): array => ['source'=>$s,'record_id'=>$id];
foreach ([false,true] as $batch) {
    foreach ([['journey',103],['journey',104],['ronbelisle',2],['calcforadvisors',2]] as [$source,$id]) {
        signupFixture($conn);
        $before = snapshotSignup($conn);
        $trials = journey_admin_list_recent_signups($conn);
        $orphan = array_values(array_filter($trials, static fn(array $r): bool => $r['source_key']==='journey' && $r['record_id']===103))[0];
        checkSignup($orphan['full_name']==='' && $orphan['email']==='' && $orphan['signup_at_label']==='Jul 30, 2026 9:10 AM CDT', 'orphan fixture reproduces reported row');
        $expected = $before;
        $table = ['journey'=>'user_product_subscriptions','ronbelisle'=>'users','calcforadvisors'=>'calcforadvisors_subscribers'][$source];
        $expected[$table] = array_values(array_filter($expected[$table], static fn(array $r): bool => (int)$r['id']!==$id));
        if ($source==='journey') {
            $sub = $id===103 ? 'sub_orphan' : 'sub_shared_a';
            $expected['journey_admin_trial_notifications'] = array_values(array_filter($expected['journey_admin_trial_notifications'], static fn(array $r): bool => $r['stripe_subscription_id']!==$sub));
        } else {
            $expected['admin_signup_reviews'] = array_values(array_filter($expected['admin_signup_reviews'], static fn(array $r): bool => !($r['source']===$source && (int)$r['record_id']===$id)));
        }
        $ok = $batch ? journey_admin_delete_signups($conn,[$record($source,$id)])===1 : journey_admin_delete_signup($conn,$source,$id);
        checkSignup($ok && snapshotSignup($conn)===$expected, ($batch?'batch':'individual')." $source:$id deletes only selected row and its review metadata");
    }
}
signupFixture($conn);
checkSignup(journey_admin_delete_signups($conn,[$record('journey',103),$record('ronbelisle',2),$record('calcforadvisors',2)])===3, 'mixed-source batch succeeds with orphan and colliding calculator IDs');
checkSignup((int)$conn->query('SELECT COUNT(*) FROM users WHERE id IN (1,3)')->fetch_row()[0]===2, 'mixed-source batch preserves unselected users');
foreach ([[$record('journey',104),$record('journey',999)],[$record('journey',104),$record('journey',104)],[$record('journey',104),$record('unknown',2)],[$record('journey',106)],[$record('journey',107)],[$record('ronbelisle',1)],[$record('journey',0)]] as $invalid) {
    signupFixture($conn);
    $before = snapshotSignup($conn);
    checkSignup(journey_admin_delete_signups($conn,$invalid)===0 && snapshotSignup($conn)===$before, 'invalid, missing, duplicate or undisplayable selection preserves all rows');
}
signupFixture($conn);
$conn->query('DROP TEMPORARY TABLE journey_admin_trial_notifications');
$before = $conn->query('SELECT * FROM user_product_subscriptions ORDER BY id')->fetch_all(MYSQLI_ASSOC);
checkSignup(!journey_admin_delete_signup($conn,'journey',103) && $conn->query('SELECT * FROM user_product_subscriptions ORDER BY id')->fetch_all(MYSQLI_ASSOC)===$before, 'review cleanup failure preserves signup');
echo "Recent signup deletion regression tests passed.\n";
