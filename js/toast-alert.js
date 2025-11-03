/**
 * Global Toast Alert Component
 * A lightweight, reusable notification system for success, error, warning, and info messages
 * 
 * Usage:
 * Toast.success('Operation completed successfully!')
 * Toast.error('An error occurred!')
 * Toast.warning('Please be careful!')
 * Toast.info('Here is some information')
 * Toast.show('Custom message', 'custom-type', 3000)
 */

class ToastAlert {
    constructor() {
        this.toastContainer = null;
        this.init();
    }

    /**
     * Initialize the toast container
     */
    init() {
        if (!this.toastContainer) {
            this.toastContainer = document.createElement('div');
            this.toastContainer.id = 'toast-container';
            this.toastContainer.className = 'toast-container';
            document.body.appendChild(this.toastContainer);
        }
    }

    /**
     * Display a success toast
     * @param {string} message - The message to display
     * @param {number} duration - Duration in milliseconds (default: 3000)
     */
    success(message, duration = 3000) {
        this.show(message, 'success', duration);
    }

    /**
     * Display an error toast
     * @param {string} message - The message to display
     * @param {number} duration - Duration in milliseconds (default: 5000)
     */
    error(message, duration = 5000) {
        this.show(message, 'error', duration);
    }

    /**
     * Display a warning toast
     * @param {string} message - The message to display
     * @param {number} duration - Duration in milliseconds (default: 4000)
     */
    warning(message, duration = 4000) {
        this.show(message, 'warning', duration);
    }

    /**
     * Display an info toast
     * @param {string} message - The message to display
     * @param {number} duration - Duration in milliseconds (default: 3000)
     */
    info(message, duration = 3000) {
        this.show(message, 'info', duration);
    }

    /**
     * Display a custom toast
     * @param {string} message - The message to display
     * @param {string} type - Type of toast (success, error, warning, info)
     * @param {number} duration - Duration in milliseconds
     */
    show(message, type = 'info', duration = 3000) {
        // Create toast element
        const toastElement = document.createElement('div');
        toastElement.className = `toast toast-${type}`;
        
        // Map icons for each type
        const icons = {
            success: '<i class="fas fa-check-circle"></i>',
            error: '<i class="fas fa-exclamation-circle"></i>',
            warning: '<i class="fas fa-exclamation-triangle"></i>',
            info: '<i class="fas fa-info-circle"></i>'
        };

        const icon = icons[type] || icons.info;

        toastElement.innerHTML = `
            <div class="toast-content">
                <div class="toast-icon">${icon}</div>
                <div class="toast-message">${this.escapeHtml(message)}</div>
                <button class="toast-close" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        // Add to container
        this.toastContainer.appendChild(toastElement);

        // Add show animation
        setTimeout(() => {
            toastElement.classList.add('show');
        }, 10);

        // Auto remove after duration
        setTimeout(() => {
            toastElement.classList.remove('show');
            setTimeout(() => {
                toastElement.remove();
            }, 300);
        }, duration);
    }

    /**
     * Escape HTML to prevent XSS
     * @param {string} text - The text to escape
     * @returns {string} - Escaped text
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Create global Toast instance
const Toast = new ToastAlert();
