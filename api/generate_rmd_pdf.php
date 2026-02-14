<?php
session_start();
require_once '../includes/db_config.php';
require_once '../vendor/autoload.php';

// Check if user is logged in and premium
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Not logged in']));
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT subscription_status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user['subscription_status'] !== 'premium') {
    http_response_code(403);
    die(json_encode(['error' => 'Premium subscription required']));
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    die(json_encode(['error' => 'No data provided']));
}

// Create PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('RonBelisle.com');
$pdf->SetAuthor('RMD Impact Calculator');
$pdf->SetTitle('RMD Impact Analysis Report');
$pdf->SetSubject('Retirement Planning');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

// Header with gradient background
$pdf->SetFillColor(102, 126, 234); // Blue gradient color
$pdf->Rect(0, 0, 210, 40, 'F');

// Title
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 24);
$pdf->SetY(12);
$pdf->Cell(0, 10, 'RMD Impact Analysis', 0, 1, 'C');

// Subtitle
$pdf->SetFont('helvetica', '', 12);
$pdf->SetY(25);
$pdf->Cell(0, 6, 'Your Personalized Retirement Distribution Report', 0, 1, 'C');

// Report date
$pdf->SetTextColor(100, 100, 100);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetY(33);
$pdf->Cell(0, 5, 'Generated: ' . date('F j, Y'), 0, 1, 'C');

// Reset text color
$pdf->SetTextColor(0, 0, 0);
$pdf->SetY(50);

// Input Parameters Section
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(102, 126, 234);
$pdf->Cell(0, 8, 'Your Information', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(2);

// Blue box for input data
$pdf->SetFillColor(240, 245, 255);
$pdf->SetDrawColor(102, 126, 234);
$pdf->SetLineWidth(0.3);

$html = '<table border="0" cellpadding="8" style="background-color: #f0f5ff;">
<tr>
    <td width="45%" style="border-bottom: 1px solid #ddd;"><b>Current Age:</b></td>
    <td width="55%" style="border-bottom: 1px solid #ddd;">' . $data['currentAge'] . '</td>
</tr>
<tr>
    <td style="border-bottom: 1px solid #ddd;"><b>Account Balance:</b></td>
    <td style="border-bottom: 1px solid #ddd;">$' . number_format($data['accountBalance'], 0) . '</td>
</tr>
<tr>
    <td style="border-bottom: 1px solid #ddd;"><b>Expected Growth Rate:</b></td>
    <td style="border-bottom: 1px solid #ddd;">' . $data['growthRate'] . '%</td>
</tr>
<tr>
    <td style="border-bottom: 1px solid #ddd;"><b>Annual Social Security:</b></td>
    <td style="border-bottom: 1px solid #ddd;">$' . number_format($data['socialSecurity'], 0) . '</td>
</tr>
<tr>
    <td style="border-bottom: 1px solid #ddd;"><b>Annual Pension:</b></td>
    <td style="border-bottom: 1px solid #ddd;">$' . number_format($data['pension'], 0) . '</td>
</tr>
<tr>
    <td style="border-bottom: 1px solid #ddd;"><b>Other Annual Income:</b></td>
    <td style="border-bottom: 1px solid #ddd;">$' . number_format($data['otherIncome'], 0) . '</td>
</tr>
<tr>
    <td><b>Tax Filing Status:</b></td>
    <td>' . ucfirst($data['filingStatus']) . '</td>
</tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(8);

// Summary Results Section
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(102, 126, 234);
$pdf->Cell(0, 8, 'Key Results', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(2);

// Summary cards in a grid
$summaryHtml = '<table border="0" cellpadding="10">
<tr>
    <td width="50%" style="background-color: #f0f9ff; border: 2px solid #667eea; border-radius: 8px;">
        <div style="text-align: center;">
            <div style="font-size: 11px; color: #666; margin-bottom: 5px;">First RMD (Age 73)</div>
            <div style="font-size: 20px; font-weight: bold; color: #667eea;">$' . number_format($data['summary']['firstRMD'], 0) . '</div>
        </div>
    </td>
    <td width="50%" style="background-color: #fef3f2; border: 2px solid #ef4444; border-radius: 8px;">
        <div style="text-align: center;">
            <div style="font-size: 11px; color: #666; margin-bottom: 5px;">RMD at Age 80</div>
            <div style="font-size: 20px; font-weight: bold; color: #ef4444;">$' . number_format($data['summary']['age80RMD'], 0) . '</div>
        </div>
    </td>
</tr>
<tr>
    <td style="background-color: #fef7e6; border: 2px solid #f59e0b; border-radius: 8px;">
        <div style="text-align: center;">
            <div style="font-size: 11px; color: #666; margin-bottom: 5px;">RMD at Age 90</div>
            <div style="font-size: 20px; font-weight: bold; color: #f59e0b;">$' . number_format($data['summary']['age90RMD'], 0) . '</div>
        </div>
    </td>
    <td style="background-color: #f0fdf4; border: 2px solid #10b981; border-radius: 8px;">
        <div style="text-align: center;">
            <div style="font-size: 11px; color: #666; margin-bottom: 5px;">Peak Tax Bracket</div>
            <div style="font-size: 20px; font-weight: bold; color: #10b981;">' . $data['summary']['peakTaxBracket'] . '%</div>
        </div>
    </td>
</tr>
</table>';

$pdf->writeHTML($summaryHtml, true, false, true, false, '');
$pdf->Ln(8);

// Chart Section
if (isset($data['chartImage']) && !empty($data['chartImage'])) {
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(102, 126, 234);
    $pdf->Cell(0, 8, 'Account Balance & RMD Over Time', 0, 1);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(3);
    
    // Decode base64 image
    $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['chartImage']));
    
    // Save temporarily
    $tempFile = tempnam(sys_get_temp_dir(), 'chart_') . '.png';
    file_put_contents($tempFile, $imageData);
    
    // Add image to PDF (centered, 170mm wide)
    $pdf->Image($tempFile, 20, $pdf->GetY(), 170, 0, 'PNG');
    
    // Clean up
    unlink($tempFile);
    
    $pdf->Ln(85); // Space after chart
}

