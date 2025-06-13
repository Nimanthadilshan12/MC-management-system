<?php
$host = "localhost";
$db = "mc";
$user = "root";
$pass = ""; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";
$showForm = "signin";

if (isset($_POST['register'])) {
    $username = $_POST['UserID'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $name = $_POST['Fullname'];
    $email = $_POST['Email'];
    $age = $_POST['Age'];
    $gender = $_POST['Gender'];
    $bday = $_POST['Birth'];
    $blood = $_POST['Blood_Type'];
    $year = $_POST['Academic_Year'];
    $faculty = $_POST['Faculty'];
    $citizen = $_POST['Citizenship'];
    $contact = $_POST['Contact_No:'];
    $allergy = $_POST['Any_allergies'];
    $econtact = $_POST['Emergency_Contact'];

    // Prepare SQL with all fields
    $stmt = $conn->prepare("
        INSERT INTO users (
            username, password, role, name, email, age, gender, bday,
            blood, year, faculty, citizen, contact,
            allergy, econtact
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // Bind all variables
    $stmt->bind_param(
        "ssssssissssssss",  // Data types: s=string, i=integer
        $username,
        $password,
        $role,
        $name,
        $email,
        $age,
        $gender,
        $bday,
        $blood,
        $year,
        $faculty,
        $citizen,
        $contact,
        $allergy,
        $econtact
    );

    if ($stmt->execute()) {
        $message = "Registration successful. Please sign in.";
        $showForm = "signin";
    } else {
        $message = "Registration failed. Username might already exist.";
        $showForm = "register";
    }

    $stmt->close();
}


if (isset($_POST['signin'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
    session_start();
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    switch ($user['role']) {
        case 'Doctor':
            header("Location: doctor_dashboard.php");
            break;
        case 'Patient':
            header("Location: patient_dashboard.php");
            break;
        case 'Pharmacist':
            header("Location: pharmacist_dashboard.php");
            break;
        case 'Admin':
            header("Location: admin_dashboard.php");
            break;
        default:
            header("Location: dashboard.php");
    }
    exit();
}
 else {
            $message = "Invalid login.";
        }
    } else {
        $message = "Invalid login.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>University Medical Centre - Login</title>
    <style>
        * {
            box-sizing: border-box;
        }

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

        h2, h3 {
            text-align: center;
            color: #333;
        }

        .message {
            text-align: center;
            color: #d9534f;
            margin-bottom: 15px;
        }

        input, select, button {
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

        form {
            display: none;
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
    </script>
</head>
<body onload="showForm('<?php echo $showForm; ?>')">
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

    <select name="role" required>
        <option value="">Select Role</option>
        <option value="Patient">Patient</option>
        <option value="Doctor">Doctor</option>
        <option value="Admin">Admin</option>
        <option value="Pharmacist">Pharmacist</option>
    </select>

    <input type="text" name="Fullname" placeholder="Full Name" required>
    <input type="email" name="Email" placeholder="Email Address" required>
    <input type="number" name="Age" placeholder="Age" min="0" required>

    <select name="Gender" required>
        <option value="">Select Gender</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
    </select>

    <label>Birthdate:</label>
    <input type="date" name="Birth" required>

    <select name="Blood_Type" required>
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

    <input type="text" name="Academic_Year" placeholder="Academic Year (e.g. 2nd Year)" >
    <input type="text" name="Faculty" placeholder="Faculty (e.g. Medicine)" required>
    <input type="text" name="Citizenship" placeholder="Citizenship" required>
    <input type="text" name="Contact_No:" placeholder="Contact Number" required>
    <textarea name="Any_allergies" placeholder="Any allergies (if any)"></textarea>
    <input type="text" name="Emergency_Contact" placeholder="Emergency Contact Number" required>

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

