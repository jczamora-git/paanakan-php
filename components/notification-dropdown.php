<?php
/**
 * Global Notification Dropdown Component
 * 
 * Displays pending appointments in a dropdown notification panel
 * Include this component in your layout to enable global notifications
 * 
 * Usage:
 * <?php include 'components/notification-dropdown.php'; ?>
 */

if (!isset($con)) {
    require_once __DIR__ . '/../connections/connections.php';
    $con = connection();
}

// Fetch pending appointments from the database
$stmt = $con->prepare("
    SELECT a.appointment_id, 
           p.first_name, 
           p.last_name, 
           p.contact_number,
           a.scheduled_date,
           a.appointment_type,
           p.case_id
    FROM appointments a 
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.status = 'Pending'
    ORDER BY a.scheduled_date ASC
    LIMIT 10
");
$stmt->execute();
$pendingAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count pending appointments
$pendingCount = count($pendingAppointments);
?>

<!-- Notification Dropdown Component -->
<div class="notification-dropdown-wrapper">
    <!-- Notification Bell Button -->
    <button id="notificationBellBtn" class="notification-bell-btn" type="button" title="View pending appointments">
        <i class="fas fa-bell"></i>
        <?php if ($pendingCount > 0): ?>
            <span class="notification-badge"><?= $pendingCount ?></span>
        <?php endif; ?>
    </button>

    <!-- Notification Dropdown Panel -->
    <div id="notificationDropdown" class="notification-dropdown-panel">
        <!-- Header -->
        <div class="notification-header">
            <h6 class="notification-title">
                <i class="fas fa-bell me-2"></i>Pending Appointments
            </h6>
            <button class="notification-close" id="notificationCloseBtn" type="button">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Divider -->
        <div class="notification-divider"></div>

        <!-- Appointments List -->
        <div class="notification-content">
            <?php if (!empty($pendingAppointments)): ?>
                <ul class="notification-list">
                    <?php foreach ($pendingAppointments as $appointment): ?>
                        <li class="notification-item">
                            <div class="notification-item-header">
                                <div class="notification-patient-info">
                                    <p class="notification-patient-name">
                                        <strong><?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?></strong>
                                    </p>
                                    <p class="notification-patient-id">
                                        <i class="fas fa-id-card me-1"></i><?= htmlspecialchars($appointment['case_id']) ?>
                                    </p>
                                </div>
                                <span class="notification-badge-small">
                                    <?= htmlspecialchars($appointment['appointment_type']) ?>
                                </span>
                            </div>
                            <div class="notification-item-details">
                                <p class="notification-date">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    <?= date('M d, Y \a\t h:i A', strtotime($appointment['scheduled_date'])) ?>
                                </p>
                                <p class="notification-contact">
                                    <i class="fas fa-phone me-1"></i>
                                    <?= htmlspecialchars($appointment['contact_number']) ?>
                                </p>
                            </div>
                            <div class="notification-item-actions">
                                <a href="online_appointments.php?appointment_id=<?= $appointment['appointment_id'] ?>" class="btn-action btn-view" aria-label="View appointment <?= htmlspecialchars($appointment['appointment_id']) ?>">
                                    <i class="fas fa-eye me-1"></i>View
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Footer with link to all appointments -->
                <div class="notification-footer">
                    <a href="online_appointments.php" class="notification-view-all">
                        View All Appointments <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="notification-empty">
                    <div class="notification-empty-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <p class="notification-empty-text">No pending appointments</p>
                    <p class="notification-empty-subtext">All caught up!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    /* ===== Notification Dropdown Wrapper ===== */
    .notification-dropdown-wrapper {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
    }

    /* ===== Notification Bell Button ===== */
    .notification-bell-btn {
        position: relative;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: white;
        border: 2px solid #2E8B57;
        color: #2E8B57;
        font-size: 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(46, 139, 87, 0.15);
    }

    .notification-bell-btn:hover {
        background: #2E8B57;
        color: white;
        transform: scale(1.1);
        box-shadow: 0 6px 16px rgba(46, 139, 87, 0.25);
    }

    .notification-bell-btn:active {
        transform: scale(0.95);
    }

    /* ===== Notification Badge ===== */
    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        border: 2px solid white;
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
    }

    /* ===== Notification Dropdown Panel ===== */
    .notification-dropdown-panel {
        position: absolute;
        top: 65px;
        right: 0;
        width: 420px;
        max-height: 600px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        display: none;
        flex-direction: column;
        overflow: hidden;
        animation: slideDown 0.3s ease-out;
        z-index: 1001;
    }

    .notification-dropdown-panel.show {
        display: flex;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* ===== Notification Header ===== */
    .notification-header {
        padding: 16px 20px;
        background: linear-gradient(135deg, #2E8B57 0%, #3CB371 100%);
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .notification-title {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
    }

    .notification-close {
        background: none;
        border: none;
        color: white;
        font-size: 18px;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .notification-close:hover {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
    }

    /* ===== Notification Divider ===== */
    .notification-divider {
        height: 1px;
        background: #e9ecef;
    }

    /* ===== Notification Content ===== */
    .notification-content {
        flex: 1;
        overflow-y: auto;
        padding: 0;
    }

    .notification-content::-webkit-scrollbar {
        width: 6px;
    }

    .notification-content::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .notification-content::-webkit-scrollbar-thumb {
        background: #2E8B57;
        border-radius: 3px;
    }

    .notification-content::-webkit-scrollbar-thumb:hover {
        background: #1C6E3B;
    }

    /* ===== Notification List ===== */
    .notification-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    /* ===== Notification Item ===== */
    .notification-item {
        padding: 14px 16px;
        border-bottom: 1px solid #f0f0f0;
        transition: all 0.2s ease;
        background: white;
    }

    .notification-item:hover {
        background: #f8f9fa;
    }

    .notification-item:last-child {
        border-bottom: none;
    }

    /* Notification Item Header */
    .notification-item-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
    }

    .notification-patient-info {
        flex: 1;
    }

    .notification-patient-name {
        margin: 0 0 4px 0;
        font-size: 14px;
        font-weight: 600;
        color: #2E8B57;
    }

    .notification-patient-id {
        margin: 0;
        font-size: 12px;
        color: #6c757d;
        display: flex;
        align-items: center;
    }

    .notification-badge-small {
        display: inline-block;
        background: #e8f5e9;
        color: #2E8B57;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }

    /* Notification Item Details */
    .notification-item-details {
        margin: 8px 0;
        padding: 8px 0;
        border-left: 3px solid #2E8B57;
        padding-left: 12px;
    }

    .notification-date,
    .notification-contact {
        margin: 0 0 4px 0;
        font-size: 12px;
        color: #495057;
        display: flex;
        align-items: center;
    }

    .notification-date:last-child,
    .notification-contact:last-child {
        margin-bottom: 0;
    }

    .notification-date i,
    .notification-contact i {
        color: #2E8B57;
        min-width: 14px;
    }

    /* Notification Item Actions */
    .notification-item-actions {
        display: flex;
        gap: 8px;
        margin-top: 10px;
    }

    .btn-action {
        flex: 1;
        padding: 6px 10px;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 500;
    }

    .btn-view {
        background: #e8f5e9;
        color: #2E8B57;
    }

    .btn-view:hover {
        background: #2E8B57;
        color: white;
    }

    .btn-manage {
        background: #e3f2fd;
        color: #1976d2;
    }

    .btn-manage:hover {
        background: #1976d2;
        color: white;
    }

    /* ===== Notification Footer ===== */
    .notification-footer {
        padding: 12px 16px;
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
        text-align: center;
    }

    .notification-view-all {
        color: #2E8B57;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
    }

    .notification-view-all:hover {
        color: #1C6E3B;
        transform: translateX(2px);
    }

    /* ===== Notification Empty State ===== */
    .notification-empty {
        padding: 40px 20px;
        text-align: center;
        background: #f8f9fa;
    }

    .notification-empty-icon {
        font-size: 48px;
        color: #2E8B57;
        margin-bottom: 12px;
    }

    .notification-empty-text {
        margin: 0 0 4px 0;
        font-size: 14px;
        font-weight: 600;
        color: #2E8B57;
    }

    .notification-empty-subtext {
        margin: 0;
        font-size: 12px;
        color: #6c757d;
    }

    /* ===== Responsive Design ===== */
    @media (max-width: 768px) {
        .notification-dropdown-wrapper {
            top: 10px;
            right: 10px;
        }

        .notification-bell-btn {
            width: 45px;
            height: 45px;
            font-size: 18px;
        }

        .notification-dropdown-panel {
            width: 360px;
            max-height: 80vh;
            top: 60px;
        }

        .notification-item-actions {
            flex-direction: column;
        }

        .btn-action {
            width: 100%;
        }
    }

    @media (max-width: 480px) {
        .notification-dropdown-wrapper {
            top: 8px;
            right: 8px;
        }

        .notification-bell-btn {
            width: 42px;
            height: 42px;
            font-size: 16px;
        }

        .notification-dropdown-panel {
            width: calc(100vw - 20px);
            max-height: 70vh;
            top: 55px;
            right: -10px;
            border-radius: 8px;
        }

        .notification-item {
            padding: 12px 14px;
        }

        .notification-title {
            font-size: 14px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const notificationBellBtn = document.getElementById('notificationBellBtn');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const notificationCloseBtn = document.getElementById('notificationCloseBtn');

        // Toggle dropdown on bell click
        notificationBellBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('show');
        });

        // Close dropdown on close button click
        notificationCloseBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.remove('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.notification-dropdown-wrapper')) {
                notificationDropdown.classList.remove('show');
            }
        });

        // Close dropdown when pressing Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                notificationDropdown.classList.remove('show');
            }
        });
    });
</script>
