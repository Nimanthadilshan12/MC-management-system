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
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$UserID = $_SESSION['UserID'];
$stmt = $conn->prepare("SELECT Fullname, Email, Contact_No FROM admins WHERE UserID = ?");
$stmt->bind_param("s", $UserID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $table = $_POST['table'] ?? '';
    
    if ($action === 'delete' && isset($_POST['user_id'])) {
        $stmt = $conn->prepare("DELETE FROM $table WHERE UserID = ?");
        $stmt->bind_param("s", $_POST['user_id']);
        $stmt->execute();
    } elseif ($action === 'add') {
        $user_id = uniqid();
        $fullname = $_POST['fullname'];
        $email = $_POST['email'];
        $contact = $_POST['contact'];
        
        $query = "INSERT INTO $table (UserID, Fullname, Email, Contact_No";
        $values = "VALUES (?, ?, ?, ?";
        $types = "ssss";
        $params = [$user_id, $fullname, $email, $contact];
        
        if ($table === 'patients') {
            $query .= ", DOB, Address)";
            $values .= ", ?, ?)";
            $types .= "ss";
            $params[] = $_POST['dob'];
            $params[] = $_POST['address'];
        } elseif ($table === 'doctors') {
            $query .= ", Specialization, License_No)";
            $values .= ", ?, ?)";
            $types .= "ss";
            $params[] = $_POST['specialization'];
            $params[] = $_POST['license_no'];
        } elseif ($table === 'pharmacists') {
            $query .= ", License_No)";
            $values .= ", ?)";
            $types .= "s";
            $params[] = $_POST['license_no'];
        }
        
        $stmt = $conn->prepare("$query $values");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
    }
}

