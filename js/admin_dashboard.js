document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.querySelector(".dashboard-sidebar");
    const toggleButton = document.querySelector(".toggle-sidebar");

    // Lock/Unlock Sidebar on Toggle Button Click
    toggleButton.addEventListener("click", () => {
        sidebar.classList.toggle("locked");
    });

    // Expand Sidebar on Hover (if not locked)
    sidebar.addEventListener("mouseenter", () => {
        if (!sidebar.classList.contains("locked")) {
            sidebar.classList.add("expanded");
        }
    });

    // Minimize Sidebar on Mouse Leave (if not locked)
    sidebar.addEventListener("mouseleave", () => {
        if (!sidebar.classList.contains("locked")) {
            sidebar.classList.remove("expanded");
        }
    });
});
