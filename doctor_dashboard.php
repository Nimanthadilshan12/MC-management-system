<?php
// DB connection
$servername = "localhost";
$username = "root";
$password = ""; // or your password
$database = "mc1";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
if (!isset($_SESSION['UserID']) || $_SESSION['role'] !== 'Doctor') {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $user_id = $_SESSION['UserID'];
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Server-side validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo "<script>showToast('All password fields are required.', 'warning');</script>";
    } elseif (strlen($new_password) < 8) {
        echo "<script>showToast('New password must be at least 8 characters long.', 'warning');</script>";
    } elseif ($new_password !== $confirm_password) {
        echo "<script>showToast('New passwords do not match.', 'warning');</script>";
    } else {
        // Check if UserID exists
        $stmt = $conn->prepare("SELECT Password FROM doctors WHERE UserID = ?");
        if (!$stmt) {
            echo "<script>showToast('Database error: Failed to prepare query: " . addslashes($conn->error) . "', 'error');</script>";
        } else {
            $stmt->bind_param("s", $user_id);
            if (!$stmt->execute()) {
                echo "<script>showToast('Database error: Failed to execute query: " . addslashes($conn->error) . "', 'error');</script>";
            } else {
                $stmt->bind_result($hashed_password);
                if ($stmt->fetch()) {
                    // Verify current password
                    if (password_verify($current_password, $hashed_password)) {
                        $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt->close();
                        $update = $conn->prepare("UPDATE doctors SET Password = ? WHERE UserID = ?");
                        if (!$update) {
                            echo "<script>showToast('Database error: Failed to prepare update query: " . addslashes($conn->error) . "', 'error');</script>";
                        } else {
                            $update->bind_param("ss", $new_hashed, $user_id);
                            if ($update->execute()) {
                                echo "<script>showToast('Password changed successfully.', 'success');</script>";
                            } else {
                                echo "<script>showToast('Error updating password: " . addslashes($conn->error) . "', 'error');</script>";
                            }
                            $update->close();
                        }
                    } else {
                        echo "<script>showToast('Current password is incorrect.', 'error');</script>";
                    }
                } else {
                    echo "<script>showToast('User not found for UserID: " . htmlspecialchars($user_id) . "', 'error');</script>";
                }
                $stmt->close();
            }
        }
    }
}

// Fetch doctor record using UserID
$sql = "SELECT * FROM doctors WHERE UserID = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database error: Failed to prepare query: " . $conn->error);
}
$stmt->bind_param("s", $_SESSION['UserID']);
if (!$stmt->execute()) {
    die("Database error: Failed to execute query: " . $conn->error);
}
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();
$stmt->close();
if (!$doctor) {
    die("No doctor record found for UserID: " . htmlspecialchars($_SESSION['UserID']));
}
// Fetch doctor's appointments
$appointments = [];
$sql = "SELECT a.id, a.appointment_date, a.status, p.Fullname AS patient_name 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.UserID 
        WHERE a.doctor_id = ? 
        ORDER BY a.appointment_date DESC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "<script>showToast('Database error: Failed to prepare appointment query: " . addslashes($conn->error) . "', 'error');</script>";
} else {
    $stmt->bind_param("s", $_SESSION['UserID']);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $appointments = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        echo "<script>showToast('Error fetching appointments: " . addslashes($conn->error) . "', 'error');</script>";
    }
    $stmt->close();
}

// Handle profile update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $national_id = trim($_POST['national_id']);
    $license_number = trim($_POST['license_number']);
    $full_name = trim($_POST['full_name']);
    $specialization = trim($_POST['specialization']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    
    // Validate inputs
    if (empty($national_id) || empty($full_name) || empty($email) || empty($phone_number)) {
        echo "<script>showToast('Please fill in all required fields.', 'warning');</script>";
    } else {
        // Prepare SQL statement to update doctor details
        $sql = "UPDATE doctors SET UserID = ?, RegNo = ?, Fullname = ?, Specialization = ?, Email = ?, Contact_No = ? WHERE UserID = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo "<script>showToast('Database error: Failed to prepare update query: " . addslashes($conn->error) . "', 'error');</script>";
        } else {
            $stmt->bind_param("sssssss", $national_id, $license_number, $full_name, $specialization, $email, $phone_number, $_SESSION['UserID']);
            if ($stmt->execute()) {
                // Update successful, refresh doctor data
                $_SESSION['UserID'] = $national_id; // Update session UserID
                $sql = "SELECT * FROM doctors WHERE UserID = ?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    echo "<script>showToast('Database error: Failed to prepare fetch query: " . addslashes($conn->error) . "', 'error');</script>";
                } else {
                    $stmt->bind_param("s", $national_id);
                    if ($stmt->execute()) {
                        $result = $stmt->get_result();
                        $doctor = $result->fetch_assoc();
                        echo "<script>showToast('Profile updated successfully.', 'success');</script>";
                    } else {
                        echo "<script>showToast('Error fetching updated profile: " . addslashes($conn->error) . "', 'error');</script>";
                    }
                }
            } else {
                echo "<script>showToast('Error updating profile: " . addslashes($conn->error) . "', 'error');</script>";
            }
            $stmt->close();
        }
    }
}

