/**
 * Toast Alert Integration Helper
 * Automatically converts PHP session messages to toast alerts
 * 
 * Include this after toast-alert.js to enable automatic toast display
 */

document.addEventListener('DOMContentLoaded', function() {
    /**
     * Display toast alerts from PHP session messages (data attributes)
     * 
     * Usage in PHP:
     * <div id="toast-trigger" data-toast-type="success" data-toast-message="<?= htmlspecialchars($message) ?>"></div>
     */
    
    // Check for data attributes on body or specific element
    const toastType = document.body.getAttribute('data-toast-type');
    const toastMessage = document.body.getAttribute('data-toast-message');
    
    if (toastType && toastMessage) {
        setTimeout(() => {
            if (toastType === 'success') {
                Toast.success(toastMessage);
            } else if (toastType === 'error') {
                Toast.error(toastMessage);
            } else if (toastType === 'warning') {
                Toast.warning(toastMessage);
            } else if (toastType === 'info') {
                Toast.info(toastMessage);
            }
        }, 100);
    }
});

/**
 * Helper function to display toast from inline script
 * Usage: showToast('success', 'Operation completed!');
 */
function showToast(type, message, duration) {
    if (typeof Toast === 'undefined') {
        console.error('Toast component not loaded');
        return;
    }

    switch (type.toLowerCase()) {
        case 'success':
            Toast.success(message, duration);
            break;
        case 'error':
            Toast.error(message, duration);
            break;
        case 'warning':
            Toast.warning(message, duration);
            break;
        case 'info':
            Toast.info(message, duration);
            break;
        default:
            Toast.show(message, type, duration);
    }
}
