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

    // Basic validation
    if (strlen($UserID) < 3 || strlen($UserID) > 50) {
        $message = "User ID must be between 3 and 50 characters.";
        $showForm = "register";
    } elseif (strlen($passwordRaw) < 6) {
        $message = "Password must be at least 6 characters long.";
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
    } elseif (!preg_match("/^[0-9]{7,15}$/", $Contact_No)) {
        $message = "Contact number must be digits only (7-15 digits).";
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
                } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $Birth)) {
                    $message = "Invalid birthdate format.";
                    $showForm = "register";
                } elseif (!empty($Blood_Type) && !in_array($Blood_Type, ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])) {
                    $message = "Invalid blood type.";
                    $showForm = "register";
                } elseif (!empty($Emergency_Contact) && !preg_match("/^[0-9]{7,15}$/", $Emergency_Contact)) {
                    $message = "Emergency contact must be digits only (7-15 digits).";
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
                    $_SESSION['role'] = $roleName;
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Medical Centre - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #7c3aed; /* Purple */
            --secondary: #ec4899; /* Pink */
            --accent: #06b6d4; /* Cyan */
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 60px 20px;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://images.unsplash.com/photo-1505751172876-fa1923c5c528');
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
            background: radial-gradient(circle at center, rgba(255, 255, 255, 0.4), transparent 70%);
            z-index: -1;
        }

        .container {
            max-width: 600px;
            width: 95%;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
            animation: bounceIn 0.8s ease-out;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            z-index: 1;
        }

        .container:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            transition: all 0.4s ease;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        h2 {
            font-family: 'Rubik', sans-serif;
            font-size: 2.7rem;
            font-weight: 700;
            background: linear-gradient(to right, var(--primary), var(--secondary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: textPop 1.5s ease-in-out infinite alternate;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-toggle {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            border-radius: 12px;
            padding: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .form-toggle button {
            flex: 1;
            padding: 14px;
            background: transparent;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-toggle button.active {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            transform: scale(1.03);
        }

        .form-toggle button:hover:not(.active) {
            background: rgba(124, 58, 237, 0.2);
            color: var(--primary);
        }

        h3 {
            font-family: 'Rubik', sans-serif;
            font-size: 1.8rem;
            font-weight: 600;
            background: linear-gradient(to right, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-align: center;
            margin-bottom: 25px;
        }

        .message {
            text-align: center;
            color: var(--error);
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
            font-size: 0.95rem;
            animation: popIn 0.5s ease;
            border: 2px solid rgba(239, 68, 68, 0.3);
        }

        .message.success {
            color: var(--success);
            border: 2px solid rgba(16, 185, 129, 0.3);
        }

        form {
            display: none;
        }

        form.active {
            display: block;
            animation: popIn 0.5s ease;
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
            color: var(--accent);
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        .form-group:hover i {
            transform: translateY(-50%) scale(1.2);
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 12px 12px 45px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            color: var(--text);
            transition: all 0.3s ease;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(124, 58, 237, 0.4);
            background: rgba(255, 255, 255, 0.2);
            outline: none;
        }

        input:hover, select:hover, textarea:hover {
            border-color: var(--secondary);
        }

        label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 6px;
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
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
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #6d28d9, #db2777);
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(124, 58, 237, 0.5);
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:active {
            transform: scale(1);
            box-shadow: 0 0 10px rgba(124, 58, 237, 0.3);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            padding: 12px 20px;
            background: linear-gradient(90deg, #6b7280, #4b5563);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-back::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn-back:hover {
            background: linear-gradient(90deg, #4b5563, #374151);
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(75, 85, 99, 0.5);
        }

        .btn-back:hover::before {
            left: 100%;
        }

        #patientFields, #doctorFields, #pharmacistFields {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: popIn 0.5s ease;
        }

        /* Animations */
        @keyframes zoomInOut {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
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

        @keyframes popIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            body {
                padding: 80px 15px;
            }
            .container {
                margin: 0 15px;
                padding: 30px;
                border-radius: 16px;
            }
            h2 {
                font-size: 2.4rem;
            }
            h3 {
                font-size: 1.6rem;
            }
            .form-toggle button {
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 60px 10px;
            }
            .container {
                margin: 0 10px;
                padding: 24px;
                border-radius: 12px;
            }
            h2 {
                font-size: 2.1rem;
            }
            h3 {
                font-size: 1.4rem;
            }
            .form-toggle {
                flex-direction: column;
                gap: 8px;
            }
            .form-toggle button {
                padding: 12px;
                font-size: 0.95rem;
            }
            input, select, textarea {
                padding: 10px 10px 10px 40px;
                font-size: 0.95rem;
            }
            .btn-primary, .btn-back {
                padding: 12px;
                font-size: 1rem;
            }
            .btn-primary:hover, .btn-back:hover {
                transform: scale(1.1);
            }
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
        };
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>University Medical Centre</h2>
        </div>
        <div class="form-toggle">
            <button type="button" id="btnRegister" onclick="showForm('register')">Register</button>
            <button type="button" id="btnSignin" onclick="showForm('signin')">Sign In</button>
        </div>
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'successful') !== false ? 'success' : ''; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <form id="registerForm" method="post">
            <h3>Create Account</h3>
            <div class="form-group">
                <i class="fas fa-id-badge"></i>
                <label for="UserID">User ID</label>
                <input type="text" name="UserID" id="UserID" placeholder="Enter User ID" required>
            </div>
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Minimum 6 characters" required>
            </div>
            <div class="form-group">
                <i class="fas fa-user-tag"></i>
                <label for="roleSelect">Role</label>
                <select name="role" id="roleSelect" required onchange="toggleRoleFields(this.value)">
                    <option value="">Select Role</option>
                    <option value="Patient">Patient</option>
                    <option value="Doctor">Doctor</option>
                    <option value="Admin">Admin</option>
                    <option value="Pharmacist">Pharmacist</option>
                </select>
            </div>
            <div class="form-group">
                <i class="fas fa-user"></i>
                <label for="Fullname">Full Name</label>
                <input type="text" name="Fullname" id="Fullname" placeholder="Enter Full Name" required>
            </div>
            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <label for="Email">Email Address</label>
                <input type="email" name="Email" id="Email" placeholder="Enter Email" required>
            </div>
            <div class="form-group">
                <i class="fas fa-phone"></i>
                <label for="Contact_No">Contact Number</label>
                <input type="text" name="Contact_No" id="Contact_No" placeholder="Enter Contact Number" required>
            </div>
            <div id="patientFields" style="display:none;">
                <div class="form-group">
                    <i class="fas fa-child"></i>
                    <label for="Age">Age</label>
                    <input type="number" name="Age" id="Age" placeholder="Enter Age" min="0">
                </div>
                <div class="form-group">
                    <i class="fas fa-venus-mars"></i>
                    <label for="Gender">Gender</label>
                    <select name="Gender" id="Gender">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <i class="fas fa-calendar-alt"></i>
                    <label for="Birth">Birth Date</label>
                    <input type="date" name="Birth" id="Birth" placeholder="Select Birthdate">
                </div>
                <div class="form-group">
                    <i class="fas fa-tint"></i>
                    <label for="Blood_Type">Blood Type</label>
                    <select name="Blood_Type" id="Blood_Type">
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
                </div>
                <div class="form-group">
                    <i class="fas fa-graduation-cap"></i>
                    <label for="Academic_Year">Academic Year</label>
                    <input type="text" name="Academic_Year" id="Academic_Year" placeholder="Enter Academic Year">
                </div>
                <div class="form-group">
                    <i class="fas fa-university"></i>
                    <label for="Faculty">Faculty</label>
                    <input type="text" name="Faculty" id="Faculty" placeholder="Enter Faculty">
                </div>
                <div class="form-group">
                    <i class="fas fa-globe"></i>
                    <label for="Citizenship">Citizenship</label>
                    <input type="text" name="Citizenship" id="Citizenship" placeholder="Enter Citizenship">
                </div>
                <div class="form-group">
                    <i class="fas fa-allergies"></i>
                    <label for="Any_allergies">Allergies</label>
                    <textarea name="Any_allergies" id="Any_allergies" placeholder="Enter any allergies (if any)"></textarea>
                </div>
                <div class="form-group">
                    <i class="fas fa-phone-alt"></i>
                    <label for="Emergency_Contact">Emergency Contact</label>
                    <input type="text" name="Emergency_Contact" id="Emergency_Contact" placeholder="Enter Emergency Contact">
                </div>
            </div>
            <div id="doctorFields" style="display:none;">
                <div class="form-group">
                    <i class="fas fa-stethoscope"></i>
                    <label for="Specialization">Specialization</label>
                    <input type="text" name="Specialization" id="Specialization" placeholder="Enter Specialization">
                </div>
                <div class="form-group">
                    <i class="fas fa-id-card"></i>
                    <label for="RegNo">Medical Registration Number</label>
                    <input type="text" name="RegNo" id="RegNo" placeholder="Enter Registration Number">
                </div>
            </div>
            <div id="pharmacistFields" style="display:none;">
                <div class="form-group">
                    <i class="fas fa-id-card"></i>
                    <label for="License_No">License Number</label>
                    <input type="text" name="License_No" id="License_No" placeholder="Enter License Number">
                </div>
            </div>
            <button type="submit" name="register" class="btn-primary"><i class="fas fa-user-plus me-2"></i>Register</button>
        </form>
        <form id="signinForm" method="post" class="active">
            <h3>Sign In</h3>
            <div class="form-group">
                <i class="fas fa-id-badge"></i>
                <label for="username">Username</label>
                <input type="text" name="username" id="username" placeholder="Enter Username" required>
            </div>
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <label for="password_signin">Password</label>
                <input type="password" name="password" id="password_signin" placeholder="Enter Password" required>
            </div>
            <button type="submit" name="signin" class="btn-primary"><i class="fas fa-sign-in-alt me-2"></i>Sign In</button>
        </form>
        <a href="index.php" class="btn-back mt-4"><i class="fas fa-arrow-left me-2"></i>Back to Home</a>
    </div>
</body>
</html>
