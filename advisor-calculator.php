<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/calcforadvisors_portal.php';
header('X-Robots-Tag: noindex, nofollow', true);
$slug=(string)($_GET['portal_slug']??''); $id=(string)($_GET['calculator_id']??'');
$host=strtolower((string)($_SERVER['HTTP_HOST']??'')); $isLocal=$host==='localhost'||str_starts_with($host,'localhost:')||$host==='127.0.0.1'||str_starts_with($host,'127.0.0.1:');
$loader=static function(string $validSlug):?array{try{mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);require __DIR__.'/includes/db_config.php';return isset($conn)&&$conn instanceof mysqli?cfa_load_public_portal($conn,$validSlug):null;}catch(Throwable $e){error_log('CalcForAdvisors wrapper database unavailable: '.$e->getMessage());return null;}};
if($isLocal&&$slug==='demo-advisor')$loader=static fn(string $validSlug):array=>['id'=>0,'portal_slug'=>$validSlug,'firm_name'=>'Northgate Retirement Planning','advisor_name'=>'Jordan Morgan, CFP®','plan'=>'monthly','status'=>'active','stripe_subscription_status'=>'active'];
$portal=cfa_resolve_public_portal($slug,$loader); $calculator=cfa_advisor_calculator($id); $state=$portal['state']==='available'&&$calculator?'available':($portal['state']==='unavailable'?'unavailable':'invalid');
if($state==='invalid')http_response_code(404); if($state==='unavailable')http_response_code(403); $e=static fn($v):string=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); $profile=$portal['profile'];
$base=$isLocal?((!empty($_SERVER['HTTPS'])?'https':'http').'://'.($_SERVER['HTTP_HOST']??'localhost')):'https://ronbelisle.com';
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?= $state==='available'?$e($calculator['name']):'Calculator Unavailable' ?></title><link rel="stylesheet" href="/css/advisor-portal.css"></head><body class="cfa-page">
<?php if($state!=='available'):?><main class="cfa-shell"><section class="cfa-notice"><p class="cfa-eyebrow">CalcForAdvisors</p><h1><?= $state==='unavailable'?'This advisor portal is currently unavailable.':'Calculator not found' ?></h1><p><a class="cfa-return" href="<?= $e(cfa_portal_path($slug)) ?>">Return to advisor portal</a></p></section></main><?php else:?>
<header class="cfa-wrapper-header"><div class="cfa-shell cfa-wrapper-bar"><div class="cfa-wrapper-brand"><?php if($profile['logo_url']):?><img src="<?= $e($profile['logo_url']) ?>" alt=""><?php endif;?><div><strong><?= $e($profile['firm_name']?:'Your Advisor') ?></strong><small>Powered by RonBelisle.com</small></div></div><a class="cfa-return" href="<?= $e(cfa_portal_path($profile['portal_slug'])) ?>">← Return to advisor portal</a></div></header>
<iframe class="cfa-frame" src="<?= $e(cfa_calculator_embed_url($calculator,$base)) ?>" title="<?= $e($calculator['name']) ?>"></iframe><?php endif;?></body></html>
