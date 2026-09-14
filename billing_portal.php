<?php
require_once __DIR__.'/includes/session_bootstrap.php';rb_session_start();
require_once __DIR__.'/includes/db_config.php';
require_once __DIR__.'/includes/csrf.php';
require_once __DIR__.'/includes/stripe_config.php';
require_once __DIR__.'/includes/consumer_checkout.php';
require_once __DIR__.'/vendor/autoload.php';
header('Cache-Control: no-store');header('X-Robots-Tag: noindex, nofollow');
if(empty($_SESSION['user_id'])){header('Location: /auth/login.php');exit;}
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){header('Location: /account.php');exit;}
if(!rb_csrf_validate(is_string($_POST['csrf_token']??null)?$_POST['csrf_token']:null)){http_response_code(403);exit('Your session expired. Return to your account and try again.');}
$id=(int)$_SESSION['user_id'];session_write_close();
try {
    $store=new RbConsumerSubscriptionStore($conn);$row=$store->subscriber($id);
    $customer=$row?cfa_billing_customer($row,$id):null;
    if(!$customer)throw new RuntimeException('No billing customer');
    rb_consumer_stripe_api();
    $portal=\Stripe\BillingPortal\Session::create(['customer'=>$customer,'return_url'=>'https://ronbelisle.com/account.php']);
    $url=(string)$portal->url;
    if(parse_url($url,PHP_URL_SCHEME)!=='https'||parse_url($url,PHP_URL_HOST)!=='billing.stripe.com')throw new RuntimeException('Invalid billing destination');
    header('Location: '.$url,true,303);exit;
} catch(Throwable $e){error_log('Consumer billing portal unavailable');header('Location: /account.php?msg=error',true,303);exit;}
