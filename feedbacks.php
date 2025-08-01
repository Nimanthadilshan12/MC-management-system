<?php
session_start();
if (!isset($_SESSION['UserID']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

$host = "localhost";
$db = "mc1";
$user = "root";
$pass = "";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = '';
$feedbacks = [];
$feedbackCount = 0;

// Set consistent collation for the query
$conn->query("SET collation_connection = 'utf8mb4_unicode_ci'");

// Fetch feedback with faculty information from patients table
$stmt = $conn->prepare("
    SELECT f.feedback_text, f.submit_date, p.Faculty 
    FROM feedback f 
    LEFT JOIN patients p ON f.user_id = p.UserID 
    ORDER BY f.submit_date DESC
");
if ($stmt === false) {
    // Fallback query if the original query fails (e.g., patients table issue)
    $error_message = "Error preparing query: " . $conn->error . ". Displaying feedback without faculty information.";
    $stmt = $conn->prepare("
        SELECT feedback_text, submit_date, NULL as Faculty 
        FROM feedback 
        ORDER BY submit_date DESC
    ");
}
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $feedbacks = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $error_message = "Error preparing fallback query: " . $conn->error;
}

// Count total feedback
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM feedback");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $feedbackCount = $result->fetch_assoc()['count'];
    $stmt->close();
} else {
    $error_message .= " Error counting feedback: " . $conn->error;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>View Patient Feedback - University of Ruhuna Medical Centre</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="../img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500&family=Roboto:wght@500;700;900&display=swap" rel="stylesheet"> 

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="../lib/animate/animate.min.css" rel="stylesheet">
    <link href="../lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="../lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <!-- Customized Bootstrap Stylesheet -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="../css/style.css" rel="stylesheet">

    <!-- Inline CSS for Specific Enhancements and Dark Mode -->
    <style>
        /* Color Variables */
        :root {
            --primary: rgb(86, 85, 183);
            --secondary: #ec4899;
            --accent: #06b6d4;
            --success: #10b981;
            --error: #ef4444;
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

        /* Card Hover Effect */
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15) !important;
        }

        /* Button Hover Effect */
        .btn-primary, .btn-danger, .btn-secondary {
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before, .btn-danger::before, .btn-secondary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn-primary:hover::before, .btn-danger:hover::before, .btn-secondary:hover::before {
            left: 100%;
        }

        /* Modal Animation */
        .modal-content {
            animation: zoomIn 0.4s ease;
        }

        /* Custom Animations */
        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.5); }
            50% { transform: scale(1.2); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.7); }
            to { opacity: 1; transform: scale(1); }
        }

        /* Page Header Background */
        .page-header {
            background-image: url('https://cdn.pixabay.com/photo/2016/11/15/07/58/return-1825515_1280.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(192, 169, 232, 0.5), rgba(207, 139, 173, 0.5));
            z-index: 1;
        }

        .page-header .container {
            position: relative;
            z-index: 2;
        }

        .page-header h1, .page-header .breadcrumb-item a, .page-header .breadcrumb-item {
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        /* Dark Mode Styles */
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

        .dark-mode .card {
            background-color: var(--light-bg);
            color: var(--text);
        }

        .dark-mode .form-control {
            background-color: var(--light-bg);
            color: var(--text);
            border-color: var(--text-light);
        }

        .dark-mode .form-floating > label {
            color: var(--text-light);
        }

        .dark-mode .modal-content {
            background-color: var(--light-bg);
            color: var(--text);
        }

        .dark-mode .btn.btn-outline-light.btn-social {
            background-color: var(--light-bg);
            color: var(--text-light);
        }

        .dark-mode .btn.btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            color: var(--text-light);
        }

        .dark-mode .btn.btn-danger {
            background-color: var(--error);
            border-color: var(--error);
            color: var(--text-light);
        }

        .dark-mode .btn.btn-secondary {
            background-color: var(--secondary);
            border-color: var(--secondary);
            color: var(--text-light);
        }

        .dark-mode .border {
            border-color: var(--text-light) !important;
        }

        .dark-mode .alert {
            color: var(--text-light);
            background-color: var(--light-bg);
            border-color: var(--text-light);
        }

        .dark-mode .list-group-item {
            background-color: var(--light-bg);
            color: var(--text);
        }

        .dark-mode .list-group-item:hover {
            background-color: rgba(124, 58, 237, 0.1);
        }

        #darkModeToggle i {
            font-size: 1.2rem;
        }

        /* Feedback Item Styling */
        .feedback-item {
            transition: background-color 0.3s ease;
        }

        .feedback-item:hover {
            background-color: rgba(86, 85, 183, 0.1);
        }
    </style>
