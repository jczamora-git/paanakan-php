<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require '../connections/connections.php';
$con = connection();
$con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Get parameters from URL
$case_id = isset($_GET['case_id']) ? $_GET['case_id'] : null;
$record_id = isset($_GET['record_id']) ? $_GET['record_id'] : null;

// Build the transactions query
$transactionsQuery = "
    SELECT 
        CONCAT(p.first_name, ' ', p.last_name) as patient_name,
        ms.service_name,
        t.diagnosis,
        t.results
    FROM medical_transactions t
    JOIN medical_services ms ON t.service_id = ms.service_id
    JOIN patients p ON t.case_id = p.case_id
    WHERE t.transaction_id = :record_id
";

$recordsStmt = $con->prepare($transactionsQuery);
$recordsStmt->bindParam(':record_id', $record_id);
$recordsStmt->execute();
$record = $recordsStmt->fetch();

// Get patient info if case_id is provided
$patient = null;
if ($case_id) {
    $patientQuery = "
        SELECT CONCAT(first_name, ' ', last_name) as full_name, case_id, gender, contact_number
        FROM patients
        WHERE case_id = :case_id
    ";
    $patientStmt = $con->prepare($patientQuery);
    $patientStmt->bindParam(':case_id', $case_id);
    $patientStmt->execute();
    $patient = $patientStmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Details - Paanakan</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <!-- Google Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
    <style>
        .record-details {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .record-details h3 {
            margin-bottom: 1.5rem;
            color: #333;
        }
        .detail-row {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .detail-label {
            font-weight: 600;
            color: #666;
            margin-bottom: 0.5rem;
        }
        .detail-value {
            color: #333;
            font-size: 1.1rem;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .edit-input {
            display: none;
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 0.25rem;
            margin-top: 0.5rem;
        }
        .edit-mode .edit-input {
            display: block;
        }
        .edit-mode .btn-edit {
            display: none !important;
        }
        .edit-mode .btn-save {
            display: inline-block !important;
        }
        .btn-save {
            display: none !important;
        }
        textarea.edit-input {
            min-height: 100px;
            resize: vertical;
        }
        .badge {
            font-size: 0.875rem;
            padding: 0.5em 0.75em;
        }
    </style>
</head>

<body style="font-family: 'Poppins', sans-serif;">
    <!-- Include Sidebar -->
    <?php include '../sidebar.php'; ?>
    <main class="dashboard-main-content">
        <div class="container mt-4">
            <!-- Display Success/Error Messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                <?php unset($_SESSION['message']); ?>
            <?php elseif (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Breadcrumb Navigation -->
            <?php include '../admin/breadcrumb.php'; ?>

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Record Details</h2>
                <div>
                    <button class="btn btn-primary btn-edit" onclick="toggleEditMode()">
                        <i class="fas fa-edit me-2"></i>Edit
                    </button>
                    <button class="btn btn-success btn-save" onclick="saveChanges()">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                    <a href="transactions.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Transactions
                    </a>
                </div>
            </div>

            <?php if ($record): ?>
                <!-- Transaction Details -->
                <div class="record-details" id="recordDetails">
                    <div class="detail-row">
                        <div class="detail-label">Patient Name</div>
                        <div class="detail-value"><?= htmlspecialchars($record['patient_name']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Service Name</div>
                        <div class="detail-value"><?= htmlspecialchars($record['service_name']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Diagnosis</div>
                        <div class="detail-value" data-field="diagnosis"><?= htmlspecialchars($record['diagnosis']) ?></div>
                        <textarea class="edit-input" name="diagnosis" rows="3"><?= htmlspecialchars($record['diagnosis']) ?></textarea>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Results</div>
                        <div class="detail-value" data-field="results"><?= htmlspecialchars($record['results']) ?></div>
                        <textarea class="edit-input" name="results" rows="3"><?= htmlspecialchars($record['results']) ?></textarea>
                    </div>
                    <div class="detail-row mt-3">
                        <button class="btn btn-success btn-save" onclick="saveChanges()">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">Transaction not found.</div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleEditMode() {
            const recordDetails = document.getElementById('recordDetails');
            recordDetails.classList.toggle('edit-mode');
        }

        function saveChanges() {
            // Get form data
            const formData = new FormData();
            formData.append('record_id', '<?= $record_id ?>');
            formData.append('diagnosis', document.querySelector('textarea[name="diagnosis"]').value);
            formData.append('results', document.querySelector('textarea[name="results"]').value);

            // Send AJAX request
            fetch('update_record.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update displayed values
                    document.querySelector('.detail-value[data-field="diagnosis"]').textContent = formData.get('diagnosis');
                    document.querySelector('.detail-value[data-field="results"]').textContent = formData.get('results');
                    
                    // Show success message
                    alert('Changes saved successfully!');
                    
                    // Exit edit mode
                    toggleEditMode();
                } else {
                    alert('Error saving changes: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error saving changes: ' + error);
            });
        }
    </script>
</body>

</html> 