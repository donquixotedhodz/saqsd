/**
 * Sidebar Toggle Logic
 * Handles the collapse/expand feature of the admin sidebar when the hamburger icon is clicked.
 */

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('adminSidebar');
    const toggleBtn = document.getElementById('sidebarToggleBtn');

    // Safety check just in case either element doesn't exist on the page
    if (sidebar && toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            // Check if we are currently hidden/moved completely to the left
            if (sidebar.classList.contains('-ml-64')) {
                // Restore it
                sidebar.classList.remove('-ml-64');
            } else {
                // Collapse it
                sidebar.classList.add('-ml-64');
            }
        });
    }
});
