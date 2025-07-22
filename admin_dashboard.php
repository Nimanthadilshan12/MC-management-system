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

// Define absolute paths
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/Uploads/admins/";
$photoPath = $uploadDir . $UserID . '.jpg';
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$photoUrl = (file_exists($photoPath) && is_readable($photoPath)) ? "/Uploads/admins/$UserID.jpg?t=" . time() : null;
$message = isset($_SESSION['message']) ? $_SESSION['message'] : "";
unset($_SESSION['message']);

// Log paths for debugging
error_log("Upload Directory: $uploadDir", 3, $_SERVER['DOCUMENT_ROOT'] . "/error.log");
error_log("Photo Path: $photoPath", 3, $_SERVER['DOCUMENT_ROOT'] . "/error.log");
if ($photoUrl) {
    error_log("Photo URL: $baseUrl$photoUrl", 3, $_SERVER['DOCUMENT_ROOT'] . "/error.log");
} else if (file_exists($photoPath)) {
    error_log("Photo exists but is not readable: $photoPath", 3, $_SERVER['DOCUMENT_ROOT'] . "/error.log");
}

// Handle photo upload
if (isset($_POST['upload_photo'])) {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowedTypes = ['image/jpeg', 'image/png'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        $fileType = $_FILES['profile_photo']['type'];
        $fileSize = $_FILES['profile_photo']['size'];

        if (!in_array($fileType, $allowedTypes)) {
            $_SESSION['message'] = "Only JPEG or PNG images are allowed.";
        } elseif ($fileSize > $maxSize) {
            $_SESSION['message'] = "Image size must be less than 2MB.";
        } else {
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    $_SESSION['message'] = "Failed to create upload directory.";
                    error_log("Failed to create directory: $uploadDir", 3, $_SERVER['DOCUMENT_ROOT'] . "/error.log");
                } else {
                    chmod($uploadDir, 0755);
                    error_log("Created directory: $uploadDir", 3, $_SERVER['DOCUMENT_ROOT'] . "/error.log");
                }
            }
            if (file_exists($photoPath)) {
                if (!unlink($photoPath)) {
                    error_log("Failed to delete existing file: $photoPath", 3, $_SERVER['DOCUMENT_ROOT'] . "/error.log");
                }
            }
            $tempPath = $_FILES['profile_photo']['tmp_name'];
            if (move_uploaded_file($tempPath, $photoPath)) {
                chmod($photoPath, 0644);
                $_SESSION['message'] = "Photo uploaded successfully!";
                error_log("Photo uploaded to: $photoPath", 3, $_SERVER['DOCUMENT_ROOT'] . "/error.log");
            } else {
                $_SESSION['message'] = "Failed to upload photo.";
                error_log("Failed to move uploaded file to $photoPath. Error: " . $_FILES['profile_photo']['error'], 3, $_SERVER['DOCUMENT_ROOT'] . "/error.log");
            }
        }
    } else {
        $_SESSION['message'] = "Please select a valid image file.";
        error_log("No valid file uploaded. Error: " . ($_FILES['profile_photo']['error'] ?? 'No file'), 3, $_SERVER['DOCUMENT_ROOT'] . "/error.log");
    }
    header("Location: admin_dashboard.php");
    exit;
}

// Handle photo removal
if (isset($_POST['remove_photo'])) {
    if (file_exists($photoPath)) {
        if (unlink($photoPath)) {
            $_SESSION['message'] = "Photo removed successfully!";
            error_log("Photo removed: $photoPath", 3, $_SERVER['DOCUMENT_ROOT'] . "/error.log");
        } else {
            $_SESSION['message'] = "Failed to remove photo.";
            error_log("Failed to remove file: $photoPath", 3, $_SERVER['DOCUMENT_ROOT'] . "/error.log");
        }
    } else {
        $_SESSION['message'] = "No photo to remove.";
        error_log("No photo found to remove: $photoPath", 3, $_SERVER['DOCUMENT_ROOT'] . "/error.log");
    }
    header("Location: admin_dashboard.php");
    exit;
}

