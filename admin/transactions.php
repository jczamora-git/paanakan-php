<?php
// Start session and check if the user is logged in as admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require '../connections/connections.php';
require '../activity_log.php';

$con = connection();
$con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Create an instance of ActivityLog
$activityLog = new ActivityLog($con);

// Fetch all services from medical_services grouped by category
$categoriesQuery = "SELECT DISTINCT category FROM medical_services";
$categories = $con->query($categoriesQuery)->fetchAll();

$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total count of pending transactions
$totalCountQuery = "SELECT COUNT(*) FROM medical_transactions WHERE payment_status = 'Pending'";
$totalCountResult = $con->query($totalCountQuery);
$totalCount = $totalCountResult->fetchColumn();
$totalPages = ceil($totalCount / $limit);

// Update transactions query to include only pending transactions and exclude service_id = 10
$transactionsQuery = "
    SELECT t.transaction_id, t.case_id, t.transaction_date, s.service_name, t.amount, t.payment_status,
           CONCAT(p.first_name, ' ', p.last_name) as patient_name
    FROM medical_transactions t
    LEFT JOIN medical_services s ON t.service_id = s.service_id
    LEFT JOIN patients p ON t.case_id = p.case_id
    WHERE t.payment_status = 'Pending' AND t.service_id != 10
    ORDER BY t.transaction_date DESC
    LIMIT :limit OFFSET :offset
";
$transactionsStmt = $con->prepare($transactionsQuery);
$transactionsStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$transactionsStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$transactionsStmt->execute();
$transactions = $transactionsStmt->fetchAll();

// If no transactions, initialize as an empty array
if (!$transactions) {
    $transactions = [];
}

// Fetch services for each category
$servicesQuery = "SELECT * FROM medical_services WHERE category = :category";

