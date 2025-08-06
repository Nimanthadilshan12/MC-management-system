<?php
session_start();
if (!isset($_SESSION['UserID']) || $_SESSION['role'] !== 'Patient') {
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
$success_message = '';
$error_message = '';
$appointment = null;

// Get appointment ID from query parameter
$appointment_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$appointment_id) {
    $_SESSION['error_message'] = "Invalid appointment ID.";
    header("Location: patient_dashboard.php");
    exit;
}

// Fetch appointment details
$stmt = $conn->prepare("
    SELECT a.id, a.appointment_date, a.doctor_id, a.status, d.Fullname AS doctor_name
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.UserID
    WHERE a.id = ? AND a.patient_id = ? AND a.status != 'Cancelled'
");
$stmt->bind_param("is", $appointment_id, $UserID);
$stmt->execute();
$result = $stmt->get_result();
$appointment = $result->fetch_assoc();
$stmt->close();

if (!$appointment) {
    $_SESSION['error_message'] = "Appointment not found or you do not have permission to modify it.";
    header("Location: patient_dashboard.php");
    exit;
}

// Parse current appointment date and time
$current_date = date('Y-m-d', strtotime($appointment['appointment_date']));
$current_time = date('H:i', strtotime($appointment['appointment_date']));

// Fetch all doctors for the dropdown
$doctors = [];
$stmt = $conn->prepare("SELECT UserID, Fullname FROM doctors");
$stmt->execute();
$result = $stmt->get_result();
$doctors = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = filter_input(INPUT_POST, 'doctor_id', FILTER_SANITIZE_STRING);
    $appointment_date = filter_input(INPUT_POST, 'appointment_date', FILTER_SANITIZE_STRING);
    $appointment_time = filter_input(INPUT_POST, 'appointment_time', FILTER_SANITIZE_STRING);
    $appointment_datetime = $appointment_date . ' ' . $appointment_time;

    // Validate inputs
    $start_time = strtotime('08:00:00');
    $end_time = strtotime('17:00:00');
    $selected_time = strtotime($appointment_time);

    if (empty($doctor_id) || empty($appointment_date) || empty($appointment_time)) {
        $error_message = "Please fill in all required fields.";
    } elseif (strtotime($appointment_datetime) <= time()) {
        $error_message = "Appointment time must be in the future.";
    } elseif ($selected_time < $start_time || $selected_time > $end_time) {
        $error_message = "Appointments must be scheduled between 8:00 AM and 5:00 PM.";
    } else {
        // Check if the doctor is available at the new time (excluding the current appointment)
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM appointments 
            WHERE doctor_id = ? AND appointment_date = ? AND status != 'Cancelled' AND id != ?
        ");
        $stmt->bind_param("ssi", $doctor_id, $appointment_datetime, $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if ($row['count'] > 0) {
            $error_message = "The selected doctor is not available at this time.";
        } else {
            // Update the appointment
            $status = 'Pending'; // Changes by patients require approval
            $stmt = $conn->prepare("
                UPDATE appointments 
                SET doctor_id = ?, appointment_date = ?, status = ?
                WHERE id = ? AND patient_id = ?
            ");
            $stmt->bind_param("sssis", $doctor_id, $appointment_datetime, $status, $appointment_id, $UserID);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['success_message'] = "Appointment updated successfully! Awaiting approval.";
                    header("Location: patient_dashboard.php");
                    exit;
                } else {
                    $error_message = "No changes made or appointment not found.";
                }
            } else {
                $error_message = "Error updating appointment: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Appointment - University Medical Centre</title>
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
        .btn-secondary {
            background: linear-gradient(to right, #6c757d, #adb5bd);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
        }
        .btn-secondary:hover {
            background: linear-gradient(to right, #5c636a, #9ea5ad);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Change Appointment</h2>
            </div>
            <?php if ($success_message) { ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php } ?>
            <?php if ($error_message) { ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php } ?>
            <form method="POST" action="" onsubmit="return validateTime()">
                <div class="mb-3">
                    <label for="appointment_date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="appointment_date" name="appointment_date" 
                           value="<?php echo htmlspecialchars($current_date); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="appointment_time" class="form-label">Time (8:00 AM - 5:00 PM)</label>
                    <input type="time" class="form-control" id="appointment_time" name="appointment_time" 
                           value="<?php echo htmlspecialchars($current_time); ?>" min="08:00" max="17:00" required>
                </div>
                <div class="mb-3">
                    <label for="doctor_id" class="form-label">Select Doctor</label>
                    <select class="form-control" id="doctor_id" name="doctor_id" required>
                        <option value="">Choose a doctor</option>
                        <?php foreach ($doctors as $doctor) { ?>
                            <option value="<?php echo htmlspecialchars($doctor['UserID']); ?>" 
                                    <?php echo $doctor['UserID'] === $appointment['doctor_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($doctor['Fullname']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Appointment</button>
                <a href="patient_dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancel</a>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function validateTime() {
            const timeInput = document.getElementById('appointment_time').value;
            const time = new Date(`1970-01-01T${timeInput}:00`);
            const hours = time.getHours();
            const minutes = time.getMinutes();
            if (hours < 8 || hours > 17 || (hours === 17 && minutes > 0)) {
                alert('Please select a time between 8:00 AM and 5:00 PM.');
                return false;
            }
            return true;
        }

        // Update doctor list based on selected date and time
        document.getElementById('appointment_date').addEventListener('change', updateDoctors);
        document.getElementById('appointment_time').addEventListener('change', updateDoctors);

        function updateDoctors() {
            const date = document.getElementById('appointment_date').value;
            const time = document.getElementById('appointment_time').value;
            const appointmentId = <?php echo json_encode($appointment_id); ?>;
            if (date && time) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'get_available_doctors.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        const doctors = JSON.parse(xhr.responseText);
                        const doctorSelect = document.getElementById('doctor_id');
                        doctorSelect.innerHTML = '<option value="">Choose a doctor</option>';
                        doctors.forEach(doctor => {
                            const option = document.createElement('option');
                            option.value = doctor.UserID;
                            option.textContent = doctor.Fullname;
                            doctorSelect.appendChild(option);
                        });
                        // Restore the current doctor if still available
                        doctorSelect.value = <?php echo json_encode($appointment['doctor_id']); ?>;
                    }
                };
                xhr.send(`appointment_datetime=${encodeURIComponent(date + ' ' + time)}&exclude_appointment_id=${appointmentId}`);
            }
        }
    </script>
</body>
</html>