<?php
if (!isset($con)) {
    require_once '../connections/connections.php';
    $con = connection();
}

// Initialize breadcrumb array with Home
$breadcrumb = [
    'Home' => ['link' => 'dashboard.php', 'icon' => 'fas fa-home'], // Home link
];

// Get the current page name to dynamically change breadcrumb
$currentPage = basename($_SERVER['PHP_SELF']); // Get current page name

// Check which page is being viewed and update breadcrumb accordingly
if ($currentPage == 'manage_health_records.php') {
    $breadcrumb['Health Records'] = ['link' => 'manage_health_records.php', 'icon' => 'fas fa-folder'];
} elseif ($currentPage == 'admission.php') {
    $breadcrumb['Health Records'] = ['link' => 'manage_health_records.php', 'icon' => 'fas fa-folder'];
    $breadcrumb['Admit Patient'] = ['link' => '#', 'icon' => 'fas fa-user-plus'];
}elseif ($currentPage == 'prenatal_records.php') {
    $breadcrumb['Health Records'] = ['link' => 'manage_health_records.php', 'icon' => 'fas fa-folder'];
    $breadcrumb['Prenatal Record'] = ['link' => '#', 'icon' => 'fas fa-heart'];
}elseif ($currentPage == 'discharge.php') {
    $breadcrumb['Health Records'] = ['link' => 'manage_health_records.php', 'icon' => 'fas fa-folder'];
    $breadcrumb['Discharge Patient'] = ['link' => '#', 'icon' => 'fas fa-user-times'];
} elseif ($currentPage == 'manage_appointments.php') {
    $breadcrumb['Manage Appointments'] = ['link' => 'manage_appointments.php', 'icon' => 'fas fa-calendar-alt'];
} elseif ($currentPage == 'appointment_records.php') {
    $breadcrumb['Appointment Records'] = ['link' => 'appointment_records.php', 'icon' => 'fas fa-calendar-check'];
} elseif ($currentPage == 'transactions.php') {
    $breadcrumb['Transactions'] = ['link' => 'transactions.php', 'icon' => 'fas fa-credit-card'];
} elseif ($currentPage == 'transaction_history.php') {
    $breadcrumb['Transactions'] = ['link' => 'transactions.php', 'icon' => 'fas fa-credit-card'];
    $breadcrumb['Transaction History'] = ['link' => 'transaction_history.php', 'icon' => 'fas fa-history'];
} elseif ($currentPage == 'patient.php') {
    $breadcrumb['Patients'] = ['link' => 'patient.php', 'icon' => 'fas fa-user'];
} elseif ($currentPage == 'supply.php') {
    $breadcrumb['Supplies'] = ['link' => 'supply.php', 'icon' => 'fas fa-cogs'];
} elseif ($currentPage == 'reports.php') {
    $breadcrumb['Reports'] = ['link' => 'reports.php', 'icon' => 'fas fa-chart-line'];
} elseif ($currentPage == 'billing.php') {
    $breadcrumb['Billing Records'] = ['link' => 'billing.php', 'icon' => 'fas fa-file-invoice-dollar'];
} elseif ($currentPage == 'add_billing.php') {
    $breadcrumb['Billing Records'] = ['link' => 'billing.php', 'icon' => 'fas fa-file-invoice-dollar'];
    $breadcrumb['Add Billing Record'] = ['link' => 'add_billing.php', 'icon' => 'fas fa-plus'];
} elseif ($currentPage == 'view_billing.php') {
    $breadcrumb['Billing Records'] = ['link' => 'billing.php', 'icon' => 'fas fa-file-invoice-dollar'];
    $breadcrumb['View Billing Record'] = ['link' => 'view_billing.php', 'icon' => 'fas fa-eye'];
} elseif ($currentPage == 'add_patient.php') {
    $breadcrumb['Patients'] = ['link' => 'patient.php', 'icon' => 'fas fa-user'];
    $breadcrumb['Add Patient'] = ['link' => 'add_patient.php', 'icon' => 'fas fa-plus'];
} elseif ($currentPage == 'edit_patient.php') {
    $breadcrumb['Patients'] = ['link' => 'patient.php', 'icon' => 'fas fa-user'];
    $breadcrumb['Edit Patient'] = ['link' => 'edit_patient.php', 'icon' => 'fas fa-edit'];
} elseif ($currentPage == 'manage_users.php') {
    $breadcrumb['Manage Users'] = ['link' => 'manage_users.php', 'icon' => 'fas fa-users-cog'];
} elseif ($currentPage == 'logs.php') {
    $breadcrumb['Logs'] = ['link' => 'logs.php', 'icon' => 'fas fa-history'];
} elseif ($currentPage == 'patient_health_records.php' && isset($_GET['patient_id'])) {
    // Check if a specific patient page is being viewed
    if (isset($patient['first_name']) && isset($patient['last_name'])) {
        // Add 'Health Records' link and patient's name as current breadcrumb
        $breadcrumb['Health Records'] = ['link' => 'manage_health_records.php', 'icon' => 'fas fa-folder'];
        $breadcrumb[htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name'])] = ['link' => '#', 'icon' => 'fas fa-user']; // Patient name, no link
    }
}
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<!-- Breadcrumb Navigation -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-white p-3 rounded shadow-sm">
        <?php foreach ($breadcrumb as $name => $data): ?>
            <li class="breadcrumb-item <?php if ($data['link'] == '#') echo 'active'; ?>">
                <?php if ($data['link'] != '#'): ?>
                    <a href="<?= $data['link'] ?>"><i class="<?= $data['icon'] ?>"></i> <?= $name ?></a>
                <?php else: ?>
                    <i class="<?= $data['icon'] ?>"></i> <?= $name ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>

<style>
 /* General styling for the breadcrumb container */
.breadcrumb {
    background-color: #f8f9fa; /* Light background */
    padding: 10px 20px; /* Space around the breadcrumb items */
    border-radius: 5px; /* Rounded corners */
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); /* Slight shadow for depth */
    font-family: 'Poppins', sans-serif; /* Custom font */
    align-items: center;
}

/* Styling for breadcrumb items */
.breadcrumb-item {
    display: inline-block;
    font-size: 16px;
    color: #6c757d; /* Gray color for breadcrumb text */
    margin-right: 10px; /* Space between items */
}

/* Styling for active breadcrumb item */
.breadcrumb-item.active {
    color: #2E8B57; /* Highlight color for active breadcrumb */
    font-weight: bold; /* Make active item bold */
}

/* Styling for the breadcrumb link */
.breadcrumb-item a {
    color:rgb(0, 0, 0); /* Link color */
    text-decoration: none; /* Remove underline */
    transition: color 0.3s ease; /* Smooth transition */
}

/* Change link color on hover */
.breadcrumb-item a:hover {
    color: #2E8B57; /* Darker shade on hover */
}

/* Adjust icon size and spacing */
.breadcrumb-item i {
    margin-right: 5px; /* Space between icon and text */
    font-size: 18px; /* Adjust icon size */
    color: #2E8B57; /* Set icon color to match the breadcrumb text */
}

/* Optional: Style the breadcrumb on smaller screens */
@media (max-width: 768px) {
    .breadcrumb {
        font-size: 14px; /* Slightly smaller font size on mobile */
        padding: 8px 15px; /* Adjust padding on smaller screens */
    }
}
</style>