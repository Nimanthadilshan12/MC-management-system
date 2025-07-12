<?php
session_start();
if (!isset($_SESSION['UserID']) || !in_array($_SESSION['role'], ['Patient', 'Doctor'])) {
    header("Location: ../login.php");
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
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'Patient') {
    $doctor_id = filter_input(INPUT_POST, 'doctor_id', FILTER_SANITIZE_STRING);
    $appointment_date = filter_input(INPUT_POST, 'appointment_date', FILTER_SANITIZE_STRING) . ' ' . filter_input(INPUT_POST, 'appointment_time', FILTER_SANITIZE_STRING);
    if (empty($doctor_id) || empty($appointment_date)) {
        $error_message = "Please select a doctor and appointment time.";
    } elseif (strtotime($appointment_date) <= time()) {
        $error_message = "Appointment time must be in the future.";
    } else {
        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status) VALUES (?, ?, ?, 'Pending')");
        if ($stmt) {
            $stmt->bind_param("sss", $UserID, $doctor_id, $appointment_date);
            if ($stmt->execute()) {
                $success_message = "Appointment booked successfully!";
            } else {
                $error_message = "Error booking appointment: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Prepare failed: " . $conn->error;
        }
    }
}

// Fetch available doctors
$stmt = $conn->prepare("SELECT UserID, Fullname FROM doctors");
$stmt->execute();
$result = $stmt->get_result();
$doctors = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - University Medical Centre</title>
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
        .form-label {
            font-weight: 500;
            color: #1a3556;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #ced4da;
        }
        .btn-primary {
            background: linear-gradient(to right, #007bff, #00c4b4);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
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
                <h2>Book Appointment</h2>
            </div>
            <?php if ($success_message) { ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php } ?>
            <?php if ($error_message) { ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php } ?>
            <?php if ($role === 'Patient') { ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="doctor_id" class="form-label">Select Doctor</label>
                        <select class="form-control" id="doctor_id" name="doctor_id" required>
                            <option value="">Choose a doctor</option>
                            <?php foreach ($doctors as $doctor) { ?>
                                <option value="<?php echo htmlspecialchars($doctor['UserID']); ?>">
                                    <?php echo htmlspecialchars($doctor['Fullname']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="appointment_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="appointment_date" name="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="appointment_time" class="form-label">Time</label>
                        <input type="time" class="form-control" id="appointment_time" name="appointment_time" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-calendar-check"></i> Book Appointment</button>
                </form>
                <a href="patient_dashboard.php" class="btn btn-primary mt-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <?php } else { ?>
                <p class="text-center">Doctors cannot book appointments. Please use the dashboard to manage patient history.</p>
                <a href="patient_dashboard.php" class="btn btn-primary mt-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <?php } ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>