// What This Means Section
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(102, 126, 234);
$pdf->Cell(0, 8, 'What This Means For You', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 10);
$interpretationHtml = '<div style="background-color: #fafafa; padding: 10px; border-left: 4px solid #667eea;">';

if ($data['accountBalance'] <= 50000) {
    $interpretationHtml .= '<p><b>Your RMDs will be very modest.</b> With a current balance of $' . number_format($data['accountBalance'], 0) . ', your first RMD at age 73 will only be around $' . number_format($data['summary']['firstRMD'], 0) . '. This is unlikely to create any significant tax burden.</p>';
} else if ($data['accountBalance'] <= 200000) {
    $interpretationHtml .= '<p><b>Your RMDs will be manageable.</b> Starting at $' . number_format($data['summary']['firstRMD'], 0) . ' at age 73, these withdrawals shouldn\'t dramatically impact your taxes for most situations.</p>';
} else if ($data['accountBalance'] <= 600000) {
    $interpretationHtml .= '<p><b>RMD planning may be beneficial.</b> With $' . number_format($data['accountBalance'], 0) . ' in tax-deferred accounts, your RMDs will be substantial enough that strategies like Roth conversions or QCDs could help reduce your tax burden.</p>';
} else {
    $interpretationHtml .= '<p><b>RMD planning is important for you.</b> With $' . number_format($data['accountBalance'], 0) . ' in tax-deferred accounts, RMDs will be significant. You should seriously consider tax planning strategies like Roth conversions, qualified charitable distributions, and tax bracket management.</p>';
}

if ($data['summary']['peakTaxBracket'] <= 12) {
    $interpretationHtml .= '<p><b>You\'re likely in a favorable tax situation.</b> Your estimated tax bracket remains low even with RMDs.</p>';
} else if ($data['summary']['peakTaxBracket'] <= 22) {
    $interpretationHtml .= '<p><b>You\'re in a moderate tax bracket.</b> RMDs are adding to your tax bill, but you still have room for planning opportunities.</p>';
} else {
    $interpretationHtml .= '<p><b>RMDs may push you into higher tax brackets.</b> Consider strategies to reduce your tax-deferred balance before RMDs become mandatory.</p>';
}

$interpretationHtml .= '</div>';
$pdf->writeHTML($interpretationHtml, true, false, true, false, '');

// Add new page for table
$pdf->AddPage();

// Year-by-Year Table
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(102, 126, 234);
$pdf->Cell(0, 8, 'Year-by-Year Projection', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(3);

$tableHtml = '<table border="1" cellpadding="6" style="border-collapse: collapse;">
<thead>
<tr style="background-color: #667eea; color: white; font-weight: bold; text-align: center;">
<th width="12%">Age</th>
<th width="22%">Balance</th>
<th width="22%">RMD</th>
<th width="22%">Total Income</th>
<th width="22%">Tax Bracket</th>
</tr>
</thead>
<tbody>';

$rowColor = '#ffffff';
foreach ($data['projections'] as $i => $row) {
    // Alternate row colors
    $rowColor = ($i % 2 == 0) ? '#f9fafb' : '#ffffff';
    
    $tableHtml .= '<tr style="background-color: ' . $rowColor . ';">
    <td style="text-align: center;">' . $row['age'] . '</td>
    <td style="text-align: right;">$' . number_format($row['balance'], 0) . '</td>
    <td style="text-align: right;">$' . number_format($row['rmdAmount'], 0) . '</td>
    <td style="text-align: right;">$' . number_format($row['totalIncome'], 0) . '</td>
    <td style="text-align: center;">' . $row['taxBracket'] . '%</td>
    </tr>';
}

$tableHtml .= '</tbody></table>';

$pdf->SetFont('helvetica', '', 9);
$pdf->writeHTML($tableHtml, true, false, true, false, '');

// Footer on last page
$pdf->SetY(-20);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(150, 150, 150);
$pdf->Cell(0, 5, 'Generated by RonBelisle.com - Professional Financial Planning Tools', 0, 0, 'C');
$pdf->Ln(4);
$pdf->Cell(0, 5, 'This report is for informational purposes only and does not constitute financial advice.', 0, 0, 'C');

// Output PDF
$pdf->Output('RMD_Analysis_' . date('Y-m-d') . '.pdf', 'D');
?>