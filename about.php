<?php
// Start session and database connection
$host = "localhost";
$db = "mc1";
$user = "root";
$pass = "";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch doctors from the database
$stmt = $conn->prepare("SELECT Fullname, Specialization FROM doctors");
$stmt->execute();
$doctors = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Medical Centre - University of Ruhuna</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500&family=Roboto:wght@500;700;900&display=swap" rel="stylesheet"> 

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">

    <!-- Inline CSS for Dark Mode -->
     <style>
        /* Color Variables */
        :root {
            --primary: rgb(86, 85, 183); /* Purple, matches login.php, contact.html, health_resources.php */
            --secondary: #ec4899; /* Pink, matches login.php, contact.html, health_resources.php */
            --accent: #06b6d4; /* Cyan */
            --success: #10b981; /* Green */
            --error: #ef4444; /* Red */
            --background: #ffffff;
            --text: #000000;
            --light-bg: #f8f9fa;
            --dark-bg: rgb(8, 50, 92);
            --text-light: #ffffff;
        }

        .dark-mode {
            --background: #1a1a1a;
            --text: #e0e0e0;
            --light-bg: #2c2c2c;
            --dark-bg: rgb(56, 41, 150);
            --text-light: #e0e0e0;
        }

        body {
            background-color: var(--background);
            color: var(--text);
        }

        .bg-light {
            background-color: var(--light-bg) !important;
        }

        .bg-dark {
            background-color: var(--dark-bg) !important;
        }

        .text-light {
            color: var(--text-light) !important;
        }

        .text-primary {
            color: var(--primary) !important;
        }

        .navbar.bg-white {
            background-color: var(--background) !important;
        }

        .navbar-light .navbar-nav .nav-link {
            color: var(--text);
        }

        .dark-mode .page-header::before {
            background: rgba(0, 0, 0, 0.5); /* Darker overlay for better header contrast, matches health_resources.php */
        }

        .page-header .container {
            position: relative;
            z-index: 2;
        }

        .page-header h1, .page-header .breadcrumb-item a, .page-header .breadcrumb-item {
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .page-header h1, .dark-mode .page-header .breadcrumb-item a, .dark-mode .page-header .breadcrumb-item {
            color: var(--text-light) !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        }

        .dark-mode h1, .dark-mode h3, .dark-mode h5, .dark-mode .border.rounded-pill {
            color: var(--text-light) !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        }

        .dark-mode .mission-vision-text, .dark-mode .team-text {
            background-color: var(--light-bg);
            color: var(--text-light);
        }

        .dark-mode .mission-vision-text p, .dark-mode .team-text p {
            color: var(--text-light) !important;
        }

        .dark-mode .mission-vision-text a, .dark-mode .team-text a {
            color: var(--accent);
        }

        .dark-mode .mission-vision-text a:hover, .dark-mode .team-text a:hover {
            color: var(--success);
        }

        .dark-mode .btn.btn-outline-light.btn-social {
            background-color: var(--light-bg);
            color: var(--text-light);
        }

        .dark-mode .btn.btn-outline-light.btn-social:hover {
            background-color: var(--accent);
            color: var(--text-light);
        }

        .dark-mode .btn.btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            color: var(--text-light);
        }

        .dark-mode .border {
            border-color: var(--text-light) !important;
        }

        #darkModeToggle i {
            font-size: 1.2rem;
        }
    </style>
</head>

