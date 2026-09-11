<?php
// Run the real CLI PDF path: syntax tests alone cannot catch undefined helpers.
$row = ['age'=>60,'year'=>2026,'filingStatus'=>'single','conversion'=>100000,'rmd'=>0,
    'socialSecurity'=>0,'magi'=>100000,'income'=>100000,'federalTax'=>13170,'allInTax'=>13170,
    'spending'=>1000,'requestedSpending'=>10000,'spendingShortfall'=>9000,'taxShortfall'=>0,
    'traditionalBalance'=>100000,'rothBalance'=>100000,'taxableBalance'=>85830];
$data = ['currentAge'=>60,'traditionalIRA'=>200000,'conversionAmount'=>100000,'conversionYears'=>1,
    'withConversion'=>['yearlyData'=>[$row]],'withoutConversion'=>['yearlyData'=>[$row]],
    'includeIrmaa'=>false,'includeNiit'=>false];
$input = tempnam(sys_get_temp_dir(), 'phase2-pdf-input-');
$output = tempnam(sys_get_temp_dir(), 'phase2-pdf-output-');
try {
    file_put_contents($input, json_encode($data));
    $env = array_merge(getenv(), ['ROTH_PDF_QA_INPUT'=>$input,'ROTH_PDF_QA_OUTPUT'=>$output]);
    $process = proc_open([PHP_BINARY, __DIR__ . '/../api/generate_roth_pdf.php'],
        [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, dirname(__DIR__), $env);
    if (!is_resource($process)) throw new RuntimeException('Could not start PDF regression');
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $status = proc_close($process);
    $pdf = file_get_contents($output);
    if ($status !== 0 || substr($pdf, 0, 5) !== '%PDF-' || strlen($pdf) < 1000) {
        throw new RuntimeException("Roth PDF generation failed (exit $status): $stdout $stderr");
    }
    echo "Phase 2 Roth PDF generation regression passed.\n";
} finally {
    unlink($input);
    unlink($output);
}
