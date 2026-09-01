<?php
/** Create a root-only temporary MySQL client option file from live config. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || $argc !== 1) { fwrite(STDERR,"CLI only; no arguments.\n"); exit(2); }
$path='/run/calcforadvisors-phase2-mysql.cnf';
if (file_exists($path)) { fwrite(STDERR,"STOP: option file already exists.\n"); exit(1); }
$cfg=require '/etc/ronbelisle/config.php';
$db=$cfg['db']??[];
foreach(['host','name','user','pass'] as $key) if(!isset($db[$key])||!is_string($db[$key])) { fwrite(STDERR,"STOP: missing database config.\n"); exit(1); }
if($db['name']!=='ronbelisle_premium'){fwrite(STDERR,"STOP: wrong configured database.\n");exit(1);}
$quote=static fn(string $v):string=>'"'.str_replace(['\\','"'],['\\\\','\\"'],$v).'"';
$body="[client]\nuser=".$quote($db['user'])."\npassword=".$quote($db['pass'])."\nhost=".$quote($db['host'])."\ndefault-character-set=utf8mb4\n";
if(file_put_contents($path,$body,LOCK_EX)===false){fwrite(STDERR,"STOP: could not create option file.\n");exit(1);}
chmod($path,0600); echo $path,"\n";
