<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $existingRecord ? 'Update' : 'Add' ?> Prenatal Record</title>
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
                            <h2><i class="fas fa-notes-medical me-2"></i><?= $existingRecord ? 'Update' : 'Add' ?> Prenatal Record</h2>
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
                    <h5><i class="fas fa-history me-2"></i>Previous Prenatal Records</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Visit Date</th>
                                    <th>Attending Physician</th>
                                    <th>Gravida</th>
                                    <th>PARA</th>
                                    <th>OB Score</th>
                                    <th>Blood Pressure</th>
                                    <th>Weight</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($existingRecords as $record): ?>
                                <tr>
                                    <td><?= date('F j, Y g:i A', strtotime($record['visit_date'])) ?></td>
                                    <td><?= htmlspecialchars($record['attending_physician']) ?></td>
                                    <td><?= htmlspecialchars($record['gravida']) ?></td>
                                    <td><?= htmlspecialchars($record['para']) ?></td>
                                    <td><?= htmlspecialchars($record['ob_score']) ?></td>
                                    <td><?= htmlspecialchars($record['blood_pressure']) ?></td>
                                    <td><?= htmlspecialchars($record['weight']) ?> kg</td>
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
                                        <?php foreach ($physicians as $physician): 
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

                    <!-- Obstetric History -->
                    <div class="section-container">
                        <h5><i class="fas fa-file-medical me-2"></i>Obstetric History</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="gravida" name="gravida" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['gravida']) : '' ?>" required>
                                    <label for="gravida">Gravida</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="para" name="para" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['para']) : '' ?>" required>
                                    <label for="para">PARA</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="ob_score" name="ob_score" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['ob_score']) : '' ?>" required>
                                    <label for="ob_score">OB Score</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Menstrual and Pregnancy Details -->
                    <div class="section-container">
                        <h5><i class="fas fa-calendar-alt me-2"></i>Menstrual and Pregnancy Details</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <?php
                                $lmp_value = $existingRecord['lmp'] ?? ($latestStatic['lmp'] ?? '');
                                ?>
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="lmp" name="lmp" 
                                        value="<?= htmlspecialchars($lmp_value) ?>" required>
                                    <label for="lmp">Last Menstrual Period (LMP)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <?php
                                $edc_by_lmp_value = $existingRecord['edc_by_lmp'] ?? ($latestStatic['edc_by_lmp'] ?? '');
                                ?>
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="edc_by_lmp" name="edc_by_lmp" 
                                        value="<?= htmlspecialchars($edc_by_lmp_value) ?>" required>
                                    <label for="edc_by_lmp">EDC by LMP</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="aog_by_lmp" name="aog_by_lmp" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['aog_by_lmp']) : '' ?>" required>
                                    <label for="aog_by_lmp">Age of Gestation (AOG) by LMP</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <?php
                                $edc_by_usg_value = $existingRecord['edc_by_usg'] ?? ($latestStatic['edc_by_usg'] ?? '');
                                ?>
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="edc_by_usg" name="edc_by_usg" 
                                        value="<?= htmlspecialchars($edc_by_usg_value) ?>">
                                    <label for="edc_by_usg">EDC by USG</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Vital Signs -->
                    <div class="section-container">
                        <h5><i class="fas fa-heartbeat me-2"></i>Vital Signs</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="blood_pressure" name="blood_pressure" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['blood_pressure']) : '' ?>" required>
                                    <label for="blood_pressure">Blood Pressure</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="number" step="0.01" class="form-control" id="weight" name="weight" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['weight']) : '' ?>" required>
                                    <label for="weight">Weight (kg)</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="number" step="0.01" class="form-control" id="temperature" name="temperature" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['temperature']) : '' ?>" required>
                                    <label for="temperature">Temperature (°C)</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="respiratory_rate" name="respiratory_rate" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['respiratory_rate']) : '' ?>" required>
                                    <label for="respiratory_rate">Respiratory Rate</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="number" step="0.01" class="form-control" id="fundal_height" name="fundal_height" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['fundal_height']) : '' ?>" required>
                                    <label for="fundal_height">Fundal Height (cm)</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="fetal_heart_tones" name="fetal_heart_tones" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['fetal_heart_tones']) : '' ?>" required>
                                    <label for="fetal_heart_tones">Fetal Heart Tones (bpm)</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Clinical Notes -->
                    <div class="section-container">
                        <h5><i class="fas fa-notes-medical me-2"></i>Clinical Notes</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="internal_examination" name="internal_examination" style="height: 100px"><?= $existingRecord ? htmlspecialchars($existingRecord['internal_examination']) : '' ?></textarea>
                                    <label for="internal_examination">Internal Examination</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="chief_complaint" name="chief_complaint" style="height: 100px"><?= $existingRecord ? htmlspecialchars($existingRecord['chief_complaint']) : '' ?></textarea>
                                    <label for="chief_complaint">Chief Complaint</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="history_of_present_illness" name="history_of_present_illness" style="height: 100px"><?= $existingRecord ? htmlspecialchars($existingRecord['history_of_present_illness']) : '' ?></textarea>
                                    <label for="history_of_present_illness">History of Present Illness</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="past_medical_history" name="past_medical_history" style="height: 100px"><?= $existingRecord ? htmlspecialchars($existingRecord['past_medical_history']) : '' ?></textarea>
                                    <label for="past_medical_history">Past Medical History</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="past_social_history" name="past_social_history" style="height: 100px"><?= $existingRecord ? htmlspecialchars($existingRecord['past_social_history']) : '' ?></textarea>
                                    <label for="past_social_history">Past Social History</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="family_history" name="family_history" style="height: 100px"><?= $existingRecord ? htmlspecialchars($existingRecord['family_history']) : '' ?></textarea>
                                    <label for="family_history">Family History</label>
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
                                    <input type="text" class="form-control" id="tt_dose" name="tt_dose" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['tt_dose']) : '' ?>">
                                    <label for="tt_dose">TT Dose</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <textarea class="form-control" id="plan" name="plan" style="height: 100px"><?= $existingRecord ? htmlspecialchars($existingRecord['plan']) : '' ?></textarea>
                                    <label for="plan">Plan</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <textarea class="form-control" id="lab_results" name="lab_results" style="height: 100px"><?= $existingRecord ? htmlspecialchars($existingRecord['lab_results']) : '' ?></textarea>
                                    <label for="lab_results">Lab Results</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4 mb-5">
                        <button type="button" id="saveRecordBtn" class="btn btn-success btn-lg px-5 me-2">
                            <i class="fas fa-save me-2"></i>Save Prenatal Record
                        </button>
                        <?php if (isset($appointment) && isset($existingRecord) && $existingRecord): ?>
                        <input type="hidden" name="confirm_complete" id="confirm_complete" value="0" />
                        <button type="button" id="completeAppointmentBtn" class="btn btn-success btn-lg px-5 me-2"><i class="fas fa-check-circle me-2"></i>Complete</button>
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

            // Obstetric History
            document.getElementById('gravida').value = record.gravida || '';
            document.getElementById('para').value = record.para || '';
            document.getElementById('ob_score').value = record.ob_score || '';

            // Menstrual and Pregnancy Details
            document.getElementById('lmp').value = record.lmp || '';
            document.getElementById('edc_by_lmp').value = record.edc_by_lmp || '';
            document.getElementById('aog_by_lmp').value = record.aog_by_lmp || '';
            document.getElementById('edc_by_usg').value = record.edc_by_usg || '';

            // Vital Signs
            document.getElementById('blood_pressure').value = record.blood_pressure || '';
            document.getElementById('weight').value = record.weight || '';
            document.getElementById('temperature').value = record.temperature || '';
            document.getElementById('respiratory_rate').value = record.respiratory_rate || '';
            document.getElementById('fundal_height').value = record.fundal_height || '';
            document.getElementById('fetal_heart_tones').value = record.fetal_heart_tones || '';

            // Clinical Notes
            document.getElementById('internal_examination').value = record.internal_examination || '';
            document.getElementById('chief_complaint').value = record.chief_complaint || '';
            document.getElementById('history_of_present_illness').value = record.history_of_present_illness || '';
            document.getElementById('past_medical_history').value = record.past_medical_history || '';
            document.getElementById('past_social_history').value = record.past_social_history || '';
            document.getElementById('family_history').value = record.family_history || '';

            // Additional Information
            document.getElementById('tt_dose').value = record.tt_dose || '';
            document.getElementById('plan').value = record.plan || '';
            document.getElementById('lab_results').value = record.lab_results || '';

            // Scroll to form
            document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
        }
    </script>

    <script>
    function calculateAOG() {
        const lmpInput = document.getElementById('lmp');
        const visitInput = document.getElementById('visit_date');
        const aogInput = document.getElementById('aog_by_lmp');
        if (!lmpInput || !visitInput || !aogInput) return;

        const lmp = lmpInput.value;
        let visit = visitInput.value;
        if (!lmp) return;

        // If visit date is not set, use today
        if (!visit) {
            const today = new Date();
            visit = today.toISOString().slice(0, 10);
        } else {
            // If visit is datetime-local, get only the date part
            visit = visit.slice(0, 10);
        }

        const lmpDate = new Date(lmp);
        const visitDate = new Date(visit);

        if (isNaN(lmpDate) || isNaN(visitDate) || visitDate < lmpDate) {
            aogInput.value = '';
            return;
        }

        const diffMs = visitDate - lmpDate;
        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        const weeks = Math.floor(diffDays / 7);
        const days = diffDays % 7;
        aogInput.value = weeks + ' weeks' + (days ? ' ' + days + ' days' : '');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const lmpInput = document.getElementById('lmp');
        const visitInput = document.getElementById('visit_date');
        if (lmpInput) lmpInput.addEventListener('change', calculateAOG);
        if (visitInput) visitInput.addEventListener('change', calculateAOG);
        // Initial calculation
        calculateAOG();
    });
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