<?php
session_start();
date_default_timezone_set('Asia/Manila'); // Set timezone to Manila
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection and activity log class
require '../connections/connections.php';
require '../activity_log.php';

$pdo = connection();
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Fetch all active staff for professional name dropdown
$all_staff = [];
$stmt = $pdo->prepare("SELECT staff_id, first_name, last_name FROM staff WHERE status = 'Active' ORDER BY first_name, last_name");
$stmt->execute();
$all_staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_billing'])) {

    // Retrieve header fields
    $transaction_id = $_POST['transaction_id'];
    $case_id = $_POST['case_id'];  // hidden field populated via transaction selection
    $service_amount = $_POST['service_amount'];
    $service_name = $_POST['service_name'];
    // If billing_date is not provided, use the current Manila timestamp
    $billing_date = isset($_POST['billing_date']) ? $_POST['billing_date'] : date("Y-m-d H:i:s", strtotime('now'));

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

    try {
        // Start a transaction
        $pdo->beginTransaction();

        // Insert billing header record with total_items
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
                }
            }
        }

        // Update inventory quantities and track deductions for logging
        $inventoryDeductions = [];
        if (!empty($item_ids)) {
            $updateInventoryQuery = "UPDATE inventory SET quantity = quantity - :quantity WHERE item_id = :item_id";
            $stmtUpdateInventory = $pdo->prepare($updateInventoryQuery);
            
            // Query to get item details for logging
            $itemDetailsQuery = $pdo->prepare("SELECT item_name, unit FROM inventory WHERE item_id = :item_id");
            
            for ($i = 0; $i < count($item_ids); $i++) {
                if (!empty($quantities[$i])) {
                    $stmtUpdateInventory->execute([
                        ':quantity' => $quantities[$i],
                        ':item_id' => $item_ids[$i]
                    ]);

                    // Get item details for logging
                    $itemDetailsQuery->execute([':item_id' => $item_ids[$i]]);
                    $itemDetails = $itemDetailsQuery->fetch();
                    if ($itemDetails) {
                        $inventoryDeductions[] = [
                            'item_name' => $itemDetails['item_name'],
                            'quantity' => $quantities[$i],
                            'unit' => $itemDetails['unit']
                        ];
                    }
                }
            }
        }

        // Update transaction status
        $updateTransactionQuery = "UPDATE medical_transactions 
                                 SET payment_status = 'Paid' 
                                 WHERE transaction_id = :transaction_id";
        $stmtUpdate = $pdo->prepare($updateTransactionQuery);
        $stmtUpdate->execute([':transaction_id' => $transaction_id]);

        // Commit the transaction
        $pdo->commit();

        // --- Retrieve Additional Data for Activity Log ---

        // Get patient name from patients table using case_id
        $patientQuery = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS patient_name FROM patients WHERE case_id = :case_id");
        $patientQuery->execute([':case_id' => $case_id]);
        $patientRow = $patientQuery->fetch();
        $patient_name = $patientRow ? $patientRow['patient_name'] : 'Unknown Patient';

        // Get user's full name for logging
        $userQuery = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS user_name FROM users WHERE user_id = :user_id");
        $userQuery->execute([':user_id' => $_SESSION['user_id']]);
        $userRow = $userQuery->fetch();
        $user_name = $userRow ? $userRow['user_name'] : 'Unknown User';

        // Log the billing activity
        $net_amount = $service_amount + $total_professional_fees + $total_items - $total_discounts;
        $billing_action = $user_name . " added billing record for " . $patient_name . " - " . $service_name . 
                         " with net amount of ₱" . number_format($net_amount, 2);

        // Log the activity using the ActivityLog class
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        $activityLog = new ActivityLog($pdo);
        $activityLog->logActivity($user_id, $billing_action);

        // Log inventory deductions separately
        if (!empty($inventoryDeductions)) {
            foreach ($inventoryDeductions as $deduction) {
                $inventory_action = $user_name . " deducted " . $deduction['item_name'] . 
                                  " on qty of " . $deduction['quantity'] . " " . $deduction['unit'] . 
                                  " for " . $patient_name . "'s billing";
                $activityLog->logActivity($user_id, $inventory_action);
            }
        }

        $_SESSION['message'] = "Billing record added successfully.";
        header('Location: view_billing.php?billing_id=' . $billing_id);
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to add billing record: " . $e->getMessage();
        header("Location: add_billing.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Billing Record</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/form.css">
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    /* Custom styles for dropdown suggestions */
    .suggestions-list {
      position: absolute;
      z-index: 1000;
      width: 50%;
      max-height: 200px;
      overflow-y: auto;
      background-color: white;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
    .suggestions-list .list-group-item {
      cursor: pointer;
    }
    .suggestions-list .list-group-item:hover {
      background-color: #f1f1f1;
    }
    .section-container {
      background-color: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    .discount-row, .professional-fee-row {
      background-color: white;
      padding: 10px;
      border-radius: 5px;
      margin-bottom: 10px;
    }
    .modal-content {
        border: none;
        border-radius: 15px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    
    .modal-header {
        border-radius: 15px 15px 0 0;
        padding: 1rem 1.5rem;
        background-color: #2E8B57 !important;
    }
    
    .table {
        margin-bottom: 0;
    }
    
    .table th {
        font-weight: 600;
        border-top: none;
        background-color: #f8f9fa;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .select-transaction {
        padding: 0.4rem 1rem;
        border-radius: 8px;
        transition: all 0.3s ease;
        background-color: #2E8B57 !important;
        border-color: #2E8B57 !important;
    }
    
    .select-transaction:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        background-color: #246B47 !important;
        border-color: #246B47 !important;
    }
    
    .pagination {
        margin-bottom: 0;
    }
    
    .pagination .page-link {
        border-radius: 5px;
        margin: 0 2px;
        color: #2E8B57;
    }
    
    .pagination .page-item.active .page-link {
        background-color: #2E8B57;
        border-color: #2E8B57;
    }

    .btn-outline-primary {
        color: #2E8B57;
        border-color: #2E8B57;
    }

    .btn-outline-primary:hover {
        background-color: #2E8B57;
        border-color: #2E8B57;
        color: white;
    }

    .btn-outline-primary:disabled {
        color: #6c757d;
        border-color: #6c757d;
    }

    .text-muted {
        color: #2E8B57 !important;
    }
  </style>
</head>
<body style="font-family: 'Poppins', sans-serif;">
  <div class="dashboard-container">
    <?php include '../sidebar.php'; ?>

    <main class="dashboard-main-content">
      <div class="container mt-5">
             <!-- Breadcrumb Navigation -->
             <?php include '../admin/breadcrumb.php'; ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2>Add Billing Record</h2>
          <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#pendingRecordsModal">
            Show Records
          </button>
        </div>

        <!-- Modal for Pending Records -->
        <div class="modal fade" id="pendingRecordsModal" tabindex="-1" aria-labelledby="pendingRecordsModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="pendingRecordsModalLabel">
                  <i class="fas fa-file-invoice me-2"></i>Pending Transactions
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="table-responsive">
                  <table class="table table-hover align-middle">
                    <thead class="table-light">
                      <tr>
                        <th>Transaction ID</th>
                        <th>Patient Name</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th class="text-center">Action</th>
                      </tr>
                    </thead>
                    <tbody id="pendingTransactionsList">
                      <!-- Records will be populated here via AJAX -->
                    </tbody>
                  </table>
                </div>
                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                  <div class="text-muted">
                    Showing <span id="currentPage">1</span> of <span id="totalPages">1</span> pages
                  </div>
                  <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0" id="pagination">
                      <!-- Pagination will be populated here -->
                    </ul>
                  </nav>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['message'])): ?>
          <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
          <?php unset($_SESSION['message']); ?>
        <?php elseif (isset($_SESSION['error'])): ?>
          <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
          <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form action="" method="POST" class="shadow p-4 bg-white rounded" id="addBillingForm">
          <!-- Patient Information -->
          <div class="section-container">
            <h5 class="mb-3">Patient Information</h5>
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="transaction_id" class="form-label">Transaction ID</label>
                <input type="text" class="form-control" name="transaction_id" id="transaction_id" readonly>
              </div>
              <div class="col-md-6">
                <label for="patient_name" class="form-label">Patient Name</label>
                <input type="text" class="form-control" name="patient_name" id="patient_name" readonly>
                <!-- Hidden field to store the actual case_id fetched from the transaction -->
                <input type="hidden" name="case_id" id="case_id">
              </div>
            </div>
          </div>

          <!-- Service Information -->
          <div class="section-container">
            <h5 class="mb-3">Service Information</h5>
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="service_name" class="form-label">Service Name</label>
                <input type="text" class="form-control" name="service_name" id="service_name" readonly>
              </div>
              <div class="col-md-6">
                <label for="service_amount" class="form-label">Service Amount</label>
                <input type="number" class="form-control" name="service_amount" id="service_amount" readonly>
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

          <input type="hidden" name="service_name" id="service_name_hidden">

          <button type="button" id="confirmBillingBtn" class="btn btn-success">Add Billing Record</button>
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
  <div class="modal fade" id="confirmBillingModal" tabindex="-1" aria-labelledby="confirmBillingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="confirmBillingModalLabel">
            <i class="fas fa-exclamation-triangle me-2"></i>Confirm Billing Record
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to add this billing record? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-success" id="confirmBillingModalYes">Yes, Add Record</button>
        </div>
      </div>
    </div>
  </div>

  <!-- JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Move function to global scope
    function fetchPendingTransactions(page = 1) {
      $.ajax({
        url: 'fetch_pending_transactions.php',
        method: 'GET',
        data: { page: page, limit: 8 },
        success: function(response) {
          if (response.error) {
            $('#pendingTransactionsList').html('<tr><td colspan="5" class="text-center text-danger">' + response.error + '</td></tr>');
            return;
          }

          const records = response.records;
          if (!records || records.length === 0) {
            $('#pendingTransactionsList').html('<tr><td colspan="5" class="text-center">No pending transactions found</td></tr>');
            return;
          }

          let html = '';
          records.forEach(record => {
            html += `
              <tr>
                <td>${record.transaction_id}</td>
                <td>${record.patient_name}</td>
                <td>${record.service_name}</td>
                <td>${record.transaction_date}</td>
                <td class="text-center">
                  <button class="btn btn-sm btn-primary select-transaction" 
                          data-id="${record.transaction_id}"
                          data-case="${record.case_id}"
                          data-service="${record.service_name}"
                          data-amount="${record.amount}"
                          data-patient_name="${record.patient_name}">
                    <i class="fas fa-check"></i> Select
                  </button>
                </td>
              </tr>
            `;
          });
          $('#pendingTransactionsList').html(html);

          // Update pagination
          updatePagination(response.page, response.total_pages);
        },
        error: function(xhr, status, error) {
          $('#pendingTransactionsList').html('<tr><td colspan="5" class="text-center text-danger">Error loading transactions</td></tr>');
          console.error('Error:', error);
        }
      });
    }

    function updatePagination(currentPage, totalPages) {
      let paginationHtml = `
        <div class="d-flex justify-content-between align-items-center">
          <div class="text-muted">
            Page ${currentPage} of ${totalPages}
          </div>
          <div>
            <button class="btn btn-sm btn-outline-primary me-2" 
                    onclick="fetchPendingTransactions(${currentPage - 1})"
                    ${currentPage === 1 ? 'disabled' : ''}>
              <i class="fas fa-chevron-left"></i> Previous
            </button>
            <button class="btn btn-sm btn-outline-primary" 
                    onclick="fetchPendingTransactions(${currentPage + 1})"
                    ${currentPage === totalPages ? 'disabled' : ''}>
              Next <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </div>
      `;
      $('#pagination').html(paginationHtml);
    }

    $(document).ready(function () {
      let currentPage = 1;
      const recordsPerPage = 8;
      let totalRecords = 0;

      // Initial fetch when modal is shown
      $('#pendingRecordsModal').on('show.bs.modal', function() {
        fetchPendingTransactions(1);
      });

      // Handle transaction selection
      $(document).on('click', '.select-transaction', function() {
        var transactionId = $(this).data('id');
        var serviceName = $(this).data('service');
        var serviceAmount = $(this).data('amount');
        var caseId = $(this).data('case');
        var patientName = $(this).data('patient_name');

        $('#transaction_id').val(transactionId);
        $('#service_name').val(serviceName);
        $('#service_name_hidden').val(serviceName);
        $('#service_amount').val(serviceAmount);
        $('#case_id').val(caseId);
        $('#patient_name').val(patientName);
        
        $('#pendingRecordsModal').modal('hide');
        updateTotals();
      });

      // --- Fetch Inventory Items for Modal (with Pagination) ---
      function fetchItems(page = 1, search = '') {
        $.get('fetch_for_billing.php', { action: 'fetch_items', page: page, search: search }, function (data) {
          var response = JSON.parse(data);
          $('#itemTable').empty();
          response.items.forEach(function (item) {
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

      $('#searchItem').on('input', function () {
        fetchItems(1, $(this).val());
      });

      $(document).on('click', '.page-link', function (e) {
        e.preventDefault();
        fetchItems($(this).data('page'), $('#searchItem').val());
      });

      // --- Handle Adding Items from Modal ---
      $(document).on('click', '.add-item', function (e) {
        e.preventDefault();
        var itemId = $(this).data('id');
        var itemName = $(this).data('name');
        var itemPrice = $(this).data('price');
        // Create a new row for the item with a default quantity of 1
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

      // --- Remove an Item Row ---
      $(document).on('click', '.remove-item', function () {
        $(this).closest('.item-row').remove();
        updateTotals();
      });

      // --- Update Total Amount on any Quantity Change ---
      $(document).on('input', '.item-quantity', function () {
        updateTotals();
      });

      // Professional Fees
      $('#addProfessionalFee').click(function() {
        const serviceName = $('#service_name').val();
        let options = `<option value="">Select Professional</option>`;
        <?php foreach ($all_staff as $staff):
          $fullName = $staff['first_name'] . ' ' . $staff['last_name']; ?>
          options += `<option value="<?= htmlspecialchars($fullName) ?>"><?= htmlspecialchars($fullName) ?></option>`;
        <?php endforeach; ?>
        const profFeeHtml = `
          <div class="professional-fee-row">
            <div class="row">
              <div class="col-md-4">
                <label class="form-label">Professional Name</label>
                <select class="form-control professional-select" name="professional_name[]" required>
                  ${options}
                </select>
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

      // Add event listener for professional select change
      $(document).on('change', '.professional-select', function() {
        if ($(this).val() === '') {
          $(this).addClass('is-invalid');
          $(this).after('<div class="invalid-feedback">Please select a professional.</div>');
        } else {
          $(this).removeClass('is-invalid');
          $(this).next('.invalid-feedback').remove();
        }
      });

      // Discounts
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

      // Update totals on input change
      $(document).on('input', '.prof-fee, .discount-amount, .item-quantity', function() {
        updateTotals();
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

      // Validation and Confirmation before submitting billing form
      $('#confirmBillingBtn').on('click', function(e) {
        e.preventDefault();
        
        // Reset previous validation states
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        
        let isValid = true;
        let firstInvalidField = null;

        // Check if transaction is selected
        if (!$('#transaction_id').val()) {
            $('#transaction_id').addClass('is-invalid');
            $('#transaction_id').after('<div class="invalid-feedback">Please select a transaction first.</div>');
            isValid = false;
            firstInvalidField = $('#transaction_id');
        }

        // Check if service amount is filled
        if (!$('#service_amount').val()) {
            $('#service_amount').addClass('is-invalid');
            $('#service_amount').after('<div class="invalid-feedback">Service amount is required.</div>');
            isValid = false;
            if (!firstInvalidField) firstInvalidField = $('#service_amount');
        }

        // Check if at least one professional fee is added
        if ($('.professional-fee-row').length === 0) {
            $('#professionalFeesList').addClass('is-invalid');
            $('#professionalFeesList').after('<div class="invalid-feedback">At least one professional fee is required.</div>');
            isValid = false;
            if (!firstInvalidField) firstInvalidField = $('#professionalFeesList');
        }

        // Check if all professional fees have valid selections and amounts
        $('.professional-fee-row').each(function() {
            const profSelect = $(this).find('select[name="professional_name[]"]');
            const feeAmount = $(this).find('.prof-fee');
            
            // Check if "Select Professional" is chosen
            if (profSelect.val() === '') {
                profSelect.addClass('is-invalid');
                profSelect.after('<div class="invalid-feedback">Please select a professional.</div>');
                isValid = false;
                if (!firstInvalidField) firstInvalidField = profSelect;
            }
            
            // Check if fee amount is filled
            if (!feeAmount.val()) {
                feeAmount.addClass('is-invalid');
                feeAmount.after('<div class="invalid-feedback">Fee amount is required.</div>');
                isValid = false;
                if (!firstInvalidField) firstInvalidField = feeAmount;
            }
        });

        // If all validations pass, show the confirmation modal
        if (isValid) {
            $('#confirmBillingModal').modal('show');
        } else {
            // Scroll to the first invalid field
            if (firstInvalidField) {
                $('html, body').animate({
                    scrollTop: firstInvalidField.offset().top - 100
                }, 500);
            }
        }
      });

      $('#confirmBillingModalYes').on('click', function() {
        $('form#addBillingForm').append('<input type="hidden" name="add_billing" value="1">');
        $('form#addBillingForm')[0].submit();
      });
    });
  </script>
</body>
</html>
