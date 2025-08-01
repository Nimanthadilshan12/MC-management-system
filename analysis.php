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

// Determine the selected week
$selected_date = isset($_POST['visit_date']) ? $_POST['visit_date'] : date('Y-m-d');
$selected_date_obj = new DateTime($selected_date);
$selected_date_obj->modify('monday this week');
$week_start = $selected_date_obj->format('Y-m-d');
$selected_date_obj->modify('+6 days');
$week_end = $selected_date_obj->format('Y-m-d');

// Format week range for display
$week_start_display = date('F j, Y', strtotime($week_start));
$week_end_display = date('F j, Y', strtotime($week_end));

// Initialize arrays for chart data
$visit_counts = array_fill(0, 7, 0);
$visit_labels = [];
$diseases = [];
$disease_counts = [];
$medicines = [];
$medicine_quantities = [];

// Generate labels for each day of the week
$date = new DateTime($week_start);
for ($i = 0; $i < 7; $i++) {
    $visit_labels[] = $date->format('D, M j');
    $date->modify('+1 day');
}

// Error logging for debugging
$errors = [];

// Fetch patient visits
$stmt = $conn->prepare("
    SELECT DATE(DateIssued) AS visit_day, COUNT(DISTINCT PatientID) AS visit_count
    FROM prescriptions
    WHERE DateIssued BETWEEN ? AND ?
    GROUP BY DATE(DateIssued)
    ORDER BY visit_day
");
if (!$stmt) {
    $errors[] = "Failed to prepare patient visits query: " . $conn->error;
} else {
    $stmt->bind_param("ss", $week_start, $week_end);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $day_index = (new DateTime($row['visit_day']))->diff(new DateTime($week_start))->days;
            if ($day_index >= 0 && $day_index < 7) {
                $visit_counts[$day_index] = $row['visit_count'];
            }
        }
    } else {
        $errors[] = "Failed to execute patient visits query: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch illness distribution
$stmt = $conn->prepare("
    SELECT Illness, COUNT(*) AS illness_count
    FROM prescriptions
    WHERE DateIssued BETWEEN ? AND ? AND Illness IS NOT NULL
    GROUP BY Illness
    ORDER BY illness_count DESC
    LIMIT 5
");
if (!$stmt) {
    $errors[] = "Failed to prepare illness query: " . $conn->error;
} else {
    $stmt->bind_param("ss", $week_start, $week_end);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $diseases[] = $row['Illness'] ?: 'Unknown';
            $disease_counts[] = $row['illness_count'];
        }
    } else {
        $errors[] = "Failed to execute illness query: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch medication dispensing
$stmt = $conn->prepare("
    SELECT i.medication_name, SUM(d.QuantityDispensed) AS total_quantity
    FROM dispense_log d
    JOIN inventory i ON d.MedicineID = i.id
    WHERE d.DispenseDate BETWEEN ? AND ?
    GROUP BY i.medication_name
    ORDER BY total_quantity DESC
    LIMIT 5
");
if (!$stmt) {
    $errors[] = "Failed to prepare medication query: " . $conn->error;
} else {
    $stmt->bind_param("ss", $week_start, $week_end);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $medicines[] = $row['medication_name'];
            $medicine_quantities[] = $row['total_quantity'];
        }
    } else {
        $errors[] = "Failed to execute medication query: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();

// If no data for medicines, provide fallback
if (empty($medicines)) {
    $medicines = ['No Data'];
    $medicine_quantities = [0];
}
if (empty($diseases)) {
    $diseases = ['No Data'];
    $disease_counts = [0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Data Analysis - University Medical Centre</title>
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

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Inline CSS -->
    <style>
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

        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15) !important;
        }

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

        .modal-content {
            animation: zoomIn 0.4s ease;
        }

        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.5); }
            50% { transform: scale(1.2); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.7); }
            to { opacity: 1; transform: scale(1); }
        }

        .page-header {
            background-image: url('https://static.vecteezy.com/system/resources/thumbnails/051/360/291/small_2x/an-orange-bar-chart-is-shown-next-to-a-window-photo.jpg');
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

        .dark-mode .no-data-message {
            background-color: #3a2f1f;
            color: #ffca2c;
        }

        #darkModeToggle i {
            font-size: 1.2rem;
        }

        .chart-container {
            max-width: 600px;
            margin: 0 auto 30px;
            position: relative;
        }

        .chart-title {
            font-size: 1.5rem;
            font-weight: 500;
            color: var(--primary);
            text-align: center;
            margin-bottom: 15px;
        }

        .no-data-message {
            text-align: center;
            color: #856404;
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
            <h1 class="display-3 text-white mb-3 animated slideInDown">Data Analysis Dashboard</h1>
            <nav aria-label="breadcrumb animated slideInDown">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="admin_dashboard.php" class="text-white">Dashboard</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page">Data Analysis</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Page Header End -->

    <!-- Data Analysis Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                <p class="d-inline-block border rounded-pill py-1 px-4 text-primary">Admin Portal</p>
                <h1 class="text-primary">Data Analysis Dashboard</h1>
            </div>
            <div class="row g-4">
                <div class="col-12 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="card border-0 shadow-sm bg-light">
                        <div class="card-body">
                            <div class="card-header d-flex justify-content-between align-items-center mb-4 border-0 bg-transparent">
                                <h3 class="text-primary">Weekly Analysis</h3>
                                <a href="admin_dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                            </div>
                            <?php if (!empty($errors)) { ?>
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    <strong>Database Errors:</strong>
                                    <ul>
                                        <?php foreach ($errors as $error) { ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php } ?>
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php } ?>
                            <form method="POST" class="form-floating mb-4">
                                <div class="form-floating mb-3" style="max-width: 300px;">
                                    <input type="date" class="form-control" id="visit_date" name="visit_date" value="<?php echo htmlspecialchars($selected_date); ?>">
                                    <label for="visit_date"><i class="fas fa-calendar-alt me-2"></i>Select Date for Weekly Analysis</label>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-2"></i>Analyze</button>
                            </form>
                            <div class="chart-container">
                                <h4 class="chart-title">Patient Visits (<?php echo $week_start_display . ' - ' . $week_end_display; ?>)</h4>
                                <?php if (array_sum($visit_counts) == 0) { ?>
                                    <div class="no-data-message">No patient visit data available for this week.</div>
                                <?php } else { ?>
                                    <canvas id="visitChart"></canvas>
                                <?php } ?>
                            </div>
                            <div class="chart-container">
                                <h4 class="chart-title">Common Illnesses (<?php echo $week_start_display . ' - ' . $week_end_display; ?>)</h4>
                                <?php if (empty($diseases) || $diseases[0] == 'No Data') { ?>
                                    <div class="no-data-message">No illness data available for this week.</div>
                                <?php } else { ?>
                                    <canvas id="diseaseChart"></canvas>
                                <?php } ?>
                            </div>
                            <div class="chart-container">
                                <h4 class="chart-title">Most Dispensed Medicines (<?php echo $week_start_display . ' - ' . $week_end_display; ?>)</h4>
                                <?php if (empty($medicines) || $medicines[0] == 'No Data') { ?>
                                    <div class="no-data-message">No medication dispensing data available for this week.</div>
                                <?php } else { ?>
                                    <canvas id="medicineChart"></canvas>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Data Analysis End -->

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
        // Patient Visits Chart
        <?php if (array_sum($visit_counts) > 0) { ?>
            const visitCtx = document.getElementById('visitChart').getContext('2d');
            new Chart(visitCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($visit_labels); ?>,
                    datasets: [{
                        label: 'Number of Visits',
                        data: <?php echo json_encode($visit_counts); ?>,
                        backgroundColor: 'rgba(86, 85, 183, 0.5)',
                        borderColor: 'rgba(86, 85, 183, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Visits'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Day'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });
        <?php } ?>

        // Disease Chart
        <?php if (!empty($diseases) && $diseases[0] != 'No Data') { ?>
            const diseaseCtx = document.getElementById('diseaseChart').getContext('2d');
            new Chart(diseaseCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($diseases); ?>,
                    datasets: [{
                        data: <?php echo json_encode($disease_counts); ?>,
                        backgroundColor: ['#5655b7', '#ec4899', '#06b6d4', '#10b981', '#ef4444'],
                        borderColor: ['#3b3a8c', '#c0266e', '#05889b', '#0d8c66', '#c02626'],
                        borderWidth: 1
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        <?php } ?>

        // Medicine Chart
        <?php if (!empty($medicines) && $medicines[0] != 'No Data') { ?>
            const medicineCtx = document.getElementById('medicineChart').getContext('2d');
            new Chart(medicineCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($medicines); ?>,
                    datasets: [{
                        label: 'Quantity Dispensed',
                        data: <?php echo json_encode($medicine_quantities); ?>,
                        backgroundColor: 'rgba(86, 85, 183, 0.5)',
                        borderColor: 'rgba(86, 85, 183, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Quantity'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Medicine'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });
        <?php } ?>

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