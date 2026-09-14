<?php
require_once __DIR__.'/includes/session_bootstrap.php';rb_session_start();
require_once __DIR__.'/includes/db_config.php';
require_once __DIR__.'/includes/csrf.php';
require_once __DIR__.'/includes/stripe_config.php';
require_once __DIR__.'/includes/auth_flow_helpers.php';
require_once __DIR__.'/includes/consumer_checkout.php';
require_once __DIR__.'/vendor/autoload.php';
header('Cache-Control: no-store');header('X-Robots-Tag: noindex, nofollow');
$plan=$_POST['plan']??$_GET['plan']??'';
if(!in_array($plan,['monthly','annual'],true)){http_response_code(400);exit('Choose monthly or annual Premium.');}
if(empty($_SESSION['user_id']))rb_auth_redirect_to_login('/checkout.php?plan='.$plan,'trial');
$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
    if(!rb_csrf_validate(is_string($_POST['csrf_token']??null)?$_POST['csrf_token']:null)){http_response_code(403);$error='Your session expired. Reload and try again.';}
    else {
        $id=(int)$_SESSION['user_id'];session_write_close();
        try{$url=rb_consumer_start_checkout(new RbConsumerSubscriptionStore($conn),$id,$plan,rb_consumer_prices(),rb_consumer_stripe_api());header('Location: '.$url, true,303);exit;}
        catch(Throwable $e){error_log('Consumer checkout requires retry or reconciliation');$error='Checkout could not be opened. Use Manage Subscription in your account if you already subscribed; otherwise try again or contact support.';}
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Start Calculator Premium — Ron Belisle</title><link rel="stylesheet" href="/css/shared-styles.css"></head>
<body><main style="max-width:640px;margin:4rem auto;padding:1rem"><a href="/">Ron Belisle Financial Calculators</a><h1>Calculator Premium</h1>
<p><?php echo $plan==='annual'?'$30 per year':'$3 per month'; ?> after a 7-day introductory trial for eligible accounts. One trial per account. Your existing trial or checkout is reused when available. Cancel through your account before the trial ends to avoid a charge.</p>
<p>Premium adds saving, exports and explanations on supported calculators. Journey Premium is a separate guided planning product.</p>
<?php if($error):?><p role="alert"><?php echo htmlspecialchars($error,ENT_QUOTES,'UTF-8');?></p><?php endif;?>
<form method="post"><?php echo rb_csrf_field();?><input type="hidden" name="plan" value="<?php echo $plan;?>"><button type="submit">Continue to secure checkout</button></form><p><a href="/account.php">Manage your account</a></p></main></body></html>
