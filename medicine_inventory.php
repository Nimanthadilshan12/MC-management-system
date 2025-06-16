<?php
session_start();
if (!isset($_SESSION['UserID']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}
include '../db_connection.php';

$result = $conn->query("SELECT * FROM medicine_inventory");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medicine Inventory</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; padding: 20px; }
        .container { background: #fff; padding: 20px; border-radius: 10px; max-width: 1000px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ccc; padding: 10px; }
        th { background-color: #007bff; color: white; }
    </style>
</head>
<body>
<div class="container">
    <h2>Medicine Inventory</h2>
    <table>
        <tr>
            <th>ID</th><th>Name</th><th>Quantity</th><th>Expiry Date</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['id']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['quantity']) ?></td>
            <td><?= htmlspecialchars($row['expiry_date']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
