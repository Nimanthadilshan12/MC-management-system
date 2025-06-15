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
    <title>University Medical Centre - Login</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f0f4f8, #d9e2ec);
        }
        .container {
            max-width: 420px;
            margin: 60px auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px 40px;
        }
        h2, h3 { text-align: center; color: #333; }
        .message {
            text-align: center;
            color: #d9534f;
            margin-bottom: 15px;
        }
        input, select, button, textarea {
            width: 100%;
            padding: 12px;
            margin: 8px 0 16px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        button {
            background-color: #0069d9;
            color: white;
            border: none;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s ease;
        }
        button:hover {
            background-color: #0053ba;
        }
        .form-toggle {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .form-toggle button {
            width: 48%;
            background-color: #6c757d;
        }
        .form-toggle button.active {
            background-color: #007bff;
        }
        form { display: none; }
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
            <button type="button" id="btnSignin" onclick="showForm('signin')">Sign In</button>
        </div>
        <div class="message"><?php echo $message; ?></div>
        <form id="registerForm" method="post">
            <h3>Create Account</h3>
            <input type="text" name="UserID" placeholder="Enter UserID" required>
            <input type="password" name="password" placeholder="Enter Password (min 6 chars)" required>
            <select name="role" id="roleSelect" required onchange="toggleRoleFields(this.value)">
                <option value="">Select Role</option>
                <option value="Patient">Patient</option>
                <option value="Doctor">Doctor</option>
                <option value="Admin">Admin</option>
                <option value="Pharmacist">Pharmacist</option>
            </select>
            <input type="text" name="Fullname" placeholder="Full Name" required>
            <input type="email" name="Email" placeholder="Email Address" required>
            <input type="text" name="Contact_No" placeholder="Contact Number" required>
            <div id="patientFields" style="display:none;">
                <input type="number" name="Age" placeholder="Age" min="0">
                <select name="Gender">
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
                <label>Birthdate:</label>
                <input type="date" name="Birth">
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
                <input type="text" name="Academic_Year" placeholder="Academic Year">
                <input type="text" name="Faculty" placeholder="Faculty">
                <input type="text" name="Citizenship" placeholder="Citizenship">
                <textarea name="Any_allergies" placeholder="Any allergies (if any)"></textarea>
                <input type="text" name="Emergency_Contact" placeholder="Emergency Contact Number">
            </div>
            <div id="doctorFields" style="display:none;">
                <input type="text" name="Specialization" placeholder="Specialization">
                <input type="text" name="RegNo" placeholder="Medical Registration Number">
            </div>
            <div id="pharmacistFields" style="display:none;">
                <input type="text" name="License_No" placeholder="License Number">
            </div>
            <button type="submit" name="register">Register</button>
        </form>
        <form id="signinForm" method="post">
            <h3>Login</h3>
            <input type="text" name="username" placeholder="Enter Username" required>
            <input type="password" name="password" placeholder="Enter Password" required>
            <button type="submit" name="signin">Sign In</button>
        </form>
    </div>
</body>
</html>
