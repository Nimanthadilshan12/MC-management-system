<?php
$role = $_GET['role'] ?? 'Unknown';
echo "<h1>Welcome!</h1>";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>University Medical Centre</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f5f9ff;
            text-align: center;
            padding: 60px;
        }

        h1 {
            font-size: 3em;
            color: #0056b3;
        }

        p {
            font-size: 1.2em;
            color: #333;
        }

        a.button {
            display: inline-block;
            padding: 15px 30px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 1.1em;
        }

        a.button:hover {
            background-color: #0056b3;
        }
            
    </style>
</head>
<body>
    <h1>University of Ruhuna Medical Centre</h1>
    <p>Your health is our priority. Please sign in or register to manage your account.</p>
    <a class="button" href="login.php">Go to Login/Register</a>

</body>
</html>

    