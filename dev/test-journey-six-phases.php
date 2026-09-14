<?php
declare(strict_types=1);
$root=dirname(__DIR__).'/journey.ronbelisle.com';
$phases=['spending-goals'=>'Spending & Goals','social-security'=>'Social Security','build-your-plan'=>'Build Your Plan','stress-test'=>'Stress Test','tax-strategy'=>'Tax Strategy','survivor-planning'=>'Survivor Planning'];
$_SERVER['REQUEST_URI']='/';$_SERVER['HTTP_HOST']='journey.ronbelisle.com';
$checks=0;
foreach(array_merge(['index.php'],array_map(static fn($k)=>'phases/'.$k.'.php',array_keys($phases))) as $file){
 ob_start();include $root.'/'.$file;$html=ob_get_clean();
 if(substr_count($html,'<h1 ')!==1 || !str_contains($html,'id="journey-main"'))throw new RuntimeException('Heading/skip destination: '.$file);
 if(!preg_match('/<nav class="journey-progress".*?<\/nav>/s',$html,$m))throw new RuntimeException('Navigation missing: '.$file);
 foreach($phases as $key=>$title){
  if(!str_contains($m[0],'/phases/'.$key.'.php')||!str_contains(html_entity_decode($m[0]),$title))throw new RuntimeException('Missing phase '.$key.' on '.$file);
  $checks++;
 }
 if(substr_count($m[0],'data-journey-phase=')!==6)throw new RuntimeException('Phase count '.$file);
 if($file==='index.php' && !str_contains($html,'For a quick retirement snapshot'))throw new RuntimeException('Product distinction');
}
echo "Journey six-phase PHP-render verification passed ($checks phase links/titles, seven pages, heading and skip destinations). No HTTP/browser used.\n";
