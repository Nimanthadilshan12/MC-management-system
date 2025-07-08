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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - University Medical Centre</title>
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
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #a5b4fc,rgb(198, 168, 249), #22d3ee);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://thumbs.dreamstime.com/b/secure-access-system-hospital-corridor-healthcare-facility-digital-visualization-modern-design-detailed-view-high-tech-367247749.jpg');
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
            opacity: 0.1;
            z-index: -1;
            animation: zoomInOut 20s ease-in-out infinite;
        }
        

        .container {
            max-width: 1300px;
            margin: 100px auto;
            padding: 0 24px;
            display: flex;
            gap: 28px;
            position: relative;
            z-index: 1;
        }

        .sidebar {
            flex: 0 0 360px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 36px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: bounceIn 0.8s ease-out;
        }

        .main-content {
            flex: 1;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 48px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: bounceIn 0.8s ease-out;
        }

        .sidebar:hover, .main-content:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            transition: all 0.4s ease;
        }

        .card-header h2 {
            font-family: 'Rubik', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(to right, var(--primary), var(--secondary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-align: center;
            margin-bottom: 28px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            animation: textPop 1.5s ease-in-out infinite alternate;
        }

        .avatar-container {
            position: relative;
            margin: 0 auto 24px;
            width: 140px;
            height: 140px;
        }

        .avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4.5rem;
            color: #fff;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: 5px solid transparent;
            box-shadow: 0 0 0 5px rgba(236, 72, 153, 0.3);
            transition: all 0.4s ease;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 0 0 5px rgba(236, 72, 153, 0.5), 0 0 20px rgba(124, 58, 237, 0.4);
        }

        .status-indicator {
            position: absolute;
            bottom: 8px;
            right: 8px;
            width: 20px;
            height: 20px;
            background: linear-gradient(45deg, var(--success), #34d399);
            border-radius: 50%;
            border: 3px solid #fff;
            animation: bouncePulse 1.8s ease-in-out infinite;
        }

        .welcome-title {
            font-family: 'Rubik', sans-serif;
            font-size: 2rem;
            font-weight: 600;
            color: var(--text);
            text-align: center;
            margin-bottom: 24px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .info-row {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            justify-content: center;
            margin-bottom: 24px;
        }

        .info-item {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            padding: 14px 24px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            flex: 1;
            min-width: 250px;
        }

        .info-item:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.03);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .icon {
            font-size: 1.4rem;
            color: var(--accent);
            margin-right: 12px;
            transition: transform 0.3s ease;
        }

        .info-item:hover .icon {
            transform: scale(1.2);
        }

        .label {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text);
        }

        .value {
            font-size: 1rem;
            color: var(--text);
            font-weight: 400;
        }

        .photo-upload-form, .edit-profile-form {
            margin-top: 24px;
            text-align: center;
        }

        .photo-upload-form input[type="file"] {
            display: none;
        }

        .btn {
            padding: 12px 28px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: none;
        }

        .btn-upload, .btn-edit {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: #fff;
        }

        .btn-upload:hover, .btn-edit:hover {
            background: linear-gradient(90deg, #6d28d9, #db2777);
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(124, 58, 237, 0.5);
        }

        .btn-remove {
            background: linear-gradient(90deg, var(--error), #b91c1c);
            color: #fff;
            margin-top: 12px;
        }

        .btn-remove:hover {
            background: linear-gradient(90deg, #dc2626, #991b1b);
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.5);
        }

        .btn-logout {
            background: linear-gradient(90deg, var(--error), #b91c1c);
            color: #fff;
            padding: 12px 32px;
            margin-top: 24px;
            display: inline-block;
        }

        .btn-logout:hover {
            background: linear-gradient(90deg, #dc2626, #991b1b);
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.5);
        }

        .btn-cancel {
            background: linear-gradient(90deg, #6b7280, #4b5563);
            color: #fff;
        }

        .btn-cancel:hover {
            background: linear-gradient(90deg, #4b5563, #374151);
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(75, 85, 99, 0.5);
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .message {
            text-align: center;
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 1rem;
            font-weight: 500;
            animation: popIn 0.5s ease;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
        }

        .message.success {
            color: var(--success);
            border: 2px solid rgba(16, 185, 129, 0.3);
        }

        .message:not(.success) {
            color: var(--error);
            border: 2px solid rgba(239, 68, 68, 0.3);
        }

        .admin-actions h5 {
            font-family: 'Rubik', sans-serif;
            font-size: 1.6rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 28px;
        }

        .list-group-item {
            border: none;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            margin-bottom: 14px;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .list-group-item:hover {
            transform: translateX(10px) scale(1.02);
            background: rgba(255, 255, 255, 0.3);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        .list-group-item a {
            color: var(--primary);
            font-weight: 500;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 16px;
            font-size: 1.1rem;
        }

        .list-group-item a i {
            margin-right: 12px;
            font-size: 1.4rem;
            transition: transform 0.3s ease;
        }

        .list-group-item a:hover i {
            transform: scale(1.2);
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            animation: zoomIn 0.4s ease;
        }

        .modal-header {
            border-bottom: none;
            padding: 28px 36px 0;
        }

        .modal-title {
            font-family: 'Rubik', sans-serif;
            font-size: 2rem;
            font-weight: 600;
            color: var(--text);
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .modal-body {
            padding: 28px 36px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 8px;
            display: block;
        }

        .form-group input {
            width: 100%;
            padding: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            font-size: 1rem;
            color: var(--text);
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(124, 58, 237, 0.4);
            background: rgba(255, 255, 255, 0.2);
            outline: none;
        }

        .form-group input:hover {
            border-color: var(--secondary);
        }

        .modal-footer {
            border-top: none;
            padding: 0 36px 28px;
            display: flex;
            gap: 16px;
            justify-content: flex-end;
        }

        /* Animations */
        @keyframes colorShift {
            0% { background: linear-gradient(45deg, rgba(124, 58, 237, 0.1), rgba(236, 72, 153, 0.1), rgba(6, 182, 212, 0.1)); }
            50% { background: linear-gradient(45deg, rgba(6, 182, 212, 0.1), rgba(124, 58, 237, 0.1), rgba(236, 72, 153, 0.1)); }
            100% { background: linear-gradient(45deg, rgba(124, 58, 237, 0.1), rgba(236, 72, 153, 0.1), rgba(6, 182, 212, 0.1)); }
        }

        @keyframes bounceIn {
            0% { opacity: 0; transform: scale(0.8); }
            60% { opacity: 1; transform: scale(1.05); }
            100% { opacity: 1; transform: scale(1); }
        }

        @keyframes textPop {
            from { transform: scale(1); text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
            to { transform: scale(1.02); text-shadow: 0 3px 6px rgba(0, 0, 0, 0.15); }
        }

        @keyframes bouncePulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.5); }
            50% { transform: scale(1.2); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        @keyframes popIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.7); }
            to { opacity: 1; transform: scale(1); }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                margin-top: 80px;
                padding: 0 16px;
                flex-direction: column;
            }
            .sidebar {
                flex: 0 0 100%;
                max-width: 100%;
                padding: 28px;
            }
            .main-content {
                padding: 36px;
                border-radius: 16px;
            }
            .card-header h2 {
                font-size: 2.2rem;
            }
            .info-item {
                min-width: 100%;
            }
            .avatar-container {
                width: 120px;
                height: 120px;
            }
            .welcome-title {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                margin-top: 60px;
                padding: 0 12px;
            }
            .sidebar, .main-content {
                padding: 24px;
                border-radius: 12px;
            }
            .card-header h2 {
                font-size: 1.8rem;
            }
            .avatar-container {
                width: 100px;
                height: 100px;
            }
            .avatar {
                font-size: 3.5rem;
            }
            .btn, .btn-logout, .btn-cancel {
                padding: 10px 24px;
                font-size: 0.95rem;
            }
            .btn:hover {
                transform: scale(1.1);
            }
            .modal-title {
                font-size: 1.6rem;
            }
            .form-group input {
                padding: 12px;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <?php if ($message): ?>
                <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : ''; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <div class="avatar-container">
                <div class="avatar">
                    <?php if ($photoUrl): ?>
                        <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Profile Photo" onerror="console.error('Failed to load image: <?php echo htmlspecialchars($photoUrl); ?>'); this.src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='; alert('Failed to load profile photo. Check error.log for details.');">
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                    <?php endif; ?>
                </div>
                <div class="status-indicator"></div>
            </div>
            <h4 class="welcome-title">Welcome, <?php echo htmlspecialchars($user['Fullname']); ?>!</h4>
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
            </div>
            <form class="photo-upload-form" method="post" enctype="multipart/form-data" id="photoUploadForm">
                <label for="profile_photo" class="btn btn-upload"><i class="fas fa-upload me-2"></i><?php echo $photoUrl ? 'Update Photo' : 'Upload Photo'; ?></label>
                <input type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png">
                <button type="submit" name="upload_photo" class="btn btn-upload" style="display: none;"></button>
                <?php if ($photoUrl): ?>
                    <button type="submit" name="remove_photo" class="btn btn-remove"><i class="fas fa-trash-alt me-2"></i>Remove Photo</button>
                <?php endif; ?>
            </form>
            <div class="text-center" style="margin-top: 24px;">
                <button class="btn btn-edit" data-bs-toggle="modal" data-bs-target="#editProfileModal"><i class="fas fa-edit me-2"></i>Edit Profile</button>
            </div>
        </div>
        <div class="main-content">
            <div class="card-header">
                <h2>Admin Portal</h2>
            </div>
            <div class="admin-actions">
                <h5>Actions</h5>
                <ul class="list-group">
                    <li class="list-group-item">
                        <a href="manage_users.php"><i class="fas fa-users"></i>Manage Users</a>
                    </li>
                    <li class="list-group-item">
                        <a href="medicine_inventory.php"><i class="fas fa-prescription-bottle-alt"></i>Medicine Inventory</a>
                    </li>
                    <li class="list-group-item">
                        <a href="system_settings.php"><i class="fas fa-cog"></i>Configure Settings</a>
                    </li>
                    <li class="list-group-item">
                        <a href="analysis.php"><i class="fas fa-chart-bar"></i>View Data Analysis</a>
                    </li>
                    <li class="list-group-item">
                        <a href="admin_feedback.php"><i class="fas fa-comment-dots"></i>View Feedback</a>
                    </li>
                </ul>
            </div>
            <a href="logout.php" class="btn btn-logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" class="edit-profile-form">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="Fullname">Full Name</label>
                            <input type="text" name="Fullname" id="Fullname" value="<?php echo htmlspecialchars($user['Fullname']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="Email">Email Address</label>
                            <input type="email" name="Email" id="Email" value="<?php echo htmlspecialchars($user['Email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="Contact_No">Contact Number</label>
                            <input type="text" name="Contact_No" id="Contact_No" value="<?php echo htmlspecialchars($user['Contact_No']); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-cancel">Cancel</button>
                        <button type="submit" name="update_profile" class="btn btn-upload"><i class="fas fa-save me-2"></i>Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Trigger form submission when file is selected
        document.getElementById('profile_photo')?.addEventListener('change', function() {
            this.nextElementSibling.click();
        });
    </script>
</body>
</html>
