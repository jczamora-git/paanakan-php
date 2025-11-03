<?php
// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Linking Google Fonts for Icons -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
<link rel="stylesheet" href="../css/sidebar.css"><!-- Components styles -->

<aside class="sidebar">
    <!-- Sidebar Header -->
    <header class="sidebar-header">
        <a href="#" class="header-logo">
            <img src="../PSC_white.png" alt="Paanakan Logo" />
        </a>
        <div class="header-text">
            <h2>Paanakan</h2>
            <h6>sa Calapan</h6>
        </div>
        <button class="sidebar-toggler">
            <span class="material-symbols-rounded">chevron_left</span>
        </button>
    </header>
    
    <nav class="sidebar-nav">
        <ul class="nav-list primary-nav">
            <li class="nav-item">
                <a href="../admin/dashboard.php" class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">dashboard</span>
                    <span class="nav-label">Dashboard</span>
                </a>
            </li>
            
            <!-- Appointments Dropdown -->
            <li class="nav-item dropdown-container">
                <a href="#" class="nav-link dropdown-toggle <?= ($current_page == 'manage_appointments.php' || $current_page == 'appointment_records.php') ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">calendar_today</span>
                    <span class="nav-label">Appointments</span>
                    <span class="dropdown-icon material-symbols-rounded">keyboard_arrow_down</span>
                </a>
                <ul class="dropdown-menu">
                    <li class="nav-item">
                        <a href="../admin/online_appointments.php" class="nav-link <?= ($current_page == 'online_appointments.php') ? 'active' : '' ?>">Online Appointments</a>
                    </li>
                    <li class="nav-item">
                        <a href="../admin/manage_appointments.php" class="nav-link <?= ($current_page == 'manage_appointments.php') ? 'active' : '' ?>">Manage Appointments</a>
                    </li>
                    <li class="nav-item">
                        <a href="../admin/appointment_records.php" class="nav-link <?= ($current_page == 'appointment_records.php') ? 'active' : '' ?>">Appointment Records</a>
                    </li>
                </ul>
            </li>

            <li class="nav-item">
                <a href="../admin/manage_health_records.php" class="nav-link <?= ($current_page == 'manage_health_records.php') ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">local_hospital</span>
                    <span class="nav-label">Health Records</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="../admin/transactions.php" class="nav-link <?= ($current_page == 'transactions.php') ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">medication</span>
                    <span class="nav-label">Medical Services</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="../admin/patient.php" class="nav-link <?= ($current_page == 'patient.php') ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">group</span>
                    <span class="nav-label">Patients</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="../admin/supply.php" class="nav-link <?= ($current_page == 'supply.php') ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">business</span>
                    <span class="nav-label">Supplies</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="../admin/billing.php" class="nav-link <?= ($current_page == 'billing.php') ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">payment</span>
                    <span class="nav-label">Billing</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="../admin/reports.php" class="nav-link <?= ($current_page == 'reports.php') ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">assessment</span>
                    <span class="nav-label">Reports</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="../admin/manage_users.php" class="nav-link <?= ($current_page == 'manage_users.php') ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">people</span>
                    <span class="nav-label">Users</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="../admin/logs.php" class="nav-link <?= ($current_page == 'logs.php') ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">book</span>
                    <span class="nav-label">Logs</span>
                </a>
            </li>
        </ul>

        <!-- Bottom Nav -->
        <ul class="nav-list secondary-nav">
            <li class="nav-item">
                <a href="../logout.php" class="nav-link">
                    <span class="material-symbols-rounded">logout</span>
                    <span class="nav-label">Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.sidebar');
    const headerText = document.querySelector('.header-text');
    const sidebarToggler = document.querySelector('.sidebar-toggler');

    // Always start expanded
    sidebar.classList.remove('collapsed');
    sidebar.classList.add('expanded');
    
    // Sidebar toggling logic
    document.querySelectorAll('.sidebar-toggler, .sidebar-menu-button').forEach(button => {
        button.addEventListener('click', () => {
            // Toggle between expanded and collapsed states
            const isExpanded = sidebar.classList.contains('expanded');
            if (isExpanded) {
                sidebar.classList.remove('expanded');
                sidebar.classList.add('collapsed');
            } else {
                sidebar.classList.remove('collapsed');
                sidebar.classList.add('expanded');
            }
        });
    });

    // Handle hover behavior for collapsed sidebar
    sidebar.addEventListener('mouseenter', () => {
        if (sidebar.classList.contains('collapsed')) {
            sidebar.classList.add('expanding');
            headerText.style.opacity = '1';
            sidebarToggler.style.opacity = '1';
        }
    });

    sidebar.addEventListener('mouseleave', () => {
        if (sidebar.classList.contains('collapsed')) {
            sidebar.classList.remove('expanding');
            headerText.style.opacity = '0';
            sidebarToggler.style.opacity = '0';
        }
    });

    // Dropdown toggle logic
    document.querySelectorAll('.dropdown-toggle').forEach(dropdownToggle => {
        dropdownToggle.addEventListener('click', function (event) {
            event.preventDefault();

            const dropdown = this.closest('.dropdown-container');
            const menu = dropdown.querySelector('.dropdown-menu');

            // Close all other dropdowns first
            document.querySelectorAll('.dropdown-container').forEach(otherDropdown => {
                const otherMenu = otherDropdown.querySelector('.dropdown-menu');
                if (otherDropdown !== dropdown) {
                    otherDropdown.classList.remove('open');
                    if (otherMenu) {
                        otherMenu.style.height = '0';
                    }
                }
            });

            // Toggle the clicked dropdown
            const isOpen = dropdown.classList.contains('open');
            if (!isOpen) {
                dropdown.classList.add('open');
                menu.offsetHeight;
                menu.style.height = `${menu.scrollHeight}px`;
            } else {
                dropdown.classList.remove('open');
                menu.style.height = '0';
            }
        });
    });

    // Collapse sidebar on small screens
    if (window.innerWidth <= 1024) {
        sidebar.classList.remove('expanded');
        sidebar.classList.add('collapsed');
    }
});
</script> 