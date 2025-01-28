// Add any specific JavaScript functionality for the login page here, if needed
// Example: Toggle password visibility (similar logic can be applied)
document.getElementById("toggle-password").addEventListener("click", function () {
    const passwordField = document.getElementById("password");
    const eyeIcon = document.getElementById("eye-icon");
    if (passwordField.type === "password") {
        passwordField.type = "text";
        eyeIcon.textContent = "visibility";
    } else {
        passwordField.type = "password";
        eyeIcon.textContent = "visibility_off";
    }
});
