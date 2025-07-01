<?php
session_start();
if (!isset($_SESSION['UserID']) || $_SESSION['role'] !== 'Pharmacist') {
    header("Location: ../index.php");
    exit;
}

// Dummy pharmacist name (you can fetch from DB like before)
$pharmacistName = $_SESSION['UserName'] ?? 'Pharmacist';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pharmacist Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #eef2f3, #8e9eab);
            font-family: 'Segoe UI', sans-serif;
        }
        .container {
            margin-top: 50px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .btn-block {
            margin-bottom: 10px;
            text-align: left;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card p-4">
        <h2 class="text-center mb-4">Pharmacist Dashboard</h2>
        <div class="alert alert-info">
            Welcome, <strong><?php echo htmlspecialchars($pharmacistName); ?></strong>
        </div>

        <div class="row">
            <div class="col-md-6">
                <a href="prescription_management.php" class="btn btn-primary btn-block w-100">📋 Manage Prescriptions</a>
                <a href="inventory_management.php" class="btn btn-success btn-block w-100">💊 Manage Inventory</a>
                <a href="drug_info.php" class="btn btn-info btn-block w-100">📚 Drug Information</a>
                <a href="billing.php" class="btn btn-warning btn-block w-100">💳 Billing & Insurance</a>
            </div>

            <div class="col-md-6">
                <a href="counseling.php" class="btn btn-secondary btn-block w-100">🗣️ Patient Counseling</a>
                <a href="compliance_log.php" class="btn btn-danger btn-block w-100">📑 Compliance Logging</a>
                <a href="reports.php" class="btn btn-dark btn-block w-100">📈 Reports & Analytics</a>
                <a href="drug_interactions.php" class="btn btn-outline-primary btn-block w-100">🤝 Drug Interaction Lookup</a>
            </div>
        </div>

        <a href="logout.php" class="btn btn-outline-danger mt-4 w-100">🚪 Logout</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
