<?php
session_start();
if (!isset($_SESSION['UserID']) || !in_array($_SESSION['role'], ['Patient', 'Doctor', 'Admin'])) {
    header("Location: ../../login.php");
    exit;
}

$host = "localhost";
$db = "mc1";
$user = "root";
$pass = "";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$UserID = $_SESSION['UserID'];
$role = $_SESSION['role'];
$edit = isset($_GET['edit']) && $_GET['edit'] === 'true' && $role === 'Doctor';

if ($role === 'Patient') {
    $stmt = $conn->prepare("SELECT visit_date, diagnosis, treatment FROM patient_history WHERE patient_id = ?");
    $stmt->bind_param("s", $UserID);
} else {
    $stmt = $conn->prepare("SELECT patient_id, visit_date, diagnosis, treatment FROM patient_history WHERE doctor_id = ?");
    $stmt->bind_param("s", $UserID);
}
$stmt->execute();
$result = $stmt->get_result();
$history = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #e0e7ff, #b9d1ff, #e6f0ff);
            min-height: 100vh;
        }
        .container {
            margin-top: 80px;
            max-width: 900px;
            padding: 0 20px;
        }
        .card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 12px 50px rgba(0, 50, 120, 0.15);
        }
        .card-header h2 {
            font-size: 2.5rem;
            background: linear-gradient(to right, #007bff, #00c4b4);
            -webkit-background-clip: text;
            color: transparent;
            text-align: center;
        }
        .btn-primary {
            background: linear-gradient(to right, #007bff, #00c4b4);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(to right, #0056b3, #00a896);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Patient History</h2>
            </div>
            <?php if (empty($history)) { ?>
                <p>No history found.</p>
            <?php } else { ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <?php if ($role === 'Doctor') { ?>
                                <th>Patient ID</th>
                            <?php } ?>
                            <th>Visit Date</th>
                            <th>Diagnosis</th>
                            <th>Treatment</th>
                            <?php if ($edit) { ?>
                                <th>Actions</th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $record) { ?>
                            <tr>
                                <?php if ($role === 'Doctor') { ?>
                                    <td><?php echo htmlspecialchars($record['patient_id']); ?></td>
                                <?php } ?>
                                <td><?php echo htmlspecialchars($record['visit_date']); ?></td>
                                <td><?php echo htmlspecialchars($record['diagnosis']); ?></td>
                                <td><?php echo htmlspecialchars($record['treatment']); ?></td>
                                <?php if ($edit) { ?>
                                    <td><a href="edit_history.php?id=<?php echo htmlspecialchars($record['patient_id']); ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a></td>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
            <div class="d-flex justify-content-between mt-4">
                <a href="patient_dashboard.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <?php if (in_array($role, ['Admin', 'Patient'])) { ?>
                    <a href="analyze_history.php" class="btn btn-primary"><i class="fas fa-chart-bar"></i> Analyze My History</a>
                <?php } ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>