// Handle appointment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_appointment_status'])) {
    $appointment_id = trim($_POST['appointment_id']);
    $new_status = trim($_POST['new_status']);
    
    if (in_array($new_status, ['Approved', 'Declined'])) {
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?");
        if (!$stmt) {
            echo "<script>showToast('Database error: Failed to prepare update query: " . addslashes($conn->error) . "', 'error');</script>";
        } else {
            $stmt->bind_param("sss", $new_status, $appointment_id, $_SESSION['UserID']);
            if ($stmt->execute()) {
                echo "<script>showToast('Appointment status updated successfully.', 'success');</script>";
                // Refresh page to reflect changes
                header("Refresh:0");
            } else {
                echo "<script>showToast('Error updating appointment status: " . addslashes($conn->error) . "', 'error');</script>";
            }
            $stmt->close();
        }
    } else {
        echo "<script>showToast('Invalid status provided.', 'error');</script>";
    }
}

// Handle patient search by UserID or Fullname
$selected_patient = null;
$prescription_history = [];
$search_error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_userid'])) {
    $search_term = trim($_POST['search_userid']);
    if (!empty($search_term)) {
        $sql = "SELECT id, UserID, Fullname, Email, Contact_No, Age, Gender, Birth, Blood_Type, Academic_Year, Faculty, Citizenship, Any_allergies, Emergency_Contact 
                FROM patients 
                WHERE UserID LIKE ? OR Fullname LIKE ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $search_error = "Database error: Failed to prepare search query: " . $conn->error;
            echo "<script>showToast('$search_error', 'error');</script>";
        } else {
            $search_pattern = "%" . $search_term . "%";
            $stmt->bind_param("ss", $search_pattern, $search_pattern);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $selected_patient = $result->fetch_assoc();
                    $patient_id = $selected_patient['UserID'];
                    $sql = "SELECT PrescriptionID, Medication, Dosage, DateIssued, Status 
                            FROM prescriptions 
                            WHERE PatientID = ? 
                            ORDER BY DateIssued DESC";
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        echo "<script>showToast('Database error: Failed to prepare prescription query: " . addslashes($conn->error) . "', 'error');</script>";
                    } else {
                        $stmt->bind_param("s", $patient_id);
                        if ($stmt->execute()) {
                            $prescription_result = $stmt->get_result();
                            while ($row = $prescription_result->fetch_assoc()) {
                                $prescription_history[] = $row;
                            }
                        } else {
                            echo "<script>showToast('Error fetching prescriptions: " . addslashes($conn->error) . "', 'error');</script>";
                        }
                    }
                } else {
                    $search_error = "No patient found for search term: " . htmlspecialchars($search_term);
                    echo "<script>showToast('$search_error', 'warning');</script>";
                }
                $stmt->close();
            } else {
                $search_error = "Database error: Failed to execute search query: " . $conn->error;
                echo "<script>showToast('$search_error', 'error');</script>";
            }
        }
    } else {
        $search_error = "Please enter a UserID or Fullname to search.";
        echo "<script>showToast('$search_error', 'warning');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Center - Doctor Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background);
            color: var(--text);
            transition: background-color 0.3s, color 0.3s;
        }
        .update-label {
            display: block;
            width: 200px;
            padding: 5px;
            border-radius: 4px;
            cursor: pointer;
            background: var(--primary);
            color: var(--text-light);
        }
        nav {
            display: block !important;
        }
        #emergency-mail:target ~ nav {
            display: block !important;
        }
        .input-image {
            display: none;
        }
        .font-small { font-size: 16px; }
        .font-medium { font-size: 17px; }
        .font-large { font-size: 19px; }
        .navbar {
            background-color: var(--dark-bg);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand {
            font-weight: 700;
        }
        .sidebar {
            height: calc(100vh - 56px);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 56px;
            left: 0;
            width: 250px;
            z-index: 100;
            transition: all 0.3s;
            background-color: var(--light-bg);
        }
        .sidebar .nav-link {
            border-radius: 0;
            padding: 12px 20px;
            margin: 2px 0;
            transition: all 0.3s;
            color: var(--text);
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary);
            border-left: 4px solid var(--primary);
        }
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
            background-color: var(--light-bg);
            color: var(--text);
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            font-weight: 600;
            padding: 15px 20px;
            background-color: var(--light-bg);
        }
        .card profileimg {
            width: 50px;
            height: 50px;
            border-radius: 10%;
            margin-bottom: 5px;
        }
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            color: var(--text-light);
        }
        .btn-success {
            background-color: var(--success);
            border-color: var(--success);
            color: var(--text-light);
        }
        .btn-danger {
            background-color: var(--error);
            border-color: var(--error);
            color: var(--text-light);
        }
        .profile-header {
            background-color: var(--primary);
            color: var(--text-light);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .profile-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid var(--text-light);
            object-fit: cover;
        }
        .search-box {
            position: relative;
        }
        .search-box input {
            padding-left: 40px;
            border-radius: 20px;
        }
        .search-box i {
            position: absolute;
            left: 15px;
            top: 10px;
            color: #aaa;
        }
        .patient-card {
            cursor: pointer;
            transition: all 0.3s;
        }
        .patient-card:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }
        .patient-card.active {
            background-color: rgba(52, 152, 219, 0.2);
            border-left: 4px solid var(--primary);
        }
        .medicine-table tbody tr {
            transition: background-color 0.3s;
        }
        .medicine-table tbody tr:hover {
            background-color: rgba(46, 204, 113, 0.1);
        }
        .tab-content {
            padding: 20px 0;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--error);
            color: var(--text-light);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                padding: 0;
            }
            .sidebar.show {
                width: 250px;
            }
            .main-content {
                margin-left: 0;
            }
        }
        .profile-photo-container {
            position: relative;
            display: inline-block;
        }
        .profile-photo-edit {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: var(--primary);
            color: var(--text-light);
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid var(--text-light);
        }
        .upload-photo-input {
            display: none;
        }
        .patient-form-container {
            display: none;
        }
        .patient-form-container.show {
            display: block;
        }
        .settings-section {
            margin-bottom: 30px;
        }
        .settings-section-title {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .settings-card {
            margin-bottom: 20px;
        }
        .settings-card-header {
            border-bottom: 1px solid #dee2e6;
            font-weight: 500;
        }
        .settings-form-group {
            margin-bottom: 15px;
        }
        .settings-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .settings-toggle-label {
            margin-right: 15px;
        }
        .settings-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #dee2e6;
        }
        .settings-avatar-container {
            position: relative;
            display: inline-block;
        }
        .settings-avatar-edit {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background-color: var(--primary);
            color: var(--text-light);
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .settings-security-level {
            height: 10px;
            border-radius: 5px;
            background-color: #e9ecef;
            margin-top: 5px;
            overflow: hidden;
        }
        .settings-security-level-bar {
            height: 100%;
            background-color: var(--success);
            transition: width 0.3s;
        }
        .settings-password-strength {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        .settings-notification-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f1f1f1;
        }
        .settings-notification-item:last-child {
            border-bottom: none;
        }
        .profile button {
            margin-top: 20px;
            padding: 5px 10px;
            font-size: 14px;
            cursor: pointer;
            background-color: var(--primary);
            color: var(--text-light);
            border: 2px solid var(--text-light);
        }
        .profile img {
            object-fit: cover;
            object-position: center;
        }
        .dark-mode .card,
        .dark-mode .form-control,
        .dark-mode .form-select,
        .dark-mode .dropdown-menu,
        .dark-mode .list-group-item,
        .dark-mode .table,
        .dark-mode .modal-content {
            background-color: var(--light-bg);
            color: var(--text);
            border-color: var(--text-light);
        }
        .dark-mode .form-control::placeholder,
        .dark-mode .form-select,
        .dark-mode .text-muted {
            color: var(--text-light);
        }
        .dark-mode .card-header {
            background-color: var(--light-bg);
            border-bottom-color: var(--text-light);
        }
        .dark-mode .list-group-item {
            background-color: var(--light-bg);
            color: var(--text);
        }
        .dark-mode .table th,
        .dark-mode .table td {
            border-color: var(--text-light);
        }
        #darkModeToggle i {
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/home">
                <i class="bi bi-heart-pulse-fill me-2"></i>Medical Center - Doctor Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" id="sidebar-toggle" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
                            <span><?php echo htmlspecialchars($doctor['Fullname']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="index.php">
                                    <i class="bi bi-house-door me-2" aria-hidden="true"></i>Home
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="login.php">
                                     <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <button id="darkModeToggle" class="btn btn-primary rounded-circle ms-3" style="width: 40px; height: 40px;">
                            <i class="fas fa-moon"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize tooltips
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            [...tooltipTriggerList].forEach(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

            // Sidebar and navbar toggle
            const sidebarToggle = document.getElementById('sidebar-toggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function () {
                    const navbarNav = document.getElementById('navbarNav');
                    navbarNav.classList.toggle('show');
                    const sidebar = document.getElementById('sidebar');
                    if (sidebar) {
                        sidebar.classList.toggle('active');
                    }
                });
            }

            // Logout confirmation
            const logoutBtn = document.getElementById('logoutBtn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (confirm('Are you sure you want to log out?')) {
                        window.location.href = '/logout';
                    }
                });
            }

            // Dark Mode Toggle
            const darkModeToggle = document.getElementById('darkModeToggle');
            const body = document.documentElement;
            if (localStorage.getItem('darkMode') === 'enabled') {
                body.classList.add('dark-mode');
                darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            }
            darkModeToggle.addEventListener('click', function () {
                body.classList.toggle('dark-mode');
                if (body.classList.contains('dark-mode')) {
                    darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                    localStorage.setItem('darkMode', 'enabled');
                } else {
                    darkModeToggle.innerHTML = '<i class="fas fa-moon"></i>';
                    localStorage.setItem('darkMode', 'disabled');
                }
            });

            // Activate the correct tab based on URL or form submission
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab') || '<?php echo isset($_POST['active_tab']) ? $_POST['active_tab'] : (isset($_SESSION['active_tab']) ? $_SESSION['active_tab'] : 'dashboard'); ?>';
            if (activeTab) {
                const tabLink = document.querySelector(`a.nav-link[href="#${activeTab}"]`);
                if (tabLink) {
                    const bsTab = new bootstrap.Tab(tabLink);
                    bsTab.show();
                    document.querySelectorAll('.sidebar .nav-link').forEach(link => link.classList.remove('active'));
                    tabLink.classList.add('active');
                }
            }

            // Store active tab in session or localStorage
            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                link.addEventListener('click', function() {
                    const tabId = this.getAttribute('href').substring(1);
                    localStorage.setItem('activeTab', tabId);
                });
            });

            // Password strength indicator
            const newPasswordInput = document.getElementById('newPassword');
            const passwordStrengthBar = document.getElementById('passwordStrengthBar');
            const passwordStrengthText = document.getElementById('passwordStrengthText');
            if (newPasswordInput && passwordStrengthBar && passwordStrengthText) {
                newPasswordInput.addEventListener('input', function() {
                    const password = this.value;
                    let strength = 0;
                    if (password.length >= 8) strength += 25;
                    if (/[A-Z]/.test(password)) strength += 25;
                    if (/[0-9]/.test(password)) strength += 25;
                    if (/[^A-Za-z0-9]/.test(password)) strength += 25;

                    passwordStrengthBar.style.width = `${strength}%`;
                    if (strength <= 25) {
                        passwordStrengthText.textContent = 'Password strength: Weak';
                        passwordStrengthBar.style.backgroundColor = 'var(--error)';
                    } else if (strength <= 50) {
                        passwordStrengthText.textContent = 'Password strength: Fair';
                        passwordStrengthBar.style.backgroundColor = '#ffc107';
                    } else if (strength <= 75) {
                        passwordStrengthText.textContent = 'Password strength: Good';
                        passwordStrengthBar.style.backgroundColor = '#17a2b8';
                    } else {
                        passwordStrengthText.textContent = 'Password strength: Strong';
                        passwordStrengthBar.style.backgroundColor = 'var(--success)';
                    }
                });
            }

            // Client-side form validation for password change
            const securityForm = document.getElementById('securityForm');
            if (securityForm) {
                securityForm.addEventListener('submit', function(e) {
                    const currentPassword = document.querySelector('input[name="current_password"]').value;
                    const newPassword = document.querySelector('input[name="new_password"]').value;
                    const confirmPassword = document.querySelector('input[name="confirm_password"]').value;

                    if (!currentPassword || !newPassword || !confirmPassword) {
                        e.preventDefault();
                        showToast('All password fields are required.', 'warning');
                        return;
                    }
                    if (newPassword.length < 8) {
                        e.preventDefault();
                        showToast('New password must be at least 8 characters long.', 'warning');
                        return;
                    }
                    if (newPassword !== confirmPassword) {
                        e.preventDefault();
                        showToast('New passwords do not match.', 'warning');
                        return;
                    }
                });
            }
        });
    </script>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="p-3">
            <div class="d-flex align-items-center mb-3">
        
            </div>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo (!isset($_POST['active_tab']) && !isset($_SESSION['active_tab']) || (isset($_SESSION['active_tab']) && $_SESSION['active_tab'] === 'dashboard')) ? 'active' : ''; ?>" href="#dashboard" data-bs-toggle="tab">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (isset($_POST['active_tab']) && $_POST['active_tab'] === 'patients') || (isset($_SESSION['active_tab']) && $_SESSION['active_tab'] === 'patients') ? 'active' : ''; ?>" href="#patients" data-bs-toggle="tab">
                    <i class="bi bi-people"></i> Patients
                </a>
            </li>
            <li class="nav-item">
    <a class="nav-link <?php echo (isset($_POST['active_tab']) && $_POST['active_tab'] === 'appointments') || (isset($_SESSION['active_tab']) && $_SESSION['active_tab'] === 'appointments') ? 'active' : ''; ?>" href="#appointments" data-bs-toggle="tab">
        <i class="bi bi-calendar-check"></i> Appointments
    </a>
