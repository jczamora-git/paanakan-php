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

// Patients Widget: Get new patients from the past month and compare it to the previous month
$current_month = date('Y-m-01');
$last_month = date('Y-m-01', strtotime('-1 month'));

$queryNewPatients = $pdo->prepare("
    SELECT COUNT(*) as total FROM patients 
    WHERE created_at >= :current_month
");
$queryNewPatients->execute(['current_month' => $current_month]);
$newPatients = $queryNewPatients->fetch(PDO::FETCH_ASSOC)['total'];

$queryLastMonthPatients = $pdo->prepare("
    SELECT COUNT(*) as total FROM patients 
    WHERE created_at >= :last_month AND created_at < :current_month
");
$queryLastMonthPatients->execute(['last_month' => $last_month, 'current_month' => $current_month]);
$lastMonthPatients = $queryLastMonthPatients->fetch(PDO::FETCH_ASSOC)['total'];

// Calculate percentage increase
$patientIncrease = ($lastMonthPatients > 0) ? (($newPatients - $lastMonthPatients) / $lastMonthPatients) * 100 : 0;

// Appointments Widget: Get today's appointments and compare to the same day last week
$today = date('Y-m-d');
$lastWeek = date('Y-m-d', strtotime('-7 days'));

$queryAppointmentsToday = $pdo->prepare("
    SELECT COUNT(*) as total FROM appointments 
    WHERE DATE(scheduled_date) = :today
");
$queryAppointmentsToday->execute(['today' => $today]);
$appointmentsToday = $queryAppointmentsToday->fetch(PDO::FETCH_ASSOC)['total'];

$queryAppointmentsLastWeek = $pdo->prepare("
    SELECT COUNT(*) as total FROM appointments 
    WHERE DATE(scheduled_date) = :last_week
");
$queryAppointmentsLastWeek->execute(['last_week' => $lastWeek]);
$appointmentsLastWeek = $queryAppointmentsLastWeek->fetch(PDO::FETCH_ASSOC)['total'];

// Calculate percentage change
$appointmentsChange = ($appointmentsLastWeek > 0) ? (($appointmentsToday - $appointmentsLastWeek) / $appointmentsLastWeek) * 100 : 0;

// Rooms Widget: Get available and occupied rooms
$queryRooms = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'Available' THEN 1 END) AS available_rooms,
        COUNT(CASE WHEN status = 'Occupied' THEN 1 END) AS occupied_rooms
    FROM rooms
");
$queryRooms->execute();
$roomData = $queryRooms->fetch(PDO::FETCH_ASSOC);
$availableRooms = $roomData['available_rooms'];
$occupiedRooms = $roomData['occupied_rooms'];

// Get the first day of the current and last month
$current_month = date('Y-m-01');
$last_month = date('Y-m-01', strtotime('-1 month'));

// Function to fetch total sales (billing_header + billing_items)
function getTotalSales($pdo, $startDate, $endDate = null) {
    $query = "
        SELECT 
            SUM(bh.service_amount + COALESCE(bi.total_item_amount, 0)) AS total_sales
        FROM billing_header bh
        LEFT JOIN (
            SELECT billing_id, SUM(item_amount) AS total_item_amount 
            FROM billing_items 
            GROUP BY billing_id
        ) bi ON bh.billing_id = bi.billing_id
        WHERE bh.billing_date >= :start_date" . ($endDate ? " AND bh.billing_date < :end_date" : "");

    $stmt = $pdo->prepare($query);

    // Bind parameters based on whether an end date is required
    if ($endDate) {
        $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
    } else {
        $stmt->execute(['start_date' => $startDate]);
    }

    return $stmt->fetch(PDO::FETCH_ASSOC)['total_sales'] ?? 0;
}

// Fetch total sales for this month and last month
$salesThisMonth = getTotalSales($pdo, $current_month);
$salesLastMonth = getTotalSales($pdo, $last_month, $current_month);

// Calculate percentage change in sales
$salesChange = ($salesLastMonth > 0) ? (($salesThisMonth - $salesLastMonth) / $salesLastMonth) * 100 : 0;

// Get daily appointment counts for the last 7 days
$queryAppointments = $pdo->prepare("
    SELECT DATE(scheduled_date) AS appointment_date, COUNT(*) AS total
    FROM appointments
    WHERE scheduled_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(scheduled_date)
    ORDER BY appointment_date ASC
");
$queryAppointments->execute();
$appointmentsData = $queryAppointments->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for Chart.js
$labels = [];
$appointmentsCount = [];

$dates = [];
for ($i = 6; $i >= 0; $i--) {
    $dates[] = date('Y-m-d', strtotime("-$i days"));
}

// Format fetched data
$appointmentsMap = [];
foreach ($appointmentsData as $row) {
    $appointmentsMap[$row['appointment_date']] = $row['total'];
}

// Populate chart data
foreach ($dates as $date) {
    $labels[] = date('M d', strtotime($date)); // Format as 'Feb 05'
    $appointmentsCount[] = $appointmentsMap[$date] ?? 0; // Default to 0 if no data
}

// Encode data for JavaScript
$labelsJSON = json_encode($labels);
$appointmentsCountJSON = json_encode($appointmentsCount);


// Process appointment status updates (back-end logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id']) && isset($_POST['status'])) {
    $appointmentId = $_POST['appointment_id'];
    $status = $_POST['status'];

    // Ensure status is either 'Done' or 'Missed'
    if (!in_array($status, ['Done', 'Missed'])) {
        die("Invalid status update.");
    }

    try {
        // Update appointment status in database
        $updateQuery = "UPDATE Appointments SET status = :status WHERE appointment_id = :appointment_id";
        $stmt = $pdo->prepare($updateQuery);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);
        $stmt->execute();

        // Refresh the page after update
        header("Location: dashboard.php?success=Appointment status updated");
        exit();
    } catch (PDOException $e) {
        die("Error updating appointment: " . $e->getMessage());
    }
}

// Fetch top 5 appointments for today
$today = date("Y-m-d");
$appointmentsQuery = "
    SELECT a.appointment_id, a.scheduled_date, a.status, 
           p.first_name, p.last_name, p.contact_number, a.appointment_type
    FROM Appointments a
    JOIN Patients p ON a.patient_id = p.patient_id
    WHERE DATE(a.scheduled_date) = :today AND a.status = 'Ongoing'       
    ORDER BY a.scheduled_date ASC
    LIMIT 5";

$stmt = $pdo->prepare($appointmentsQuery);
$stmt->bindParam(':today', $today, PDO::PARAM_STR);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch midwives from staff table, including attendance_status
$queryStaff = $pdo->prepare("SELECT staff_id, first_name, last_name, attendance_status FROM staff WHERE role = 'Midwife'");
$queryStaff->execute();
$midwives = $queryStaff->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <!-- Google Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/components.css"><!-- Components styles -->
    <link rel="stylesheet" href="../css/sidebar.css"><!-- Sidebar styles -->
    <link rel="stylesheet" href="../css/widgets.css"><!-- Widgets styles -->
    <link rel="stylesheet" href="../css/toast-alert.css"><!-- Toast alert styles -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

</head>

<body style="font-family: 'Poppins', sans-serif;">
        
<div class="dashboard-container">
    <!-- Main Content -->
    <main class="dashboard-main-content">
        
        <!-- Include Sidebar -->
        <?php include '../sidebar.php'; ?>
        <!-- Include Notification Dropdown -->
        <?php include '../components/notification-dropdown.php'; ?>
        <div class="container mt-5">
            <!-- Breadcrumb Navigation -->
            <?php include '../admin/breadcrumb.php'; ?>
            
            <!-- Widgets Section -->
            <div class="row g-3 justify-content-between">
                <!-- New Patients Widget -->
                <div class="col-md-3 new-patients">
                    <div class="widget p-3 rounded d-flex align-items-center">
                        <div class="icon-container">
                            <i class="fas fa-users"></i> <!-- Font Awesome Icon -->
                        </div>
                        <div class="text-container">
                            <h5>New Patients</h5>
                            <div class="h2">+<?php echo number_format($newPatients); ?></div>
                            <p class="text-muted"><?php echo number_format($patientIncrease, 2); ?>% from last month</p>
                        </div>
                    </div>
                </div>

                <!-- Today's Appointment Widget -->
                <div class="col-md-3 todays-appointment">
                    <div class="widget p-3 rounded d-flex align-items-center">
                        <div class="icon-container">
                            <i class="fas fa-calendar-check"></i> <!-- Font Awesome Icon -->
                        </div>
                        <div class="text-container">
                            <h5>Today's Appointment</h5>
                            <div class="h2"><?php echo number_format($appointmentsToday); ?></div>
                            <p class="text-muted"><?php echo number_format($appointmentsChange, 2); ?>% from last week</p>
                        </div>
                    </div>
                </div>

                <!-- Available Rooms Widget -->
                <div class="col-md-3 available-rooms">
                    <div class="widget p-3 rounded d-flex align-items-center">
                        <div class="icon-container">
                            <i class="fas fa-bed"></i> <!-- Font Awesome Icon -->
                        </div>
                        <div class="text-container">
                            <h5>Available Rooms</h5>
                            <div class="h2"><?php echo number_format($availableRooms); ?></div>
                            <p class="text-muted"><?php echo number_format($occupiedRooms); ?> room is occupied</p>
                        </div>
                    </div>
                </div>

                <!-- Sales Widget -->
                <div class="col-md-3 sales">
                    <div class="widget p-3 rounded d-flex align-items-center">
                        <div class="icon-container">
                            <i class="fas fa-dollar-sign"></i> <!-- Font Awesome Icon -->
                        </div>
                        <div class="text-container">
                            <h5>Sales</h5>
                            <div class="h2">₱<?php echo number_format($salesThisMonth, 2); ?></div>
                            <p class="text-muted"><?php echo number_format($salesChange, 2); ?>% from last month</p>
                        </div>
                    </div>
                </div>
            </div>

            <h3>Daily Appointments</h3>
            <!-- Charts Section -->
            <div class="chart-container">
                <canvas id="lineChart"></canvas>
            </div>
            <div class="row">

           <!-- Appointments Section -->
        <div class="col-lg-8 col-md-7">
                <div class="appointments-section h-100">
                    <h3>Appointments Today</h3>
                    <div class="appointment-container shadow p-4 rounded">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Patient Name</th>
                                    <th>Contact</th>
                                    <th>Type</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="appointments-list">
                                <?php if (!empty($appointments)): ?>
                                    <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td><?= date("g:i A", strtotime($appointment['scheduled_date'])) ?></td>
                                            <td><?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?></td>
                                            <td><?= htmlspecialchars($appointment['contact_number']) ?></td>
                                            <td><?= htmlspecialchars($appointment['appointment_type']) ?></td>
                                            <td>
                                                <form method="POST" action="dashboard.php" style="display:inline;">
                                                    <input type="hidden" name="appointment_id" value="<?= $appointment['appointment_id'] ?>">
                                                    <button type="submit" name="status" value="Done" class="btn btn-sm btn-success">✔</button>
                                                    <button type="submit" name="status" value="Missed" class="btn btn-sm btn-danger">✖</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No appointments for today.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Midwives Side Panel -->
            <div class="col-lg-4 col-md-5 d-flex align-items-stretch">
                <div class="side-panel w-100 p-3" style="background: #f8fafc; border-radius: 1rem; box-shadow: 0 2px 8px rgba(44,62,80,0.08);">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="mb-0" style="font-weight:600; letter-spacing:0.5px; color:#256d47;">Midwives</h3>
                        <i class="fas fa-edit edit-icon" data-bs-toggle="modal" data-bs-target="#editModal" style="cursor:pointer; color:#4CAF50;"></i>
                    </div>
                    <table class="table table-borderless align-middle mb-0">
                        <thead>
                            <tr style="background:#e8f5e9; color:#2E8B57;">
                                <th style="font-weight:600;">Midwife Name</th>
                                <th style="font-weight:600;">Status</th>
                            </tr>
                        </thead>
                        <tbody id="midwifeList">
                            <?php if (!empty($midwives)): ?>
                                <?php foreach ($midwives as $midwife): ?>
                                    <tr data-staff-id="<?= $midwife['staff_id'] ?>">
                                        <td style="font-size:1.05rem; font-weight:500; color:#222;">
                                            <i class="fas fa-user-nurse me-2 text-success"></i><?= htmlspecialchars($midwife['first_name'] . ' ' . $midwife['last_name']) ?>
                                        </td>
                                        <td class="attendance-status-cell">
                                            <?php if ($midwife['attendance_status'] === 'Present'): ?>
                                                <span class="badge rounded-pill bg-success px-3 py-2" style="font-size:0.95rem;">Present</span>
                                            <?php elseif ($midwife['attendance_status'] === 'Absent'): ?>
                                                <span class="badge rounded-pill bg-danger px-3 py-2" style="font-size:0.95rem;">Absent</span>
                                            <?php else: ?>
                                                <span class="badge rounded-pill bg-secondary px-3 py-2" style="font-size:0.95rem;">Not Set</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="2" class="text-center">No midwives found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


            <!-- Modal for Adding/Removing Midwives -->
            <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Midwives</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="midwifeListModal">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Midwife Name</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($midwives)): ?>
                                    <?php foreach ($midwives as $midwife): ?>
                                        <tr data-staff-id="<?= $midwife['staff_id'] ?>" data-attendance-status="<?= $midwife['attendance_status'] ?>">
                                            <td><?= htmlspecialchars($midwife['first_name'] . ' ' . $midwife['last_name']) ?></td>
                                            <td class="modal-action-cell">
                                                <?php if ($midwife['attendance_status'] === 'Present'): ?>
                                                    <button class="btn btn-danger btn-sm" onclick="setMidwifeStatus(<?= $midwife['staff_id'] ?>, 'Absent', this)">Mark Absent</button>
                                                <?php else: ?>
                                                    <button class="btn btn-success btn-sm" onclick="setMidwifeStatus(<?= $midwife['staff_id'] ?>, 'Present', this)">Mark Present</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="2" class="text-center">No midwives found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>
            </div>
            </div>
            </div>
        </div>
    </main>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Toast Alert System -->
<script src="../js/toast-alert.js"></script>
<!-- Toast Integration Helper -->
<script src="../js/toast-integration.js"></script>

<!-- Chart.js Script -->
<script>
    // Get data from PHP
var labels = <?php echo $labelsJSON; ?>;
var data = <?php echo $appointmentsCountJSON; ?>;

// Create Line Chart with dynamic hover behavior
var ctx = document.getElementById('lineChart').getContext('2d');

var lineChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels, // X-axis labels (dates)
        datasets: [{
            label: 'Daily Appointments',
            data: data, // Y-axis data (appointment counts)
            borderColor: '#4CAF50', // Softer green for the line
            backgroundColor: 'rgba(76, 175, 80, 0.15)', // Light green fill area with softer opacity
            fill: true, // Fill the area under the line
            tension: 0.4, // Smooth curve
            pointBackgroundColor: '#4CAF50', // Softer green points
            pointRadius: 4, // Adjust point size
            pointHoverRadius: 6, // Increase point size on hover
            pointBorderWidth: 2, // Thicker border for better visibility
            borderWidth: 3, // Thicker line for a modern feel
            pointHoverBackgroundColor: '#ffffff', // White hover point
            pointHoverBorderColor: '#4CAF50', // Green hover border
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            axis: 'x',
            intersect: false
        },
        plugins: {
            legend: {
                display: false // Hide legend for a cleaner UI
            },
            tooltip: {
                enabled: true,
                mode: 'index',
                intersect: false,
                backgroundColor: 'rgba(0, 0, 0, 0.8)', // Dark background
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: '#4CAF50',
                borderWidth: 1,
                padding: 10,
                displayColors: false // Hide dataset color in tooltip
            }
        },
        scales: {
            y: {
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)', // Light gray grid lines
                    drawBorder: false // Hide axis border
                },
                ticks: {
                    color: '#666', // Darker labels for contrast
                    font: {
                        size: 12
                    }
                }
            },
            x: {
                grid: {
                    display: false // Hide vertical grid lines for a cleaner UI
                },
                ticks: {
                    color: '#666',
                    font: {
                        size: 12
                    }
                }
            }
        }
    }
});



    // Bar Chart for age groups
    const barCtx = document.getElementById('barChart').getContext('2d');
    const barChart = new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: ['18-25', '26-35', '36-45', '46-55', '56+'],
            datasets: [{
                label: 'Age Distribution',
                data: [12, 19, 3, 5, 2],
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        }
    });
