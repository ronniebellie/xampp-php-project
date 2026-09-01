<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$files=[
 'runner'=>$root.'/scripts/calcforadvisors-phase2-migration.php',
 'options'=>$root.'/scripts/calcforadvisors-db-option-file.php',
 'a'=>$root.'/sql/migrations/20260901_calcforadvisors_phase2_a_scenarios.sql',
 'b'=>$root.'/sql/migrations/20260831_calcforadvisors_phase2_foundation.sql',
 'c'=>$root.'/sql/migrations/20260831_calcforadvisors_phase2_legacy_backfill.sql',
 'd'=>$root.'/sql/migrations/20260901_calcforadvisors_phase2_d_stripe_reconciliation.sql',
 'e'=>$root.'/sql/migrations/20260901_calcforadvisors_phase2_e_portal_slugs.sql',
 'preflight'=>$root.'/sql/preflight_calcforadvisors_phase2_final.sql',
 'verify'=>$root.'/sql/verify_calcforadvisors_phase2_final.sql',
 'runbook'=>$root.'/docs/CALCFORADVISORS_PHASE2_FINAL_MIGRATION_RUNBOOK.md',
];
$failed=[];$checks=0;
$expect=function(bool $ok,string $why)use(&$failed,&$checks){$checks++;if(!$ok)$failed[]=$why;};
$src=[];foreach($files as $k=>$p){$src[$k]=is_file($p)?(string)file_get_contents($p):'';$expect($src[$k]!=='',"missing {$k}");}
$expect(strpos($src['runner'],"PHP_SAPI !== 'cli'")!==false,'runner not CLI-only');
$expect(strpos($src['runner'],'$argc !== 2')!==false,'runner accepts unexpected arguments');
foreach(['--preflight','--stage=A','--stage=B','--stage=C','--stage=D','--stage=E','--verify'] as $cmd)$expect(strpos($src['runner'],$cmd)!==false,"missing {$cmd}");
$expect(strpos($src['runner'],"!== 'ronbelisle_premium'")!==false,'database guard missing');
$expect(strpos($src['runner'],'subscriber count drifted')!==false,'count guard missing');
$expect(substr_count($src['runner'],'Stripe identity drift')===1,'hashed Stripe guard missing');
$expect(strpos($src['runner'],'partial foundation detected')!==false,'partial-schema stop missing');
$expect(strpos($src['runner'],'affected-row mismatch')!==false,'affected-row guard missing');
$expect(strpos($src['runner'],"id=13 AND plan='monthly' AND status='active'")!==false,'subscriber 13 guard missing');
$expect(strpos($src['runner'],"stripe_subscription_status='unresolved'")!==false,'unresolved fail-closed value missing');
$expect(strpos($src['runner'],"trial_used_at=created_at")!==false,'trial eligibility marker missing');
$expect(substr_count($src['e'],'UPDATE calcforadvisors_subscribers')===16,'Stage E must contain 16 fixed assignments');
$expect(strpos($src['e'],'email')===false,'Stage E must not derive from email');
$expect(strpos($src['e'],"id=10")!==false&&strpos($src['e'],"id=12")!==false,'legacy slug preservation missing');
$expect(strpos($src['c'],'portal_slug')===false,'Stage C must not perform Stage E work');
foreach(['preflight','verify'] as $key){
 $plain=preg_replace('/^[[:space:]]*--.*$/m','',$src[$key]);
 $stmts=array_values(array_filter(array_map('trim',explode(';',(string)$plain))));
 foreach($stmts as $i=>$sql)$expect((bool)preg_match('/^SELECT\b/i',$sql),"{$key} statement ".($i+1).' not SELECT');
 $expect(!preg_match('/\b(?:INSERT|UPDATE|DELETE|ALTER|DROP|CREATE|REPLACE|TRUNCATE|CALL|LOAD|LOCK|UNLOCK)\b/i',(string)$plain),"{$key} contains write keyword");
}
foreach(['a','b','c','d','e'] as $key){
 $plain=preg_replace('/^[[:space:]]*--.*$/m','',$src[$key]);
 $plain=preg_replace('~/\*.*?\*/~s','',(string)$plain);
 $expect(!preg_match('/(?:^|;)\s*(?:DELETE|DROP|TRUNCATE|REPLACE)\b/im',(string)$plain),"destructive statement in Stage {$key}");
}
$expect(!preg_match('/@[A-Za-z0-9._%+-]+\.[A-Za-z]{2,}/',$src['d'].$src['e']),'migration contains email-like PII');
$expect(strpos($src['runbook'],'/var/www/ronbelisle/current')!==false,'immutable release model missing');
$expect(strpos($src['runbook'],'Do not run `git pull` in `/var/www/html`')!==false,'stale deploy warning missing');
$expect(strpos($src['runbook'],'DROP DATABASE ronbelisle_premium')!==false,'authoritative full restore missing');
$expect(strpos($src['options'],'/etc/ronbelisle/config.php')!==false&&strpos($src['options'],'chmod($path,0600)')!==false,'secure option-file helper incomplete');
if($failed){fwrite(STDERR,"Migration package tests failed:\n- ".implode("\n- ",$failed)."\n");exit(1);}echo "CalcForAdvisors final migration package validation passed ({$checks} checks).\n";