</li>
            <li class="nav-item">
                <a class="nav-link" href="email.html">
                    <i class="bi bi-envelope-exclamation"></i> Emergency Mail
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (isset($_POST['active_tab']) && $_POST['active_tab'] === 'settings') || (isset($_SESSION['active_tab']) && $_SESSION['active_tab'] === 'settings') ? 'active' : ''; ?>" href="#settings" data-bs-toggle="tab">
                    <i class="bi bi-gear"></i> Settings
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="tab-content">
            <!-- Dashboard Tab -->
            <div class="tab-pane fade <?php echo (!isset($_POST['active_tab']) && !isset($_SESSION['active_tab']) || (isset($_SESSION['active_tab']) && $_SESSION['active_tab'] === 'dashboard')) ? 'show active' : ''; ?>" id="dashboard">
                <div class="profile-header">
                    <h4><i class="bi bi-person-badge"></i> Doctor Profile</h4>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Personal Information</span>
                                <button class="btn btn-sm btn-outline-primary" id="goToProfileSettingsBtn"><i class="bi bi-pencil"></i> Edit</button>
                            </div>
                            <script>
                                document.getElementById('goToProfileSettingsBtn').addEventListener('click', function () {
                                    const settingsTab = new bootstrap.Tab(document.querySelector('a.nav-link[href="#settings"]'));
                                    settingsTab.show();
                                    setTimeout(() => {
                                        const profileTab = new bootstrap.Tab(document.querySelector('a.nav-link[href="#profile"]'));
                                        profileTab.show();
                                    }, 300);
                                });
                            </script>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($doctor['Fullname']); ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Specialization</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($doctor['Specialization']); ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($doctor['Email']); ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($doctor['Contact_No']); ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">License Number</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($doctor['RegNo']); ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Profile Information</span>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">National ID</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($doctor['UserID']); ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Patients Tab -->
            <div class="tab-pane fade <?php echo (isset($_POST['active_tab']) && $_POST['active_tab'] === 'patients') || (isset($_SESSION['active_tab']) && $_SESSION['active_tab'] === 'patients') ? 'show active' : ''; ?>" id="patients">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4><i class="bi bi-people"></i> Patient Management</h4>
                    <form method="POST" action="doctor_dashboard.php" class="search-box d-flex align-items-center">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control me-2" id="patientSearch" name="search_userid" placeholder="Enter Patient UserID or Fullname">
                        <input type="hidden" name="active_tab" value="patients">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                </div>
                <div class="row">
                    <div class="col-md-12 patient-details-card">
                        <div class="card">
                            <div class="card-header">
                                <span>Patient Details</span>
                            </div>
                            <div class="card-body">
                                <?php if ($selected_patient): ?>
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">UserID</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($selected_patient['UserID']); ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Full Name</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($selected_patient['Fullname']); ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($selected_patient['Email'] ?? 'Not specified'); ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Contact Number</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($selected_patient['Contact_No'] ?? 'Not specified'); ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Gender</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($selected_patient['Gender'] ?? 'Not specified'); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Date of Birth</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($selected_patient['Birth'] ?? 'Not specified'); ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Blood Type</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($selected_patient['Blood_Type'] ?? 'Not specified'); ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Academic Year</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($selected_patient['Academic_Year'] ?? 'Not specified'); ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Faculty</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($selected_patient['Faculty'] ?? 'Not specified'); ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Citizenship</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($selected_patient['Citizenship'] ?? 'Not specified'); ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Allergies</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($selected_patient['Any_allergies'] ?? 'None'); ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Emergency Contact</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($selected_patient['Emergency_Contact'] ?? 'Not specified'); ?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="tab-content mt-4">
                                        <div>
                                            <a href="new_prescription.php?patient_id=<?php echo urlencode($selected_patient['UserID']); ?>&patient_name=<?php echo urlencode($selected_patient['Fullname']); ?>">
                                                <button class="btn btn-primary" id="newPrescriptionBtn">
                                                    <i class="bi bi-plus-circle"></i> New Prescription
                                                </button>
                                            </a>
                                            <a href="patien_histry.php?patient_id=<?php echo urlencode($selected_patient['UserID']); ?>&patient_name=<?php echo urlencode($selected_patient['Fullname']); ?>">
                                                <button class="btn btn-primary" id="patientHistoryBtn">
                                                    <i class="bi bi-file-earmark-text"></i> Patient History
                                                </button>
                                            </a>
      <a href="medical_form.php?patient_id=<?php echo urlencode($selected_patient['UserID']); ?>&patient_name=<?php echo urlencode($selected_patient['Fullname']); ?>">
    <button class="btn btn-primary" id="patientMedicalBtn">
        <i class="bi bi-file-earmark-text"></i> Medical Report
    </button>
