<?php
// Turn off error display and enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../error.log');

// Start output buffering
ob_start();

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

require '../connections/connections.php';
require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');    

$pdo = connection();

// Optimize logo - read once and compress
$logo_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'psc_greenbanner.png';
$logo_data = '';
if (file_exists($logo_path)) {
    // Create image resource and compress
    $source = imagecreatefrompng($logo_path);
    if ($source) {
        // Scale down if needed (e.g., if original is too large)
        $width = imagesx($source);
        $height = imagesy($source);
        $new_width = 600; // Target width
        $new_height = ($height / $width) * $new_width;
        
        $resized = imagecreatetruecolor($new_width, $new_height);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        
        // Capture output
        ob_start();
        imagepng($resized, null, 6); // Compression level 6 (0-9, higher = smaller file but slower)
        $logo_data = ob_get_clean();
        
        // Clean up
        imagedestroy($source);
        imagedestroy($resized);
    }
}
$logo_base64 = $logo_data ? 'data:image/png;base64,' . base64_encode($logo_data) : '';

if (!isset($_GET['billing_id']) || !is_numeric($_GET['billing_id'])) {
    $_SESSION['error'] = "Invalid billing record.";
    header("Location: billing.php");
    exit();
}

$billing_id = (int)$_GET['billing_id'];

// Query to get detailed billing information
$query = "SELECT 
            bh.*,
            mt.transaction_id,
            CONCAT(p.first_name, ' ', COALESCE(p.middle_name, ''), ' ', p.last_name) AS patient_name,
            p.address,
            p.date_of_birth,
            p.patient_status,
            p.philhealth_no,
            s.service_name,
            s.category as service_category,
            mt.amount as service_amount
          FROM billing_header bh
          JOIN patients p ON bh.case_id = p.case_id
          JOIN medical_transactions mt ON bh.transaction_id = mt.transaction_id
          JOIN medical_services s ON mt.service_id = s.service_id
          WHERE bh.billing_id = :billing_id";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':billing_id', $billing_id, PDO::PARAM_INT);
$stmt->execute();
$billingRecord = $stmt->fetch(PDO::FETCH_ASSOC);

// Query to fetch billing items with proper category
$itemQuery = "SELECT bi.*, 
              i.item_name, 
              i.category as item_category,
              i.unit, 
              i.price as unit_price
              FROM billing_items bi
              JOIN inventory i ON bi.item_id = i.item_id
              WHERE bi.billing_id = :billing_id
              ORDER BY i.category";
$itemStmt = $pdo->prepare($itemQuery);
$itemStmt->bindValue(':billing_id', $billing_id, PDO::PARAM_INT);
$itemStmt->execute();
$billingItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

// Query to fetch professional fees
$feeQuery = "SELECT *
             FROM professional_fees
             WHERE billing_id = :billing_id
             ORDER BY professional_name";
$feeStmt = $pdo->prepare($feeQuery);
$feeStmt->bindValue(':billing_id', $billing_id, PDO::PARAM_INT);
$feeStmt->execute();
$professionalFees = $feeStmt->fetchAll(PDO::FETCH_ASSOC);

// Group items by category
$itemsByCategory = [];
foreach ($billingItems as $item) {
    $category = isset($item['item_category']) ? $item['item_category'] : 'Others';
    switch($category) {
        case 'Medications':
            $groupCategory = 'Drugs and Medicines';
            break;
        case 'Medical Supply':
            $groupCategory = 'Supplies';
            break;
        case 'Medical Equipment':
            $groupCategory = 'HCI fees';
            break;
        case 'Laboratory':
        case 'Radiology':
            $groupCategory = 'Laboratory/Radiology & Diagnostic';
            break;
        default:
            $groupCategory = 'Others';
    }
    
    if (!isset($itemsByCategory[$groupCategory])) {
        $itemsByCategory[$groupCategory] = [];
    }
    $itemsByCategory[$groupCategory][] = $item;
}

