<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paanakan sa Calapan - Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        function updateDate() {
            const dateElement = document.getElementById("current-date");
            const now = new Date();
            dateElement.innerHTML = `Philippine Standard Time | ${now.toLocaleString("en-US", { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' })}`;
        }
        window.onload = updateDate;
    </script>
    <link rel="stylesheet" href="style.css">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('img/Paanakan_Place.png');
            background-size: cover;
            background-position: center;
            min-height: 80vh;
            display: flex;
            align-items: center;
            color: white;
            background-color: #888; /* fallback */
        }
        .service-card {
            transition: transform 0.3s;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .service-card:hover {
            transform: translateY(-5px);
        }
        .service-icon {
            font-size: 2.5rem;
            color: #2E8B57;
            margin-bottom: 1rem;
        }
        .about-section {
            background-color: #f8f9fa;
            padding: 5rem 0;
        }
        .contact-info {
            background-color: #2E8B57;
            color: white;
            padding: 2rem;
            border-radius: 10px;
        }
        .contact-icon {
            font-size: 1.5rem;
            margin-right: 10px;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <h1 class="display-4 fw-bold mb-4">Welcome to Paanakan sa Calapan</h1>
                    <p class="lead mb-4">Providing quality healthcare services with compassion and excellence since 1995.</p>
                    <a href="appointment.php" class="btn btn-success btn-lg">Book an Appointment</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Our Services</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card service-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-baby service-icon"></i>
                            <h5 class="card-title">Maternal Care</h5>
                            <p class="card-text">Comprehensive care for expectant mothers, including prenatal and postnatal services.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card service-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-heartbeat service-icon"></i>
                            <h5 class="card-title">Pediatric Care</h5>
                            <p class="card-text">Specialized healthcare services for children from birth through adolescence.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card service-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-stethoscope service-icon"></i>
                            <h5 class="card-title">General Medicine</h5>
                            <p class="card-text">Complete medical services for patients of all ages with various health concerns.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <h2 class="mb-4">About Us</h2>
            <p class="lead">Paanakan sa Calapan is committed to providing exceptional healthcare services to our community.</p>
            <p>With over 25 years of experience, we have built a reputation for excellence in maternal and child healthcare. Our team of dedicated healthcare professionals works tirelessly to ensure the well-being of our patients.</p>
            <a href="about.php" class="btn btn-outline-success">Learn More</a>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Contact Us</h2>
            <div class="row">
                <div class="col-md-6">
                    <div class="contact-info">
                        <h4 class="mb-4">Get in Touch</h4>
                        <p><i class="fas fa-map-marker-alt contact-icon"></i> Unit 6, Martinez Building, Roxas Drive, Lumang Bayan, Calapan City, Oriental Mindoro</p>
                        <p><i class="fas fa-phone contact-icon"></i> (043) 286-7728</p>
                        <p><i class="fas fa-envelope contact-icon"></i> info@paanakansacalapan.com</p>
                        <p><i class="fas fa-clock contact-icon"></i> Open 24/7 for Emergency Services</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <form class="p-4 shadow rounded">
                        <div class="mb-3">
                            <input type="text" class="form-control" placeholder="Your Name" required>
                        </div>
                        <div class="mb-3">
                            <input type="email" class="form-control" placeholder="Your Email" required>
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" rows="4" placeholder="Your Message" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Paanakan sa Calapan</h5>
                    <p>Providing quality healthcare services to our community.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>&copy; 2024 Paanakan sa Calapan. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
