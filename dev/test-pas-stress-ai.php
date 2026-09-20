<?php
declare(strict_types=1);
$s=file_get_contents(__DIR__.'/../api/explain_results.php');$a=strpos($s,'// Follow-up requests from the shared modal');$b=strpos($s,"\$messages = [['role'",$a);$code=substr($s,$a,$b-$a);
foreach ([['stress','Retirement Stress Test. Hypothetical.'],['','Retirement Stress Test. Follow-up.'],['simple','Simple Projection: fees.']] as [$mode,$summary]) {
    $calculator_type='vanguard-pas-vs-target-date';$data=['analysis_mode'=>$mode];$results_summary=$summary;eval($code);
    if ($mode==='simple') {if(str_contains($system_prompt,'This is Retirement Stress Test mode'))throw new RuntimeException('Simple mode prompt changed');}
    else {if(!str_contains($system_prompt,'never predictions or guarantees')||str_contains($system_prompt,'Total Opportunity Cost is the grand total'))throw new RuntimeException('Stress or follow-up prompt mismatch');}
}
echo "PASS Simple/Stress AI instructions and Stress Test follow-up mode preservation.\n";
