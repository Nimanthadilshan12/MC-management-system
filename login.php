<?php
session_start();
$host = "localhost";
$db = "mc1";
$user = "root";
$pass = "";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";
$showForm = "signin";

// REGISTER LOGIC
if (isset($_POST['register'])) {
    $UserID = trim($_POST['UserID']);
    $passwordRaw = $_POST['password'];
    $role = $_POST['role'];
    $Fullname = trim($_POST['Fullname']);
    $Email = filter_var($_POST['Email'], FILTER_SANITIZE_EMAIL);
    $Contact_No = trim($_POST['Contact_No']);

    // Check if UserID already exists in any table
    $tables = ['patients', 'doctors', 'admins', 'pharmacists'];
    $userExists = false;
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SELECT UserID FROM `$table` WHERE UserID = ?");
        $stmt->bind_param("s", $UserID);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $userExists = true;
            break;
        }
    }

    // Validate UserID format for Patients
    $validPatientID = ($role === 'Patient' && preg_match("/^(sc|R)\d{5}$/", $UserID));
    
    // Basic validation
    if ($userExists) {
        $message = "User ID already exists. Please choose a different User ID.";
        $showForm = "register";
    } elseif ($role === 'Patient' && !$validPatientID) {
        $message = "Patient User ID must start with 'sc' or 'R' followed by 5 digits (e.g., sc12345 or R12345).";
        $showForm = "register";
    } elseif (strlen($UserID) < 3 || strlen($UserID) > 50) {
        $message = "User ID must be between 3 and 50 characters.";
        $showForm = "register";
    } elseif (strlen($passwordRaw) < 6 || !preg_match("/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/", $passwordRaw)) {
        $message = "Password must be at least 6 characters long and contain letters, numbers, and symbols.";
        $showForm = "register";
    } elseif (!in_array($role, ['Patient', 'Doctor', 'Admin', 'Pharmacist'])) {
        $message = "Invalid role selected.";
        $showForm = "register";
    } elseif (strlen($Fullname) < 3 || strlen($Fullname) > 100) {
        $message = "Full name must be between 3 and 100 characters.";
        $showForm = "register";
    } elseif (!filter_var($Email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $showForm = "register";
    } elseif (!preg_match("/^[0-9]{10,20}$/", $Contact_No)) {
        $message = "Contact number must be digits only (10-20 digits).";
        $showForm = "register";
    } else {
        $password = password_hash($passwordRaw, PASSWORD_DEFAULT);

        switch ($role) {
            case 'Patient':
                $Age = $_POST['Age'] ?? null;
                $Gender = $_POST['Gender'] ?? null;
                $Birth = $_POST['Birth'] ?? null;
                $Blood_Type = $_POST['Blood_Type'] ?? null;
                $Academic_Year = $_POST['Academic_Year'] ?? null;
                $Faculty = $_POST['Faculty'] ?? null;
                $Citizenship = $_POST['Citizenship'] ?? null;
                $Allergies = $_POST['Any_allergies'] ?? null;
                $Emergency_Contact = $_POST['Emergency_Contact'] ?? null;

                // Validate Patient fields
                if (!is_numeric($Age) || $Age < 0 || $Age > 120) {
                    $message = "Please enter a valid age.";
                    $showForm = "register";
                } elseif (!in_array($Gender, ['Male', 'Female'])) {
                    $message = "Please select a valid gender.";
                    $showForm = "register";
                } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $Birth) || strtotime($Birth) >= time()) {
                    $message = "Invalid birthdate format or date must be before today.";
                    $showForm = "register";
                } elseif (!empty($Blood_Type) && !in_array($Blood_Type, ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])) {
                    $message = "Invalid blood type.";
                    $showForm = "register";
                } elseif (!empty($Emergency_Contact) && !preg_match("/^[0-9]{10,20}$/", $Emergency_Contact)) {
                    $message = "Emergency contact must be digits only (10-20 digits).";
                    $showForm = "register";
                } else {
                    $stmt = $conn->prepare("INSERT INTO patients (UserID, Password, Fullname, Email, Contact_No, Age, Gender, Birth, Blood_Type, Academic_Year, Faculty, Citizenship, Any_allergies, Emergency_Contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssssssssssss", $UserID, $password, $Fullname, $Email, $Contact_No, $Age, $Gender, $Birth, $Blood_Type, $Academic_Year, $Faculty, $Citizenship, $Allergies, $Emergency_Contact);
                }
                break;

            case 'Doctor':
                $Specialization = $_POST['Specialization'] ?? null;
                $RegNo = $_POST['RegNo'] ?? null;

                if (empty($Specialization) || strlen($Specialization) < 2) {
                    $message = "Specialization is required and must be at least 2 characters.";
                    $showForm = "register";
                } elseif (empty($RegNo) || strlen($RegNo) < 3) {
                    $message = "Medical registration number is required and must be at least 3 characters.";
                    $showForm = "register";
                } else {
                    $stmt = $conn->prepare("INSERT INTO doctors (UserID, Password, Fullname, Email, Contact_No, Specialization, RegNo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssss", $UserID, $password, $Fullname, $Email, $Contact_No, $Specialization, $RegNo);
                }
                break;

            case 'Admin':
                $stmt = $conn->prepare("INSERT INTO admins (UserID, Password, Fullname, Email, Contact_No) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $UserID, $password, $Fullname, $Email, $Contact_No);
                break;

            case 'Pharmacist':
                $License_No = $_POST['License_No'] ?? null;
                if (empty($License_No) || strlen($License_No) < 3) {
                    $message = "License number is required and must be at least 3 characters.";
                    $showForm = "register";
                } else {
                    $stmt = $conn->prepare("INSERT INTO pharmacists (UserID, Password, Fullname, Email, Contact_No, License_No) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssss", $UserID, $password, $Fullname, $Email, $Contact_No, $License_No);
                }
                break;
        }

        if ($showForm === "register") {
            // Skip execution if validation failed
        } elseif ($stmt && $stmt->execute()) {
            $message = "Registration successful! Please sign in.";
            $showForm = "signin";
        } else {
            $message = "Error: " . ($stmt ? $stmt->error : "Statement preparation failed.");
            $showForm = "register";
        }
    }
}

