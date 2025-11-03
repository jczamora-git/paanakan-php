<?php
// Check if a session is active
if (session_status() == PHP_SESSION_ACTIVE) {
    // Unset all session variables
    $_SESSION = [];

    // Destroy the session
    session_destroy();
}

// Check if the form was submitted with "GET"
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["error"])) {
    $error_message = "You must agree to the Terms and Conditions before proceeding.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PSC Appointment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #e8f5e9;
            font-family: 'Poppins', sans-serif;
        }
        .appointment-container {
            background-color: #fff;
            border-radius: 15px;
            max-width: 600px;
            margin: 40px auto 30px auto;
            box-shadow: 0px 8px 24px rgba(0,0,0,0.18);
            padding: 40px 30px 30px 30px;
            position: relative;
            z-index: 2;
        }
        .error-message {
            color: #d9534f;
            font-weight: 500;
            margin-bottom: 15px;
        }
        .steps-section {
            margin: 30px 0 20px 0;
        }
        .step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 18px;
        }
        .step .step-icon {
            width: 38px;
            height: 38px;
            background: #2E8B57;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-right: 15px;
        }
        .step .step-text {
            font-size: 1.05rem;
            color: #333;
        }
        .reminders-section {
            background: #E6F9F1;
            border: 1px solid #D3F2E1;
            border-radius: 10px;
            padding: 18px 20px;
            margin-bottom: 20px;
        }
        .reminders-section ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        .reminders-section li {
            margin-bottom: 7px;
            color: #2E8B57;
        }
        .agree-section {
            margin-top: 18px;
            text-align: left;
        }
        input[type="checkbox"] {
            margin-right: 5px;
            transform: scale(1.2);
        }
        .continue-btn {
            margin-top: 18px;
            padding: 10px 24px;
            background-color: #2E8B57;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 17px;
            transition: 0.3s;
        }
        .continue-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .terms-link {
            color: #2E8B57;
            text-decoration: underline;
            cursor: pointer;
        }
        .contact-section {
            text-align: center;
            margin-top: 30px;
            color: #888;
            font-size: 0.98rem;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="appointment-container">
    <h2 class="mb-3">Welcome!</h2>
    <p class="mb-4">To ensure a smooth appointment process, please review the steps and reminders below before proceeding.</p>
    <?php if (!empty($error_message)) : ?>
        <p class="error-message"><?php echo $error_message; ?></p>
    <?php endif; ?>
    <div class="steps-section">
        <div class="step">
            <div class="step-icon"><i class="fas fa-user-plus"></i></div>
            <div class="step-text">Register or log in to your account.</div>
        </div>
        <div class="step">
            <div class="step-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="step-text">Select your desired appointment type and date.</div>
        </div>
        <div class="step">
            <div class="step-icon"><i class="fas fa-clock"></i></div>
            <div class="step-text">Choose an available time slot.</div>
        </div>
        <div class="step">
            <div class="step-icon"><i class="fas fa-check-circle"></i></div>
            <div class="step-text">Confirm your appointment and receive a summary.</div>
        </div>
    </div>
    <div class="reminders-section mb-4">
        <strong class="mb-2 d-block"><i class="fas fa-info-circle"></i> Reminders Before Booking:</strong>
        <ul>
            <li>Make sure your contact information is up to date.</li>
            <li>Arrive at least 10 minutes before your scheduled appointment.</li>
            <li>Bring a valid ID and any necessary documents.</li>
            <li>If you need to reschedule, please do so at least 24 hours in advance.</li>
        </ul>
    </div>
    <form action="step1.php" method="GET">
        <div class="agree-section">
            <input type="checkbox" id="agreeCheckbox" name="agree" required>
            <label for="agreeCheckbox">I Agree to the <span class="terms-link" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</span></label>
        </div>
        <button type="submit" class="continue-btn">Continue</button>
    </form>
</div>
<div class="contact-section">
    <i class="fas fa-phone-alt"></i> Need help? Contact us at <a href="tel:043-738-1874">043-738-1874</a> or <a href="mailto:info@paanakansacalapan.com">info@paanakansacalapan.com</a>
</div>
<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="max-height: 350px; overflow-y: auto;">
        <p>By using the Paanakan sa Calapan Appointment System, you agree to the following:</p>
        <ul>
            <li>All information provided is accurate and up to date.</li>
            <li>You will arrive on time for your appointment or notify us in advance if you need to reschedule.</li>
            <li>Repeated no-shows or late arrivals may affect your ability to book future appointments.</li>
            <li>Your data will be handled in accordance with our privacy policy.</li>
        </ul>
        <p>For more details, please contact our staff.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
