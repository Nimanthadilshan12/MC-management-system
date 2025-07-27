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
$UserID = $_SESSION['UserID'];
$message = '';

// Fetch admin details
$stmt = $conn->prepare("SELECT Fullname, Email, Contact_No FROM admins WHERE UserID = ?");
if (!$stmt) die("Prepare failed: " . $conn->error);
$stmt->bind_param("s", $UserID);
if (!$stmt->execute()) die("Execute failed: " . $stmt->error);
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if (!$user) die("No admin found with UserID: " . htmlspecialchars($UserID));
$stmt->close();

// Handle add inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $medication_name = $_POST['medication_name'];
    $quantity = $_POST['quantity'];
    $expiry_date = $_POST['expiry_date'] ?: null;
    $stmt = $conn->prepare("INSERT INTO inventory (medication_name, quantity, expiry_date) VALUES (?, ?, ?)");
    if (!$stmt) {
        $message = "Prepare failed: " . $conn->error;
    } else {
        $stmt->bind_param("sis", $medication_name, $quantity, $expiry_date);
        if ($stmt->execute()) {
            $message = "Medication added successfully!";
        } else {
            $message = "Execute failed: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle edit inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id = $_POST['edit_id'];
    $medication_name = $_POST['medication_name'];
    $quantity = $_POST['quantity'];
    $expiry_date = $_POST['expiry_date'] ?: null;
    $stmt = $conn->prepare("UPDATE inventory SET medication_name = ?, quantity = ?, expiry_date = ? WHERE id = ?");
    if (!$stmt) {
        $message = "Prepare failed: " . $conn->error;
    } else {
        $stmt->bind_param("sisi", $medication_name, $quantity, $expiry_date, $edit_id);
        if ($stmt->execute()) {
            $message = "Medication updated successfully!";
        } else {
            $message = "Execute failed: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle delete inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
    if (!$stmt) {
        $message = "Prepare failed: " . $conn->error;
    } else {
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $message = "Medication deleted successfully!";
        } else {
            $message = "Execute failed: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch inventory data
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT id, medication_name AS name, quantity, expiry_date FROM inventory";
if ($search) {
    $query .= " WHERE medication_name LIKE ?";
}
$stmt = $conn->prepare($query);
if (!$stmt) die("Prepare failed: " . $conn->error);
if ($search) {
    $search_term = "%$search%";
    $stmt->bind_param("s", $search_term);
}
if (!$stmt->execute()) die("Execute failed: " . $stmt->error);
$result = $stmt->get_result();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Medicine Inventory - University of Ruhuna Medical Centre</title>
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

        

        .page-header {
            background-image: url('https://thumbs.dreamstime.com/z/medicine-background-header-medicines-many-colorful-177348244.jpg');
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
        

       

        .card-header h2 {
            font-size: 2.5rem;
            font-weight: 600;
            color: var(--primary);
            text-align: center;
        }

        .welcome-card {
            background-color: var(--light-bg);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            border: 2px solid transparent;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 16px;
            padding: 2px;
            background: linear-gradient(45deg, var(--primary), var(--secondary), var(--primary));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: destination-out;
            mask-composite: exclude;
            z-index: -1;
        }

        .message { color: var(--success); text-align: center; }
        .error { color: var(--error); text-align: center; }

        .btn-primary, .btn-danger {
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before, .btn-danger::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            color: var(--text-light);
        }

        

        .btn-danger {
            background-color: var(--error);
            border-color: var(--error);
            color: var(--text-light);
        }

        

        .table th {
            background-color: var(--primary);
            color: var(--text-light);
        }

        .modal-content {
            animation: zoomIn 0.4s ease;
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

        .dark-mode .modal-content {
            background-color: var(--light-bg);
            color: var(--text);
        }

        .dark-mode .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            color: var(--text-light);
        }

        .dark-mode .btn-danger {
            background-color: var(--error);
            border-color: var(--error);
            color: var(--text-light);
        }

        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.7); }
            to { opacity: 1; transform: scale(1); }
        }

        @media (max-width: 768px) {
            .container { padding-top: 60px; }
            .card-header h2 { font-size: 2rem; }
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
            <h1 class="display-3 text-white mb-3 animated slideInDown">Medicine Inventory</h1>
            <nav aria-label="breadcrumb animated slideInDown">
                
            </nav>
        </div>
    </div>
    <!-- Page Header End -->

    <!-- Medicine Inventory Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                <p class="d-inline-block border rounded-pill py-1 px-4 text-primary">Inventory Management</p>
                <h1 class="text-primary">Welcome, <?php echo htmlspecialchars($user['Fullname']); ?>!</h1>
            </div>
            <?php if ($message): ?>
                <div class="alert alert-<?php echo strpos($message, 'failed') !== false ? 'danger' : 'success'; ?> text-center wow fadeInUp" data-wow-delay="0.1s" role="alert">
                    <i class="fas <?php echo strpos($message, 'failed') !== false ? 'fa-exclamation-circle' : 'fa-check-circle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <div class="row g-4">
                <div class="col-12 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="card border-0 shadow-sm bg-light">
                        <div class="card-body">
                            <div class="welcome-card position-relative">
                                <h4 class="text-center mb-4">Manage Inventory</h4>
                            </div>
                            <!-- Add Inventory Form -->
                            <h5 class="mb-3">Add New Medication</h5>
                            <form method="POST" class="mb-4">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <input type="text" name="medication_name" class="form-control" placeholder="Medication Name" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <input type="number" name="quantity" class="form-control" placeholder="Quantity" min="0" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <input type="date" name="expiry_date" class="form-control" placeholder="Expiry Date">
                                        <label for="Expiry Date"><i class="fas fa-calendar-alt me-2"></i>Expiry Date</label>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <button type="submit" name="add" class="btn btn-primary w-100">Add</button>
                                    </div>
                                </div>
                            </form>
                            <!-- Search Form -->
                            <form method="GET" class="mb-4">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Search by medication name" value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                                </div>
                            </form>
                            <!-- Back Button -->
                            <?php if ($search): ?>
                                <a href="medicine_inventory.php" class="btn btn-primary mb-4"><i class="fas fa-arrow-left"></i> Back to Full Inventory</a>
                            <?php endif; ?>
                            <!-- Inventory Table -->
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th><th>Name</th><th>Quantity</th><th>Expiry Date</th><th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                                                <td><?php echo htmlspecialchars($row['expiry_date'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this medication?');">
                                                        <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <!-- Edit Modal -->
                                            <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header border-0">
                                                            <h5 class="modal-title text-primary" id="editModalLabel<?php echo $row['id']; ?>">Edit Medication</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="edit_id" value="<?php echo $row['id']; ?>">
                                                                <div class="form-floating mb-3">
                                                                    <input type="text" name="medication_name" id="medication_name<?php echo $row['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($row['name']); ?>" required>
                                                                    <label for="medication_name<?php echo $row['id']; ?>"><i class="fas fa-prescription me-2"></i>Medication Name</label>
                                                                </div>
                                                                <div class="form-floating mb-3">
                                                                    <input type="number" name="quantity" id="quantity<?php echo $row['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($row['quantity']); ?>" min="0" required>
                                                                    <label for="quantity<?php echo $row['id']; ?>"><i class="fas fa-boxes me-2"></i>Quantity</label>
                                                                </div>
                                                                <div class="form-floating mb-3">
                                                                    <input type="date" name="expiry_date" id="expiry_date<?php echo $row['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($row['expiry_date']); ?>">
                                                                    <label for="expiry_date<?php echo $row['id']; ?>"><i class="fas fa-calendar-alt me-2"></i>Expiry Date</label>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-0">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-2"></i>Cancel</button>
                                                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="no-data">No inventory records found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <a href="admin_dashboard.php" class="btn btn-primary mt-3">Back to Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Medicine Inventory End -->

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