</head>
<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-grow text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

    <!-- Navbar Start -->
    <nav class="navbar navbar-expand-lg bg-white navbar-light sticky-top p-0 wow fadeIn" data-wow-delay="0.1s">
        <a href="../index.php" class="navbar-brand d-flex align-items-center px-4 px-lg-5">
            <h1 class="m-0 text-primary"><i class="far fa-hospital me-3"></i>Medical Centre - University of Ruhuna</h1>
        </a>
        <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav ms-auto p-4 p-lg-0">
                <a href="index.php" class="nav-item nav-link">Home</a>
                <a href="about.html" class="nav-item nav-link">About</a>
                <a href="health_resources.php" class="nav-item nav-link">Health Resources</a>
                <a href="feature.php" class="nav-item nav-link">Opening Information</a>
                <a href="../contact.php" class="nav-item nav-link">Contact</a>
                <button id="darkModeToggle" class="btn btn-primary rounded-circle ms-3" style="width: 40px; height: 40px;">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            <a href="../login.php" class="btn btn-danger rounded-0 py-4 px-lg-5 d-none d-lg-block">Logout<i class="fa fa-arrow-right ms-3"></i></a>
        </div>    
    </nav>
    <!-- Navbar End -->

    <!-- Page Header Start -->
    <div class="container-fluid page-header py-5 mb-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <h1 class="display-3 text-white mb-3 animated slideInDown">View Patient Feedback</h1>
            <nav aria-label="breadcrumb animated slideInDown">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="admin_dashboard.php" class="text-white">Dashboard</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page">Feedback</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Page Header End -->

    <!-- View Feedback Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                <p class="d-inline-block border rounded-pill py-1 px-4 text-primary">Admin Portal</p>
                <h1 class="text-primary">Patient Feedback</h1>
            </div>
            <div class="row g-4">
                <div class="col-12 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="card border-0 shadow-sm bg-light">
                        <div class="card-body">
                            <div class="card-header d-flex justify-content-between align-items-center mb-4 border-0 bg-transparent">
                                <h3 class="text-primary">Feedback Management</h3>
                                <a href="admin_dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                            </div>
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger text-center wow fadeInUp" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?php echo htmlspecialchars($error_message); ?>
                                </div>
                            <?php endif; ?>
                            <div class="mb-3 text-primary" id="feedbackCount">
                                <i class="fas fa-comment-alt me-2"></i>Total Feedback Submitted: <?php echo htmlspecialchars($feedbackCount); ?>
                            </div>
                            <div id="feedbackHistory" class="list-group">
                                <?php if (empty($feedbacks)): ?>
                                    <div class="list-group-item text-center">No feedback submitted yet.</div>
                                <?php else: ?>
                                    <?php foreach ($feedbacks as $index => $feedback): ?>
                                        <div class="list-group-item feedback-item" id="feedbackItem_<?php echo $index; ?>">
                                            <p><strong>Faculty:</strong> <?php echo htmlspecialchars($feedback['Faculty'] ?? 'Unknown'); ?></p>
                                            <p><strong>Feedback:</strong> <?php echo htmlspecialchars($feedback['feedback_text']); ?></p>
                                            <p><strong>Date:</strong> <?php echo htmlspecialchars($feedback['submit_date']); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- View Feedback End -->

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
                    <a class="btn btn-link" href="about.html">About Us</a>
                    <a class="btn btn-link" href="health_resources.php">Health Resources</a>
                    <a class="btn btn-link" href="feature.php">Opening Information</a>
                    <a class="btn btn-link" href="contact.html">Contact Us</a>
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
    <a href="#" class="btn btn-lg btn-primary btn-lg-square rounded-circle back-to-top" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;"><i class="bi bi-arrow-up"></i></a>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../lib/wow/wow.min.js"></script>
    <script src="../lib/easing/easing.min.js"></script>
    <script src="../lib/waypoints/waypoints.min.js"></script>
    <script src="../lib/counterup/counterup.min.js"></script>
    <script src="../lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="../lib/tempusdominus/js/moment.min.js"></script>
    <script src="../lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="../lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

    <!-- Template Javascript -->
    <script src="../js/main.js"></script>
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