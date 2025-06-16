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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - University Medical Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f0f4f8, #d9e2ec); }
        .container { margin-top: 50px; }
        .card { box-shadow: 0 0 20px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="card p-4">
            <h2 class="text-center mb-4">Admin Dashboard</h2>
            <div class="alert alert-info">
                <h4>Welcome, <?php echo htmlspecialchars($user['Fullname']); ?>!</h4>
                <p>Email: <?php echo htmlspecialchars($user['Email']); ?></p>
                <p>Contact: <?php echo htmlspecialchars($user['Contact_No']); ?></p>
            </div>
            <div class="mt-4">
                <h5>Admin Actions</h5>
                <ul class="list-group">
                    <li class="list-group-item"><a href="manage_users.php" class="text-decoration-none">Manage Users</a></li>
                    <li class="list-group-item"><a href="medicine_inventory.php" class="text-decoration-none">Medicine Inventory</a></li>
                    <li class="list-group-item"><a href="#" class="text-decoration-none">Configure Settings</a></li>
                </ul>
            </div>
            <a href="logout.php" class="btn btn-danger mt-4">Logout</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>