// LOGIN LOGIC
if (isset($_POST['signin'])) {
    $UserID = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($UserID) || empty($password)) {
        $message = "Please enter both username and password.";
        $showForm = "signin";
    } else {
        $roles = [
            'patients' => 'Patient',
            'doctors' => 'Doctor',
            'admins' => 'Admin',
            'pharmacists' => 'Pharmacist'
        ];

        foreach ($roles as $table => $roleName) {
            $stmt = $conn->prepare("SELECT * FROM `$table` WHERE UserID = ?");
            $stmt->bind_param("s", $UserID);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $row = $result->fetch_assoc()) {
                if (password_verify($password, $row['Password'])) {
                    $_SESSION['UserID'] = $UserID;
                    $_SESSION['role'] = $roleName ;
                    header("Location: dashboards/" . strtolower($roleName) . "_dashboard.php");
                    exit;
                }
            }
        }
        $message = "Invalid username or password.";
        $showForm = "signin";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="utf-8">
    <title>Login - University of Ruhuna Medical Centre</title>
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

    <!-- Inline CSS for Page Header and Dark Mode -->
    <style>
        /* Color Variables */
        :root {
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
            background-image: url('https://thumbs.dreamstime.com/b/header-image-nurse-holding-medical-chart-hospital-setting-website-shot-female-examining-clipboard-pointing-out-specific-367569784.jpg');
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

        .email-error {
            color: var(--error);
            font-size: 0.9rem;
            margin-top: 0.25rem;
            display: none;
        }
    </style>
    <script>
        function showForm(form) {
            document.getElementById('registerForm').style.display = form === 'register' ? 'block' : 'none';
            document.getElementById('registerForm').classList.toggle('active', form === 'register');
            document.getElementById('signinForm').style.display = form === 'signin' ? 'block' : 'none';
            document.getElementById('signinForm').classList.toggle('active', form === 'signin');

            document.getElementById('btnRegister').classList.toggle('active', form === 'register');
            document.getElementById('btnSignin').classList.toggle('active', form === 'signin');
        }

        function toggleRoleFields(role) {
            document.getElementById('patientFields').style.display = role === 'Patient' ? 'block' : 'none';
            document.getElementById('doctorFields').style.display = role === 'Doctor' ? 'block' : 'none';
            document.getElementById('pharmacistFields').style.display = role === 'Pharmacist' ? 'block' : 'none';
        }

        window.onload = function () {
            showForm('<?php echo $showForm; ?>');
            const roleSelect = document.getElementById('roleSelect');
            if (roleSelect) toggleRoleFields(roleSelect.value);

            // Email validation on input
            const emailInput = document.getElementById('Email');
            const emailError = document.getElementById('emailError');
            emailInput.addEventListener('input', function() {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(emailInput.value)) {
                    emailError.style.display = 'block';
                } else {
                    emailError.style.display = 'none';
                }
            });
        };
    </script>
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
              <a href="index.php" class="nav-item nav-link ">Home</a>
                <a href="about.php" class="nav-item nav-link">About</a>
                <a href="health_resources.php" class="nav-item nav-link">Health Resources</a>
                <a href="feature.php" class="nav-item nav-link">Opening Information</a>
                <a href="contact.php" class="nav-item nav-link">Contact</a>
                <button id="darkModeToggle" class="btn btn-primary rounded-circle ms-3" style="width: 40px; height: 40px;">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            <a href class=""><i class="fa fa- ms-3"></i></a>
        </div>
    </nav>
    <!-- Navbar End -->

     <!-- Page Header Start -->
    <div class="container-fluid page-header py-5 mb-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <h1 class="display-3 text-white mb-3 animated slideInDown">Login & Register</h1>
            <nav aria-label="breadcrumb animated slideInDown">
                
            </nav>
        </div>
    </div>
    <!-- Page Header End -->
    <!-- Login & Register Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="text-center mx-auto mb-5 wowrows fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                <p class="d-inline-block border rounded-pill py-1 px-4">Login & Register</p>
                <h1>Access Your Account</h1>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-12 text-center">
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" id="btnRegister" class="btn btn-primary py-2 px-4 <?php echo $showForm === 'register' ? 'active' : ''; ?>" onclick="showForm('register')">Register</button>
                        <button type="button" id="btnSignin" class="btn btn-primary py-2 px-4 <?php echo $showForm === 'signin' ? 'active' : ''; ?>" onclick="showForm('signin')">Sign In</button>
                    </div>
                </div>
            </div>

            <div class="container">
                <?php if ($message): ?>
                    <div class="message <?php echo strpos($message, 'successful') !== false ? 'success' : ''; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
            <form id="registerForm" method="post" class="wow fadeInUp" data-wow-delay="0.1s">
            <h3 class="text-center mb-4">Create Account</h3>
            <div class="row g-3">
                <div class="col-12">
                 <div class="form-floating">
                   <input type="text" name="UserID" id="UserID" class="form-control" placeholder="Enter User ID" required>
                   <label for="UserID"><i class="fas fa-id-badge me-2"></i>User ID</label>
                    <div class="rule-message">For Patients: Must start with 'sc' or 'R' followed by 5 digits (e.g., sc12345). For others: 3-50 characters.</div>
                 </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Minimum 6 characters, letters, numbers, and symbols" required>
                        <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                        <div class="rule-message">Minimum 6 characters, must include letters, numbers, and symbols (e.g., @$!%*?&).</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <select name="role" id="roleSelect" class="form-control" required onchange="toggleRoleFields(this.value)">
                            <option value="">Select Role</option>
                            <option value="Patient">Patient</option>
                            <option value="Doctor">Doctor</option>
                            <option value="Admin">Admin</option>
                            <option value="Pharmacist">Pharmacist</option>
                        </select>
                        <label for="roleSelect"><i class="fas fa-user-tag me-2"></i>Role</label>
                        <div class="rule-message">Select one: Patient, Doctor, Admin, or Pharmacist.</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <input type="text" name="Fullname" id="Fullname" class="form-control" placeholder="Enter Full Name" required>
                        <label for="Fullname"><i class="fas fa-user me-2"></i>Full Name</label>
                        <div class="rule-message">Must be between 3 and 100 characters.</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <input type="email" name="Email" id="Email" class="form-control" placeholder="Enter Email" required>
                        <label for="Email"><i class="fas fa-envelope me-2"></i>Email Address</label>
                        <div class="rule-message">Must be a valid email format (e.g., example@domain.com).</div>
                        <div id="emailError" class="email-error">Please enter a valid email address (e.g., example@domain.com).</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <input type="text" name="Contact_No" id="Contact_No" class="form-control" placeholder="Enter Contact Number" required>
                        <label for="Contact_No"><i class="fas fa-phone me-2"></i>Contact Number</label>
                        <div class="rule-message">Must be digits only, 10-20 digits.</div>
                    </div>
                </div>
        <div id="patientFields" style="display:none;" class="border rounded p-3">
            <div class="row g-3">
                <div class="col-12">
                    <div class="form-floating">
                        <input type="number" name="Age" id="Age" class="form-control" placeholder="Enter Age" min="0">
                        <label for="Age"><i class="fas fa-child me-2"></i>Age</label>
                        <div class="rule-message">Must be a number between 0 and 120.</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <select name="Gender" id="Gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                        <label for="Gender"><i class="fas fa-venus-mars me-2"></i>Gender</label>
                        <div class="rule-message">Select either Male or Female.</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <input type="date" name="Birth" id="Birth" class="form-control" placeholder="Select Birthdate" max="<?php echo date('Y-m-d'); ?>">
                        <label for="Birth"><i class="fas fa-calendar-alt me-2"></i>Birth Date</label>
                         <div class="rule-message">Must be in DD-MM-YYYY format and before today.</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <select name="Blood_Type" id="Blood_Type" class="form-control">
                            <option value="">Select Blood Type</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                        <label for="Blood_Type"><i class="fas fa-tint me-2"></i>Blood Type</label>
                        <div class="rule-message">Select a valid blood type.</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <select name="Academic_Year" id="Academic_Year" class="form-control">
                            <option value="">Select Academic Year</option>
                            <option value="2021">2021</option>
                            <option value="2022">2022</option>
                            <option value="2023">2023</option>
                            <option value="2024">2024</option>
                        </select>
                        <label for="Academic_Year"><i class="fas fa-graduation-cap me-2"></i>Academic Year</label>
                        <div class="rule-message">Select your academic year.</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <select name="Faculty" id="Faculty" class="form-control">
                            <option value="">Select Faculty</option>
                            <option value="Science">Science</option>
                            <option value="Humanities & Social Sciences">Humanities & Social Sciences</option>
                            <option value="Management & Finance">Management & Finance</option>
                            <option value="Fisheries & Marine Sciences & Technology">Fisheries & Marine Sciences & Technology</option>
                        </select>
                        <label for="Faculty"><i class="fas fa-university me-2"></i>Faculty</label>
                        <div class="rule-message">Select your faculty.</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <input type="text" name="Citizenship" id="Citizenship" class="form-control" placeholder="Enter Citizenship">
                        <label for="Citizenship"><i class="fas fa-globe me-2"></i>Citizenship</label>
                        <div class="rule-message">Enter your citizenship.</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <textarea name="Any_allergies" id="Any_allergies" class="form-control" placeholder="Enter any allergies (if any)" style="height: 100px;"></textarea>
                        <label for="Any_allergies"><i class="fas fa-allergies me-2"></i>Allergies</label>
                         <div class="rule-message">Enter any allergies (optional).</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <input type="text" name="Emergency_Contact" id="Emergency_Contact" class="form-control" placeholder="Enter Emergency Contact">
                        <label for="Emergency_Contact"><i class="fas fa-phone-alt me-2"></i>Emergency Contact</label>
                        <div class="rule-message">Must be digits only, 10-20 digits.</div>
                    </div>
                </div>
            </div>
        </div>
        <div id="doctorFields" style="display:none;" class="border rounded p-3">
            <div class="row g-3">
                <div class="col-12">
                    <div class="form-floating">
                        <input type="text" name="Specialization" id="Specialization" class="form-control" placeholder="Enter Specialization">
                        <label for="Specialization"><i class="fas fa-stethoscope me-2"></i>Specialization</label>
                        <div class="rule-message">Must be at least 2 characters (optional).</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <input type="text" name="RegNo" id="RegNo" class="form-control" placeholder="Enter Registration Number">
                        <label for="RegNo"><i class="fas fa-id-card me-2"></i>Medical Registration Number</label>
                        <div class="rule-message">Must be at least 3 characters.</div>
                    </div>
                </div>
            </div>
        </div>
        <div id="pharmacistFields" style="display:none;" class="border rounded p-3">
            <div class="row g-3">
                <div class="col-12">
                    <div class="form-floating">
                        <input type="text" name="License_No" id="License_No" class="form-control" placeholder="Enter License Number">
                        <label for="License_No"><i class="fas fa-id-card me-2"></i>License Number</label>
                        <div class="rule-message">Must be at least 3 characters.</div>
                    </div>
                </div>
            </div>
        </div>
            <div class="col-12">
                <button type="submit" name="register" class="btn btn-primary w-100 py-3"><i class="fas fa-user-plus me-2"></i>Register</button>
            </div>
        </div>    
    </div>