</a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">Enter a valid UserID or Fullname to view patient details.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Appointments Tab -->
<div class="tab-pane fade <?php echo (isset($_POST['active_tab']) && $_POST['active_tab'] === 'appointments') || (isset($_SESSION['active_tab']) && $_SESSION['active_tab'] === 'appointments') ? 'show active' : ''; ?>" id="appointments">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-calendar-check"></i> Appointments</h4>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <span>Scheduled Appointments</span>
                </div>
                <div class="card-body">
                    <?php if (!empty($appointments)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered medicine-table">
                                <thead>
                                    <tr>
                                        <th>Patient Name</th>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                            <td><?php echo date('F j, Y, g:i A', strtotime($appointment['appointment_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['status']); ?></td>
                                            <td>
                                                <?php if ($appointment['status'] === 'Pending'): ?>
                                                    <form method="POST" action="doctor_dashboard.php" style="display:inline;">
                                                        <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['id']); ?>">
                                                        <input type="hidden" name="new_status" value="Approved">
                                                        <input type="hidden" name="active_tab" value="appointments">
                                                        <button type="submit" name="update_appointment_status" class="btn btn-sm btn-success"><i class="bi bi-check-circle"></i> Approve</button>
                                                    </form>
                                                    <form method="POST" action="doctor_dashboard.php" style="display:inline;">
                                                        <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['id']); ?>">
                                                        <input type="hidden" name="new_status" value="Declined">
                                                        <input type="hidden" name="active_tab" value="appointments">
                                                        <button type="submit" name="update_appointment_status" class="btn btn-sm btn-danger"><i class="bi bi-x-circle"></i> Decline</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted">No actions available</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No appointments scheduled.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
            
            <!-- Settings Tab -->
            <div class="tab-pane fade <?php echo (isset($_POST['active_tab']) && $_POST['active_tab'] === 'settings') || (isset($_SESSION['active_tab']) && $_SESSION['active_tab'] === 'settings') ? 'show active' : ''; ?>" id="settings">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4><i class="bi bi-gear"></i> Settings</h4>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card settings-card">
                            <div class="card-header settings-card-header">
                                <h5 class="mb-0">Account Settings</h5>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-pills flex-column" id="settingsTabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="profile-tab" data-bs-toggle="pill" href="#profile" role="tab">
                                            <i class="bi bi-person me-2"></i> Profile
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="security-tab" data-bs-toggle="pill" href="#security" role="tab">
                                            <i class="bi bi-shield-lock me-2"></i> Security
                                        </a>
                                    </li>
                                    
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="tab-content" id="settingsTabContent">
                            <!-- Profile Settings -->
                            <div class="tab-pane fade show active" id="profile" role="tabpanel">
                                <div class="card settings-card">
                                    <div class="card-header settings-card-header">
                                        <h5 class="mb-0">Profile Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="profileForm" action="doctor_dashboard.php" method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="update_profile" value="1">
                                            <input type="hidden" name="active_tab" value="settings">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="settings-form-group">
                                                        <label class="form-label">National ID</label>
                                                        <input type="text" class="form-control" name="national_id" value="<?php echo htmlspecialchars($doctor['UserID'] ?? ''); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="settings-form-group">
                                                        <label class="form-label">License Number</label>
                                                        <input type="text" class="form-control" name="license_number" value="<?php echo htmlspecialchars($doctor['RegNo'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="settings-form-group">
                                                <label class="form-label">Full Name</label>
                                                <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($doctor['Fullname'] ?? ''); ?>" required>
                                            </div>
                                            <div class="settings-form-group">
                                                <label class="form-label">Specialization</label>
                                                <select class="form-select" name="specialization" required>
                                                    <option value="Cardiologist" <?php echo ($doctor['Specialization'] ?? '') == 'Cardiologist' ? 'selected' : ''; ?>>Cardiologist</option>
                                                    <option value="Neurologist" <?php echo ($doctor['Specialization'] ?? '') == 'Neurologist' ? 'selected' : ''; ?>>Neurologist</option>
                                                    <option value="Pediatrician" <?php echo ($doctor['Specialization'] ?? '') == 'Pediatrician' ? 'selected' : ''; ?>>Pediatrician</option>
                                                    <option value="General Practitioner" <?php echo ($doctor['Specialization'] ?? '') == 'General Practitioner' ? 'selected' : ''; ?>>General Practitioner</option>
                                                    <option value="Surgeon" <?php echo ($doctor['Specialization'] ?? '') == 'Surgeon' ? 'selected' : ''; ?>>Surgeon</option>
                                                    <option value="Other" <?php echo ($doctor['Specialization'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                            <div class="settings-form-group">
                                                <label class="form-label">Email</label>
                                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($doctor['Email'] ?? ''); ?>" required>
                                            </div>
                                            <div class="settings-form-group">
                                                <label class="form-label">Phone Number</label>
                                                <input type="tel" class="form-control" name="phone_number" value="<?php echo htmlspecialchars($doctor['Contact_No'] ?? ''); ?>" required>
                                            </div>
                                            <div class="d-flex justify-content-end mt-4">
                                                <button type="button" class="btn btn-secondary me-2">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <!-- Security Settings -->
                            <div class="tab-pane fade" id="security" role="tabpanel">
                                <div class="card settings-card">
                                    <div class="card-header settings-card-header">
                                        <h5 class="mb-0">Security Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="securityForm" action="doctor_dashboard.php" method="POST">
                                            <input type="hidden" name="change_password" value="1">
                                            <input type="hidden" name="active_tab" value="settings">
                                            <div class="settings-form-group">
                                                <label class="form-label">Current Password</label>
                                                <input type="password" class="form-control" name="current_password" placeholder="Enter current password" required>
                                            </div>
                                            <div class="settings-form-group">
                                                <label class="form-label">New Password</label>
                                                <input type="password" class="form-control" id="newPassword" name="new_password" placeholder="Enter new password" required>
                                                <div class="settings-security-level mt-2">
                                                    <div class="settings-security-level-bar" id="passwordStrengthBar" style="width: 0%"></div>
                                                </div>
                                                <div class="settings-password-strength" id="passwordStrengthText">Password strength: Weak</div>
                                            </div>
                                            <div class="settings-form-group">
                                                <label class="form-label">Confirm New Password</label>
                                                <input type="password" class="form-control" name="confirm_password" placeholder="Confirm new password" required>
                                            </div>
                                            <div class="d-flex justify-content-end mt-4">
                                                <button type="button" class="btn btn-secondary me-2">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Update Password</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <!-- Preferences Settings -->
                            <div class="tab-pane fade" id="preferences" role="tabpanel">
                                <div class="card settings-card">
                                    <div class="card-header settings-card-header">
                                        <h5 class="mb-0">Application Preferences</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="preferencesForm">
                                            <h6 class="settings-section-title">Display Settings</h6>
                                            <div class="settings-form-group">
                                                <label class="form-label">Theme</label>
                                                <select class="form-select" id="themeSelector">
                                                    <option value="light">Light</option>
                                                    <option value="dark">Dark</option>
                                                </select>
                                            </div>
                                            <script>
                                                document.addEventListener('DOMContentLoaded', function() {
                                                    const themeSelector = document.getElementById('themeSelector');
                                                    const savedTheme = localStorage.getItem('darkMode') === 'enabled' ? 'dark' : 'light';
                                                    if (savedTheme) {
                                                        themeSelector.value = savedTheme;
                                                    }
                                                    themeSelector.addEventListener('change', function() {
                                                        if (this.value === 'dark') {
                                                            document.documentElement.classList.add('dark-mode');
                                                            localStorage.setItem('darkMode', 'enabled');
                                                            document.getElementById('darkModeToggle').innerHTML = '<i class="fas fa-sun"></i>';
                                                        } else {
                                                            document.documentElement.classList.remove('dark-mode');
                                                            localStorage.setItem('darkMode', 'disabled');
                                                            document.getElementById('darkModeToggle').innerHTML = '<i class="fas fa-moon"></i>';
                                                        }
                                                    });
                                                });
                                            </script>
                                            <div class="settings-form-group">
                                                <label class="form-label">Font Size</label>
                                                <select class="form-select" id="fontSizeSelector">
                                                    <option value="small">Small</option>
                                                    <option value="medium" selected>Medium</option>
                                                    <option value="large">Large</option>
                                                </select>
                                            </div>
                                            <script>
                                                document.addEventListener("DOMContentLoaded", function () {
                                                    const fontSizeSelector = document.getElementById("fontSizeSelector");
                                                    const savedFontSize = localStorage.getItem("fontSize");
                                                    if (savedFontSize) {
                                                        document.body.classList.add(`font-${savedFontSize}`);
                                                        if (fontSizeSelector) {
                                                            fontSizeSelector.value = savedFontSize;
                                                        }
                                                    } else {
                                                        document.body.classList.add("font-medium");
                                                        if (fontSizeSelector) {
                                                            fontSizeSelector.value = "medium";
                                                        }
                                                    }
                                                    if (fontSizeSelector) {
                                                        fontSizeSelector.addEventListener("change", function () {
                                                            document.body.classList.remove("font-small", "font-medium", "font-large");
                                                            const selectedSize = this.value;
                                                            document.body.classList.add(`font-${selectedSize}`);
                                                            localStorage.setItem("fontSize", selectedSize);
                                                        });
                                                    }
                                                });
                                            </script>
                                            <div class="settings-form-group settings-toggle">
                                            </div>
                                            <h6 class="settings-section-title mt-4">Workflow Preferences</h6>
                                            <div class="settings-form-group">
                                                <label class="form-label">Default View</label>
                                                <select class="form-select" id="defaultViewSelect">
                                                    <option value="dashboard">Dashboard</option>
                                                    <option value="patients">Patients</option>
                                                    <option value="emergency-mail">Emergency-mail</option>
                                                </select>
                                            </div>
                                            <script>
                                                document.addEventListener("DOMContentLoaded", function () {
                                                    const defaultView = localStorage.getItem("defaultView") || "dashboard";
                                                    const triggerTab = document.querySelector(`a.nav-link[href="#${defaultView}"]`);
                                                    if (triggerTab && !new URLSearchParams(window.location.search).get('tab') && !'<?php echo isset($_POST['active_tab']) ? $_POST['active_tab'] : (isset($_SESSION['active_tab']) ? $_SESSION['active_tab'] : ''); ?>') {
                                                        new bootstrap.Tab(triggerTab).show();
                                                        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                                                            link.classList.remove('active');
                                                        });
                                                        triggerTab.classList.add('active');
                                                    }
                                                    const defaultViewSelect = document.getElementById("defaultViewSelect");
                                                    if (defaultViewSelect) {
                                                        defaultViewSelect.value = defaultView;
                                                        defaultViewSelect.addEventListener("change", function () {
                                                            localStorage.setItem("defaultView", this.value);
                                                        });
                                                    }
                                                });
                                            </script>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container"></div>
    
    <!-- New Prescription Modal -->
    <div class="modal fade" id="newPrescriptionModal" tabindex="-1" aria-labelledby="newPrescriptionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="newPrescriptionModalLabel"><i class="bi bi-clipboard2-pulse me-2"></i>New Prescription</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="newPrescriptionForm">
                        <div class="mb-3">
                            <label for="patientName" class="form-label">Patient Name</label>
                            <input type="text" class="form-control" id="patientName" value="<?php echo htmlspecialchars($selected_patient['Fullname'] ?? ''); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="patientId" class="form-label">Patient ID</label>
                            <input type="text" class="form-control" id="patientId" value="<?php echo htmlspecialchars($selected_patient['UserID'] ?? ''); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="illness" class="form-label">Illness/Diagnosis</label>
                            <textarea class="form-control" id="illness" rows="3" placeholder="Describe patient's illness or diagnosis..."></textarea>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered medicine-table">
                                <thead>
                                    <tr>
                                        <th>Medicine</th>
                                        <th>Dosage</th>
                                        <th>Frequency</th>
                                        <th>Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="medicineTableBodyMain">
                                    <tr>
                                        <td>
                                            <select class="form-select">
                                                <option>Select Medicine</option>
                                                <option>Losartan</option>
                                                <option>Metoprolol</option>
                                                <option>Atenolol</option>
                                                <option>Carvedilol</option>
                                                <option>Metformin</option>
                                                <option>Glibenclamide</option>
                                                <option>Glipizide</option>
                                                <option>Other...</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" placeholder="e.g. 50mg">
                                        </td>
                                        <td>
                                            <select class="form-select">
                                                <option>Select Frequency</option>
                                                <option>Once daily</option>
                                                <option>Twice daily</option>
                                                <option>Three times daily</option>
                                                <option>As needed</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select">
                                                <option>Select Time</option>
                                                <option>Morning</option>
                                                <option>Afternoon</option>
                                                <option>Evening</option>
                                                <option>Bedtime</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger remove-row"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" class="btn btn-success" id="addNewMedicineBtn">
                                <i class="bi bi-plus-circle"></i> Add Medicine
                            </button>
                            <button type="button" class="btn btn-primary" id="savePrescriptionBtn">
                                <i class="bi bi-save"></i> Save Prescription
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Prescription Modal -->
    <div class="modal fade" id="uploadPrescriptionModal" tabindex="-1" aria-labelledby="uploadPrescriptionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="uploadPrescriptionModalLabel"><i class="bi bi-upload me-2"></i>Upload Prescription to Pharmacy</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadPrescriptionForm">
                        <div class="mb-3">
                            <label for="pharmacyEmail" class="form-label">Pharmacy Email</label>
                            <input type="email" class="form-control" id="pharmacyEmail" value="pharmacy@medicare.com">
                        </div>
                        <div class="mb-3">
                            <label for="prescriptionNotes" class="form-label">Notes for Pharmacy</label>
                            <textarea class="form-control" id="prescriptionNotes" rows="3">Please prepare these medications for patient <?php echo htmlspecialchars($selected_patient['Fullname'] ?? ''); ?> (ID: <?php echo htmlspecialchars($selected_patient['UserID'] ?? ''); ?>).</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload PDF</label>
                            <input type="file" class="form-control" id="prescriptionFile" accept=".pdf">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmUploadBtn">
                        <i class="bi bi-upload"></i> Upload Prescription
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/js/all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function showToast(message, type) {
                const toastContainer = document.querySelector('.toast-container');
                const toast = document.createElement('div');
                toast.className = `toast align-items-center text-white bg-${type} border-0`;
                toast.setAttribute('role', 'alert');
                toast.setAttribute('aria-live', 'assertive');
                toast.setAttribute('aria-atomic', 'true');
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                `;
                toastContainer.appendChild(toast);
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
                setTimeout(() => toast.remove(), 5000);
            }

            document.getElementById('sidebar-toggle').addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('show');
            });
            
            document.getElementById('logoutBtn').addEventListener('click', function(e) {
                e.preventDefault();
                showToast('Logging out...', 'info');
                setTimeout(() => {
                    window.location.href = '/logout';
                }, 1500);
            });
            
            document.getElementById('patientSearch').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.form.submit();
                }
            });
            
            document.getElementById('newPrescriptionBtn').addEventListener('click', function() {
                const newPrescriptionModal = new bootstrap.Modal(document.getElementById('newPrescriptionModal'));
                newPrescriptionModal.show();
            });
            
            document.getElementById('addNewMedicineBtn').addEventListener('click', function() {
                const medicineTableBody = document.getElementById('medicineTableBodyMain');
                const newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td>
                        <select class="form-select">
                            <option>Select Medicine</option>
                            <option>Losartan</option>
                            <option>Metoprolol</option>
                            <option>Atenolol</option>
                            <option>Carvedilol</option>
                            <option>Metformin</option>
                            <option>Glibenclamide</option>
                            <option>Glipizide</option>
                            <option>Other...</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control" placeholder="e.g. 50mg">
                    </td>
                    <td>
                        <select class="form-select">
                            <option>Select Frequency</option>
                            <option>Once daily</option>
                            <option>Twice daily</option>
                            <option>Three times daily</option>
                            <option>As needed</option>
                        </select>
                    </td>
                    <td>
                        <select class="form-select">
                            <option>Select Time</option>
                            <option>Morning</option>
                            <option>Afternoon</option>
                            <option>Evening</option>
                            <option>Bedtime</option>
                        </select>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger remove-row"><i class="bi bi-trash"></i></button>
                    </td>
                `;
                medicineTableBody.appendChild(newRow);
                newRow.querySelector('.remove-row').addEventListener('click', function() {
                    this.closest('tr').remove();
                });
            });
            
            document.querySelectorAll('.remove-row').forEach(button => {
                button.addEventListener('click', function() {
                    this.closest('tr').remove();
                });
            });
            
            document.getElementById('savePrescriptionBtn').addEventListener('click', function() {
                const illness = document.getElementById('illness').value;
                if (!illness.trim()) {
                    showToast('Please describe the patient\'s illness or diagnosis', 'warning');
                    return;
                }
                const medicineSelects = document.querySelectorAll('#medicineTableBodyMain select');
                let hasMedicine = false;
                for (let i = 0; i < medicineSelects.length; i += 4) {
                    if (medicineSelects[i].value !== 'Select Medicine') {
                        hasMedicine = true;
                        break;
                    }
                }
                if (!hasMedicine) {
                    showToast('Please add at least one medicine', 'warning');
                    return;
                }
                const newPrescriptionModal = bootstrap.Modal.getInstance(document.getElementById('newPrescriptionModal'));
                newPrescriptionModal.hide();
                showToast('Prescription saved successfully', 'success');
            });
            
            document.getElementById('confirmUploadBtn').addEventListener('click', function() {
                const uploadPrescriptionModal = bootstrap.Modal.getInstance(document.getElementById('uploadPrescriptionModal'));
                uploadPrescriptionModal.hide();
                showToast('Prescription uploaded to pharmacy', 'success');
            });
        });
    </script>
</body>
</html>
<?php
if (isset($_POST['active_tab'])) {
    $_SESSION['active_tab'] = $_POST['active_tab'];
}
$conn->close();
?>
