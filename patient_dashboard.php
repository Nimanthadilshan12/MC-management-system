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
    $stmt = $conn->prepare("SELECT Fullname, Email, Contact_No, Birth FROM patients WHERE UserID = ?");
} else { // Doctor
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
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - University Medical Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #e0e7ff, #b9d1ff, #e6f0ff);
            position: relative;
            min-height: 100vh;
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

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at top center, rgba(255, 255, 255, 0.35), transparent 60%);
            z-index: -1;
        }

        .container {
            margin-top: 80px;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }

        .logo {
            display: block;
            max-width: 150px;
            margin: 0 auto 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 50, 120, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 1s ease;
        }

        .logo:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(0, 50, 120, 0.15);
        }

        .card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(240, 245, 255, 0.95));
            border-radius: 20px;
            box-shadow: 0 12px 50px rgba(0, 50, 120, 0.15), 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 40px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.7s ease-out;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0, 50, 120, 0.2), 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0, 123, 255, 0.1), transparent 60%);
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: -1;
        }

        .card:hover::before {
            opacity: 0.3;
        }

        .card-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .card-header h2 {
            font-size: 2.5rem;
            font-weight: 600;
            background: linear-gradient(to right, #007bff, #00c4b4);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: textGlow 2s ease-in-out infinite alternate;
        }

        .welcome-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(235, 245, 255, 0.95));
            border-radius: 16px;
            padding: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 50, 120, 0.1);
            margin-bottom: 30px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.7s ease-out;
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        .welcome-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 40px rgba(0, 50, 120, 0.15);
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
            background: linear-gradient(45deg, #007bff, #00c4b4, #007bff);
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
            background: linear-gradient(135deg, #007bff, #00c4b4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
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
            border: 2px solid white;
            animation: pulse 2s ease-in-out infinite;
        }

        .welcome-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #1a3556;
            text-align: center;
            margin-bottom: 20px;
            background: linear-gradient(to right, #007bff, #00c4b4);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: textGlow 2s ease-in-out infinite alternate;
        }

        .info-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
        }

        .info-item {
            display: flex;
            align-items: center;
            background: rgba(240, 245, 255, 0.8);
            padding: 12px 20px;
            border-radius: 8px;
            transition: transform 0.2s ease, background 0.2s ease;
            flex: 1;
            min-width: 200px;
        }

        .info-item:hover {
            transform: translateY(-3px);
            background: rgba(255, 255, 255, 1);
        }

        .icon {
            font-size: 1.2rem;
            color: #007bff;
            margin-right: 10px;
        }

        .label {
            font-size: 0.9rem;
            font-weight: 500;
            color: #4a5568;
            margin-right: 5px;
        }

        .value {
            font-size: 0.9rem;
            color: #1a3556;
            font-weight: 400;
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn-action {
            display: inline-block;
            padding: 14px 30px;
            background: linear-gradient(to right, #007bff, #00c4b4);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 500;
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .btn-action:hover {
            background: linear-gradient(to right, #0056b3, #00a896);
            transform: translateY(-4px);
            box-shadow: 0 6px 16px rgba(0, 123, 255, 0.3);
        }

        .btn-action::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.4s ease;
        }

        .btn-action:hover::before {
            left: 100%;
        }

        .btn-danger {
            background: linear-gradient(to right, #dc3545, #c82333);
            border: none;
            border-radius: 8px;
            padding: 14px 30px;
            font-weight: 500;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
        }

        .btn-danger:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(220, 53, 69, 0.3);
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes textGlow {
            from { text-shadow: 0 0 5px rgba(0, 123, 255, 0.3); }
            to { text-shadow: 0 0 10px rgba(0, 123, 255, 0.5); }
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

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                margin-top: 60px;
                padding: 0 15px;
            }
            .logo {
                max-width: 120px;
            }
            .card {
                padding: 30px;
                border-radius: 16px;
            }
            .card-header h2 {
                font-size: 2rem;
            }
            .welcome-card {
                padding: 25px;
            }
            .welcome-title {
                font-size: 1.6rem;
            }
            .avatar-container {
                width: 70px;
                height: 70px;
            }
            .avatar {
                font-size: 2.5rem;
            }
            .info-item {
                min-width: 100%;
            }
            .btn-action {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .container {
                margin-top: 40px;
            }
            .logo {
                max-width: 100px;
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
            .btn-action, .btn-danger {
                padding: 12px 20px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Patient Dashboard</h2>
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
                    <div class="info-item">
                        <i class="fas fa-envelope icon"></i>
                        <span class="label">Email:</span>
                        <span class="value"><?php echo htmlspecialchars($user['Email']); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-phone icon"></i>
                        <span class="label">Contact:</span>
                        <span class="value"><?php echo htmlspecialchars($user['Contact_No']); ?></span>
                    </div>
                    <?php if ($role === 'Patient') { ?>
                        <div class="info-item">
                            <i class="fas fa-birthday-cake icon"></i>
                            <span class="label">Date of Birth:</span>
                            <span class="value"><?php echo htmlspecialchars($user['Birth']); ?></span>
                        </div>
                    <?php } else { ?>
                        <div class="info-item">
                            <i class="fas fa-stethoscope icon"></i>
                            <span class="label">Specialization:</span>
                            <span class="value"><?php echo htmlspecialchars($user['Specialization']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-id-badge icon"></i>
                            <span class="label">Reg No:</span>
                            <span class="value"><?php echo htmlspecialchars($user['RegNo']); ?></span>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="action-buttons">
                <a href="patient_history.php?edit=<?php echo $role === 'Doctor' ? 'true' : 'false'; ?>" class="btn-action"><i class="fas fa-history me-2"></i>Patient History</a>
                <?php if ($role === 'Patient') { ?>
                    <a href="#" class="btn-action"><i class="fas fa-calendar-check me-2"></i>View Prescription</a>
                    <a href="#" class="btn-action"><i class="fas fa-calendar-check me-2"></i>Edit Personal Details</a>
                <?php } ?>
            </div>
            <a href="logout.php" class="btn btn-danger mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>