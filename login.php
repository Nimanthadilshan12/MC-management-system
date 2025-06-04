<?php
$host = "localhost";
$db = "mc";
$user = "root";
$pass = ""; // set your DB password

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";
$showForm = "signin";

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $role);

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
            // Redirect based on role
            header("Location: dashboard.php?role=" . $user['role']);
            exit();
        } else {
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
            <input type="text" name="username" placeholder="Enter Username" required>
            <input type="password" name="password" placeholder="Enter Password (min 6 chars)" required>
            <select name="role" required>
                <option value="">Select Role</option>
                <option value="Patient">Patient</option>
                <option value="Doctor">Doctor</option>
                <option value="Admin">Admin</option>
                <option value="Pharmacist">Pharmacist</option>
            </select>
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

