<?php
session_start();

// Check if user is logged in and is a pharmacist
if (!isset($_SESSION['UserID']) || $_SESSION['role'] !== 'Pharmacist') {
    header("Location: ../index.php");
    exit;
}

$host = "localhost";
$db = "mc1";
$user = "root";
$pass = "";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$pharmacist_id = $_SESSION['UserID'];
$message = "";

// Fetch pharmacist details
$stmt = $conn->prepare("SELECT Fullname, Email, Contact_No, License_No FROM pharmacists WHERE UserID = ?");
$stmt->bind_param("s", $pharmacist_id);
$stmt->execute();
$pharmacist = $stmt->get_result()->fetch_assoc();
if (!$pharmacist) {
    die("No user found with UserID: " . htmlspecialchars($pharmacist_id));
}

// Update profile logic
if (isset($_POST['update_profile'])) {
    $Fullname = trim($_POST['Fullname']);
    $Email = trim($_POST['Email']);
    $Contact_No = trim($_POST['Contact_No']);
    $License_No = trim($_POST['License_No']);

    // Basic validation
    if (empty($Fullname) || empty($Email) || empty($Contact_No) || empty($License_No)) {
        $message = "All fields are required.";
    } elseif (!filter_var($Email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("UPDATE pharmacists SET Fullname = ?, Email = ?, Contact_No = ?, License_No = ? WHERE UserID = ?");
        $stmt->bind_param("sssss", $Fullname, $Email, $Contact_No, $License_No, $pharmacist_id);
        
        if ($stmt->execute()) {
            $message = "Profile updated successfully!";
            $pharmacist['Fullname'] = $Fullname;
            $pharmacist['Email'] = $Email;
            $pharmacist['Contact_No'] = $Contact_No;
            $pharmacist['License_No'] = $License_No;
        } else {
            $message = "Error updating profile: " . $stmt->error;
        }
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pharmacist Details - University Medical Centre</title>
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

        .message {
            text-align: center;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: fadeIn 0.5s ease;
        }

        .message.success {
            color: #28a745;
            background: rgba(40, 167, 69, 0.1);
        }

        .message.error {
            color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
        }

        .form-group {
            position: relative;
            margin-bottom: 20px;
        }

        .form-group i {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #007bff;
        }

        input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1px solid #d1d9e6;
            border-radius: 8px;
            background: #f8fafc;
            font-size: 1rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input:focus {
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.2);
            outline: none;
        }

        label {
            display: block;
            font-size: 0.9rem;
            color: #1a3556;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .btn-primary {
            display: inline-block;
            padding: 14px 30px;
            background: linear-gradient(to right, #007bff, #00c4b4);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 500;
            border: none;
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            background: linear-gradient(to right, #0056b3, #00a896);
            transform: translateY(-4px);
            box-shadow: 0 6px 16px rgba(0, 123, 255, 0.3);
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.4s ease;
        }

        .btn-primary:hover::before {
            left: 100%;
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

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
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

        /* Responsive adjustments */
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
            .btn-primary, .btn-action {
                width: 100%;
                text-align: center;
                padding: 12px 20px;
                font-size: 1rem;
                margin: 10px 0 0;
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
            .card-header h2 {
                font-size: 1.8rem;
            }
            .btn-primary, .btn-action {
                padding: 10px 15px;
                font-size: 0.9rem;
                margin: 10px 0 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Edit Pharmacist Details</h2>
            </div>
            <?php if ($message): ?>
                <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label for="Fullname">Full Name</label>
                    <i class="fas fa-user"></i>
                    <input type="text" id="Fullname" name="Fullname" value="<?php echo htmlspecialchars($pharmacist['Fullname']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="Email">Email</label>
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="Email" name="Email" value="<?php echo htmlspecialchars($pharmacist['Email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="Contact_No">Contact Number</label>
                    <i class="fas fa-phone"></i>
                    <input type="text" id="Contact_No" name="Contact_No" value="<?php echo htmlspecialchars($pharmacist['Contact_No']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="License_No">License Number</label>
                    <i class="fas fa-id-card"></i>
                    <input type="text" id="License_No" name="License_No" value="<?php echo htmlspecialchars($pharmacist['License_No']); ?>" required>
                </div>
                <div class="action-buttons">
                    <button type="submit" name="update_profile" class="btn-primary"><i class="fas fa-save me-2"></i>Update Profile</button>
                    <a href="pharmacist_dashboard.php" class="btn-action"><i class="fas fa-home me-2"></i>Back to Dashboard</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>