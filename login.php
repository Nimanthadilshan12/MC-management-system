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
    $UserID = $_POST['UserID'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $Fullname = $_POST['Fullname'];
    $Email = $_POST['Email'];
    $Contact_No = $_POST['Contact_No'];
// Check if UserID already exists in any user table
$allTables = ['patients', 'doctors', 'admins', 'pharmacists'];
$duplicateFound = false;

foreach ($allTables as $table) {
    $checkStmt = $conn->prepare("SELECT UserID FROM $table WHERE UserID = ?");
    $checkStmt->bind_param("s", $UserID);
    $checkStmt->execute();
    $checkStmt->store_result();
    if ($checkStmt->num_rows > 0) {
        $duplicateFound = true;
        break;
    }
}

if ($duplicateFound) {
    $message = "User ID already exists. Please choose a different User ID.";
    $showForm = "register";
} else {
    // proceed to switch and insert
    switch ($role) {
        // your existing switch-case logic here...
    }

    if ($stmt->execute()) {
        $message = "Registration successful! Please sign in.";
        $showForm = "signin";
    } else {
        $message = "Error: " . $stmt->error;
        $showForm = "register";
    }
}

    switch ($role) {
        case 'Patient':
            $Age = $_POST['Age'];
            $Gender = $_POST['Gender'];
            $Birth = $_POST['Birth'];
            $Blood_Type = $_POST['Blood_Type'];
            $Academic_Year = $_POST['Academic_Year'];
            $Faculty = $_POST['Faculty'];
            $Citizenship = $_POST['Citizenship'];
            $Allergies = $_POST['Any_allergies'];
            $Emergency_Contact = $_POST['Emergency_Contact'];

            $stmt = $conn->prepare("INSERT INTO patients (UserID, Password, Fullname, Email, Contact_No, Age, Gender, Birth, Blood_Type, Academic_Year, Faculty, Citizenship, Any_allergies, Emergency_Contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssssssss", $UserID, $password, $Fullname, $Email, $Contact_No, $Age, $Gender, $Birth, $Blood_Type, $Academic_Year, $Faculty, $Citizenship, $Allergies, $Emergency_Contact);
            break;

        case 'Doctor':
            $Specialization = $_POST['Specialization'];
            $RegNo = $_POST['RegNo'];

            $stmt = $conn->prepare("INSERT INTO doctors (UserID, Password, Fullname, Email, Contact_No, Specialization, RegNo) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $UserID, $password, $Fullname, $Email, $Contact_No, $Specialization, $RegNo);
            break;

        case 'Admin':
            $stmt = $conn->prepare("INSERT INTO admins (UserID, Password, Fullname, Email, Contact_No) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $UserID, $password, $Fullname, $Email, $Contact_No);
            break;

        case 'Pharmacist':
            $License_No = $_POST['License_No'];
            $stmt = $conn->prepare("INSERT INTO pharmacists (UserID, Password, Fullname, Email, Contact_No, License_No) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $UserID, $password, $Fullname, $Email, $Contact_No, $License_No);
            break;

        default:
            $message = "Invalid role selected.";
            $showForm = "register";
            return;
    }

    if ($stmt->execute()) {
        $message = "Registration successful! Please sign in.";
        $showForm = "signin";
    } else {
        $message = "Error: " . $stmt->error;
        $showForm = "register";
    }
}

// LOGIN LOGIC
if (isset($_POST['signin'])) {
    $UserID = $_POST['username'];
    $password = $_POST['password'];

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Medical Centre - Login</title>
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
            background: linear-gradient(135deg, #e0e7ff, #b9d1ff, #e6f0ff); /* Vibrant pastel gradient */
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
            background-image: url('https://images.unsplash.com/photo-1505751172876-fa1923c5c528'); /* Medical-themed image */
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
            max-width: 500px;
            margin: 100px auto;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(240, 245, 255, 0.95));
            border-radius: 20px;
            box-shadow: 0 12px 50px rgba(0, 50, 120, 0.15);
            padding: 40px;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.7s ease-out;
        }

        .container:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0, 50, 120, 0.2);
        }

        h2 {
            font-size: 2.5rem;
            font-weight: 600;
            background: linear-gradient(to right, #007bff, #00c4b4);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-align: center;
            margin-bottom: 30px;
            animation: textGlow 2s ease-in-out infinite alternate;
        }

        h3 {
            font-size: 1.8rem;
            color: #1a3556;
            font-weight: 500;
            text-align: center;
            margin-bottom: 20px;
        }

        .message {
            text-align: center;
            color: #e63946;
            background: rgba(230, 57, 70, 0.1);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: fadeIn 0.5s ease;
        }

        .form-toggle {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            padding: 5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .form-toggle button {
            flex: 1;
            padding: 12px;
            background: transparent;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 500;
            color: #6c757d;
            cursor: pointer;
            transition: background 0.3s ease, color 0.3s ease, transform 0.2s ease;
        }

        .form-toggle button.active {
            background: linear-gradient(to right, #007bff, #00c4b4);
            color: white;
            transform: scale(1.02);
        }

        form {
            display: none;
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

        input, select, textarea {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1px solid #d1d9e6;
            border-radius: 8px;
            background: #f8fafc;
            font-size: 1rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        input:focus, select:focus, textarea:focus {
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
            width: 100%;
            padding: 14px;
            background: linear-gradient(to right, #007bff, #00c4b4);
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 500;
            color: white;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            background: linear-gradient(to right, #0056b3, #00a896);
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
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

        #patientFields, #doctorFields, #pharmacistFields {
            background: rgba(240, 245, 255, 0.5);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: fadeIn 0.5s ease;
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes textGlow {
            from { text-shadow: 0 0 5px rgba(0, 123, 255, 0.3); }
            to { text-shadow: 0 0 12px rgba(0, 123, 255, 0.5); }
        }

        @keyframes zoomInOut {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                margin: 80px 20px;
                padding: 30px;
            }
            h2 {
                font-size: 2.2rem;
            }
            .form-toggle button {
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                margin: 60px 15px;
                padding: 20px;
                border-radius: 16px;
            }
            h2 {
                font-size: 2rem;
            }
            h3 {
                font-size: 1.6rem;
            }
            .form-toggle {
                flex-direction: column;
                gap: 10px;
            }
            .form-toggle button {
                width: 100%;
            }
            input, select, textarea {
                padding: 10px 10px 10px 35px;
                font-size: 0.9rem;
            }
            .btn-primary {
                padding: 12px;
                font-size: 1rem;
            }
        }
    </style>
    <script>
        function showForm(form) {
            document.getElementById('registerForm').style.display = form === 'register' ? 'block' : 'none';
            document.getElementById('signinForm').style.display = form === 'signin' ? 'block' : 'none';

            document.getElementById('btnRegister').classList.remove('active');
            document.getElementById('btnSignin').classList.remove('active');
            if (form === 'register') {
                document.getElementById('btnRegister').classList.add('active');
            } else {
                document.getElementById('btnSignin').classList.add('active');
            }
        }

        function toggleRoleFields(role) {
            document.getElementById('patientFields').style.display = (role === 'Patient') ? 'block' : 'none';
            document.getElementById('doctorFields').style.display = (role === 'Doctor') ? 'block' : 'none';
            document.getElementById('pharmacistFields').style.display = (role === 'Pharmacist') ? 'block' : 'none';
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
        <h2>University Medical Centre</h2>
        <div class="form-toggle">
            <button type="button" id="btnRegister" onclick="showForm('register')">Register</button>
            <button type="button" id="btnSignin" class="active" onclick="showForm('signin')">Sign In</button>
        </div>
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form id="registerForm" method="post">
            <h3>Create Account</h3>
            <div class="form-group">
                <i class="fas fa-id-badge"></i>
                <input type="text" name="UserID" placeholder="User ID" required>
            </div>
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password (min 6 chars)" required>
            </div>
            <div class="form-group">
                <i class="fas fa-user-tag"></i>
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
                <input type="text" name="Fullname" placeholder="Full Name" required>
            </div>
            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="Email" placeholder="Email Address" required>
            </div>
            <div class="form-group">
                <i class="fas fa-phone"></i>
                <input type="text" name="Contact_No" placeholder="Contact Number" required>
            </div>
            <div id="patientFields" style="display:none;">
                <div class="form-group">
                    <i class="fas fa-child"></i>
                    <input type="number" name="Age" placeholder="Age" min="0">
                </div>
                <div class="form-group">
                    <i class="fas fa-venus-mars"></i>
                    <select name="Gender">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="birthDate">Birth Date</label>
                    <i class="fas fa-calendar-alt"></i>
                    <input type="date" name="Birth" placeholder="Birthdate">
                </div>
                <div class="form-group">
                    <i class="fas fa-tint"></i>
                    <select name="Blood_Type">
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
                    <input type="text" name="Academic_Year" placeholder="Academic Year">
                </div>
                <div class="form-group">
                    <i class="fas fa-university"></i>
                    <input type="text" name="Faculty" placeholder="Faculty">
                </div>
                <div class="form-group">
                    <i class="fas fa-globe"></i>
                    <input type="text" name="Citizenship" placeholder="Citizenship">
                </div>
                <div class="form-group">
                    <i class="fas fa-allergies"></i>
                    <textarea name="Any_allergies" placeholder="Any allergies (if any)"></textarea>
                </div>
                <div class="form-group">
                    <i class="fas fa-phone-alt"></i>
                    <input type="text" name="Emergency_Contact" placeholder="Emergency Contact Number">
                </div>
            </div>
            <div id="doctorFields" style="display:none;">
                <div class="form-group">
                    <i class="fas fa-stethoscope"></i>
                    <input type="text" name="Specialization" placeholder="Specialization">
                </div>
                <div class="form-group">
                    <i class="fas fa-id-card"></i>
                    <input type="text" name="RegNo" placeholder="Medical Registration Number">
                </div>
            </div>
            <div id="pharmacistFields" style="display:none;">
                <div class="form-group">
                    <i class="fas fa-id-card"></i>
                    <input type="text" name="License_No" placeholder="License Number">
                </div>
            </div>
            <button type="submit" name="register" class="btn-primary"><i class="fas fa-user-plus me-2"></i>Register</button>
        </form>
        <form id="signinForm" method="post">
            <h3>Sign In</h3>
            <div class="form-group">
                <i class="fas fa-id-badge"></i>
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" name="signin" class="btn-primary"><i class="fas fa-sign-in-alt me-2"></i>Sign In</button>
        </form>
    </div>
</body>
</html>