<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

require '../connections/connections.php';
$pdo = connection();

// Check if the billing_id is provided in the URL
if (!isset($_GET['billing_id']) || !is_numeric($_GET['billing_id'])) {
    $_SESSION['error'] = "Invalid billing record.";
    header("Location: billing.php");
    exit();
}

// Get the billing ID from the URL
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

// If no record is found, redirect to the billing page with an error message
if (!$billingRecord) {
    $_SESSION['error'] = "Billing record not found.";
    header("Location: billing.php");
    exit();
}

// Query to fetch billing items
$itemQuery = "SELECT bi.*, i.item_name, i.category as item_category, i.unit, i.price as unit_price
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

// Calculate totals for each category
$categoryTotals = [
    'HCI fees' => 0,
    'Drugs and Medicines' => 0,
    'Laboratory/Radiology & Diagnostic' => 0,
    'Supplies' => 0,
    'Others' => 0
];

// First, categorize the medical service
$serviceCategory = $billingRecord['service_category'];
switch($serviceCategory) {
    case 'Laboratory':
    case 'Radiology':
        $categoryTotals['Laboratory/Radiology & Diagnostic'] += $billingRecord['service_amount'];
        break;
    case 'Equipment':
        $categoryTotals['HCI fees'] += $billingRecord['service_amount'];
        break;
    default:
        $categoryTotals['Others'] += $billingRecord['service_amount'];
}

// Then add inventory items to their respective categories
foreach ($billingItems as $item) {
    $category = isset($item['item_category']) ? $item['item_category'] : 'Others';
    switch($category) {
        case 'Medications':
            $categoryTotals['Drugs and Medicines'] += $item['item_amount'];
            break;
        case 'Medical Supply':
            $categoryTotals['Supplies'] += $item['item_amount'];
            break;
        case 'Medical Equipment':
            $categoryTotals['HCI fees'] += $item['item_amount'];
            break;
        case 'Laboratory':
        case 'Radiology':
            $categoryTotals['Laboratory/Radiology & Diagnostic'] += $item['item_amount'];
            break;
        default:
            $categoryTotals['Others'] += $item['item_amount'];
    }
}

// Calculate subtotal for items and services
$itemsAndServicesSubtotal = array_sum($categoryTotals);

$totalProfessionalFees = array_sum(array_column($professionalFees, 'fee_amount'));

// Calculate total amount
$totalAmount = $billingRecord['service_amount'] + $billingRecord['total_items'];

// Format the billing date (YYYYMMDD)
$billing_date = date('Ymd', strtotime($billingRecord['billing_date']));