// Handle profile update
if (isset($_POST['update_profile'])) {
    $Fullname = trim($_POST['Fullname']);
    $Email = filter_var($_POST['Email'], FILTER_SANITIZE_EMAIL);
    $Contact_No = trim($_POST['Contact_No']);

    if (strlen($Fullname) < 3 || strlen($Fullname) > 100) {
        $message = "Full name must be between 3 and 100 characters.";
    } elseif (!filter_var($Email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif (!preg_match("/^[0-9]{7,15}$/", $Contact_No)) {
        $message = "Contact number must be digits only (7-15 digits).";
    } else {
        $stmt = $conn->prepare("UPDATE admins SET Fullname = ?, Email = ?, Contact_No = ? WHERE UserID = ?");
        $stmt->bind_param("ssss", $Fullname, $Email, $Contact_No, $UserID);
        if ($stmt->execute()) {
            $message = "Profile updated successfully!";
            $user['Fullname'] = $Fullname;
            $user['Email'] = $Email;
            $user['Contact_No'] = $Contact_No;
        } else {
            $message = "Failed to update profile: " . $stmt->error;
            error_log("Profile update failed: " . $stmt->error, 3, $_SERVER['DOCUMENT_ROOT'] . "/error.log");
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Admin Dashboard - University of Ruhuna Medical Centre</title>
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
            --dark-bg:rgb(8, 50, 92);
            --text-light: #ffffff;
        }

        .dark-mode {
            --background: #1a1a1a;
            --text: #e0e0e0;
            --light-bg: #2c2c2c;
            --dark-bg:rgb(56, 41, 150);
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

        /* Avatar Animation */
        .avatar-container {
            position: relative;
            transition: transform 0.3s ease;
        }

        .avatar-container:hover {
            transform: scale(1.05);
        }

        .avatar-container .avatar {
            border: 4px solid var(--primary);
            transition: border-color 0.3s ease;
        }

        .avatar-container:hover .avatar {
            border-color: var(--secondary);
        }

        .status-indicator {
            animation: pulse 2s infinite ease-in-out;
        }

        /* List Group Item Animation */
        .list-group-item {
            transition: transform 0.3s ease, background-color 0.3s ease;
        }

        .list-group-item:hover {
            transform: translateX(10px);
            background-color: rgba(124, 58, 237, 0.1);
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
            background-image: url('https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d');
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
            background-color: rgba(124, 58, 237, 0.1); /* Preserve original hover color */
        }

        .dark-mode .avatar-container .avatar {
            border-color: var(--primary);
        }

        .dark-mode .avatar-container:hover .avatar {
            border-color: var(--secondary);
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
                <a href="about.php" class="nav-item nav-link">About</a>
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
            <h1 class="display-3 text-white mb-3 animated slideInDown">Admin Dashboard</h1>
            <nav aria-label="breadcrumb animated slideInDown">
                
            </nav>
        </div>
    </div>
    <!-- Page Header End -->

    <!-- Admin Dashboard Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                <p class="d-inline-block border rounded-pill py-1 px-4 text-primary">Admin Portal</p>
                <h1 class="text-primary">Welcome, <?php echo htmlspecialchars($user['Fullname']); ?>!</h1>
            </div>
            <?php if ($message): ?>
                <div class="alert alert-<?php echo strpos($message, 'successfully') !== false ? 'success' : 'danger'; ?> text-center wow fadeInUp" data-wow-delay="0.1s" role="alert">
                    <i class="fas <?php echo strpos($message, 'successfully') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="card border-0 shadow-sm bg-light">
                        <div class="card-body text-center">
                            <div class="avatar-container mb-4 mx-auto" style="width: 140px; height: 140px;">
                                <div class="avatar rounded-circle overflow-hidden position-relative">
                                    <?php if ($photoUrl): ?>
                                        <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Profile Photo" class="w-100 h-100 object-fit-cover" onerror="console.error('Failed to load image: <?php echo htmlspecialchars($photoUrl); ?>'); this.src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='; alert('Failed to load profile photo. Check error.log for details.');">
                                    <?php else: ?>
                                        <i class="fas fa-user-circle fa-4x text-white" style="background: linear-gradient(135deg, var(--primary), var(--secondary));"></i>
                                    <?php endif; ?>
                                    <div class="status-indicator position-absolute bottom-0 end-0" style="width: 20px; height: 20px; background: linear-gradient(45deg, var(--success), #34d399); border-radius: 50%; border: 3px solid #fff;"></div>
                                </div>
                            </div>
                            <h4 class="mb-3 text-primary"><?php echo htmlspecialchars($user['Fullname']); ?></h4>
                            <div class="mb-4">
                                <div class="d-flex align-items-center justify-content-center mb-2">
                                    <i class="fas fa-envelope me-2 text-primary"></i>
                                    <span><?php echo htmlspecialchars($user['Email']); ?></span>
                                </div>
                                <div class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-phone me-2 text-primary"></i>
                                    <span><?php echo htmlspecialchars($user['Contact_No']); ?></span>
                                </div>
                            </div>
                            <form class="photo-upload-form" method="post" enctype="multipart/form-data" id="photoUploadForm">
                                <label for="profile_photo" class="btn btn-primary mb-2 w-100"><i class="fas fa-upload me-2"></i><?php echo $photoUrl ? 'Update Photo' : 'Upload Photo'; ?></label>
                                <input type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png" style="display: none;">
                                <button type="submit" name="upload_photo" class="btn btn-primary" style="display: none;"></button>
                                <?php if ($photoUrl): ?>
                                    <button type="submit" name="remove_photo" class="btn btn-danger mb-2 w-100"><i class="fas fa-trash-alt me-2"></i>Remove Photo</button>
                                <?php endif; ?>
                            </form>
                            <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#editProfileModal"><i class="fas fa-edit me-2"></i>Edit Profile</button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="card border-0 shadow-sm bg-light">
                        <div class="card-body">
                            <h3 class="text-center mb-4 text-primary">Admin Actions</h3>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item bg-transparent">
                                    <a href="manage_users.php" class="d-flex align-items-center text-decoration-none text-primary">
                                        <i class="fas fa-users me-2"></i>Manage Users
                                    </a>
                                </li>
                                <li class="list-group-item bg-transparent">
                                    <a href="manage_appointments.php" class="d-flex align-items-center text-decoration-none text-primary">
                                        <i class="fas fa-calendar-check me-2"></i>Manage Appointments
                                    </a>
                                </li>
                                <li class="list-group-item bg-transparent">
                                    <a href="medicine_inventory.php" class="d-flex align-items-center text-decoration-none text-primary">
                                        <i class="fas fa-prescription-bottle-alt me-2"></i>Medicine Inventory
                                    </a>
                                </li>
                                <li class="list-group-item bg-transparent">
                                    <a href="system_settings.php" class="d-flex align-items-center text-decoration-none text-primary">
                                        <i class="fas fa-cog me-2"></i>Configure Settings
                                    </a>
                                </li>
                                <li class="list-group-item bg-transparent">
                                    <a href="analysis.php" class="d-flex align-items-center text-decoration-none text-primary">
                                        <i class="fas fa-chart-bar me-2"></i>View Data Analysis
                                    </a>
                                </li>
                                <li class="list-group-item bg-transparent">
                                    <a href="feedbacks.php" class="d-flex align-items-center text-decoration-none text-primary">
                                        <i class="fas fa-comment-dots me-2"></i>View Feedback
                                    </a>
                                </li>
                            </ul>
                            <div class="text-center mt-4">
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Admin Dashboard End -->

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-primary" id="editProfileModalLabel">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" class="edit-profile-form">
                    <div class="modal-body">
                        <div class="form-floating mb-3">
                            <input type="text" name="Fullname" id="Fullname" class="form-control" value="<?php echo htmlspecialchars($user['Fullname']); ?>" required>
                            <label for="Fullname"><i class="fas fa-user me-2"></i>Full Name</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="email" name="Email" id="Email" class="form-control" value="<?php echo htmlspecialchars($user['Email']); ?>" required>
                            <label for="Email"><i class="fas fa-envelope me-2"></i>Email Address</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" name="Contact_No" id="Contact_No" class="form-control" value="<?php echo htmlspecialchars($user['Contact_No']); ?>" required>
                            <label for="Contact_No"><i class="fas fa-phone me-2"></i>Contact Number</label>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-2"></i>Cancel</button>
                        <button type="submit" name="update_profile" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Changes</button>
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
        // Trigger form submission when file is selected
        document.getElementById('profile_photo')?.addEventListener('change', function() {
            this.nextElementSibling.click();
        });

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
