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

// Title
$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 10, 'RMD Impact Analysis Report', 0, 1, 'C');
$pdf->Ln(5);

// Report date
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Generated: ' . date('F j, Y'), 0, 1, 'C');
$pdf->Ln(10);

// Input Parameters Section
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'Your Information', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->Ln(3);

$html = '<table border="0" cellpadding="5">
<tr><td width="200"><b>Current Age:</b></td><td>' . $data['currentAge'] . '</td></tr>
<tr><td><b>Account Balance:</b></td><td>$' . number_format($data['accountBalance'], 0) . '</td></tr>
<tr><td><b>Growth Rate:</b></td><td>' . $data['growthRate'] . '%</td></tr>
<tr><td><b>Social Security:</b></td><td>$' . number_format($data['socialSecurity'], 0) . '</td></tr>
<tr><td><b>Pension:</b></td><td>$' . number_format($data['pension'], 0) . '</td></tr>
<tr><td><b>Other Income:</b></td><td>$' . number_format($data['otherIncome'], 0) . '</td></tr>
<tr><td><b>Filing Status:</b></td><td>' . ucfirst($data['filingStatus']) . '</td></tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(10);

// Summary Results
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'Key Results', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->Ln(3);

$summaryHtml = '<table border="0" cellpadding="5">
<tr><td width="200"><b>First RMD (Age 73):</b></td><td>$' . number_format($data['summary']['firstRMD'], 0) . '</td></tr>
<tr><td><b>RMD at Age 80:</b></td><td>$' . number_format($data['summary']['age80RMD'], 0) . '</td></tr>
<tr><td><b>RMD at Age 90:</b></td><td>$' . number_format($data['summary']['age90RMD'], 0) . '</td></tr>
<tr><td><b>Peak Tax Bracket:</b></td><td>' . $data['summary']['peakTaxBracket'] . '%</td></tr>
</table>';

$pdf->writeHTML($summaryHtml, true, false, true, false, '');
$pdf->Ln(10);

// Year-by-Year Table
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'Year-by-Year Projection', 0, 1);
$pdf->Ln(3);

$tableHtml = '<table border="1" cellpadding="4">
<thead>
<tr style="background-color: #667eea; color: white; font-weight: bold;">
<th width="50">Age</th>
<th width="100">Balance</th>
<th width="100">RMD</th>
<th width="100">Total Income</th>
<th width="70">Tax Bracket</th>
</tr>
</thead>
<tbody>';

foreach ($data['projections'] as $row) {
    $tableHtml .= '<tr>
    <td>' . $row['age'] . '</td>
    <td>$' . number_format($row['balance'], 0) . '</td>
    <td>$' . number_format($row['rmdAmount'], 0) . '</td>
    <td>$' . number_format($row['totalIncome'], 0) . '</td>
    <td>' . $row['taxBracket'] . '%</td>
    </tr>';
}

$tableHtml .= '</tbody></table>';

$pdf->SetFont('helvetica', '', 9);
$pdf->writeHTML($tableHtml, true, false, true, false, '');

// Output PDF
$pdf->Output('RMD_Analysis_' . date('Y-m-d') . '.pdf', 'D');
?>