<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.patient-sidebar');
    const headerText = document.querySelector('.patient-sidebar .header-text');
    const sidebarToggler = document.querySelector('.patient-sidebar .sidebar-toggler');

    // Remove any existing classes that might affect the initial state
    sidebar.classList.remove('collapsed', 'expanding');
    
    // Always start expanded
    sidebar.classList.add('expanded');
    
    // Sidebar toggling logic
    document.querySelectorAll('.patient-sidebar .sidebar-toggler, .patient-sidebar .sidebar-menu-button').forEach(button => {
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
    document.querySelectorAll('.patient-sidebar .dropdown-toggle').forEach(dropdownToggle => {
        dropdownToggle.addEventListener('click', function (event) {
            event.preventDefault();

            const dropdown = this.closest('.dropdown-container');
            const menu = dropdown.querySelector('.dropdown-menu');

            // Close all other dropdowns first
            document.querySelectorAll('.patient-sidebar .dropdown-container').forEach(otherDropdown => {
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

    // Only collapse on small screens if not already collapsed
    if (window.innerWidth <= 1024 && !sidebar.classList.contains('collapsed')) {
        sidebar.classList.remove('expanded');
        sidebar.classList.add('collapsed');
    }
});
</script> 