// Calculate totals
$itemsAndServicesSubtotal = $billingRecord['total_items'] + $billingRecord['service_amount'];
$totalProfessionalFees = $billingRecord['total_professional_fees'];
$netAmountDue = $billingRecord['net_amount'] - $billingRecord['total_discounts'];

// Format the billing date (YYYYMMDD)
$billing_date = date('Ymd', strtotime($billingRecord['billing_date']));

// Format the reference number
$soa_reference = $billing_date . '-' . 
                 $billingRecord['transaction_id'] . 
                 $billingRecord['billing_id'] . $billingRecord['case_id'] ;

// Create new PDF document
class MYPDF extends TCPDF {
    public function Header() {}
    public function Footer() {}
}

// Create new PDF document with optimized settings
$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('PAANAKAN');
$pdf->SetAuthor('Admin');
$pdf->SetTitle('Statement of Account');

// Optimize PDF settings
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetCompression(true);
$pdf->setImageScale(1.53);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 10);
$pdf->AddPage();

// Prepare HTML content with optimized styles
$html = '
<style>
    * { font-family: "dejavusans", sans-serif; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 9pt; }
    th, td { border: 1px solid #ddd; padding: 3px 4px; font-size: 9pt; }
    th { font-weight: 600; }
    .text-end { text-align: right; }
    .text-center { text-align: center; }
    .fw-bold { font-weight: 600; }
    .amount-column { text-align: right; }
    .statement-header { margin-bottom: 8px; }
    .logo-container { text-align: left; padding-right: 10px; }
    .logo-container img { width: 160px; height: auto; }
    h2 { font-size: 13pt; font-weight: 600; margin: 0; padding: 0; }
    h3 { font-size: 11pt; font-weight: 600; margin: 4px 0; }
    h4 { font-size: 10pt; font-weight: 600; margin: 4px 0; }
    p { font-size: 9pt; margin: 2px 0; }
    .no-border { border: none; }
    .no-border td { border: none; }
    .highlight { font-weight: 600; }
    .total-row { font-weight: 600; }
    .peso { font-family: "dejavusans"; }
    .important { color: #000; font-weight: 600; }
    .section-title { border-bottom: 1px solid black; padding: 5px; font-weight: bold; }
</style>

<div class="statement-header">
    <table class="no-border" cellpadding="1">
        <tr>
            <td width="50%" class="logo-container">
                <img src="' . $logo_base64 . '" alt="Logo">
            </td>
            <td width="50%" style="text-align: right; vertical-align: top;">
                <h2 style="color: #2d3748;">STATEMENT OF ACCOUNT</h2>
                <p>SOA Reference No.: <span class="important">' . $soa_reference . '</span><br>
                Contact No.: 043-286-7728</p>
            </td>
        </tr>
    </table>
</div>

<div class="patient-info">
    <table class="no-border" cellpadding="2">
        <tr>
            <td width="60%"><strong>Name of Patient:</strong> <span class="important">' . htmlspecialchars($billingRecord['patient_name']) . '</span></td>
            <td width="20%"><strong>Age:</strong> ' . date_diff(date_create($billingRecord['date_of_birth']), date_create('now'))->y . ' years</td>
            <td width="20%"><strong>Room No.:</strong> <span class="important">' . ($billingRecord['patient_status'] === 'Admitted' ? 'IPD' : 'OPD') . '</span></td>
        </tr>
        <tr>
            <td colspan="3"><strong>Address:</strong> ' . htmlspecialchars($billingRecord['address'] ?: 'N/A') . '</td>
        </tr>
        ' . ($billingRecord['philhealth_no'] ? '<tr>
            <td colspan="3"><strong>PhilHealth No.:</strong> <span class="important">' . htmlspecialchars($billingRecord['philhealth_no']) . '</span></td>
        </tr>' : '') . '
        <tr>
            <td colspan="2"><strong>Date & Time Admitted:</strong> <span class="important">' . (new DateTime($billingRecord['billing_date']))->format('F j, Y g:i a') . '</span></td>
            <td><strong>Date & Time Discharged:</strong> <span class="important">' . (new DateTime($billingRecord['billing_date']))->format('F j, Y g:i a') . '</span></td>
        </tr>
    </table>
</div>

<h3 class="section-title" style="text-align: center;">SUMMARY OF FEES</h3>

<table>
    <thead>
        <tr>
            <th style="text-align: left; ">Particulars</th>
            <th style="text-align: center; ">Quantity</th>
            <th style="text-align: right; ">Unit Price</th>
            <th style="text-align: right; ">Amount</th>
            <th style="text-align: right; ">VAT Exempt</th>
            <th style="text-align: right; ">Out of Pocket</th>
        </tr>
    </thead>
    <tbody>
        <!-- Medical Service -->
        <tr>
            <td colspan="6" class="section-title">Medical Service</td>
        </tr>
        <tr>
            <td style="text-align: left;">' . htmlspecialchars($billingRecord['service_name']) . '</td>
            <td style="text-align: center;">1</td>
            <td style="text-align: right;"><span class="peso">₱</span> ' . number_format($billingRecord['service_amount'], 2) . '</td>
            <td style="text-align: right;"><span class="peso">₱</span> ' . number_format($billingRecord['service_amount'], 2) . '</td>
            <td style="text-align: right;"><span class="peso">₱</span> ' . number_format($billingRecord['service_amount'], 2) . '</td>
            <td style="text-align: right;"><span class="peso">₱</span> ' . number_format($billingRecord['service_amount'], 2) . '</td>
        </tr>';

// Add items by category
$displayCategories = ['HCI fees', 'Drugs and Medicines', 'Laboratory/Radiology & Diagnostic', 'Supplies', 'Others'];
foreach ($displayCategories as $category) {
    if (isset($itemsByCategory[$category]) && !empty($itemsByCategory[$category])) {
        $html .= '
        <tr>
            <td colspan="6" class="section-title">' . htmlspecialchars($category) . '</td>
        </tr>';
        
        foreach ($itemsByCategory[$category] as $item) {
            $html .= '
        <tr>
            <td style="text-align: left;">' . htmlspecialchars($item['item_name']) . '</td>
            <td style="text-align: center;">' . $item['quantity'] . '</td>
            <td style="text-align: right;"><span class="peso">₱</span> ' . number_format($item['item_price'], 2) . '</td>
            <td style="text-align: right;"><span class="peso">₱</span> ' . number_format($item['item_amount'], 2) . '</td>
            <td style="text-align: right;"><span class="peso">₱</span> ' . number_format($item['item_amount'], 2) . '</td>
            <td style="text-align: right;"><span class="peso">₱</span> ' . number_format($item['item_amount'], 2) . '</td>
        </tr>';
        }
    }
}

$html .= '
        <tr class="total-row">
            <td colspan="3" class="text-end">Subtotal</td>
            <td class="amount-column"><span class="peso">₱</span> ' . number_format($itemsAndServicesSubtotal, 2) . '</td>
            <td class="amount-column"><span class="peso">₱</span> ' . number_format($itemsAndServicesSubtotal, 2) . '</td>
            <td class="amount-column"><span class="peso">₱</span> ' . number_format($itemsAndServicesSubtotal, 2) . '</td>
        </tr>';

if (!empty($professionalFees)) {
    $html .= '
        <tr>
            <td colspan="6" class="section-title">Professional Fees</td>
        </tr>';
    
    foreach ($professionalFees as $fee) {
        $html .= '
        <tr>
            <td>' . htmlspecialchars($fee['professional_name']) . '<br>' . htmlspecialchars($fee['service_description']) . '</td>
            <td class="text-center">1</td>
            <td class="amount-column"><span class="peso">₱</span> ' . number_format($fee['fee_amount'], 2) . '</td>
            <td class="amount-column"><span class="peso">₱</span> ' . number_format($fee['fee_amount'], 2) . '</td>
            <td class="amount-column"><span class="peso">₱</span> ' . number_format($fee['fee_amount'], 2) . '</td>
            <td class="amount-column"><span class="peso">₱</span> ' . number_format($fee['fee_amount'], 2) . '</td>
        </tr>';
    }
    
    $html .= '
        <tr class="total-row">
            <td colspan="3" class="text-end">Professional Fees Subtotal</td>
            <td class="amount-column"><span class="peso">₱</span> ' . number_format($totalProfessionalFees, 2) . '</td>
            <td class="amount-column"><span class="peso">₱</span> ' . number_format($totalProfessionalFees, 2) . '</td>
            <td class="amount-column"><span class="peso">₱</span> ' . number_format($totalProfessionalFees, 2) . '</td>
        </tr>';
}

$html .= '
    </tbody>
</table>

<h4 class="section-title">Payments</h4>
<table>
    <tr>
        <td width="80%">Hospital Bill</td>
        <td width="20%" class="amount-column highlight"><span class="peso">₱</span> ' . number_format($itemsAndServicesSubtotal, 2) . '</td>
    </tr>
    <tr>
        <td>Professional Fees</td>
        <td class="amount-column highlight"><span class="peso">₱</span> ' . number_format($totalProfessionalFees, 2) . '</td>
    </tr>
    <tr>
        <td>Total Amount</td>
        <td class="amount-column highlight"><span class="peso">₱</span> ' . number_format($billingRecord['net_amount'], 2) . '</td>
    </tr>
    <tr>
        <td>Less: Discounts</td>
        <td class="amount-column highlight"><span class="peso">₱</span> ' . number_format($billingRecord['total_discounts'], 2) . '</td>
    </tr>
    <tr class="total-row">
        <td>Net Amount Due</td>
        <td class="amount-column"><span class="peso">₱</span> ' . number_format($netAmountDue, 2) . '</td>
    </tr>
</table>

<h4 class="section-title">HMO/GUARANTOR DETAILS</h4>
<table>
    <tr class="highlight">
        <th width="33%">Hospital Bill</th>
        <th width="33%">Professional Fee</th>
        <th width="34%">Total</th>
    </tr>
    <tr class="highlight">
        <td class="amount-column"><span class="peso">₱</span> ' . number_format($itemsAndServicesSubtotal, 2) . '</td>
        <td class="amount-column"><span class="peso">₱</span> ' . number_format($totalProfessionalFees, 2) . '</td>
        <td class="amount-column"><span class="peso">₱</span> ' . number_format($billingRecord['net_amount'], 2) . '</td>
    </tr>
</table>

<div style="text-align: right; margin: 20px 0;" class="highlight">
    <strong>BALANCE DUE: <span class="peso">₱</span> ' . number_format($netAmountDue, 2) . '</strong>
</div>

<table border="0" style="margin-top: 40px;">
    <tr>
        <td width="50%">Prepared by:</td>
        <td width="50%">Conforme:</td>
    </tr>
    <tr>
        <td style="padding-top: 60px; height: 60px; color: transparent; "></td>
        <td style="padding-top: 60px; height: 60px; color: transparent; "></td>
    </tr>
    <tr style="padding-top: 5px;">
        <td style="text-align: center; padding-top: 5px;">Billing Clerk</td>
        <td style="text-align: center; padding-top: 5px;">
            Member/Patient/Authorized representative<br>
            <small><i>(Signature over printed name)</i></small>
        </td>
    </tr>
</table>
';

// Get patient's last name
$patient_name_parts = explode(' ', $billingRecord['patient_name']);
$patient_last_name = end($patient_name_parts);

// Write HTML content with optimized settings
$pdf->SetFont('dejavusans', '', 9);
$tagvs = array(
    'h1' => array('font-family' => 'dejavusans', 'font-size' => 14),
    'h2' => array('font-family' => 'dejavusans', 'font-size' => 13),
    'h3' => array('font-family' => 'dejavusans', 'font-size' => 11),
    'h4' => array('font-family' => 'dejavusans', 'font-size' => 10),
    'p' => array('font-family' => 'dejavusans', 'font-size' => 9)
);
$pdf->setHtmlVSpace($tagvs);

// Write HTML content
$pdf->writeHTML($html, true, false, true, false, '');

// Output PDF with compression
$pdf->Output($soa_reference . '_' . $patient_last_name . '.pdf', 'I', true);
?> 