// Format the reference number
$soa_reference = $billing_date . '-' . 
                 $billingRecord['transaction_id'] . 
                 $billingRecord['billing_id'] . $billingRecord['case_id'] ;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statement of Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
    <style>
        .statement-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
        }
        .hospital-logo {
            max-width: 500px;
        }
        .soa-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .hospital-details {
            font-size: 14px;
            color: #666;
        }
        .patient-info {
            margin: 20px 0;
            font-size: 14px;
        }
        .patient-info .row {
            margin-bottom: 10px;
        }
        .fees-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .fees-table th, .fees-table td {
            border: 1px solid #ddd;
            padding: 8px;
            font-size: 14px;
        }
        .fees-table th {
            background-color: #f8f9fa;
        }
        .amount-column {
            text-align: right;
        }
        .subtotal-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .total-row {
            font-weight: bold;
            background-color: #e9ecef;
        }
        .signature-section {
            margin-top: 30px;
            font-size: 14px;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            margin-top: 25px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="dashboard-container">
        <?php include '../sidebar.php'; ?>

        <main class="dashboard-main-content">
            <div class="container mt-4">
                <!-- Statement of Account Card -->
                <div class="card shadow">
                    <!-- Header -->
                    <div class="statement-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <img src="../psc_greenbanner.png" alt="Hospital Logo" class="hospital-logo">
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="soa-title">STATEMENT OF ACCOUNT</div>
                                <div class="hospital-details">
                                    SOA Reference No.: <?php echo $soa_reference; ?><br>
                                    Contact No.: 043-286-7728
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Patient Information -->
                    <div class="card-body">
                        <div class="patient-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Name of Patient:</strong> <?= htmlspecialchars($billingRecord['patient_name']) ?>
                                </div>
                                <div class="col-md-2">
                                    <strong>Age:</strong> <?= date_diff(date_create($billingRecord['date_of_birth']), date_create('now'))->y ?> years
                                </div>
                                <div class="col-md-4">
                                    <strong>Room No.:</strong> <?= $billingRecord['patient_status'] === 'Admitted' ? 'IPD' : 'OPD' ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Address:</strong> <?= htmlspecialchars($billingRecord['address'] ?: 'N/A') ?>
                                </div>
                                <?php if ($billingRecord['philhealth_no']): ?>
                                <div class="col-md-6">
                                    <strong>PhilHealth No.:</strong> <?= htmlspecialchars($billingRecord['philhealth_no']) ?>
                                </div>
                                <?php endif; ?>
                                
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                        <strong>Date & Time Admitted:</strong> <?= (new DateTime($billingRecord['billing_date']))->format('F j, Y g:i a') ?>
                                    </div>
                                <div class="col-md-6">
                                    <strong>Date & Time Discharged:</strong> <?= (new DateTime($billingRecord['billing_date']))->format('F j, Y g:i a') ?>
                                </div>
                            </div>
                            <div class="row">
                               
                            </div>
                        </div>


                        <h5 class="text-center mb-3">SUMMARY OF FEES</h5>
                        <table class="fees-table">
                            <thead>
                                <tr>
                                    <th style="text-align: center;">Particulars</th>
                                    <th style="text-align: center;">Quantity</th>
                                    <th style="text-align: center;">Unit Price</th>
                                    <th style="text-align: center;">Amount</th>
                                    <th style="text-align: center;">VAT Exempt</th>
                                    <th style="text-align: center;">Out of Pocket</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Medical Service -->
                                <tr>
                                    <td colspan="6" class="fw-bold bg-light">Medical Service</td>
                                </tr>
                                <tr>
                                    <td><?= htmlspecialchars($billingRecord['service_name']) ?></td>
                                    <td class="amount-column">1</td>
                                    <td class="amount-column"><?= number_format($billingRecord['service_amount'], 2) ?></td>
                                    <td class="amount-column"><?= number_format($billingRecord['service_amount'], 2) ?></td>
                                    <td class="amount-column"><?= number_format($billingRecord['service_amount'], 2) ?></td>
                                    <td class="amount-column"><?= number_format($billingRecord['service_amount'], 2) ?></td>
                                </tr>

                                <!-- Group and display items by category -->
                                <?php
                                $displayCategories = ['HCI fees', 'Drugs and Medicines', 'Laboratory/Radiology & Diagnostic', 'Supplies', 'Others'];
                                foreach ($displayCategories as $category):
                                    if (isset($itemsByCategory[$category]) && !empty($itemsByCategory[$category])):
                                ?>
                                    <tr>
                                        <td colspan="6" class="fw-bold bg-light"><?= htmlspecialchars($category) ?></td>
                                    </tr>
                                    <?php foreach ($itemsByCategory[$category] as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td class="amount-column"><?= $item['quantity'] ?></td>
                                        <td class="amount-column"><?= number_format($item['item_price'], 2) ?></td>
                                        <td class="amount-column"><?= number_format($item['item_amount'], 2) ?></td>
                                        <td class="amount-column"><?= number_format($item['item_amount'], 2) ?></td>
                                        <td class="amount-column"><?= number_format($item['item_amount'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>

                                <!-- Subtotal for Items and Services -->
                                <tr class="subtotal-row">
                                    <td colspan="3">Subtotal</td>
                                    <td class="amount-column"><?= number_format($itemsAndServicesSubtotal, 2) ?></td>
                                    <td class="amount-column"><?= number_format($itemsAndServicesSubtotal, 2) ?></td>
                                    <td class="amount-column"><?= number_format($itemsAndServicesSubtotal, 2) ?></td>
                                </tr>

                                <!-- Professional Fees -->
                                <?php if (!empty($professionalFees)): ?>
                                <tr>
                                    <td colspan="6" class="fw-bold bg-light">Professional Fees</td>
                                </tr>
                                <?php foreach ($professionalFees as $fee): ?>
                                <tr>
                                    <td><?= htmlspecialchars($fee['professional_name']) ?><br><?= htmlspecialchars($fee['service_description']) ?></td>
                                    <td class="amount-column">1</td>
                                    <td class="amount-column"><?= number_format($fee['fee_amount'], 2) ?></td>
                                    <td class="amount-column"><?= number_format($fee['fee_amount'], 2) ?></td>
                                    <td class="amount-column"><?= number_format($fee['fee_amount'], 2) ?></td>
                                    <td class="amount-column"><?= number_format($fee['fee_amount'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>

                                <!-- Professional Fees Subtotal -->
                                <tr class="subtotal-row">
                                    <td colspan="3">Professional Fees Subtotal</td>
                                    <td class="amount-column"><?= number_format($billingRecord['total_professional_fees'], 2) ?></td>
                                    <td class="amount-column"><?= number_format($billingRecord['total_professional_fees'], 2) ?></td>
                                    <td class="amount-column"><?= number_format($billingRecord['total_professional_fees'], 2) ?></td>
                                </tr>
                                <?php endif; ?>

                                <!-- Grand Total -->
                                <tr class="total-row">
                                    <td colspan="3">Total</td>
                                    <td class="amount-column"><?= number_format($billingRecord['net_amount'], 2) ?></td>
                                    <td class="amount-column"><?= number_format($billingRecord['net_amount'], 2) ?></td>
                                    <td class="amount-column"><?= number_format($billingRecord['net_amount'], 2) ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Payments Section -->
                        <div class="mt-3">
                            <strong>Payments</strong>
                            <table class="fees-table mt-2">
                                <tr>
                                    <td>Hospital Bill</td>
                                    <td class="amount-column">₱<?= number_format($itemsAndServicesSubtotal, 2) ?></td>
                                </tr>
                                <tr>
                                    <td>Professional Fees</td>
                                    <td class="amount-column">₱<?= number_format($billingRecord['total_professional_fees'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td>Total Amount</td>
                                    <td class="amount-column">₱<?= number_format($billingRecord['net_amount'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td>Less: Discounts</td>
                                    <td class="amount-column">₱<?= number_format($billingRecord['total_discounts'], 2) ?></td>
                                </tr>
                                <tr class="fw-bold">
                                    <td>Net Amount Due</td>
                                    <td class="amount-column">₱<?= number_format($billingRecord['net_amount'] - $billingRecord['total_discounts'], 2) ?></td>
                                </tr>
                            </table>
                        </div>

                        <!-- HMO/GUARANTOR DETAILS -->
                        <div class="mt-4">
                            <table class="fees-table">
                                <tr>
                                    <th colspan="3" class="bg-light">HMO/GUARANTOR DETAILS</th>
                                </tr>
                                <tr>
                                    <th>Hospital Bill</th>
                                    <th>Professional Fee</th>
                                    <th>Total</th>
                                </tr>
                                <tr>
                                    <td class="amount-column">₱<?= number_format($itemsAndServicesSubtotal, 2) ?></td>
                                    <td class="amount-column">₱<?= number_format($billingRecord['total_professional_fees'], 2) ?></td>
                                    <td class="amount-column">₱<?= number_format($billingRecord['net_amount'], 2) ?></td>
                                </tr>
                            </table>
                        </div>

                        <!-- Balance Section -->
                        <div class="row mt-4">
                            <div class="col-md-12 text-end">
                                <strong>BALANCE DUE: </strong>₱<?= number_format($billingRecord['net_amount'] - $billingRecord['total_discounts'], 2) ?>
                            </div>
                        </div>

                        <!-- Signature Section -->
                        <div class="signature-section row mt-5">
                            <div class="col-md-6">
                                <div>Prepared by:</div>
                                <div class="signature-line"></div>
                                <div>Billing Clerk</div>
                            </div>
                            <div class="col-md-6">
                                <div>Conforme:</div>
                                <div class="signature-line"></div>
                                <div>Member/Patient/Authorized representative</div>
                                <div>(Signature over printed name)</div>
                            </div>
                        </div>

                        <!-- Back Button -->
                        <div class="mt-4">
                            <a href="billing.php" class="btn btn-secondary">Back to Billing Records</a>
                            <a href="generate_billing_pdf.php?billing_id=<?= $billing_id ?>" class="btn btn-primary" target="_blank">
                                <i class="fas fa-download"></i> Download PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