</form>
        <form id="signinForm" method="post" class="wow fadeInUp active" data-wow-delay="0.1s">
    <h3 class="text-center mb-4">Sign In</h3>
    <div class="row g-3">
        <div class="col-12">
            <div class="form-floating">
                <input type="text" name="username" id="username" class="form-control" placeholder="Enter Username" required>
                <label for="username"><i class="fas fa-id-badge me-2"></i>Username</label>
            </div>
        </div>
        <div class="col-12">
            <div class="form-floating">
                <input type="password" name="password" id="password_signin" class="form-control" placeholder="Enter Password" required>
                <label for="password_signin"><i class="fas fa-lock me-2"></i>Password</label>
            </div>
        </div>
        <div class="col-12">
            <button type="submit" name="signin" class="btn btn-primary w-100 py-3"><i class="fas fa-sign-in-alt me-2"></i>Sign In</button>
        </div>
    </div>
</form>
        <div class="text-center mt-4">
                <a class="btn btn-primary py-3 px-5" href="index.php"><i class="fas fa-arrow-left me-2"></i>Back to Home</a>
            </div>
    </div>
</div>
    <!-- Login & Register End -->

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
        function showForm(form) {
            document.getElementById('registerForm').style.display = form === 'register' ? 'block' : 'none';
            document.getElementById('registerForm').classList.toggle('active', form === 'register');
            document.getElementById('signinForm').style.display = form === 'signin' ? 'block' : 'none';
            document.getElementById('signinForm').classList.toggle('active', form === 'signin');

            document.getElementById('btnRegister').classList.toggle('active', form === 'register');
            document.getElementById('btnSignin').classList.toggle('active', form === 'signin');
        }

        function toggleRoleFields(role) {
            document.getElementById('patientFields').style.display = role === 'Patient' ? 'block' : 'none';
            document.getElementById('doctorFields').style.display = role === 'Doctor' ? 'block' : 'none';
            document.getElementById('pharmacistFields').style.display = role === 'Pharmacist' ? 'block' : 'none';
        }

        window.onload = function () {
            showForm('<?php echo $showForm; ?>');
            const roleSelect = document.getElementById('roleSelect');
            if (roleSelect) toggleRoleFields(roleSelect.value);

            // Email validation on input
            const emailInput = document.getElementById('Email');
            const emailError = document.getElementById('emailError');
            emailInput.addEventListener('input', function() {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(emailInput.value)) {
                    emailError.style.display = 'block';
                } else {
                    emailError.style.display = 'none';
                }
            });
        };

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