// Fetch users
$patients = $conn->query("SELECT * FROM patients")->fetch_all(MYSQLI_ASSOC);
$doctors = $conn->query("SELECT * FROM doctors")->fetch_all(MYSQLI_ASSOC);
$pharmacists = $conn->query("SELECT * FROM pharmacists")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Manage Users - University of Ruhuna Medical Centre</title>
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
            --secondary: #ec4899; /* Pink */
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

        /* Original Styles Preserved */
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
            background-image: url('https://www.hamburg-port-authority.de/fileadmin/user_upload/karriere/2025_Startseite/Header/240925_HPA_web_v2-26_1917x635.jpg');
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
            <a href="../login.php" class="btn btn-danger rounded-0 py-4 px-lg-5 d-none d-lg-block">Logout<i class="fa fa-arrow-right ms-3"></i></a>
        </div>    
    </nav>
    <!-- Navbar End -->

    <!-- Page Header Start -->
    <div class="container-fluid page-header py-5 mb-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <h1 class="display-3 text-white mb-3 animated slideInDown">Manage Users</h1>
            <nav aria-label="breadcrumb animated slideInDown">
               
            </nav>
        </div>
    </div>
    <!-- Page Header End -->

    <!-- Manage Users Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                <p class="d-inline-block border rounded-pill py-1 px-4 text-primary">Admin Portal</p>
                <h1 class="text-primary">Manage Users</h1>
            </div>
            <div class="row g-4">
                <div class="col-12 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="card border-0 shadow-sm bg-light">
                        <div class="card-body">
                            <div class="card-header d-flex justify-content-between align-items-center mb-4">
                                <h3 class="text-primary">User Management</h3>
                                <a href="admin_dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                            </div>
                            
                            <!-- Patients Section -->
                            <h5 class="mb-3 text-primary"><i class="fas fa-user-injured me-2"></i>Patients</h5>
                            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal" onclick="setAddForm('patients')"><i class="fas fa-plus me-2"></i>Add Patient</button>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Contact</th>
                                            <th>DOB</th>
                                            <th>Age</th>
                                            <th>Gender</th>
                                            <th>Blood Type</th>
                                            <th>Academic Year</th>
                                            <th>Faculty</th>
                                            <th>Citizenship</th>
                                            <th>Allergies</th>
                                            <th>Emergency Contact</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($patients as $patient): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($patient['Fullname']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['Email']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['Contact_No']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['Birth']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['Age']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['Gender']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['Blood_Type']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['Academic_Year']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['Faculty']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['Citizenship']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['Any_allergies']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['Emergency_Contact']); ?></td>
                                                <td>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="table" value="patients">
                                                        <input type="hidden" name="user_id" value="<?php echo $patient['UserID']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i> Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Doctors Section -->
                            <h5 class="mb-3 text-primary"><i class="fas fa-user-md me-2"></i>Doctors</h5>
                            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal" onclick="setAddForm('doctors')"><i class="fas fa-plus me-2"></i>Add Doctor</button>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Contact</th>
                                            <th>Specialization</th>
                                            <th>Registration No</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($doctors as $doctor): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($doctor['Fullname']); ?></td>
                                                <td><?php echo htmlspecialchars($doctor['Email']); ?></td>
                                                <td><?php echo htmlspecialchars($doctor['Contact_No']); ?></td>
                                                <td><?php echo htmlspecialchars($doctor['Specialization']); ?></td>
                                                <td><?php echo htmlspecialchars($doctor['RegNo']); ?></td>
                                                <td>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="table" value="doctors">
                                                        <input type="hidden" name="user_id" value="<?php echo $doctor['UserID']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i> Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pharmacists Section -->
                            <h5 class="mb-3 text-primary"><i class="fas fa-prescription-bottle-alt me-2"></i>Pharmacists</h5>
                            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal" onclick="setAddForm('pharmacists')"><i class="fas fa-plus me-2"></i>Add Pharmacist</button>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Contact</th>
                                            <th>License No</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pharmacists as $pharmacist): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($pharmacist['Fullname']); ?></td>
                                                <td><?php echo htmlspecialchars($pharmacist['Email']); ?></td>
                                                <td><?php echo htmlspecialchars($pharmacist['Contact_No']); ?></td>
                                                <td><?php echo htmlspecialchars($pharmacist['License_No']); ?></td>
                                                <td>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="table" value="pharmacists">
                                                        <input type="hidden" name="user_id" value="<?php echo $pharmacist['UserID']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i> Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Manage Users End -->

    <!-- Add User Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-primary" id="addModalLabel">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="table" id="modalTable">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" name="fullname" required>
                            <label for="fullname"><i class="fas fa-user me-2"></i>Full Name</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" name="email" required>
                            <label for="email"><i class="fas fa-envelope me-2"></i>Email Address</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" name="contact">
                            <label for="contact"><i class="fas fa-phone me-2"></i>Contact Number</label>
                        </div>
                        <div class="form-floating mb-3" id="dobField" style="display:none;">
                            <input type="date" class="form-control" name="dob">
                            <label for="dob"><i class="fas fa-calendar-alt me-2"></i>Date of Birth</label>
                        </div>
                        <div class="form-floating mb-3" id="addressField" style="display:none;">
                            <textarea class="form-control" name="address"></textarea>
                            <label for="address"><i class="fas fa-map-marker-alt me-2"></i>Address</label>
                        </div>
                        <div class="form-floating mb-3" id="specializationField" style="display:none;">
                            <input type="text" class="form-control" name="specialization">
                            <label for="specialization"><i class="fas fa-stethoscope me-2"></i>Specialization</label>
                        </div>
                        <div class="form-floating mb-3" id="licenseField" style="display:none;">
                            <input type="text" class="form-control" name="license_no">
                            <label for="license_no"><i class="fas fa-id-card me-2"></i>License No</label>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-2"></i>Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
        function setAddForm(table) {
            document.getElementById('modalTable').value = table;
            document.getElementById('dobField').style.display = table === 'patients' ? 'block' : 'none';
            document.getElementById('addressField').style.display = table === 'patients' ? 'block' : 'none';
            document.getElementById('specializationField').style.display = table === 'doctors' ? 'block' : 'none';
            document.getElementById('licenseField').style.display = table === 'doctors' || table === 'pharmacists' ? 'block' : 'none';
            document.querySelector('.modal-title').textContent = 'Add ' + table.charAt(0).toUpperCase() + table.slice(1, -1);
        }

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
