<?php
declare(strict_types=1);
require_once __DIR__.'/consumer_subscription.php';

/** Retriable, serialized Checkout. Only server-owned identities reach Stripe. */
function rb_consumer_start_checkout(RbConsumerSubscriptionStore $store,int $userId,string $plan,array $prices,array $api,?int $clock=null):string {
    $now=$clock??time();
    $price=array_search($plan,$prices,true);
    if(!is_string($price)||!in_array($plan,['monthly','annual'],true))throw new RuntimeException('Invalid plan');
    $store->lock();
    try {
        $user=$store->query('SELECT * FROM users WHERE id=?',[$userId])[0]??null;
        if(!$user)throw new RuntimeException('Account missing');
        $row=$store->subscriber($userId);
        if(rb_consumer_evaluate($user,$row)['has_premium'])return 'https://ronbelisle.com/account.php';
        if($row===null) {
            if(!empty($user['stripe_subscription_id']))throw new RuntimeException('Legacy subscription requires reconciliation');
            $store->query('INSERT INTO consumer_subscriptions (id) VALUES (?)',[$userId]);
            $row=$store->subscriber($userId);
        }
        if(!empty($row['stripe_subscription_id'])) {
            $sub=$api['subscription']($row['stripe_subscription_id']);
            if(($sub['id']??'')!==$row['stripe_subscription_id'] || cfa_stripe_id($sub['customer']??null,'cus')!==$row['stripe_customer_id']
                || !in_array($sub['status']??'',['canceled','incomplete_expired'],true))throw new RuntimeException('Use Manage Subscription for your current subscription');
        }
        $customer=$row['stripe_customer_id']??'';
        if(!$customer) {
            if(strtotime($row['created_at'].' UTC')<$now-82800)throw new RuntimeException('Uncertain customer creation requires reconciliation');
            $customer=cfa_stripe_id($api['customer'](['email'=>$user['email'],'metadata'=>['consumer_user_id'=>(string)$userId]],'consumer-customer-'.$userId)['id']??null,'cus');
            $store->query('UPDATE consumer_subscriptions SET stripe_customer_id=? WHERE id=?',[$customer,$userId]);
        }
        $owner=$store->byCustomer($customer);
        if(!$owner || (int)$owner['id']!==$userId)throw new RuntimeException('Customer mismatch');
        $previous=$store->query('SELECT * FROM consumer_checkout_sessions WHERE subscriber_id=? ORDER BY created_at DESC,request_id DESC LIMIT 1',[$userId]);
        $pending=$previous[0]??null;
        if($pending && !empty($pending['stripe_session_id'])) {
            $session=$api['session']($pending['stripe_session_id']);
            if(cfa_stripe_id($session['customer']??null,'cus')!==$customer)throw new RuntimeException('Checkout identity mismatch');
            if(($session['status']??'')==='open')return rb_consumer_checkout_url($session['url']??'');
            if(($session['status']??'')==='complete') {
                $completed=$api['subscription'](cfa_stripe_id($session['subscription']??null,'sub'));
                if(!in_array($completed['status']??'',['canceled','incomplete_expired'],true))return 'https://ronbelisle.com/success.php?session_id='.rawurlencode($pending['stripe_session_id']);
            } elseif(($session['status']??'')!=='expired')throw new RuntimeException('Unknown checkout state');
            $pending=null;
        }
        if($pending===null) {
            // One introductory trial per account. An abandoned checkout never restarts it.
            $trial=(!$previous && empty($row['trial_used_at']) && empty($row['stripe_subscription_id']))?$now+7*86400:null;
            $pending=['request_id'=>bin2hex(random_bytes(24)),'plan'=>$plan,'trial_end'=>$trial];
            $store->query('INSERT INTO consumer_checkout_sessions (request_id,subscriber_id,stripe_customer_id,plan,trial_end) VALUES (?,?,?,?,?)',[$pending['request_id'],$userId,$customer,$plan,$trial]);
        } elseif(strtotime($pending['created_at'].' UTC')<$now-82800 || $pending['plan']!==$plan)throw new RuntimeException('Pending checkout requires reconciliation');
        $data=['metadata'=>['consumer_user_id'=>(string)$userId]];
        if($pending['trial_end']!==null)$data['trial_end']=(int)$pending['trial_end'];
        $session=$api['create'](['mode'=>'subscription','customer'=>$customer,'client_reference_id'=>(string)$userId,
            'payment_method_types'=>['card'],'line_items'=>[['price'=>$price,'quantity'=>1]],'subscription_data'=>$data,
            'metadata'=>['product'=>'consumer','plan'=>$plan],
            'success_url'=>'https://ronbelisle.com/success.php?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'=>'https://ronbelisle.com/subscribe.php?canceled=true'], 'consumer-checkout-'.$pending['request_id']);
        $id=cfa_stripe_id($session['id']??null,'cs');
        if(cfa_stripe_id($session['customer']??null,'cus')!==$customer)throw new RuntimeException('Checkout response mismatch');
        $url=rb_consumer_checkout_url($session['url']??'');
        $store->query('UPDATE consumer_checkout_sessions SET stripe_session_id=? WHERE request_id=?',[$id,$pending['request_id']]);
        $store->query('UPDATE consumer_subscriptions SET trial_used_at=COALESCE(trial_used_at,UTC_TIMESTAMP()) WHERE id=?',[$userId]);
        return $url;
    } finally {$store->unlock();}
}
function rb_consumer_checkout_url(string $url):string {
    if(parse_url($url,PHP_URL_SCHEME)!=='https'||parse_url($url,PHP_URL_HOST)!=='checkout.stripe.com'||parse_url($url,PHP_URL_USER)!==null)throw new RuntimeException('Invalid checkout destination');
    return $url;
}
function rb_consumer_stripe_api():array {
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    $http=new \Stripe\HttpClient\CurlClient();$http->setTimeout(20);$http->setConnectTimeout(5);\Stripe\ApiRequestor::setHttpClient($http);
    return [
        'subscription'=>static fn(string $id):array=>\Stripe\Subscription::retrieve($id)->toArray(),
        'session'=>static fn(string $id):array=>\Stripe\Checkout\Session::retrieve($id)->toArray(),
        'customer'=>static fn(array $data,string $key):array=>\Stripe\Customer::create($data,['idempotency_key'=>$key])->toArray(),
        'create'=>static fn(array $data,string $key):array=>\Stripe\Checkout\Session::create($data,['idempotency_key'=>$key])->toArray(),
    ];
}

