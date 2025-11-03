<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $existingRecord ? 'Update' : 'Add' ?> Postnatal Record</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/toast-alert.css">
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

        .form-control {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 8px 12px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.25);
        }

        textarea.form-control {
            min-height: 100px;
        }

        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 30px;
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
        }

        .form-floating > .form-control {
            padding-top: 1.625rem;
            padding-bottom: 0.625rem;
        }

        .form-floating > label {
            padding: 1rem 0.75rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include('../healthrecords/sidebar.php'); ?>

        <main class="dashboard-main-content">
            <?php include '../admin/breadcrumb.php'; ?>
            
            <div class="container">
                <!-- Patient Header Section -->
                <div class="patient-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2><i class="fas fa-baby me-2"></i><?= $existingRecord ? 'Update' : 'Add' ?> Postnatal Record</h2>
                            <?php if (isset($patient) || isset($appointment) || isset($transaction)): ?>
                            <p class="mb-0">
                                <i class="fas fa-user me-2"></i>Patient: <?= htmlspecialchars($patient['fullname'] ?? $appointment['fullname'] ?? $transaction['fullname']) ?>
                                <?php if (isset($appointment)): ?>
                                    <br>
                                    <i class="fas fa-calendar me-2"></i>Appointment ID: <?= htmlspecialchars($appointment['appointment_id']) ?>
                                    <br>
                                    <i class="fas fa-clock me-2"></i>Scheduled: <?= date('F j, Y g:i A', strtotime($appointment['scheduled_date'])) ?>
                                    <br>
                                    <i class="fas fa-tag me-2"></i>Type: <?= htmlspecialchars($appointment['appointment_type']) ?>
                                <?php endif; ?>
                                <?php if (isset($transaction)): ?>
                                    <br>
                                    <i class="fas fa-file-invoice me-2"></i>Transaction ID: <?= htmlspecialchars($transaction['transaction_id']) ?>
                                <?php endif; ?>
                            </p>
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

                <!-- Existing Records Section -->
                <?php if (!empty($existingRecords)): ?>
                <div class="section-container mb-4">
                    <h5><i class="fas fa-history me-2"></i>Previous Postnatal Records</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Visit Date</th>
                                    <th>Attending Physician</th>
                                    <th>Delivery Date</th>
                                    <th>Delivery Type</th>
                                    <th>Birth Weight</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($existingRecords as $record): ?>
                                <tr>
                                    <td><?= date('F j, Y g:i A', strtotime($record['visit_date'])) ?></td>
                                    <td><?= htmlspecialchars($record['attending_physician']) ?></td>
                                    <td><?= date('F j, Y', strtotime($record['delivery_date'])) ?></td>
                                    <td><?= htmlspecialchars($record['delivery_type']) ?></td>
                                    <td><?= htmlspecialchars($record['birth_weight']) ?> kg</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" 
                                            onclick="populateForm(<?= htmlspecialchars(json_encode($record)) ?>)">
                                            <i class="fas fa-edit"></i> Use
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <form action="" method="POST" class="needs-validation" novalidate>
                    <!-- Visit Details -->
                    <div class="section-container">
                        <h5><i class="fas fa-calendar-check me-2"></i>Visit Details</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="datetime-local" class="form-control" id="visit_date" name="visit_date" 
                                        value="<?= $existingRecord ? date('Y-m-d\TH:i', strtotime($existingRecord['visit_date'])) : '' ?>" required>
                                    <label for="visit_date">Visit Date</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <!-- Attending Physician Dropdown (form-floating style) -->
                                <div class="form-floating">
                                    <select name="attending_physician" id="attending_physician" class="form-select" required>
                                        <option value="">Select Physician</option>
                                        <?php foreach (
                                            $physicians as $physician): 
                                            $fullName = $physician['first_name'] . ' ' . $physician['last_name'];
                                            $selected = '';
                                            if ((isset($existingRecord['attending_physician']) && $existingRecord['attending_physician'] === $fullName) || (isset($_POST['attending_physician']) && $_POST['attending_physician'] === $fullName)) {
                                                $selected = 'selected';
                                            }
                                        ?>
                                            <option value="<?= htmlspecialchars($fullName) ?>" <?= $selected ?>><?= htmlspecialchars($fullName) ?> (<?= htmlspecialchars($physician['role']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="attending_physician">Attending Physician</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery & Baby Details -->
                    <div class="section-container">
                        <h5><i class="fas fa-baby me-2"></i>Delivery & Baby Details</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="delivery_date" name="delivery_date" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['delivery_date']) : '' ?>" required>
                                    <label for="delivery_date">Delivery Date</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="delivery_type" name="delivery_type" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['delivery_type']) : '' ?>" required>
                                    <label for="delivery_type">Delivery Type</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="number" step="0.01" class="form-control" id="birth_weight" name="birth_weight" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['birth_weight']) : '' ?>" required>
                                    <label for="birth_weight">Birth Weight (kg)</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="number" step="0.01" class="form-control" id="birth_length" name="birth_length" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['birth_length']) : '' ?>">
                                    <label for="birth_length">Birth Length (cm)</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="apgar_score" name="apgar_score" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['apgar_score']) : '' ?>">
                                    <label for="apgar_score">APGAR Score</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Postnatal Assessment -->
                    <div class="section-container">
                        <h5><i class="fas fa-user-md me-2"></i>Postnatal Assessment</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="maternal_complications" name="maternal_complications" style="height: 100px"><?= $existingRecord ? htmlspecialchars($existingRecord['maternal_complications']) : '' ?></textarea>
                                    <label for="maternal_complications">Maternal Complications</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="neonatal_complications" name="neonatal_complications" style="height: 100px"><?= $existingRecord ? htmlspecialchars($existingRecord['neonatal_complications']) : '' ?></textarea>
                                    <label for="neonatal_complications">Neonatal Complications</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select class="form-control" id="breastfeeding_initiated" name="breastfeeding_initiated" required>
                                        <option value="">Select...</option>
                                        <option value="Yes" <?= $existingRecord && $existingRecord['breastfeeding_initiated'] === 'Yes' ? 'selected' : '' ?>>Yes</option>
                                        <option value="No" <?= $existingRecord && $existingRecord['breastfeeding_initiated'] === 'No' ? 'selected' : '' ?>>No</option>
                                    </select>
                                    <label for="breastfeeding_initiated">Breastfeeding Initiated</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="postpartum_bleeding" name="postpartum_bleeding" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['postpartum_bleeding']) : '' ?>">
                                    <label for="postpartum_bleeding">Postpartum Bleeding</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="uterine_involution" name="uterine_involution" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['uterine_involution']) : '' ?>">
                                    <label for="uterine_involution">Uterine Involution</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="perineal_healing" name="perineal_healing" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['perineal_healing']) : '' ?>">
                                    <label for="perineal_healing">Perineal Healing</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="contraceptive_counseling" name="contraceptive_counseling" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['contraceptive_counseling']) : '' ?>">
                                    <label for="contraceptive_counseling">Contraceptive Counseling</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="section-container">
                        <h5><i class="fas fa-info-circle me-2"></i>Additional Information</h5>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <textarea class="form-control" id="remarks" name="remarks" style="height: 100px"><?= $existingRecord ? htmlspecialchars($existingRecord['remarks']) : '' ?></textarea>
                                    <label for="remarks">Remarks</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4 mb-5">
                        <button type="button" id="saveRecordBtn" class="btn btn-success btn-lg px-5 me-2">
                            <i class="fas fa-save me-2"></i>Save Postnatal Record
                        </button>
                        <?php if (isset($appointment) && isset($existingRecord) && $existingRecord): ?>
                        <input type="hidden" name="confirm_complete" id="confirm_complete" value="0" />
                        <button type="button" id="completeAppointmentBtn" class="btn btn-success btn-lg px-5 me-2">
                            <i class="fas fa-check-circle me-2"></i>Complete
                        </button>
                        <?php endif; ?>
                        <a href="javascript:history.back()" class="btn btn-secondary btn-lg px-5">
                            <i class="fas fa-arrow-left me-2"></i>Back
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
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

        function populateForm(record) {
            // Visit Details
            document.getElementById('visit_date').value = record.visit_date ? record.visit_date.replace(' ', 'T') : '';
            document.getElementById('attending_physician').value = record.attending_physician || '';

            // Delivery & Baby Details
            document.getElementById('delivery_date').value = record.delivery_date || '';
            document.getElementById('delivery_type').value = record.delivery_type || '';
            document.getElementById('birth_weight').value = record.birth_weight || '';
            document.getElementById('birth_length').value = record.birth_length || '';
            document.getElementById('apgar_score').value = record.apgar_score || '';

            // Postnatal Assessment
            document.getElementById('maternal_complications').value = record.maternal_complications || '';
            document.getElementById('neonatal_complications').value = record.neonatal_complications || '';
            document.getElementById('breastfeeding_initiated').value = record.breastfeeding_initiated || '';
            document.getElementById('postpartum_bleeding').value = record.postpartum_bleeding || '';
            document.getElementById('uterine_involution').value = record.uterine_involution || '';
            document.getElementById('perineal_healing').value = record.perineal_healing || '';
            document.getElementById('contraceptive_counseling').value = record.contraceptive_counseling || '';

            // Additional Information
            document.getElementById('remarks').value = record.remarks || '';

            // Scroll to form
            document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
        }
    </script>

    <!-- Toast Alert Component -->
    <script src="../js/toast-alert.js"></script>
    <!-- Appointment Actions Handler -->
    <script src="../js/appointment-actions.js"></script>

    <!-- Session Toast Messages -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toastMessage = '<?php echo isset($_SESSION['toast_message']) ? addslashes($_SESSION['toast_message']) : ''; ?>';
            const toastType = '<?php echo isset($_SESSION['toast_type']) ? $_SESSION['toast_type'] : ''; ?>';
            
            if (toastMessage && toastType) {
                Toast[toastType](toastMessage, 3000);
                <?php 
                unset($_SESSION['toast_message']); 
                unset($_SESSION['toast_type']); 
                ?>
            }
        });
    </script>
</body>
</html> 