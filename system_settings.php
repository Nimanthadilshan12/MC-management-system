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

$UserID = $_SESSION['UserID'];
$stmt = $conn->prepare("SELECT Fullname, Email, Contact_No FROM admins WHERE UserID = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $UserID);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}
$result = $stmt->get_result();
if (!$result) {
    die("Query failed: " . $conn->error);
}
$user = $result->fetch_assoc();
if (!$user) {
    die("No admin found with UserID: " . htmlspecialchars($UserID));
}
$stmt->close();

$message = "";
$settings = [];

// Fetch current settings
$result = $conn->query("SELECT setting_key, setting_value FROM settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} else {
    die("Settings query failed: " . $conn->error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'opening_time', 'closing_time', 'operation_days',
        'emergency_contact_number', 'admin_email',
        'maintenance_mode', 'maintenance_message'
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $value = $_POST[$field];
            // Basic validation
            if ($field === 'operation_days') {
                $value = implode(',', array_filter($_POST[$field], function($day) { return in_array($day, ['1','2','3','4','5','6','7']); }));
            } elseif ($field === 'admin_email') {
                $value = filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : $settings[$field];
            } elseif ($field === 'maintenance_mode') {
                $value = $value === '1' ? '1' : '0';
            }

            $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ss", $value, $field);
            if (!$stmt->execute()) {
                die("Execute failed: " . $stmt->error);
            }
            $stmt->close();
        }
    }
    $message = "Settings updated successfully!";
    // Refresh settings
    $result = $conn->query("SELECT setting_key, setting_value FROM settings");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } else {
        die("Settings refresh query failed: " . $conn->error);
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>System Settings - University Medical Centre</title>
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

    <!-- Inline CSS for Specific Enhancements and Dark Mode -->
    <style>:root {
            --primary: rgb(86, 85, 183); /* Purple */
            --secondary: #ec4899; /* Pink */
            --accent: #06b6d4; /* Cyan */
            --success: #10b981; /* Green */
            --error: #ef4444; /* Red */
            --background: #ffffff;
            --text: #000000;
            --light-bg: #f8f9fa;
            --dark-bg: rgb(8, 50, 92);
            --text-light: #ffffff;
            --text-dark: #2c2c2c;
        }

         .dark-mode {
            --background: #1a1a1a;
            --text: #e0e0e0;
            --light-bg: #2c2c2c;
            --dark-bg: rgb(56, 41, 150);
            --text-light: #e0e0e0;
            --text-dark: #2c2c2c;
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

        .page-header {
            background-image: url('https://cdn.pixabay.com/photo/2015/09/11/08/48/banner-935469_1280.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }

        .dark-mode .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5); /* Darker overlay for readability in dark mode */
            z-index: 1;
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
        .dark-mode .form-control {
            background-color: var(--light-bg);
            color: var(--text);
            border-color: var(--text-light);
        }

        .dark-mode .form-floating > label {
            color: var(--text-light);
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

        .dark-mode .border {
            border-color: var(--text-light) !important;
        }

        .dark-mode .alert {
            color: var(--text-light);
            background-color: var(--light-bg);
            border-color: var(--text-light);
        }

        #darkModeToggle i {
            font-size: 1.2rem;
        }
 body {
            background-color: var(--background);
            color: var(--text);
            margin: 0;
            font-family: 'Open Sans', sans-serif;
            position: relative;
            min-height: 100vh;
            overflow-x: hidden;
        }
         .card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            box-shadow: 0 12px 50px rgba(0, 50, 120, 0.15), 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 40px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0, 50, 120, 0.2), 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .card-header h2 {
            font-family: 'Roboto', sans-serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }
         .message {
            text-align: center;
            color: var(--success);
            background: rgba(16, 185, 129, 0.1);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .section-title {
            font-family: 'Roboto', sans-serif;
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--primary);
            margin-top: 30px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 10px;
            color: var(--accent);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 500;
            color: var(--text);
            display: block;
            margin-bottom: 5px;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #d1d9e6;
            background: #f8fafc;
            padding: 10px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 8px rgba(86, 85, 183, 0.2);
            outline: none;
        }

        .form-check-label {
            color: var(--text);
            margin-left: 10px;
        }
        .btn-secondary {
            background-color: var(--dark-bg);
            border-color: var(--dark-bg);
            color: var(--text-light);
            border-radius: 0;
            padding: 10px 30px;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-secondary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.4s ease;
        }

        .btn-secondary:hover {
            background-color: darken(var(--dark-bg), 10%);
            transform: scale(1.05);
        }

        .btn-secondary:hover::before {
            left: 100%;
        }

        .dark-mode .card {
            background: rgba(44, 44, 44, 0.98);
            color: var(--text-light);
        }

        .dark-mode .form-control {
            background-color: var(--light-bg);
            color: var(--text);
            border-color: var(--text-light);
        }

        .dark-mode .form-label {
            color: var(--text-light);
        }

        .dark-mode .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            color: var(--text-light);
        }

        .dark-mode .btn-secondary {
            background-color: var(--dark-bg);
            border-color: var(--dark-bg);
            color: var(--text-light);
        }

         .dark-mode h1, .dark-mode h3, .dark-mode h5, .dark-mode .border.rounded-pill {
            color: var(--text-light) !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
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

        .dark-mode .border {
            border-color: var(--text-light) !important;
        }

        .dark-mode .alert {
            color: var(--text-light);
            background-color: var(--light-bg);
            border-color: var(--text-light);
        }

        
        @media (max-width: 768px) {
            .container {
                margin-top: 60px;
                padding: 0 15px;
            }
            .card {
                padding: 30px;
                border-radius: 16px;
            }
            .card-header h2 {
                font-size: 2rem;
            }
            .card-header {
                flex-direction: column;
                gap: 15px;
            }
            .btn-primary, .btn-secondary {
                padding: 10px 20px;
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .container {
                margin-top: 40px;
            }
            .card {
                padding: 20px;
                border-radius: 12px;
            }
        }

        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }
        #darkModeToggle i {
            font-size: 1.2rem;
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
            <a href="../login.php" class="btn btn-primary rounded-0 py-4 px-lg-5 d-none d-lg-block">Logout<i class="fa fa-arrow-right ms-3"></i></a>
        </div>    
    </nav>
    <!-- Navbar End -->

    <!-- Page Header Start -->
    <div class="container-fluid page-header py-5 mb-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <h1 class="display-3 text-white mb-3 animated slideInDown">System Settings</h1>
            <nav aria-label="breadcrumb animated slideInDown">
               
            </nav>
        </div>
    </div>
    <!-- Page Header End -->

    <!-- System Settings Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h2>System Settings</h2>
                    <a href="admin_dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>
                <?php if ($message): ?>
                    <div class="message"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <h6 class="section-title"><i class="fas fa-clock"></i>Operational Hours</h6>
                    <div class="form-group">
                        <label class="form-label">Opening Time</label>
                        <input type="time" class="form-control" name="opening_time" value="<?php echo htmlspecialchars($settings['opening_time']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Closing Time</label>
                        <input type="time" class="form-control" name="closing_time" value="<?php echo htmlspecialchars($settings['closing_time']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Days of Operation</label>
                        <?php $operation_days = explode(',', $settings['operation_days']); ?>
                        <div class="d-flex flex-wrap gap-3">
                            <?php $days = ['1' => 'Monday', '2' => 'Tuesday', '3' => 'Wednesday', '4' => 'Thursday', '5' => 'Friday', '6' => 'Saturday', '7' => 'Sunday']; ?>
                            <?php foreach ($days as $day_num => $day_name): ?>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="operation_days[]" value="<?php echo $day_num; ?>" <?php echo in_array($day_num, $operation_days) ? 'checked' : ''; ?>>
                                    <label class="form-check-label"><?php echo $day_name; ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <h6 class="section-title"><i class="fas fa-phone-alt"></i>Emergency Contact Information</h6>
                    <div class="form-group">
                        <label class="form-label">Emergency Contact Number</label>
                        <input type="text" class="form-control" name="emergency_contact_number" value="<?php echo htmlspecialchars($settings['emergency_contact_number']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Administrative Email</label>
                        <input type="email" class="form-control" name="admin_email" value="<?php echo htmlspecialchars($settings['admin_email']); ?>" required>
                    </div>

                    <h6 class="section-title"><i class="fas fa-tools"></i>System Maintenance</h6>
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="maintenance_mode" value="1" <?php echo $settings['maintenance_mode'] === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label">Enable Maintenance Mode</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Maintenance Message</label>
                        <textarea class="form-control" name="maintenance_message" rows="4"><?php echo htmlspecialchars($settings['maintenance_message']); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-save me-2"></i>Save Settings</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- System Settings End -->

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