<body>
    

    <!-- Navbar Start -->
    <nav class="navbar navbar-expand-lg bg-white navbar-light sticky-top p-0 wow fadeIn" data-wow-delay="0.1s">
        <a href="index.php" class="navbar-brand d-flex align-items-center px-4 px-lg-5">
            <h1 class="m-0 text-primary"><i class="far fa-hospital me-3"></i>Medical Centre - University of Ruhuna</h1>
        </a>
        <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav ms-auto p-4 p-lg-0">
                <a href="index.php" class="nav-item nav-link">Home</a>
                <a href="about.php" class="nav-item nav-link active">About</a>
                <a href="health_resources.php" class="nav-item nav-link">Health Resources</a>
                <a href="feature.php" class="nav-item nav-link">Opening Information</a>
                <a href="contact.php" class="nav-item nav-link">Contact</a>
                <button id="darkModeToggle" class="btn btn-primary rounded-circle ms-3" style="width: 40px; height: 40px;">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            <a href="login.php" class="btn btn-primary rounded-0 py-4 px-lg-5 d-none d-lg-block">LogIn/SignUp<i class="fa fa-arrow-right ms-3"></i></a>
        </div>
    </nav>
    <!-- Navbar End -->

    <!-- Page Header Start -->
    <div class="container-fluid page-header py-5 mb-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <h1 class="display-3 text-white mb-3 animated slideInDown">About Us</h1>
            <nav aria-label="breadcrumb animated slideInDown"></nav>
        </div>
    </div>
    <!-- Page Header End -->

    <!-- About Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6 wow fadeIn" data-wow-delay="0.1s">
                    <div class="d-flex flex-column">
                        <img class="img-fluid rounded w-75 align-self-end" src="https://media.licdn.com/dms/image/v2/C5622AQFYGGfSOivQuA/feedshare-shrink_2048_1536/feedshare-shrink_2048_1536/0/1628672231584?e=2147483647&v=beta&t=OA2qUyGTN5G3uvopS7LU34OiLMy2f7TkyHZqAYe15XA" alt="">
                        <img class="img-fluid rounded w-50 bg-white pt-3 pe-3" src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTLB5zR6nbgtnYxiiWqKV-BwqcFt5I8UHmYGg&s" alt="" style="margin-top: -25%;">
                    </div>
                </div>
                <div class="col-lg-6 wow fadeIn" data-wow-delay="0.5s">
                    <p class="d-inline-block border rounded-pill py-1 px-4">About Us</p>
                    <h1 class="mb-4">Get Know About Us!</h1>
                    <p>The University of Ruhuna Medical Centre, established in 1985 at the Wellamadama complex in Matara, Sri Lanka, is a vital component of the university's commitment to the health and well-being of its students, staff, and the surrounding community. As part of the prestigious University of Ruhuna, the only university in Southern Sri Lanka, the Medical Centre provides comprehensive preventive and curative health services to support an active and healthy lifestyle.</p>
                    <p class="mb-4">Our dedicated team of healthcare professionals offers a range of medical services, including general consultations, diagnostic support, and preventive care, ensuring the physical and mental well-being of the university community. The centre collaborates closely with the Faculty of Medicine and the Faculty of Allied Health Sciences, both located in Karapitiya, Galle, to integrate cutting-edge medical education and research into our healthcare practices.</p>
                    <p class="mb-4">Situated within the vibrant 72-acre main campus in Matara, the Medical Centre is a cornerstone of the university’s mission to foster social well-being and academic excellence. We are proud to serve as a trusted healthcare resource, contributing to the holistic development of our community while supporting the university’s vision to be the prime intellectual thrust of the nation.</p>
                    <p><i class="far fa-check-circle text-primary me-3"></i>Quality health care</p>
                    <p><i class="far fa-check-circle text-primary me-3"></i>Only Qualified Doctors</p>
                    <p><i class="far fa-check-circle text-primary me-3"></i>Comprehensive pharmacy services</p>
                    <p><i class="far fa-check-circle text-primary me-3"></i>Health education and counseling</p>
                    <p><i class="far fa-check-circle text-primary me-3"></i>Emergency medical support</p>
                </div>
            </div>
        </div>
    </div>
    <!-- About End -->

    <!-- Mission and Vision Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6 wow fadeIn" data-wow-delay="0.1s">
                    <div class="d-flex flex-column">
                        <img class="img-fluid rounded w-75 align-self-end" src="https://www.blogs.opengrowth.com/assets/uploads/images/co_brand_1/article/2023/importance-of-mission-and-vision-17006585731.png" alt="Mission and Vision">
                    </div>
                </div>
                <div class="col-lg-6 mission-vision-text wow fadeIn" data-wow-delay="0.5s">
                    <p class="d-inline-block border rounded-pill py-1 px-4">Mission & Vision</p>
                    <h1 class="mb-4">Our Mission & Vision</h1>
                    <h3 class="text-primary mb-3">Mission</h3>
                    <p class="mb-4">To provide compassionate, accessible, and high-quality healthcare services to the students, staff, and community of the University of Ruhuna, fostering physical and mental well-being through evidence-based medical care, health education, and preventive initiatives, while promoting a culture of wellness and academic success.</p>
                    <h3 class="text-primary mb-3">Vision</h3>
                    <p class="mb-4">To be a leading university medical centre recognized for excellence in holistic healthcare, innovative health education, and community engagement, empowering the University of Ruhuna community to thrive in a healthy and supportive environment.</p>
                </div>
            </div>
        </div>
    </div>
    <!-- Mission and Vision End -->

    <!-- Team Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                <p class="d-inline-block border rounded-pill py-1 px-4ăl">Doctors</p>
                <h1>Our Experienced Doctors</h1>
            </div>
            <div class="row g-4">
                <?php if ($doctors->num_rows > 0): ?>
                    <?php $delay = 0.1; ?>
                    <?php while ($doctor = $doctors->fetch_assoc()): ?>
                        <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="<?php echo $delay; ?>s">
                            <div class="team-item position-relative rounded overflow-hidden">
                                <div class="overflow-hidden">
                                    <!-- Placeholder image since no image field in doctors table -->
                                    <img class="img-fluid" src="img/team-placeholder.jpg" alt="<?php echo htmlspecialchars($doctor['Fullname']); ?>">
                                </div>
                                <div class="team-text bg-light text-center p-4">
                                    <h5><?php echo htmlspecialchars($doctor['Fullname']); ?></h5>
                                    <p class="text-primary"><?php echo htmlspecialchars($doctor['Specialization'] ?: 'General Practitioner'); ?></p>
                                    <div class="team-social text-center">
                                        <a class="btn btn-square" href=""><i class="fab fa-facebook-f"></i></a>
                                        <a class="btn btn-square" href=""><i class="fab fa-twitter"></i></a>
                                        <a class="btn btn-square" href=""><i class="fab fa-instagram"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php $delay += 0.2; ?>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">No doctors found in the database.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Team End -->

    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-light footer mt-5 pt-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-3 col-md-6">
                    <h5 class="text-light mb-4">Address</h5>
                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>University of Ruhuna, Matara, Sri Lanka</p>
                    <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+94 41 2222681</p>
                    <p class="mb-2"><i class="fa fa-envelope me-3"></i>medicalcentre@ruh.ac.lk</p>
                    <div class="d-flex pt-2">
                        <a class="btn btn-outline-light btn-social rounded-circle" href=""><i class="fab fa-twitter"></i></a>
                        <a class="btn btn-outline-light btn-social rounded-circle" href=""><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-outline-light btn-social rounded-circle" href=""><i class="fab fa-youtube"></i></a>
                        <a class="btn btn-outline-light btn-social rounded-circle" href=""><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="text-light mb-4">Quick Links</h5>
                    <a class="btn btn-link" href="login.php">LogIn</a>
                    <a class="btn btn-link" href="about.php">About Us</a>
                    <a class="btn btn-link" href="health_resources.php">Health Resources</a>
                    <a class="btn btn-link" href="feature.php">Opening Information</a>
                    <a class="btn btn-link" href="contact.php">Contact Us</a>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="copyright">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        © <a class="border-bottom" href="#">Medical Centre-UOR</a>, All Right Reserved.
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->

    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square rounded-circle back-to-top"><i class="bi bi-arrow-up"></i></a>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/counterup/counterup.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
    <script>
        // Dark Mode Toggle Script
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const body = document.documentElement;

            // Check for saved preference
            if (localStorage.getItem('darkMode') === 'enabled') {
                body.classList.add('dark-mode');
                darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            }

            darkModeToggle.addEventListener('click', function() {
                body.classList.toggle('dark-mode');
                if (body.classList.contains('dark-mode')) {
                    darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                    localStorage.setItem('darkMode', 'enabled');
                } else {
                    darkModeToggle.innerHTML = '<i class="fas fa-moon"></i>';
                    localStorage.setItem('darkMode', 'disabled');
                }
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
