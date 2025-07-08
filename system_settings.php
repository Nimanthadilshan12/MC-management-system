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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - University Medical Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #7c3aed;
            --secondary: #ec4899;
            --accent: #06b6d4;
            --text: #1e293b;
            --background: #f1f5f9;
            --success: #10b981;
            --error: #ef4444;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #a5b4fc, rgb(198, 168, 249), #22d3ee);
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
            background-image: url('https://thumbs.dreamstime.com/b/settings-gears-icon-abstract-blue-background-illustration-dark-digital-texture-grunge-elegant-paint-modern-design-concept-167074493.jpg');
           background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
            opacity: 0.1;
            z-index: -1;
            animation: zoomInOut 20s ease-in-out infinite;
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
            max-width: 800px;
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
            background: radial-gradient(circle, rgba(124, 58, 237, 0.1), transparent 60%);
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: -1;
        }

        .card:hover::before {
            opacity: 0.3;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .card-header h2 {
            font-family: 'Rubik', sans-serif;
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(to right, var(--primary), var(--secondary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin: 0;
            animation: textPop 1.5s ease-in-out infinite alternate;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .message {
            text-align: center;
            color: var(--success);
            background: rgba(16, 185, 129, 0.1);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: fadeIn 0.5s ease;
        }

        .section-title {
            font-family: 'Rubik', sans-serif;
            font-size: 1.4rem;
            font-weight: 600;
            background: linear-gradient(to right, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
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
            box-shadow: 0 0 8px rgba(124, 58, 237, 0.2);
            outline: none;
        }

        .form-check-label {
            color: var(--text);
            margin-left: 10px;
        }

        .btn-primary {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 500;
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
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

        .btn-primary:hover {
            background: linear-gradient(90deg, #6d28d9, #db2777);
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(124, 58, 237, 0.5);
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:active {
            transform: scale(1);
            box-shadow: 0 0 10px rgba(124, 58, 237, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(90deg, var(--text), #4b5563);
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 500;
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
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
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(30, 41, 59, 0.5);
        }

        .btn-secondary:hover::before {
            left: 100%;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes gentleDrift {
            0% { background-position: 0 0; }
            100% { background-position: 250px 250px; }
        }

        @keyframes textPop {
            from { transform: scale(1); text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
            to { transform: scale(1.02); text-shadow: 0 3px 6px rgba(0, 0, 0, 0.15); }
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
        }

        @media (max-width: 480px) {
            .container {
                margin-top: 40px;
            }
            .card {
                padding: 20px;
                border-radius: 12px;
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
    </style>
</head>
<body>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>