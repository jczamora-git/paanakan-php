// Toggle password visibility on the Register Page
document.getElementById("toggle-password").addEventListener("click", function () {
    const passwordField = document.getElementById("password");
    const eyeIcon = document.getElementById("eye-icon");
    if (passwordField.type === "password") {
        passwordField.type = "text";
        eyeIcon.textContent = "visibility"; // Change icon to 'visibility'
    } else {
        passwordField.type = "password";
        eyeIcon.textContent = "visibility_off"; // Change icon to 'visibility_off'
    }
});

document.getElementById('register-form').addEventListener('submit', function (event) {
    var isValid = true;

    // Validate Contact Number
    var contactNumber = document.getElementById('contact_number').value;
    if (contactNumber.length !== 11 || isNaN(contactNumber)) {
        event.preventDefault();
        document.getElementById('contact-number-error').style.display = 'block';
        isValid = false;
    } else {
        document.getElementById('contact-number-error').style.display = 'none';
    }

    // Validate Password and Confirm Password
    var password = document.getElementById('password').value;
    var confirmPassword = document.getElementById('confirm-password').value;
    if (password !== confirmPassword) {
        event.preventDefault();
        document.getElementById('password-error').style.display = 'block';
        isValid = false;
    } else {
        document.getElementById('password-error').style.display = 'none';
    }

    // Only submit the form if all validations pass
    if (!isValid) {
        return false;
    }
});

// Show error alert if email or username already exists
function showErrorAlert() {
    const errorMessage = document.getElementById('error-message');
    errorMessage.style.display = 'block'; // Display the error alert
}

// Hide the error alert
function hideErrorAlert() {
    const errorMessage = document.getElementById('error-message');
    errorMessage.style.display = 'none'; // Hide the error alert
}
document.getElementById("test-alert-btn").addEventListener("click", function () {
    alert("Alert is working!");
});