// Define initial midwives list
const midwives = [
    { name: 'Midwife Name 1', status: 'Present' },
    { name: 'Midwife Name 2', status: 'Present' },
    { name: 'Midwife Name 3', status: 'Present' },
    { name: 'Midwife Name 4', status: 'Present' }
];

// Keep track of midwives removed in modal
let removedMidwives = [];

// Render Midwives in the table
function renderMidwives() {
    const midwifeList = document.getElementById('midwifeList');
    midwifeList.innerHTML = '';
    midwives.forEach(midwife => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${midwife.name}</td>
        `;
        midwifeList.appendChild(row);
    });
}

// Render Midwives in the modal
function renderMidwivesModal() {
    const midwifeListModal = document.getElementById('midwifeListModal');
    midwifeListModal.innerHTML = '';
    
    // Render all midwives in the modal
    midwives.forEach((midwife, index) => {
        const div = document.createElement('div');
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <span>${midwife.name}</span>
                <span>${midwife.status}</span>
                <button class="add-button" onclick="addMidwife(${index})">
                    <i class="fas fa-plus add-icon"></i>
                </button>
                <button class="minus-button" onclick="removeMidwife(${index})">
                    <i class="fas fa-minus minus-icon"></i>
                </button>
            </div>
        `;
        midwifeListModal.appendChild(div);
    });

    // Render removed midwives at the bottom
    removedMidwives.forEach((midwife, index) => {
        const div = document.createElement('div');
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <span>${midwife.name}</span>
                <span>${midwife.status}</span>
                <button class="add-button" onclick="restoreMidwife(${index})">
                    <i class="fas fa-plus add-icon"></i>
                </button>
            </div>
        `;
        midwifeListModal.appendChild(div);
    });
}

// Add a Midwife to the side panel
function addMidwife(index) {
    midwives[index].status = 'Present'; // Update status to "Present"
    renderMidwives();
}

// Remove a Midwife from the list and add to removed list
function removeMidwife(index) {
    const removed = midwives.splice(index, 1)[0]; // Remove midwife from the array
    removedMidwives.push(removed); // Add to removed list
    renderMidwives();
    renderMidwivesModal();
}

// Restore a removed midwife
function restoreMidwife(index) {
    const restored = removedMidwives.splice(index, 1)[0]; // Remove from removed list
    midwives.push(restored); // Add back to main list
    renderMidwives();
    renderMidwivesModal();
}

// Save changes and close modal (guard if button exists)
const saveChangesBtn = document.getElementById('saveChanges');
if (saveChangesBtn) {
    saveChangesBtn.addEventListener('click', function() {
        renderMidwives();
        const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
        modal.hide();
    });
}

// Initial render
renderMidwives();
renderMidwivesModal();

function setMidwifeStatus(staffId, status, btn) {
    fetch('update_midwife_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `staff_id=${staffId}&attendance_status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the status in the main panel with badge
            const row = document.querySelector(`#midwifeList tr[data-staff-id='${staffId}']`);
            if (row) {
                const cell = row.querySelector('.attendance-status-cell');
                if (status === 'Present') {
                    cell.innerHTML = '<span class="badge rounded-pill bg-success px-3 py-2" style="font-size:0.95rem;">Present</span>';
                } else if (status === 'Absent') {
                    cell.innerHTML = '<span class="badge rounded-pill bg-danger px-3 py-2" style="font-size:0.95rem;">Absent</span>';
                } else {
                    cell.innerHTML = '<span class="badge rounded-pill bg-secondary px-3 py-2" style="font-size:0.95rem;">Not Set</span>';
                }
            }
            // Update the modal action button to show the opposite action
            const modalRow = document.querySelector(`#midwifeListModal [data-staff-id='${staffId}']`);
            if (modalRow) {
                const actionCell = modalRow.querySelector('.modal-action-cell');
                if (status === 'Present') {
                    actionCell.innerHTML = `<button class="btn btn-danger btn-sm" onclick="setMidwifeStatus(${staffId}, 'Absent', this)">Mark Absent</button>`;
                } else {
                    actionCell.innerHTML = `<button class="btn btn-success btn-sm" onclick="setMidwifeStatus(${staffId}, 'Present', this)">Mark Present</button>`;
                }
                modalRow.setAttribute('data-attendance-status', status);
            }
        } else {
            alert('Failed to update status.');
        }
    })
    .catch(() => alert('Error updating status.'));
}

</script>

</body>

</html>