/** A return can reconcile only a locally bound purchase belonging to this account. */
function rb_consumer_confirm_checkout(RbConsumerSubscriptionStore $store,int $userId,string $sessionId,array $api,array $prices,?int $clock=null):string {
    cfa_stripe_id($sessionId,'cs');
    $binding=$store->checkout($sessionId);
    if(!$binding || (int)$binding['subscriber_id']!==$userId)throw new RuntimeException('Unowned checkout');
    $session=$api['session']($sessionId);
    if(($session['id']??'')!==$sessionId || ($session['status']??'')!=='complete' || ($session['mode']??'')!=='subscription'
        || (string)($session['client_reference_id']??'')!==(string)$userId || cfa_stripe_id($session['customer']??null,'cus')!==$binding['stripe_customer_id'])throw new RuntimeException('Checkout verification failed');
    $now=$clock??time();
    // Stable receipt prevents return-page replays from overriding later webhook state.
    $event=['id'=>'evtConsumerReturn'.preg_replace('/[^A-Za-z0-9]/','',$sessionId),'type'=>'checkout.session.completed','created'=>$now,'data'=>['object'=>$session]];
    $event['id']='evt_'.substr($event['id'],3);
    return cfa_process_subscription_event($store,$event,$api['subscription'],$prices,$now);
}
