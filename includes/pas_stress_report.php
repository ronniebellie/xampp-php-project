<?php
/** Presentation only: reports consume the engine's summarized results, never rerun finance math. */
declare(strict_types=1);
require_once __DIR__ . '/report_csv.php';
function rb_pas_stress_validate(array $d): void {
    if (($d['analysisMode'] ?? '') !== 'stress' || ($d['modelVersion'] ?? '') !== 'pas-stress-v1') throw new InvalidArgumentException('Unsupported model');
    $p=$d['inputs']??[];
    foreach (['portfolioValue','years','timelineStartYear','withdrawalsStartYear','pasFee','targetDateFee','annualWithdrawal','inflation','simulations','seed'] as $k) {
        if (!isset($p[$k]) || !is_numeric($p[$k]) || !is_finite((float)$p[$k])) throw new InvalidArgumentException('Missing input');
    }
    if ($p['years']<1 || $p['years']>50 || !isset($d['annual']) || count($d['annual'])!==(int)$p['years']+1) throw new InvalidArgumentException('Invalid timeline');
    foreach (['allocation'=>3,'means'=>4,'volatilities'=>4] as $k=>$count) if (!isset($p[$k]) || !is_array($p[$k]) || count($p[$k])!==$count) throw new InvalidArgumentException('Invalid assumptions');
    foreach (['pas','target'] as $key) {
        foreach (['survival','fullyFunded','medianIncome','medianFees','failures'] as $k) if (!isset($d[$key][$k]) || !is_numeric($d[$key][$k])) throw new InvalidArgumentException('Missing summary');
        foreach (['p10','p25','p50','p75','p90'] as $k) if (!isset($d[$key]['ending'][$k]) || !is_numeric($d[$key]['ending'][$k])) throw new InvalidArgumentException('Missing percentile');
    }
    if (count($d['buckets']['depletion']??[])!==3 || count($d['correlation']??[])!==4) throw new InvalidArgumentException('Missing bucket metadata');
    $number=static function($v): void { if (!is_int($v) && !is_float($v)) throw new InvalidArgumentException('Numeric report field required'); if(!is_finite((float)$v)) throw new InvalidArgumentException('Nonfinite field'); };
    foreach (['allocation','means','volatilities'] as $k) foreach($p[$k] as $v)$number($v);
    foreach($d['correlation'] as $row) {if(!is_array($row)||count($row)!==4)throw new InvalidArgumentException('Invalid correlation');foreach($row as $v)$number($v);}
    foreach(['pas','target'] as $key) {
        foreach(['survival','fullyFunded','medianIncome','medianFees','failures'] as $k)$number($d[$key][$k]);
        foreach(['medianFailureYear','earlyFailureYear'] as $k)if(($d[$key][$k]??null)!==null)$number($d[$key][$k]);
    }
    foreach($d['buckets']['depletion'] as $bucket){$number($bucket['percent']??null);foreach(['medianYear','p10Year','p90Year'] as $k)if(($bucket[$k]??null)!==null)$number($bucket[$k]);}
    $number($d['buckets']['aggressiveNeeded']??null);$number($d['buckets']['medianAggressiveEnd']??null);
    foreach($d['annual'] as $row) {
        foreach(['year','calendarYear','required','pasSurvival','targetSurvival'] as $k)$number($row[$k]??null);
        if(count($row['buckets']??[])!==3)throw new InvalidArgumentException('Invalid annual buckets');
        foreach(array_merge([$row['pas']??[],$row['target']??[]],$row['buckets']) as $series)foreach(['p10','p25','p50','p75','p90'] as $k)$number($series[$k]??null);
    }
    if (count($d['stressCharts']??[])>3) throw new InvalidArgumentException('Too many charts');
}
function rb_pas_stress_assumptions(array $d): array {
    $p=$d['inputs'];
    $rows=[['Analysis','Retirement Stress Test'],['Model version',$d['modelVersion']],['Portfolio ($)',$p['portfolioValue']],['Timeline years',$p['years']],['Timeline start year',$p['timelineStartYear']],['Withdrawal start year',$p['withdrawalsStartYear']],['Starting annual withdrawal ($)',$p['annualWithdrawal']],['Inflation (%)',$p['inflation']],['PAS total advisory/fund cost (%)',$p['pasFee']],['Target Date fund expense (%)',$p['targetDateFee']],['Simulation count',$p['simulations']],['Seed',$p['seed']]];
    foreach (['Conservative','Moderate','Aggressive','PAS'] as $i=>$name) {
        $rows[]=[$name.' expected arithmetic return (%)',(float)$p['means'][$i]];
        $rows[]=[$name.' volatility (%)',(float)$p['volatilities'][$i]];
        if($i<3)$rows[]=[$name.' initial allocation (%)',(float)$p['allocation'][$i]];
    }
    return $rows;
}
function rb_pas_stress_summary(array $d): array {
    $rows=[['Metric','Vanguard PAS','Three-Bucket']];
    foreach (['survival'=>'Survival (%)','fullyFunded'=>'All scheduled withdrawals funded (%)','medianIncome'=>'Median cumulative income paid ($)','medianFees'=>'Median cumulative fees ($)','medianFailureYear'=>'Median failure year, failed paths only','earlyFailureYear'=>'Early failure year, 10th percentile of failed paths'] as $k=>$label) $rows[]=[$label,$d['pas'][$k]??'No failures',$d['target'][$k]??'No failures'];
    foreach (['p10'=>'10th','p25'=>'25th','p50'=>'Median','p75'=>'75th','p90'=>'90th'] as $k=>$label) $rows[]=[$label.' ending balance ($)',$d['pas']['ending'][$k],$d['target']['ending'][$k]];
    return $rows;
}
function rb_pas_stress_buckets(array $d): array {
    $rows=[['Bucket','Depleted (%)','Median year','10th year','90th year']];
    foreach (['Conservative','Moderate','Aggressive'] as $i=>$name) {
        $v=$d['buckets']['depletion'][$i];$none=empty($v['funded'])?'Not funded':'Not depleted';
        $rows[]=[$name,$v['percent']??0,$v['medianYear']??$none,$v['p10Year']??$none,$v['p90Year']??$none];
    }
    return $rows;
}
function rb_pas_stress_notes(): string {
    return 'Monte Carlo results are hypothetical planning illustrations based on the assumptions entered. They are not predictions or guarantees of future investment performance. Generic assumptions, not Vanguard forecasts. Returns, volatility, inflation, correlations and fees materially affect results. Annual normal arithmetic returns are floored at -100%; correlated shocks use a validated Cholesky matrix and independent years. The floor alters distribution moments under extreme assumptions. Timing: growth, then identical inflation-adjusted spending, then fees on remaining balances. Sequential Conservative, Moderate, Aggressive withdrawals; no replenishment or rebalancing. Failure is the first unfunded withdrawal, not a low or zero ending balance. Dollar percentiles interpolate at (n-1)*p across all paths, including failures. Event years use nearest ranks among failed/depleted paths only. Pointwise bucket medians do not constitute a single path and need not add to median total wealth. Figures are nominal USD. This simplified model omits taxes, fat tails, glide-path changes and changing correlations.';
}
function rb_pas_stress_csv($out,array $d): void {
    rb_pas_stress_validate($d);
    rb_csv_row($out,['Vanguard PAS vs Three-Bucket','Retirement Stress Test']);
    rb_csv_row($out,['Methodology',rb_pas_stress_notes()]);
    foreach(rb_pas_stress_assumptions($d) as $row)rb_csv_row($out,$row);
    rb_csv_row($out,['Correlation order','Conservative','Moderate','Aggressive','PAS']);
    foreach($d['correlation'] as $i=>$row)rb_csv_row($out,array_merge([['Conservative','Moderate','Aggressive','PAS'][$i]],$row));
    foreach(rb_pas_stress_summary($d) as $row)rb_csv_row($out,$row);
    foreach(rb_pas_stress_buckets($d) as $row)rb_csv_row($out,$row);
    rb_csv_row($out,['Aggressive needed for withdrawals (%)',$d['buckets']['aggressiveNeeded']]);
    rb_csv_row($out,['Median ending Aggressive ($)',$d['buckets']['medianAggressiveEnd']]);
    rb_csv_row($out,['Calendar point','Required spending','PAS survival (%)','Three-Bucket survival (%)','PAS p10','PAS p25','PAS median','PAS p75','PAS p90','Three-Bucket p10','Three-Bucket p25','Three-Bucket median','Three-Bucket p75','Three-Bucket p90','Conservative median','Moderate median','Aggressive median']);
    foreach($d['annual'] as $r) {
        $row=[($r['year']?'End of ':'Start of ').(int)$r['calendarYear'],$r['required'],$r['pasSurvival'],$r['targetSurvival']];
        foreach(['pas','target'] as $key)foreach(['p10','p25','p50','p75','p90'] as $k)$row[]=$r[$key][$k];
        foreach($r['buckets'] as $b)$row[]=$b['p50'];rb_csv_row($out,$row);
    }
}
function rb_pas_stress_table($pdf,array $rows): void {
    $html='<table border="1" cellpadding="5" style="font-size:9px;">';
    foreach($rows as $i=>$row) {
        $html.='<tr'.($i===0?' style="background-color:#f1f5f9;font-weight:bold;"':'').'>';
        foreach($row as $v)$html.='<td>'.htmlspecialchars(is_float($v)?number_format($v,2):(string)$v,ENT_QUOTES,'UTF-8').'</td>';
        $html.='</tr>';
    }
    $pdf->writeHTML($html.'</table>',true,false,true,false,'');
}
function rb_pas_stress_pdf(array $d): string {
    rb_pas_stress_validate($d);
    require_once __DIR__.'/../vendor/autoload.php';require_once __DIR__.'/report_pdf.php';
    $pdf=new RbReportPdf('P','mm','A4',true,'UTF-8',false);$pdf->setPrintHeader(false);$pdf->setPrintFooter(false);$pdf->SetMargins(15,15,15);$pdf->SetAutoPageBreak(true,15);
    $pdf->AddPage();$pdf->SetFont('helvetica','B',17);$pdf->MultiCell(0,9,'Vanguard PAS vs Three-Bucket Strategy',0,'L');$pdf->SetFont('helvetica','B',14);$pdf->Cell(0,8,'Retirement Stress Test',0,1);$pdf->SetFont('helvetica','',9);
    $pdf->MultiCell(0,5,'Monte Carlo results are hypothetical planning illustrations based on the assumptions entered. They are not predictions or guarantees of future investment performance.',0,'L');$pdf->Ln(3);
    rb_pas_stress_table($pdf,array_merge([['Assumption','Value']],rb_pas_stress_assumptions($d)));
    $pdf->AddPage();$pdf->SetFont('helvetica','B',14);$pdf->Cell(0,8,'Summary distributions',0,1);$pdf->SetFont('helvetica','',9);
    rb_pas_stress_table($pdf,rb_pas_stress_summary($d));$pdf->Ln(3);
    rb_pas_stress_table($pdf,rb_pas_stress_buckets($d));
    $pdf->MultiCell(0,5,'Depletion years condition on depletion; percentages indicate how often it occurred. Failure years condition on failure. Survival means all scheduled spending was funded. A zero final balance after exactly funding spending is not failure.',0,'L');
    $pdf->MultiCell(0,5,'Aggressive needed: '.number_format($d['buckets']['aggressiveNeeded'],1).'%. Median ending Aggressive balance: $'.number_format($d['buckets']['medianAggressiveEnd'],0).'.',0,'L');
    foreach($d['stressCharts']??[] as $i=>$png) {
        if(!extension_loaded('gd')&&!extension_loaded('imagick'))continue;
        $file=rb_pdf_chart_file($png);
        try{$pdf->AddPage();$pdf->SetFont('helvetica','B',14);$pdf->Cell(0,8,['Ending balance percentiles','Portfolio survival','Pointwise median bucket balances'][$i],0,1);[$iw,$ih]=getimagesize($file);$height=min(190,180*$ih/$iw);if ($pdf->Image($file,15,30,$height*$iw/$ih,$height,'PNG') === false) throw new RuntimeException('Chart embedding failed');}finally{unlink($file);}
    }
    $pdf->AddPage();$pdf->SetFont('helvetica','B',14);$pdf->Cell(0,8,'Methodology and correlations',0,1);$pdf->SetFont('helvetica','',10);$pdf->MultiCell(0,5,rb_pas_stress_notes(),0,'L');$pdf->Ln(4);
    $rows=[['Correlation','Conservative','Moderate','Aggressive','PAS']];foreach($d['correlation'] as $i=>$r)$rows[]=array_merge([['Conservative','Moderate','Aggressive','PAS'][$i]],$r);rb_pas_stress_table($pdf,$rows);
    $pdf->MultiCell(0,5,'Sequence-of-returns risk: poor early returns can force sales at depressed values while funding withdrawals. Spending conservative assets first attempts to reduce this risk; it does not eliminate it. No strategy is better solely because of one metric. The report reflects supplied calculator results, not an independently verified valuation.',0,'L');
    return $pdf->Output('','S');
}
function rb_pas_stress_export(array $d,string $format): void {
    try {
        rb_pas_stress_validate($d);
        if($format==='pdf')$bytes=rb_pas_stress_pdf($d);
        else {$out=fopen('php://temp','w+');rb_pas_stress_csv($out,$d);rewind($out);$bytes="\xEF\xBB\xBF".stream_get_contents($out);fclose($out);}
    } catch(Throwable $e) {rb_api_error(400,'Invalid Stress Test report data');}
    ob_end_clean();header('Content-Type: '.($format==='pdf'?'application/pdf':'text/csv; charset=utf-8'));header('Cache-Control: no-store');header('Content-Disposition: attachment; filename="Vanguard_Retirement_Stress_Test.'.$format.'"');echo $bytes;
}