// Handle form submissions for transactions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_transaction'])) {
    try {
        // Get user's full name for logging
        $userQuery = $con->prepare("SELECT CONCAT(first_name, ' ', last_name) AS user_name FROM users WHERE user_id = :user_id");
        $userQuery->execute([':user_id' => $_SESSION['user_id']]);
        $userRow = $userQuery->fetch();
        $user_name = $userRow ? $userRow['user_name'] : 'Unknown User';

        // Get patient name for logging
        $patientQuery = $con->prepare("SELECT CONCAT(first_name, ' ', last_name) AS patient_name FROM patients WHERE case_id = :case_id");
        $patientQuery->execute([':case_id' => $_POST['case_id']]);
        $patientRow = $patientQuery->fetch();
        $patient_name = $patientRow ? $patientRow['patient_name'] : 'Unknown Patient';

        // Get service name for logging
        $serviceQuery = $con->prepare("SELECT service_name FROM medical_services WHERE service_id = :service_id");
        $serviceQuery->execute([':service_id' => $_POST['service_id']]);
        $serviceRow = $serviceQuery->fetch();
        $service_name = $serviceRow ? $serviceRow['service_name'] : 'Unknown Service';

        // Insert the transaction
        $stmt = $con->prepare("INSERT INTO medical_transactions (case_id, service_id, transaction_date, amount, payment_status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['case_id'],
            $_POST['service_id'],
            $_POST['transaction_date'],
            $_POST['amount'],
            $_POST['payment_status']
        ]);

        // Log the activity with user's full name and patient's full name
        $action_desc = $user_name . " created transaction for " . $patient_name . " (" . $_POST['case_id'] . ") – " . 
                      $service_name . ", ₱" . number_format($_POST['amount'], 2) . ", Payment: " . $_POST['payment_status'];
        $activityLog->logActivity($_SESSION['user_id'], $action_desc);

        $_SESSION['success'] = "Transaction added successfully.";
        header("Location: transactions.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding transaction: " . $e->getMessage();
        header("Location: transactions.php");
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/services.css">
    <style>
        /* Existing styles */
        
        /* Patient Search Styles */
        .list-group {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .list-group-item {
            cursor: pointer;
            padding: 8px 12px;
            border: none;
            border-bottom: 1px solid #eee;
        }
        
        .list-group-item:last-child {
            border-bottom: none;
        }
        
        .list-group-item:hover {
            background-color: #f8f9fa;
        }
        
        .list-group-item .text-muted {
            font-size: 0.9em;
        }
        
        .form-text.text-success {
            margin-top: 4px;
            font-size: 0.9em;
        }
    </style>
</head>

<body style="font-family: 'Poppins', sans-serif;">
    <div class="dashboard-container">
        <?php include '../sidebar.php'; ?>

        <!-- Main Content -->
        <main class="dashboard-main-content">
            <div class="container mt-5">
                <?php 
                    if (isset($_SESSION['success'])) {
                        echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                        unset($_SESSION['success']);
                    }

                    if (isset($_SESSION['error'])) {
                        echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                        unset($_SESSION['error']);
                    }
                ?>
                
                <!-- Breadcrumb Navigation -->
                <?php include '../admin/breadcrumb.php'; ?>

                <!-- Settings Button -->
                <div class="d-flex justify-content-end mb-4">
                    <a href="manage_services.php" class="btn btn-outline-success" title="Manage Services">
                        <i class="fas fa-cog"></i> Manage Services
                    </a>
                </div>

                <!-- Category Cards -->
                <div class="row g-3">
                    <?php foreach ($categories as $category): ?>
                        <?php 
                            $categoryClass = strtolower(str_replace(" ", "-", $category['category']));
                            // Skip Admission category
                            if (strtolower($category['category']) === 'admission') continue;
                        ?>
                        <div class="category-card <?= $categoryClass ?>" data-bs-toggle="modal" data-bs-target="#transactionModal<?= $categoryClass ?>">
                            <img src="../img/<?= $categoryClass ?>.svg" alt="<?= $category['category'] ?>">
                            <div>
                                <h5 class="card-title"><?= htmlspecialchars($category['category']) ?></h5>
                                <p class="card-text">Services for <?= htmlspecialchars($category['category']) ?></p>
                            </div>
                        </div>

                        <!-- Modal for each category -->
                        <div class="modal fade" id="transactionModal<?= $categoryClass ?>" tabindex="-1" aria-labelledby="transactionModalLabel<?= $categoryClass ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="transactionModalLabel<?= $categoryClass ?>"><?= htmlspecialchars($category['category']) ?> Services</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form action="process_transaction.php" method="POST">
                                            <?php
                                            $stmt = $con->prepare($servicesQuery);
                                            $stmt->execute([':category' => $category['category']]);
                                            $services = $stmt->fetchAll();
                                            ?>
                                            <div class="mb-3">
                                                <label for="service_type" class="form-label">Service Type</label>
                                                <select class="form-select" name="service_id" id="service_type_<?= $categoryClass ?>" required>
                                                    <?php foreach ($services as $service): ?>
                                                        <option value="<?= $service['service_id'] ?>" data-price="<?= $service['price'] ?>"><?= $service['service_name'] ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3 position-relative">
                                                <label for="case_id" class="form-label">Case ID or Patient Name</label>
                                                <input type="text" class="form-control" name="case_id" id="case_id_<?= $categoryClass ?>" required placeholder="Enter Case ID or Patient Name" autocomplete="off">
                                                <div id="caseIdSuggestions_<?= $categoryClass ?>" class="list-group position-absolute w-100" style="z-index: 1050; display: none;"></div>
                                                <div id="selectedPatientName_<?= $categoryClass ?>" class="form-text text-success"></div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="transaction_date" class="form-label">Transaction Date</label>
                                                <input type="datetime-local" class="form-control" name="transaction_date" id="transaction_date_<?= $categoryClass ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="amount" class="form-label">Amount</label>
                                                <input type="number" step="0.01" class="form-control" name="amount" id="amount_<?= $categoryClass ?>" readonly>
                                            </div>
                                            <input type="hidden" name="payment_status" value="Pending">
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="submit_transaction" class="btn btn-primary">Make Transaction</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pending Transactions Table -->
                <div class="mt-5 table-container shadow rounded bg-white p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4>Pending Transactions</h4>
                        <a href="transaction_history.php" class="btn btn-success">
                            <i class="fas fa-history"></i> Show All Transactions
                        </a>
                    </div>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Case ID</th>
                                <th>Patient Name</th>
                                <th>Transaction Date</th>
                                <th>Service Name</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No pending transactions found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $index => $transaction): ?>
                                    <tr>
                                        <td><?= (($page - 1) * $limit) + $index + 1 ?></td>
                                        <td><?= htmlspecialchars($transaction['case_id']) ?></td>
                                        <td><?= htmlspecialchars($transaction['patient_name']) ?></td>
                                        <td><?= (new DateTime($transaction['transaction_date']))->format('F j, Y g:i a') ?></td>
                                        <td><?= htmlspecialchars($transaction['service_name']) ?></td>
                                        <td>₱<?= number_format($transaction['amount'], 2) ?></td>
                                        <td>
                                            <span class="badge <?= $transaction['payment_status'] === 'Paid' ? 'bg-success' : 'bg-warning' ?>">
                                                <?= htmlspecialchars($transaction['payment_status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <input type="hidden" value="<?= $transaction['transaction_id'] ?>">
                                            <?php if ($transaction['service_name'] === 'Transvaginal Ultrasound'): ?>
                                                <a href="http://localhost/paanakan/healthrecords/ultrasound_report.php?case_id=<?= $transaction['case_id'] ?>&transaction_id=<?= $transaction['transaction_id'] ?>" class="btn btn-sm btn me-1" title="View Ultrasound Report">
                                                    <i class="fas fa-wave-square"></i>
                                                </a>
                                            <?php elseif ($transaction['service_name'] === 'OB Ultrasound'): ?>
                                                <a href="http://localhost/paanakan/healthrecords/ob_ultrasound_report.php?case_id=<?= $transaction['case_id'] ?>&transaction_id=<?= $transaction['transaction_id'] ?>" class="btn btn-sm btn me-1" title="View OB Ultrasound Report">
                                                    <i class="fas fa-baby"></i>
                                                </a>
                                            <?php elseif ($transaction['service_name'] === 'Pap Smear'): ?>
                                                <a href="http://localhost/paanakan/healthrecords/pap_smear_report.php?case_id=<?= $transaction['case_id'] ?>&transaction_id=<?= $transaction['transaction_id'] ?>" class="btn btn-sm btn me-1" title="View Pap Smear Report">
                                                    <i class="fas fa-microscope"></i>
                                                </a>
                                            <?php elseif ($transaction['service_name'] === 'Hemoglobin Test'): ?>
                                                <a href="http://localhost/paanakan/healthrecords/hemoglobin_report.php?case_id=<?= $transaction['case_id'] ?>&transaction_id=<?= $transaction['transaction_id'] ?>" class="btn btn-sm btn me-1" title="View Hemoglobin Report">
                                                    <i class="fas fa-tint"></i>
                                                </a>
                                            <?php elseif ($transaction['service_name'] === 'Urinalysis'): ?>
                                                <a href="http://localhost/paanakan/healthrecords/urinalysis_report.php?case_id=<?= $transaction['case_id'] ?>&transaction_id=<?= $transaction['transaction_id'] ?>" class="btn btn-sm btn me-1" title="View Urinalysis Report">
                                                    <i class="fas fa-flask"></i>
                                                </a>
                                            <?php elseif ($transaction['service_name'] === 'Circumcision'): ?>
                                                <a href="http://localhost/paanakan/healthrecords/circumcision_consent.php?case_id=<?= $transaction['case_id'] ?>&transaction_id=<?= $transaction['transaction_id'] ?>" class="btn btn-sm btn me-1" title="View Circumcision Consent">
                                                    <i class="fas fa-scissors"></i>
                                                </a>
                                            <?php elseif ($transaction['service_name'] === 'Prenatal Checkup'): ?>
                                                <a href="http://localhost/paanakan/healthrecords/prenatal_records.php?case_id=<?= $transaction['case_id'] ?>&transaction_id=<?= $transaction['transaction_id'] ?>" class="btn btn-sm btn me-1" title="View Prenatal Record">
                                                    <i class="fas fa-baby-carriage"></i>
                                                </a>
                                            <?php elseif ($transaction['service_name'] === 'Postnatal Checkup'): ?>
                                                <a href="http://localhost/paanakan/healthrecords/postnatal_records.php?case_id=<?= $transaction['case_id'] ?>&transaction_id=<?= $transaction['transaction_id'] ?>" class="btn btn-sm btn me-1" title="View Postnatal Record">
                                                    <i class="fas fa-child"></i>
                                                </a>
                                            <?php elseif ($transaction['service_name'] === 'Vaccination for Newborn'): ?>
                                                <a href="http://localhost/paanakan/healthrecords/vaccination_records.php?transaction_id=<?= $transaction['transaction_id'] ?>" class="btn btn-sm btn me-1" title="View Vaccination Record">
                                                    <i class="fas fa-syringe"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="records.php?case_id=<?= $transaction['case_id'] ?>&record_id=<?= $transaction['transaction_id'] ?>" class="btn btn-sm btn me-1" title="View Records">
                                                    <i class="fas fa-file-medical"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn edit-transaction" data-id="<?= $transaction['transaction_id'] ?>" title="Edit Transaction">
                                                <i class="fas fa-pen-to-square"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            
                <!-- Pagination Controls -->
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=1" aria-label="First">
                                <span class="material-icons">first_page</span>
                            </a>
                        </li>
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                                <span class="material-icons">chevron_left</span>
                            </a>
                        </li>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                                <span class="material-icons">chevron_right</span>
                            </a>
                        </li>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $totalPages ?>" aria-label="Last">
                                <span class="material-icons">last_page</span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <!-- Edit Transaction Modal -->
                <div class="modal fade" id="editTransactionModal" tabindex="-1" aria-labelledby="editTransactionModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editTransactionModalLabel">Edit Transaction</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="editTransactionForm">
                                    <input type="hidden" id="editTransactionId" name="transaction_id">
                                    <div class="mb-3">
                                        <label for="editCategory" class="form-label">Category</label>
                                        <select class="form-select" id="editCategory" name="category" required>
                                            <option value="">Select Category</option>
                                            <?php
                                            $categoriesQuery = "SELECT DISTINCT category FROM medical_services ORDER BY category";
                                            $categories = $con->query($categoriesQuery)->fetchAll();
                                            foreach ($categories as $category) {
                                                echo '<option value="' . htmlspecialchars($category['category']) . '">' . htmlspecialchars($category['category']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="editService" class="form-label">Service Name</label>
                                        <select class="form-select" id="editService" name="service_id" required>
                                            <option value="">Select Service</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="editAmount" class="form-label">Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="text" class="form-control" id="editAmount" name="amount" readonly>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" id="saveTransactionChanges">Save changes</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const categoryCards = document.querySelectorAll('.category-card');
        
        categoryCards.forEach(card => {
            card.addEventListener('click', function() {
                const categoryClass = card.classList[1];
                const serviceTypeDropdown = document.querySelector(`#service_type_${categoryClass}`);
                const amountField = document.querySelector(`#amount_${categoryClass}`);
                const transactionDateField = document.querySelector(`#transaction_date_${categoryClass}`);
                const caseIdInput = document.querySelector(`#case_id_${categoryClass}`);
                const suggestionsDiv = document.querySelector(`#caseIdSuggestions_${categoryClass}`);
                const selectedPatientName = document.querySelector(`#selectedPatientName_${categoryClass}`);
                
                // Set current date and time as default
                const now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                transactionDateField.value = now.toISOString().slice(0, 16);
                
                // Handle service selection
                serviceTypeDropdown.addEventListener('change', function() {
                    const selectedOption = serviceTypeDropdown.options[serviceTypeDropdown.selectedIndex];
                    const price = selectedOption.getAttribute('data-price');
                    amountField.value = price;
                });
                
                // Set initial amount
                const firstOption = serviceTypeDropdown.options[0];
                if (firstOption) {
                    amountField.value = firstOption.getAttribute('data-price');
                }

                // Handle patient search
                caseIdInput.addEventListener('input', function() {
                    const query = this.value.trim();
                    if (query.length < 2) {
                        suggestionsDiv.style.display = 'none';
                        selectedPatientName.textContent = '';
                        return;
                    }

                    // Use URLSearchParams to properly encode the query
                    const params = new URLSearchParams({ query: query });
                    
                    fetch(`search_patients.php?${params.toString()}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.length > 0) {
                                let html = '';
                                data.forEach(patient => {
                                    html += `
                                        <button type="button" class="list-group-item list-group-item-action" 
                                                data-case-id="${patient.case_id}" 
                                                data-name="${patient.first_name} ${patient.last_name}">
                                            ${patient.first_name} ${patient.last_name} 
                                            <span class="text-muted">(${patient.case_id})</span>
                                        </button>`;
                                });
                                suggestionsDiv.innerHTML = html;
                                suggestionsDiv.style.display = 'block';
                            } else {
                                suggestionsDiv.style.display = 'none';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            suggestionsDiv.style.display = 'none';
                        });
                });

                // Handle suggestion click - Fix event delegation
                suggestionsDiv.addEventListener('click', function(e) {
                    const button = e.target.closest('button');
                    if (button) {
                        const caseId = button.dataset.caseId;
                        const name = button.dataset.name;
                        caseIdInput.value = caseId;
                        selectedPatientName.textContent = 'Selected: ' + name;
                        suggestionsDiv.style.display = 'none';
                    }
                });

                // Hide suggestions when clicking outside
                document.addEventListener('click', function(e) {
                    if (!e.target.closest(`#case_id_${categoryClass}, #caseIdSuggestions_${categoryClass}`)) {
                        suggestionsDiv.style.display = 'none';
                    }
                });
            });
        });

        const editModal = new bootstrap.Modal(document.getElementById('editTransactionModal'));
        const editCategory = document.getElementById('editCategory');
        const editService = document.getElementById('editService');
        const editAmount = document.getElementById('editAmount');
        const editTransactionId = document.getElementById('editTransactionId');
        const saveChangesBtn = document.getElementById('saveTransactionChanges');

        // Handle edit button clicks
        document.querySelectorAll('.edit-transaction').forEach(button => {
            button.addEventListener('click', function() {
                const transactionId = this.getAttribute('data-id');
                editTransactionId.value = transactionId;
                editModal.show();
            });
        });

        // Load services when category changes
        editCategory.addEventListener('change', function() {
            const category = this.value;
            editService.innerHTML = '<option value="">Select Service</option>';
            editAmount.value = '';
            
            if (category) {
                fetch(`get_services.php?category=${encodeURIComponent(category)}`)
                    .then(response => response.json())
                    .then(services => {
                        services.forEach(service => {
                            const option = document.createElement('option');
                            option.value = service.service_id;
                            option.textContent = service.service_name;
                            option.setAttribute('data-price', service.price);
                            editService.appendChild(option);
                        });
                    });
            }
        });

        // Update amount when service changes
        editService.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            editAmount.value = price ? parseFloat(price).toFixed(2) : '';
        });

        // Save changes
        saveChangesBtn.addEventListener('click', function() {
            const formData = new FormData(document.getElementById('editTransactionForm'));
            
            fetch('update_transaction.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Transaction updated successfully!');
                    editModal.hide();
                    location.reload();
                } else {
                    alert('Error updating transaction: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error updating transaction: ' + error);
            });
        });
    });
    </script>
</body>
</html>
