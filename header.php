<?php
// Start the session (if using authentication, session tracking)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paanakan sa Calapan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Favicon: prefer psc.ico at site root; fall back to PSC.png if .ico not present -->
    <link rel="icon" href="/psc.ico" type="image/x-icon">
    <link rel="icon" href="/PSC.png" type="image/png">
    <link rel="apple-touch-icon" href="/PSC.png">
    <script>
        function updateDate() {
            const dateElement = document.getElementById("current-date");
            const now = new Date();
            // Format date in Manila timezone
            const manilaTime = new Intl.DateTimeFormat('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true,
                timeZone: 'Asia/Manila'
            }).format(now);
            dateElement.innerHTML = `Philippine Standard Time | ${manilaTime}`;
        }

        // Update time immediately when page loads
        window.onload = function() {
            updateDate();
            // Update time every second
            setInterval(updateDate, 1000);
        };

        function toggleButton() {
            const checkbox = document.getElementById("agreeCheckbox");
            const button = document.getElementById("continueBtn");
            button.disabled = !checkbox.checked;
        }
    </script>
    <link rel="stylesheet" href="style.css"><!-- Header -->
</head>
<body>

    <!-- Header -->
    <nav class="header">
        <div class="header-left">
            <div class="logo-container">
                <img src="PSC_white.png" alt="Logo" class="logo">
                <div class="header-title">
                    Paanakan sa Calapan<br>
                    <div id="current-date" class="date-container"></div>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center" id="nav">
            <div>
                <a href="index.php">
                <button class="home-btn">Home</button>
                </a>
            </div>

            <!-- Services Dropdown -->
            <div class="dropdown">
                <button class="dropdown-btn">Services</button>
                <div class="dropdown-content">
                    <a href="appointment.php">Appointment</a>
                </div>
            </div>

            <!-- The PSC Dropdown -->
            <div class="dropdown">
                <button class="dropdown-btn">The PSC</button>
                <div class="dropdown-content">
                    <a href="about.php">About Us</a>
                    <a href="contact.php">Contact Us</a>
                </div>
            </div>
            <a href="login.php">
                <button class="login-btn">Login</button>
            </a>

        </div>
    </nav>

</body>
</html>
