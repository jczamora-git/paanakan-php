<?php
// Start session and check if the user is logged in as admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require '../connections/connections.php';
$pdo = connection();

// Get patient ID and record ID
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : null;
$record_id = isset($_GET['record_id']) ? intval($_GET['record_id']) : null;

// Initialize variables for pre-filled data
$patient_name = null;
$admission_date = $admitting_physician = $admitting_diagnosis = null;
$discharge_date = $discharge_diagnosis = $discharge_condition = $disposition = null;
$complications = $surgical_procedure = $pathological_report = null;

// Ensure patient_id and record_id are provided
if (!$patient_id || !$record_id) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: manage_health_records.php");
    exit();
}

// Fetch record details from `admissions`
$query = "SELECT * FROM admissions WHERE admission_id = :record_id AND patient_id = :patient_id";
$stmt = $pdo->prepare($query);
$stmt->execute([':record_id' => $record_id, ':patient_id' => $patient_id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    $_SESSION['error'] = "Record not found.";
    header("Location: manage_health_records.php");
    exit();
}

// Fetch patient details
$query = "SELECT case_id, CONCAT(first_name, ' ', last_name) AS fullname FROM patients WHERE patient_id = :patient_id";
$stmt = $pdo->prepare($query);
$stmt->execute([':patient_id' => $patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if ($patient) {
    $case_id = $patient['case_id'];
    $patient_name = $patient['fullname'];
} else {
    $_SESSION['error'] = "Patient not found.";
    header("Location: manage_health_records.php");
    exit();
}

// Fetch the Admission transaction for this admission
$transaction_id = null;
$stmt = $pdo->prepare("SELECT transaction_id FROM medical_transactions WHERE admission_id = :admission_id AND service_id = 10 LIMIT 1");
$stmt->execute([':admission_id' => $record_id]);
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);
if ($transaction) {
    $transaction_id = $transaction['transaction_id'];
} else {
    $_SESSION['error'] = "No Admission transaction found for this admission.";
    header("Location: manage_health_records.php");
    exit();
}

// Pre-fill record data
$admission_date = $record['admission_date'];
$admitting_physician = $record['admitting_physician'];
$admitting_diagnosis = $record['admitting_diagnosis'];

// Fetch all active staff for professional name dropdown
$all_staff = [];
$stmt = $pdo->prepare("SELECT staff_id, first_name, last_name FROM staff WHERE status = 'Active' ORDER BY first_name, last_name");
$stmt->execute();
$all_staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Process billing first
        if (isset($_POST['add_billing'])) {
            $service_amount = $_POST['service_amount'];
            $service_name = "Admission"; // Fixed service name
            $billing_date = date("Y-m-d H:i:s");

            // Process professional fees
            $professional_names = $_POST['professional_name'] ?? [];
            $service_descriptions = $_POST['service_description'] ?? [];
            $fee_amounts = $_POST['fee_amount'] ?? [];
            
            $total_professional_fees = 0;
            for ($i = 0; $i < count($professional_names); $i++) {
                $total_professional_fees += floatval($fee_amounts[$i]);
            }

            // Process discounts
            $discount_amounts = $_POST['discount_amount'] ?? [];
            $total_discounts = 0;
            foreach ($discount_amounts as $amount) {
                $total_discounts += floatval($amount);
            }

            // Process billing items
            $item_ids = $_POST['item_id'] ?? [];
            $quantities = $_POST['quantity'] ?? [];
            $item_prices = $_POST['item_amount'] ?? [];

            // Calculate total items amount
            $total_items = 0;
            for ($i = 0; $i < count($item_ids); $i++) {
                $total_items += floatval($item_prices[$i]) * intval($quantities[$i]);
            }

            // Insert billing header
            $headerQuery = "INSERT INTO billing_header (
                case_id, 
                transaction_id, 
                service_amount, 
                total_professional_fees,
                total_discounts,
                total_items,
                billing_date
            ) VALUES (
                :case_id, 
                :transaction_id, 
                :service_amount, 
                :total_professional_fees,
                :total_discounts,
                :total_items,
                :billing_date
            )";
            
            $stmt = $pdo->prepare($headerQuery);
            $stmt->execute([
                ':case_id' => $case_id,
                ':transaction_id' => $transaction_id,
                ':service_amount' => $service_amount,
                ':total_professional_fees' => $total_professional_fees,
                ':total_discounts' => $total_discounts,
                ':total_items' => $total_items,
                ':billing_date' => $billing_date
            ]);

            $billing_id = $pdo->lastInsertId();

            // Insert professional fees
            if (!empty($professional_names)) {
                $profQuery = "INSERT INTO professional_fees (
                    billing_id, 
                    professional_name, 
                    service_description, 
                    fee_amount
                ) VALUES (
                    :billing_id, 
                    :professional_name, 
                    :service_description, 
                    :fee_amount
                )";
                
                $stmtProf = $pdo->prepare($profQuery);

                for ($i = 0; $i < count($professional_names); $i++) {
                    if (!empty($fee_amounts[$i])) {
                        $stmtProf->execute([
                            ':billing_id' => $billing_id,
                            ':professional_name' => $professional_names[$i],
                            ':service_description' => $service_descriptions[$i],
                            ':fee_amount' => $fee_amounts[$i]
                        ]);
                    }
                }
            }

            // Insert billing items
            if (!empty($item_ids)) {
                $itemQuery = "INSERT INTO billing_items (
                    billing_id, 
                    item_id, 
                    quantity, 
                    item_price
                ) VALUES (
                    :billing_id, 
                    :item_id, 
                    :quantity, 
                    :item_price
                )";
                
                $stmtItem = $pdo->prepare($itemQuery);

                for ($i = 0; $i < count($item_ids); $i++) {
                    if (!empty($quantities[$i])) {
                        $stmtItem->execute([
                            ':billing_id' => $billing_id,
                            ':item_id' => $item_ids[$i],
                            ':quantity' => $quantities[$i],
                            ':item_price' => $item_prices[$i]
                        ]);
                        // Deduct from inventory
                        $updateInventory = $pdo->prepare("UPDATE inventory SET quantity = quantity - :qty WHERE item_id = :item_id");
                        $updateInventory->execute([
                            ':qty' => $quantities[$i],
                            ':item_id' => $item_ids[$i]
                        ]);
                    }
                }
            }

            // Update the amount and payment_status in the medical_transactions table for this admission
            $updateTransaction = $pdo->prepare("UPDATE medical_transactions SET amount = :amount, payment_status = 'Paid' WHERE transaction_id = :transaction_id");
            $updateTransaction->execute([
                ':amount' => $service_amount,
                ':transaction_id' => $transaction_id
            ]);
        }

        // Then process discharge
        $discharge_date = $_POST['discharge_date'] ?: null;
        $discharge_diagnosis = $_POST['discharge_diagnosis'] ?: null;
        $discharge_condition = $_POST['discharge_condition'] ?: null;
        $disposition = $_POST['disposition'] ?: null;
        $complications = $_POST['complications'] ?: null;
        $surgical_procedure = $_POST['surgical_procedure'] ?: null;
        $pathological_report = $_POST['pathological_report'] ?: null;

        $query = "
            UPDATE admissions SET
                discharge_date = :discharge_date,
                discharge_diagnosis = :discharge_diagnosis,
                discharge_condition = :discharge_condition,
                disposition = :disposition,
                complications = :complications,
                surgical_procedure = :surgical_procedure,
                pathological_report = :pathological_report
            WHERE admission_id = :record_id
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':discharge_date' => $discharge_date,
            ':discharge_diagnosis' => $discharge_diagnosis,
            ':discharge_condition' => $discharge_condition,
            ':disposition' => $disposition,
            ':complications' => $complications,
            ':surgical_procedure' => $surgical_procedure,
            ':pathological_report' => $pathological_report,
            ':record_id' => $record_id,
        ]);

        // Set the room as available after discharge
        $updateRoom = $pdo->prepare("UPDATE rooms SET status = 'Available', case_id = NULL WHERE case_id = :case_id");
        $updateRoom->execute([':case_id' => $case_id]);

        $pdo->commit();
        $_SESSION['message'] = "Patient discharged and billing completed successfully.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to process discharge and billing: " . $e->getMessage();
    }

    // Redirect back to patient records
    header("Location: patient_health_records.php?patient_id=$patient_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discharge Patient</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2E8B57;
            --primary-light: #3CB371;
            --secondary-color: #f8f9fa;
            --text-color: #333;
            --border-color: #eee;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .dashboard-main-content {
            flex-grow: 1;
            padding: 20px;
            margin-left: 270px;
            transition: margin-left 0.4s ease;
            background-color: #f8f9fa;
        }

        .sidebar.collapsed ~ .dashboard-main-content {
            margin-left: 85px;
        }

        .patient-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .patient-header h2 {
            font-weight: 600;
            margin-bottom: 10px;
        }

        .patient-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .section-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 25px;
        }

        .section-container h5 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
        }

        .form-label {
            font-weight: 500;
            color: #444;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 8px 12px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.25);
        }

        .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%232E8B57' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        }

        textarea.form-control {
            min-height: 100px;
        }

        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 12px 30px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background-color: var(--primary-light);
            border-color: var(--primary-light);
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 15px 20px;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .discharge-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .form-floating > .form-control {
            padding-top: 1.625rem;
            padding-bottom: 0.625rem;
        }

        .form-floating > label {
            padding: 1rem 0.75rem;
        }

        .admission-info {
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .admission-info p {
            margin: 0;
            font-size: 0.95rem;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include '../sidebar.php'; ?>

        <main class="dashboard-main-content">
            <?php include '../admin/breadcrumb.php'; ?>
            
            <div class="container">
                <!-- Patient Header Section -->
                <div class="patient-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2><i class="fas fa-hospital-user me-2"></i>Discharge Patient</h2>
                            <?php if ($patient_name): ?>
                            <p class="mb-0">
                                <i class="fas fa-user me-2"></i>Patient: <?= htmlspecialchars($patient_name) ?>
                            </p>
                            <div class="admission-info">
                                <p><i class="fas fa-calendar-check me-2"></i>Admission Date: <?= date('F j, Y g:i A', strtotime($admission_date)) ?></p>
                                <p><i class="fas fa-user-md me-2"></i>Admitting Physician: <?= htmlspecialchars($admitting_physician) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a href="javascript:history.back()" class="btn btn-light btn-lg">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?= $_SESSION['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php elseif (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Discharge Form -->
                <form action="" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="add_billing" value="1">
                    <!-- Discharge Details -->
                    <div class="section-container">
                        <h5><i class="fas fa-clipboard-check me-2"></i>Discharge Details</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="datetime-local" name="discharge_date" id="discharge_date" class="form-control" required>
                                    <label for="discharge_date">Discharge Date and Time</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select name="discharge_condition" id="discharge_condition" class="form-select" required>
                                        <option value="">Select Condition...</option>
                                        <option value="Recovered">Recovered</option>
                                        <option value="Improved">Improved</option>
                                        <option value="Unimproved">Unimproved</option>
                                        <option value="Died">Died</option>
                                    </select>
                                    <label for="discharge_condition">Discharge Condition</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select name="disposition" id="disposition" class="form-select" required>
                                        <option value="">Select Disposition...</option>
                                        <option value="Discharged">Discharged</option>
                                        <option value="Transferred">Transferred</option>
                                        <option value="Home Against Medical Advice">Home Against Medical Advice</option>
                                        <option value="Absconded">Absconded</option>
                                    </select>
                                    <label for="disposition">Disposition</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea name="discharge_diagnosis" id="discharge_diagnosis" class="form-control" style="height: 100px" required></textarea>
                                    <label for="discharge_diagnosis">Discharge Diagnosis</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Section -->
                    <div class="section-container">
                        <h5><i class="fas fa-file-invoice-dollar me-2"></i>Billing Details</h5>
                        
                        <!-- Hidden Patient Information -->
                        <input type="hidden" name="transaction_id" value="<?= $transaction_id ?>">
                        <input type="hidden" name="case_id" value="<?= $case_id ?>">
                        <input type="hidden" name="patient_name" value="<?= htmlspecialchars($patient_name) ?>">

                        <!-- Service Information -->
                        <div class="section-container">
                            <h5 class="mb-3">Service Information</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="service_name" class="form-label">Service Name</label>
                                    <input type="text" class="form-control" name="service_name" id="service_name" value="Admission" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="service_amount" class="form-label">Service Amount</label>
                                    <input type="number" class="form-control" name="service_amount" id="service_amount" required>
                                </div>
                            </div>
                        </div>

                        <!-- Professional Fees -->
                        <div class="section-container">
                            <h5 class="mb-3">Professional Fees</h5>
                            <button type="button" class="btn btn-success mb-3" id="addProfessionalFee">
                                Add Professional Fee
                            </button>
                            <div id="professionalFeesList"></div>
                        </div>

                        <!-- Item Information -->
                        <div class="section-container">
                            <h5 class="mb-3">Item Information</h5>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addItemModal">Add Item</button>
                            <div id="itemList" class="mt-3"></div>
                        </div>

                        <!-- Discounts -->
                        <div class="section-container">
                            <h5 class="mb-3">Discounts</h5>
                            <button type="button" class="btn btn-success mb-2" id="addDiscount">
                                Add Discount
                            </button>
                            <div id="discountsList"></div>
                        </div>

                        <!-- Total Amount -->
                        <div class="section-container">
                            <h5 class="mb-3">Summary</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="form-label">Service Amount</label>
                                    <input type="number" class="form-control" id="summary_service_amount" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Professional Fees</label>
                                    <input type="number" class="form-control" id="summary_professional_fees" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Items Total</label>
                                    <input type="number" class="form-control" id="summary_items_total" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Total Discounts</label>
                                    <input type="number" class="form-control" id="summary_discounts" readonly>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <label class="form-label">Net Amount</label>
                                    <input type="number" class="form-control" id="net_amount" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4 mb-5">
                        <button type="button" id="confirmDischargeBtn" class="btn btn-success btn-lg px-5">
                            <i class="fas fa-check-circle me-2"></i>Process Discharge & Billing
                        </button>
                        <a href="javascript:history.back()" class="btn btn-secondary btn-lg px-5 ms-2">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header text-white" style="background-color: #2E8B57;">
                    <h5 class="modal-title">Select Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" id="searchItem" class="form-control mb-3" placeholder="Search by Category or Item Name">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="itemTable">
                        </tbody>
                    </table>
                    <div id="pagination" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmDischargeModal" tabindex="-1" aria-labelledby="confirmDischargeModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title" id="confirmDischargeModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Discharge & Billing</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to process this discharge and billing? This action cannot be undone.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-success" id="confirmDischargeModalYes">Yes, Process</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    
    <!-- Form Validation Script -->
    <script>
        (() => {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()

        // Billing Form JavaScript
        $(document).ready(function() {
            // Fetch inventory items
            function fetchItems(page = 1, search = '') {
                $.get('fetch_for_billing.php', { action: 'fetch_items', page: page, search: search }, function(data) {
                    var response = JSON.parse(data);
                    $('#itemTable').empty();
                    response.items.forEach(function(item) {
                        $('#itemTable').append(
                            '<tr>' +
                                '<td>' + item.item_name + '</td>' +
                                '<td>' + item.category + '</td>' +
                                '<td>₱' + item.price + '</td>' +
                                '<td><a href="#" class="btn btn-sm btn-add-item add-item" data-id="' + item.item_id + '" data-name="' + item.item_name + '" data-price="' + item.price + '" style="background-color: #2E8B57; border-color: #2E8B57; color: white;"><i class="fas fa-plus"></i></a></td>' +
                            '</tr>'
                        );
                    });
                    $('#pagination').html(response.pagination);
                });
            }

            // Add Professional Fee
            $('#addProfessionalFee').click(function() {
                const serviceName = $('#service_name').val();
                let options = `<option value=''>Select Professional</option>`;
                <?php foreach ($all_staff as $staff):
                    $fullName = $staff['first_name'] . ' ' . $staff['last_name']; ?>
                    options += `<option value="<?= htmlspecialchars($fullName) ?>"><?= htmlspecialchars($fullName) ?></option>`;
                <?php endforeach; ?>
                const profFeeHtml = `
                    <div class="professional-fee-row">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Professional Name</label>
                                <select class="form-control" name="professional_name[]" required>${options}</select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Service Description</label>
                                <input type="text" class="form-control" name="service_description[]" value="${serviceName}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fee Amount</label>
                                <input type="number" class="form-control prof-fee" name="fee_amount[]" required>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-danger remove-prof-fee form-control">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                $('#professionalFeesList').append(profFeeHtml);
                updateTotals();
            });

            // Add Discount
            $('#addDiscount').click(function() {
                const discountHtml = `
                    <div class="discount-row">
                        <div class="row">
                            <div class="col-md-10">
                                <label class="form-label">Discount Amount</label>
                                <input type="number" class="form-control discount-amount" name="discount_amount[]" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-danger remove-discount form-control">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                $('#discountsList').append(discountHtml);
                updateTotals();
            });

            // Remove buttons
            $(document).on('click', '.remove-prof-fee', function() {
                $(this).closest('.professional-fee-row').remove();
                updateTotals();
            });

            $(document).on('click', '.remove-discount', function() {
                $(this).closest('.discount-row').remove();
                updateTotals();
            });

            $(document).on('click', '.remove-item', function() {
                $(this).closest('.item-row').remove();
                updateTotals();
            });

            // Add Item from Modal
            $(document).on('click', '.add-item', function(e) {
                e.preventDefault();
                var itemId = $(this).data('id');
                var itemName = $(this).data('name');
                var itemPrice = $(this).data('price');
                var newRow = '<div class="row item-row mb-2" data-id="' + itemId + '">' +
                               '<input type="hidden" name="item_id[]" value="' + itemId + '">' +
                               '<div class="col-md-5">' +
                                 '<input type="text" class="form-control" name="item_name[]" value="' + itemName + '" readonly>' +
                               '</div>' +
                               '<div class="col-md-3">' +
                                 '<input type="number" class="form-control" name="item_amount[]" value="' + itemPrice + '" readonly>' +
                               '</div>' +
                               '<div class="col-md-3">' +
                                 '<input type="number" class="form-control item-quantity" name="quantity[]" value="1" required>' +
                               '</div>' +
                               '<div class="col-md-1">' +
                                 '<button type="button" class="btn btn-danger btn-sm remove-item"><i class="fas fa-trash"></i></button>' +
                               '</div>' +
                             '</div>';
                $('#itemList').append(newRow);
                $('#addItemModal').modal('hide');
                updateTotals();
            });

            // Update totals on input change
            $(document).on('input', '.prof-fee, .discount-amount, .item-quantity', function() {
                updateTotals();
            });

            // Search items
            $('#searchItem').on('input', function() {
                fetchItems(1, $(this).val());
            });

            // Pagination
            $(document).on('click', '.page-link', function(e) {
                e.preventDefault();
                fetchItems($(this).data('page'), $('#searchItem').val());
            });

            // Update totals function
            function updateTotals() {
                // Service amount
                const serviceAmount = parseFloat($('#service_amount').val()) || 0;
                $('#summary_service_amount').val(serviceAmount.toFixed(2));
                
                // Professional fees
                let totalProfFees = 0;
                $('.prof-fee').each(function() {
                    totalProfFees += parseFloat($(this).val()) || 0;
                });
                $('#summary_professional_fees').val(totalProfFees.toFixed(2));
                
                // Items total
                let itemsTotal = 0;
                $('.item-row').each(function() {
                    const price = parseFloat($(this).find('input[name="item_amount[]"]').val()) || 0;
                    const quantity = parseInt($(this).find('.item-quantity').val()) || 0;
                    itemsTotal += (price * quantity);
                });
                $('#summary_items_total').val(itemsTotal.toFixed(2));
                
                // Total discounts
                let totalDiscounts = 0;
                $('.discount-amount').each(function() {
                    totalDiscounts += parseFloat($(this).val()) || 0;
                });
                $('#summary_discounts').val(totalDiscounts.toFixed(2));
                
                // Calculate net amount
                const netAmount = serviceAmount + totalProfFees + itemsTotal - totalDiscounts;
                $('#net_amount').val(netAmount.toFixed(2));
            }

            // Initially fetch inventory items
            fetchItems();
            // Initial update of totals
            updateTotals();

            // Confirmation before submitting discharge form
            $('#confirmDischargeBtn').on('click', function(e) {
                e.preventDefault();
                
                // Reset previous validation states
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();
                
                let isValid = true;
                let firstInvalidField = null;

                // Validate Discharge Details
                const dischargeDate = $('#discharge_date').val();
                const dischargeCondition = $('#discharge_condition').val();
                const disposition = $('#disposition').val();
                const dischargeDiagnosis = $('#discharge_diagnosis').val();

                if (!dischargeDate) {
                    $('#discharge_date').addClass('is-invalid');
                    $('#discharge_date').after('<div class="invalid-feedback">Please select discharge date and time</div>');
                    isValid = false;
                    firstInvalidField = firstInvalidField || $('#discharge_date');
                }

                if (!dischargeCondition) {
                    $('#discharge_condition').addClass('is-invalid');
                    $('#discharge_condition').after('<div class="invalid-feedback">Please select discharge condition</div>');
                    isValid = false;
                    firstInvalidField = firstInvalidField || $('#discharge_condition');
                }

                if (!disposition) {
                    $('#disposition').addClass('is-invalid');
                    $('#disposition').after('<div class="invalid-feedback">Please select disposition</div>');
                    isValid = false;
                    firstInvalidField = firstInvalidField || $('#disposition');
                }

                if (!dischargeDiagnosis.trim()) {
                    $('#discharge_diagnosis').addClass('is-invalid');
                    $('#discharge_diagnosis').after('<div class="invalid-feedback">Please enter discharge diagnosis</div>');
                    isValid = false;
                    firstInvalidField = firstInvalidField || $('#discharge_diagnosis');
                }

                // Validate Billing Details
                const serviceAmount = $('#service_amount').val();
                if (!serviceAmount || parseFloat(serviceAmount) <= 0) {
                    $('#service_amount').addClass('is-invalid');
                    $('#service_amount').after('<div class="invalid-feedback">Please enter a valid service amount</div>');
                    isValid = false;
                    firstInvalidField = firstInvalidField || $('#service_amount');
                }

                // Check if at least one professional fee is added
                if ($('.professional-fee-row').length === 0) {
                    $('#professionalFeesList').addClass('is-invalid');
                    $('#professionalFeesList').after('<div class="invalid-feedback">Please add at least one professional fee</div>');
                    isValid = false;
                    firstInvalidField = firstInvalidField || $('#addProfessionalFee');
                }

                // Validate professional fees
                $('.prof-fee').each(function() {
                    const feeAmount = $(this).val();
                    if (!feeAmount || parseFloat(feeAmount) <= 0) {
                        $(this).addClass('is-invalid');
                        $(this).after('<div class="invalid-feedback">Please enter a valid fee amount</div>');
                        isValid = false;
                        firstInvalidField = firstInvalidField || $(this);
                    }
                });

                // Validate item quantities
                $('.item-quantity').each(function() {
                    const quantity = $(this).val();
                    if (!quantity || parseInt(quantity) <= 0) {
                        $(this).addClass('is-invalid');
                        $(this).after('<div class="invalid-feedback">Please enter a valid quantity</div>');
                        isValid = false;
                        firstInvalidField = firstInvalidField || $(this);
                    }
                });

                // Validate discount amounts if any are added
                $('.discount-amount').each(function() {
                    const discountAmount = $(this).val();
                    if (!discountAmount || parseFloat(discountAmount) < 0) {
                        $(this).addClass('is-invalid');
                        $(this).after('<div class="invalid-feedback">Please enter a valid discount amount</div>');
                        isValid = false;
                        firstInvalidField = firstInvalidField || $(this);
                    }
                });

                if (isValid) {
                    // Check if net amount is valid
                    const netAmount = parseFloat($('#net_amount').val());
                    if (netAmount <= 0) {
                        $('#net_amount').addClass('is-invalid');
                        $('#net_amount').after('<div class="invalid-feedback">Total amount must be greater than zero</div>');
                        isValid = false;
                        firstInvalidField = firstInvalidField || $('#net_amount');
                    } else {
                        // Show confirmation modal if all validations pass
                        $('#confirmDischargeModal').modal('show');
                    }
                }

                // Scroll to first invalid field if any
                if (firstInvalidField) {
                    $('html, body').animate({
                        scrollTop: firstInvalidField.offset().top - 100
                    }, 500);
                }
            });

            // Remove invalid state on input
            $(document).on('input', '.form-control, .form-select', function() {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            });

            $('#confirmDischargeModalYes').on('click', function() {
                $('form.needs-validation')[0].submit();
            });
        });
    </script>
</body>
</html>
