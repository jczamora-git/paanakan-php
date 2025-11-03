/**
 * Appointment Actions Handler
 * Manages merged save/complete functionality with toast notifications
 * 
 * Usage in forms:
 * <button type="button" class="btn btn-primary px-4" id="saveRecordBtn">
 *     <i class="fas fa-save me-2"></i>Save Record
 * </button>
 * <button type="button" class="btn btn-success px-4 ms-2" id="completeAppointmentBtn">
 *     <i class="fas fa-check-circle me-2"></i>Complete
 * </button>
 */

class AppointmentActions {
    constructor() {
        this.form = null;
        this.completeConfirmed = false;
        this.init();
    }

    /**
     * Initialize appointment action handlers
     */
    init() {
        // Find the form (usually the main form in appointment records)
        this.form = document.querySelector('form[method="post"]');
        if (!this.form) return;

        // Handle save record button
        const saveBtn = document.getElementById('saveRecordBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.saveRecord(false);
            });
        }

        // Handle complete appointment button
        const completeBtn = document.getElementById('completeAppointmentBtn');
        if (completeBtn) {
            completeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleCompleteAppointment();
            });
        }
    }

    /**
     * Save record only (without completing appointment)
     */
    saveRecord(shouldComplete = false) {
        if (!this.form) return;

        // Add hidden inputs for submission
        this.removeHiddenInputs();
        
        if (shouldComplete) {
            const completeInput = document.createElement('input');
            completeInput.type = 'hidden';
            completeInput.name = 'complete_appointment';
            completeInput.value = '1';
            this.form.appendChild(completeInput);

            const confirmInput = document.createElement('input');
            confirmInput.type = 'hidden';
            confirmInput.name = 'confirm_complete';
            confirmInput.id = 'confirm_complete';
            confirmInput.value = '1';
            this.form.appendChild(confirmInput);
        }

        // Submit form
        this.form.submit();
    }

    /**
     * Handle complete appointment with confirmation
     */
    handleCompleteAppointment() {
        if (this.completeConfirmed) {
            // User already confirmed, save with completion
            this.saveRecord(true);
            return;
        }

        // Show confirmation dialog
        const confirmed = confirm(
            'Are you sure you want to mark this appointment as completed?\n\n' +
            'This action cannot be undone.'
        );

        if (confirmed) {
            this.completeConfirmed = true;
            // Change button text to indicate next click will complete
            const completeBtn = document.getElementById('completeAppointmentBtn');
            if (completeBtn) {
                completeBtn.innerHTML = '<i class="fas fa-check me-2"></i>Confirm Complete';
                completeBtn.classList.add('btn-danger');
                completeBtn.classList.remove('btn-success');
            }
            Toast.warning('Click the button again to confirm completion', 3000);
        }
    }

    /**
     * Remove hidden inputs to avoid duplicates
     */
    removeHiddenInputs() {
        const existingInputs = this.form.querySelectorAll(
            'input[name="complete_appointment"], input[name="confirm_complete"]'
        );
        existingInputs.forEach(input => input.remove());
    }

    /**
     * Reset the button state (call on page load or after submit)
     */
    resetCompleteButton() {
        this.completeConfirmed = false;
        const completeBtn = document.getElementById('completeAppointmentBtn');
        if (completeBtn) {
            completeBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Complete';
            completeBtn.classList.add('btn-success');
            completeBtn.classList.remove('btn-danger');
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    new AppointmentActions();
});
