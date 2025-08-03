<?php
session_start();
if (!isset($_SESSION['UserID']) || !in_array($_SESSION['role'], ['Patient', 'Doctor'])) {
    header("Location: ../login.php");
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
$role = $_SESSION['role'];
if ($role === 'Patient') {
    $stmt = $conn->prepare("SELECT Fullname, Email, Contact_No, Birth, Blood_Type, Gender FROM patients WHERE UserID = ?");
} else {
    $stmt = $conn->prepare("SELECT Fullname, Email, Contact_No, Specialization, RegNo FROM doctors WHERE UserID = ?");
}
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
    die("No user found with UserID: " . htmlspecialchars($UserID));
}

// Fetch patient's appointments
$appointments = [];
if ($role === 'Patient') {
    $stmt = $conn->prepare("SELECT a.id, a.appointment_date, a.status, d.Fullname AS doctor_name 
                            FROM appointments a 
                            JOIN doctors d ON a.doctor_id = d.UserID 
                            WHERE a.patient_id = ? 
                            ORDER BY a.appointment_date DESC");
    if ($stmt) {
        $stmt->bind_param("s", $UserID);
        $stmt->execute();
        $result = $stmt->get_result();
        $appointments = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - University Medical Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #e0e7ff;
            --text-color: #1a3556;
            --card-bg: rgba(255, 255, 255, 0.98);
            --primary-color: #007bff;
            --secondary-color: #00c4b4;
            --shadow: 0 12px 50px rgba(0, 50, 120, 0.15);
            --info-item-bg: rgba(240, 245, 255, 0.8);
            --header-bg: #e7f5ff;
        }

        [data-theme="dark"] {
            --bg-color: #1a1a2e;
            --text-color: #e0e0e0;
            --card-bg: rgba(40, 40, 60, 0.95);
            --primary-color: #4dabf7;
            --secondary-color: #26de81;
            --shadow: 0 12px 50px rgba(0, 0, 0, 0.3);
            --info-item-bg: rgba(40, 40, 60, 0.8);
            --header-bg: #2c3e50;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
            padding-top: 60px;
            transition: background 0.3s ease, color 0.3s ease;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://images.unsplash.com/photo-1522441815192-d9f04eb0615c');
            background-repeat: repeat;
            background-size: 250px;
            opacity: 0.04;
            z-index: -1;
            animation: gentleDrift 25s linear infinite;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background: var(--header-bg);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            z-index: 1000;
        }

        .header-branding {
            display: flex;
            align-items: center;
        }

        .medical-center-name {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--text-color);
        }

        .header-right {
            display: flex;
            align-items: center;
        }

        .theme-toggle {
            background: none;
            border: none;
            color: var(--text-color);
            cursor: pointer;
            margin-right: 15px;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .theme-toggle:hover {
            color: var(--primary-color);
        }

        .user-name {
            margin-right: 15px;
            font-weight: 500;
            color: var(--text-color);
        }

        .logout-btn {
            color: #dc3545;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .logout-btn:hover {
            color: #c82333;
        }

        .sidebar-toggle {
            position: fixed;
            top: 10px;
            left: 10px;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            display: none;
            z-index: 1001;
            transition: background 0.3s ease;
        }

        .sidebar-toggle:hover {
            background: var(--secondary-color);
        }

        .sidebar {
            position: fixed;
            top: 60px;
            left: 0;
            width: 250px;
            height: calc(100% - 60px);
            background: var(--card-bg);
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .sidebar.hidden {
            transform: translateX(-100%);
        }

        .sidebar ul {
            list-style: none;
            padding: 20px;
            margin: 0;
        }

        .sidebar li {
            margin-bottom: 10px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--text-color);
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .sidebar a:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }

        .sidebar a i {
            margin-right: 10px;
        }

        .main-content {
            margin-left: 250px;
            padding: 40px 20px;
            transition: margin-left 0.3s ease;
        }

        .main-content.full-width {
            margin-left: 0;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: var(--shadow);
            padding: 40px;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.7s ease-out;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0, 50, 120, 0.2);
        }

        .card-header h2 {
            font-size: 2.5rem;
            font-weight: 600;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-align: center;
            margin-bottom: 30px;
        }

        .welcome-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 30px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            position: relative;
            border: 2px solid transparent;
            background-clip: padding-box;
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
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: destination-out;
            mask-composite: exclude;
            z-index: -1;
        }

        .avatar-container {
            position: relative;
            margin: 0 auto 20px;
            width: 80px;
            height: 80px;
        }

        .avatar {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
        }

        .avatar:hover {
            transform: scale(1.1);
        }

        .status-indicator {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 16px;
            height: 16px;
            background: #28a745;
            border-radius: 50%;
            border: 2px solid var(--card-bg);
            animation: pulse 2s ease-in-out infinite;
        }

        .welcome-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--text-color);
            text-align: center;
            margin-bottom: 20px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .info-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            justify-content: center;
        }

        .info-item {
            display: flex;
            align-items: center;
            background: var(--info-item-bg);
            padding: 12px 20px;
            border-radius: 8px;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .info-item:hover {
            transform: translateY(-3px);
            background: var(--card-bg);
        }

        .icon {
            font-size: 1.2rem;
            color: var(--primary-color);
            margin-right: 10px;
        }

        .label {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-color);
            margin-right: 5px;
        }

        .value {
            font-size: 0.9rem;
            color: var(--text-color);
            font-weight: 400;
        }

        /* Appointment Card Styles */
        .appointment-card {
            margin-top: 20px;
        }

        .appointment-card .card-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 16px 16px 0 0;
        }

        .appointment-list .alert {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }

        .appointment-list .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .appointment-list .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .appointment-list .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeeba;
            color: #856404;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes gentleDrift {
            0% { background-position: 0 0; }
            100% { background-position: 250px 250px; }
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar-toggle {
                display: block;
            }
            .main-content {
                margin-left: 0;
            }
            .container {
                padding: 0 15px;
            }
            .card {
                padding: 30px;
            }
            .welcome-title {
                font-size: 1.6rem;
            }
            .info-item {
                min-width: 100%;
            }
            .medical-center-name {
                font-size: 1.2rem;
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
            .welcome-card {
                padding: 20px;
            }
            .welcome-title {
                font-size: 1.4rem;
            }
            .avatar-container {
                width: 60px;
                height: 60px;
            }
            .avatar {
                font-size: 2rem;
            }
            .info-item {
                padding: 10px 15px;
            }
            .medical-center-name {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body data-theme="light">
    <header class="header">
        <div class="header-branding">
            <div class="medical-center-name">University Medical Centre</div>
        </div>
        <div class="header-right">
            <button class="theme-toggle" onclick="toggleTheme()"><i class="fas fa-moon"></i></button>
            <span class="user-name">Welcome, <?php echo htmlspecialchars($user['Fullname']); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>
    <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
    <div class="sidebar" id="sidebar">
        <ul>
            <li><a href="patient_history.php?edit=<?php echo $role === 'Doctor' ? 'true' : 'false'; ?>"><i class="fas fa-history"></i> Patient History</a></li>
            <?php if ($role === 'Patient') { ?>
                <li><a href="view_prescriptions.php"><i class="fas fa-prescription-bottle-alt"></i> View Prescription</a></li>
                <li><a href="edit_patient_details.php"><i class="fas fa-edit"></i> Edit Details</a></li>
                <li><a href="submit_feedback.php"><i class="fas fa-comment-dots"></i> Submit Feedback</a></li>
                <li><a href="form_details.php"><i class="fas fa-file-alt"></i> Medical Form Details</a></li>
            <?php } ?>
            <li><a href="book_appointment.php"><i class="fas fa-calendar-plus"></i> Book Appointment</a></li>
        </ul>
    </div>
    <div class="main-content" id="main-content">
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h2><?php echo $role === 'Patient' ? 'Patient' : 'Doctor'; ?> Dashboard</h2>
                </div>
                <div class="welcome-card">
                    <div class="avatar-container">
                        <div class="avatar">
                            <i class="fas <?php echo $role == 'Patient' ? 'fa-user' : 'fa-user-md'; ?>"></i>
                        </div>
                        <div class="status-indicator"></div>
                    </div>
                    <h4 class="welcome-title">Welcome, <?php echo $role == 'Patient' ? htmlspecialchars($user['Fullname']) : 'Dr. ' . htmlspecialchars($user['Fullname']); ?>!</h4>
                    <div class="info-row">
                        <?php if ($role === 'Patient') { ?>
                            <div class="info-item">
                                <i class="fas fa-birthday-cake icon"></i>
                                <span class="label">Date of Birth:</span>
                                <span class="value"><?php echo htmlspecialchars($user['Birth'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-tint icon"></i>
                                <span class="label">Blood Group:</span>
                                <span class="value"><?php echo htmlspecialchars($user['Blood_Type'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-venus-mars icon"></i>
                                <span class="label">Gender:</span>
                                <span class="value"><?php echo htmlspecialchars($user['Gender'] ?? 'N/A'); ?></span>
                            </div>
                        <?php } else { ?>
                            <div class="info-item">
                                <i class="fas fa-stethoscope icon"></i>
                                <span class="label">Specialization:</span>
                                <span class="value"><?php echo htmlspecialchars($user['Specialization'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-id-badge icon"></i>
                                <span class="label">Reg No:</span>
                                <span class="value"><?php echo htmlspecialchars($user['RegNo'] ?? 'N/A'); ?></span>
                            </div>
                        <?php } ?>
                        <div class="info-item">
                            <i class="fas fa-phone icon"></i>
                            <span class="label">Contact:</span>
                            <span class="value"><?php echo htmlspecialchars($user['Contact_No'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-user icon"></i>
                            <span class="label">Age:</span>
                            <span class="value"><?php echo !empty($user['Birth']) ? (new DateTime())->diff(new DateTime($user['Birth']))->y : 'N/A'; ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-envelope icon"></i>
                            <span class="label">Email:</span>
                            <span class="value"><?php echo htmlspecialchars($user['Email'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>
                <?php if ($role === 'Patient' && !empty($appointments)) { ?>
                    <div class="card appointment-card">
                        <div class="card-header">
                            <h3>Your Appointments</h3>
                        </div>
                        <div class="card-body appointment-list">
                            <?php foreach ($appointments as $appointment) { ?>
                                <div class="alert alert-<?php echo $appointment['status'] === 'Approved' ? 'success' : ($appointment['status'] === 'Declined' ? 'danger' : 'warning'); ?> d-flex align-items-center" role="alert">
                                    <i class="fas <?php echo $appointment['status'] === 'Approved' ? 'fa-check-circle' : ($appointment['status'] === 'Declined' ? 'fa-times-circle' : 'fa-clock'); ?> me-2"></i>
                                    <div>
                                        Appointment with Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?> on <?php echo date('F j, Y, g:i A', strtotime($appointment['appointment_date'])); ?> is 
                                        <strong><?php echo htmlspecialchars($appointment['status']); ?></strong>.
                                    </div>
                                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } elseif ($role === 'Patient') { ?>
                    <div class="card appointment-card">
                        <div class="card-header">
                            <h3>Your Appointments</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">No appointments found. <a href="book_appointment.php">Book an appointment now</a>.</p>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            var sidebar = document.getElementById('sidebar');
            var mainContent = document.getElementById('main-content');
            sidebar.classList.toggle('hidden');
            mainContent.classList.toggle('full-width');
        }

        function toggleTheme() {
            var body = document.body;
            var currentTheme = body.getAttribute('data-theme');
            var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        }

        document.addEventListener('DOMContentLoaded', () => {
            var savedTheme = localStorage.getItem('theme') || 'light';
            document.body.setAttribute('data-theme', savedTheme);
        });
    </script>
</body>
</html>
