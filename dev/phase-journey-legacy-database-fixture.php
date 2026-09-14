<?php
/** Private CLI runner for older mutating Journey tests with current dated fixtures. */
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
$root=realpath($argv[1]??dirname(__DIR__));if(!$root)exit(1);
$cfg=require '/etc/ronbelisle/config.php';$d=$cfg['db'];mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli($d['host'],$d['user'],$d['pass'],$d['name']);$ddl=[];
foreach(['users','user_product_subscriptions','journey_plans','journey_plan_versions'] as $table)$ddl[]=$db->query('SHOW CREATE TABLE `'.$table.'`')->fetch_assoc()['Create Table'];
$schema='rb_journey_fixture_'.bin2hex(random_bytes(8));$db->query('CREATE DATABASE `'.$schema.'`');$configFile=tempnam(sys_get_temp_dir(),'rb-test-config-');chmod($configFile,0600);
try{
 $db->select_db($schema);foreach($ddl as $sql)$db->query($sql);
 $db->query("INSERT INTO users (id,email,password_hash,full_name,subscription_status) VALUES (999999001,'free-tester@example.invalid','synthetic','Free Tester','free'),(999999002,'account-fixture@example.invalid','synthetic','Account Fixture','free')");
 $cfg['db']['name']=$schema;file_put_contents($configFile,'<?php return '.var_export($cfg,true).';');unset($cfg,$d);
 $env=getenv();$env['RB_CONFIG_FILE']=$configFile;$failed=false;
 foreach(['test-account-helpers.php','test-milestone5-p1.php','test-milestone5-p2.php'] as $test){
  $p=proc_open([PHP_BINARY,'-d','session.save_path='.sys_get_temp_dir(),$root.'/dev/journey-premium/'.$test],[0=>['file','/dev/null','r'],1=>STDOUT,2=>STDERR],$pipes,$root,$env);
  if(!is_resource($p)||proc_close($p)!==0)$failed=true;
 }
 if($failed)throw new RuntimeException('Legacy isolated fixture failure');
 echo "All three isolated Journey database suites passed.\n";
}finally{unlink($configFile);$db->query('DROP DATABASE `'.$schema.'`');$db->close();}
