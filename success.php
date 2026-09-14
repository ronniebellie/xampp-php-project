<?php
require_once __DIR__.'/includes/session_bootstrap.php';rb_session_start();
require_once __DIR__.'/includes/db_config.php';
require_once __DIR__.'/includes/csrf.php';
require_once __DIR__.'/includes/stripe_config.php';
require_once __DIR__.'/includes/consumer_checkout.php';
require_once __DIR__.'/vendor/autoload.php';
header('Cache-Control: no-store');header('X-Robots-Tag: noindex, nofollow');header('Referrer-Policy: no-referrer');
if(empty($_SESSION['user_id'])){header('Location: /auth/login.php');exit;}
$id=(int)$_SESSION['user_id'];$error='';$status=['has_premium'=>false];
$sessionId=is_string($_POST['session_id']??$_GET['session_id']??null)?($_POST['session_id']??$_GET['session_id']):'';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
    if(!rb_csrf_validate(is_string($_POST['csrf_token']??null)?$_POST['csrf_token']:null)){http_response_code(403);$error='Your session expired. Reload and try again.';}
    else {session_write_close();try{rb_consumer_confirm_checkout(new RbConsumerSubscriptionStore($conn),$id,$sessionId,rb_consumer_stripe_api(),rb_consumer_prices());}
        catch(Throwable $e){error_log('Consumer checkout confirmation unavailable');$error='We could not confirm an active subscription for this account. Try again or open your account for billing help.';}}
}
try{$status=rb_consumer_status($conn,$id);}catch(Throwable $e){$error='Subscription status is temporarily unavailable. Please try again.';}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Calculator Premium status — Ron Belisle</title><link rel="stylesheet" href="/css/shared-styles.css"></head>
<body><main style="max-width:640px;margin:4rem auto;padding:1rem"><a href="/">Ron Belisle Financial Calculators</a>
<h1><?php echo $status['has_premium']?'Calculator Premium is active':'Confirm Calculator Premium';?></h1>
<?php if($error):?><p role="alert"><?php echo htmlspecialchars($error,ENT_QUOTES,'UTF-8');?></p><?php endif;?>
<?php if($status['has_premium']):?><p>You can use saving, exports and explanations on supported calculators. Journey Premium remains a separate subscription.</p>
<?php else:?><p>Confirm your completed checkout to refresh your account. Access begins only after your subscription is verified.</p><form method="post" action="/success.php"><?php echo rb_csrf_field();?><input type="hidden" name="session_id" value="<?php echo htmlspecialchars($sessionId,ENT_QUOTES,'UTF-8');?>"><button type="submit">Confirm my subscription</button></form><?php endif;?>
<p><a href="/account.php">Manage your account and billing</a> · <a href="/retirement-plan/">Open your quick retirement snapshot</a></p></main></body></html>
