<?php
/** Journey Premium Customer Portal security and parameter checks. */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/includes/journey_checkout.php';
require_once $root . '/includes/journey_billing_portal.php';
require_once $root . '/includes/account_helpers.php';

$passed = [];
$failed = [];
function expectPortal(string $name, bool $condition): void
{
    global $passed, $failed;
    if ($condition) {
        $passed[] = $name;
    } else {
        $failed[] = $name;
    }
}

journey_price_id_overrides_set(['monthly' => 'price_journey_portal_test', 'annual' => '']);
$local = ['stripe_subscription_id' => 'sub_owned', 'stripe_customer_id' => 'cus_owned'];
$stripe = [
    'id' => 'sub_owned',
    'customer' => 'cus_owned',
    'items' => ['data' => [['price' => ['id' => 'price_journey_portal_test']]]],
];
expectPortal('matching owned Journey subscription accepted', journey_portal_subscription_matches($local, $stripe));
$wrongSub = $stripe;
$wrongSub['id'] = 'sub_other';
expectPortal('different subscription rejected', !journey_portal_subscription_matches($local, $wrongSub));
$wrongCustomer = $stripe;
$wrongCustomer['customer'] = 'cus_other';
expectPortal('different customer rejected', !journey_portal_subscription_matches($local, $wrongCustomer));
$wrongPrice = $stripe;
$wrongPrice['items']['data'][0]['price']['id'] = 'price_not_journey';
expectPortal('non-Journey Price rejected', !journey_portal_subscription_matches($local, $wrongPrice));

$params = journey_build_portal_session_params('cus_owned', 'sub_owned');
expectPortal('portal uses server-selected customer', ($params['customer'] ?? '') === 'cus_owned');
expectPortal('portal returns to central account page', ($params['return_url'] ?? '') === 'https://ronbelisle.com/account.php');
expectPortal('portal opens Stripe cancellation flow', ($params['flow_data']['type'] ?? '') === 'subscription_cancel');
expectPortal('portal cancellation targets owned subscription', ($params['flow_data']['subscription_cancel']['subscription'] ?? '') === 'sub_owned');
expectPortal('portal completion returns to account', ($params['flow_data']['after_completion']['redirect']['return_url'] ?? '') === 'https://ronbelisle.com/account.php');

$source = (string) file_get_contents($root . '/journey-billing-portal.php');
expectPortal('endpoint is POST only', strpos($source, "REQUEST_METHOD") !== false && strpos($source, "!== 'POST'") !== false);
expectPortal('endpoint validates CSRF', strpos($source, 'rb_csrf_validate') !== false);
expectPortal('endpoint uses session user id', strpos($source, "\$_SESSION['user_id']") !== false);
expectPortal('endpoint does not accept browser Stripe ids', strpos($source, "\$_POST['stripe_") === false && strpos($source, "\$_GET['stripe_") === false);

$now = 1800000000;
$row = [
    'stripe_subscription_id' => 'sub_owned',
    'stripe_customer_id' => 'cus_owned',
    'stripe_status' => 'active',
    'entitlement_status' => 'active',
    'current_period_end' => $now + 3600,
];
expectPortal('active Journey row is manageable', journey_subscription_row_is_manageable($row, $now));
$row['entitlement_status'] = 'canceled_grace';
expectPortal('future grace remains manageable', journey_subscription_row_is_manageable($row, $now));
expectPortal('future grace retains access', journey_stored_entitlement_allows_access($row, $now));
$row['current_period_end'] = $now;
expectPortal('expired grace is not manageable', !journey_subscription_row_is_manageable($row, $now));
expectPortal('expired grace cannot retain access', !journey_stored_entitlement_allows_access($row, $now));
$row['current_period_end'] = null;
expectPortal('grace without an end is denied', !journey_stored_entitlement_allows_access($row, $now));
$row['stripe_customer_id'] = 'bad_customer';
expectPortal('malformed customer id is not manageable', !journey_subscription_row_is_manageable($row, $now));

$activeCopy = rb_account_journey_copy(true, 'active', true, true);
expectPortal('active subscription renders Active', $activeCopy['label'] === 'Active');
$graceCopy = rb_account_journey_copy(true, 'canceled_grace', true, true);
expectPortal('grace renders Active through period end', $graceCopy['label'] === 'Active through period end');
expectPortal('grace copy explains access through billing period', strpos($graceCopy['detail'], 'until the end of the current billing period') !== false);
expectPortal('grace keeps Open Journey Premium', $graceCopy['actionLabel'] === 'Open Journey Premium');
$deletedCopy = rb_account_journey_copy(false, 'canceled', true, false);
expectPortal('deleted subscription renders inactive', $deletedCopy['label'] === 'Journey Premium inactive');

$accountSource = (string) file_get_contents($root . '/account.php');
expectPortal('top banner has grace-specific heading', strpos($accountSource, 'Journey Premium active through period end') !== false);
expectPortal('top banner checks canceled_grace', strpos($accountSource, "=== 'canceled_grace'") !== false);
expectPortal('Calculator Premium management remains present', strpos($accountSource, 'Manage Calculator Premium subscription') !== false);

journey_price_id_overrides_set(null);

echo "Journey billing portal tests\n";
echo 'Passed: ' . count($passed) . "\n";
echo 'Failed: ' . count($failed) . "\n";
foreach ($failed as $failure) {
    echo '  FAIL: ' . $failure . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
