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

// Store form data to repopulate fields after submission
$form_data = [
    'appointment_date' => '',
    'appointment_time' => '',
    'doctor_id' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'Patient') {
    $doctor_id = filter_input(INPUT_POST, 'doctor_id', FILTER_SANITIZE_STRING);
    $appointment_date = filter_input(INPUT_POST, 'appointment_date', FILTER_SANITIZE_STRING);
    $appointment_time = filter_input(INPUT_POST, 'appointment_time', FILTER_SANITIZE_STRING);
    $appointment_datetime = $appointment_date . ' ' . $appointment_time;

    // Store form data for repopulation
    $form_data['appointment_date'] = $appointment_date;
    $form_data['appointment_time'] = $appointment_time;
    $form_data['doctor_id'] = $doctor_id;

    // Validate inputs
    if (empty($doctor_id) || empty($appointment_date) || empty($appointment_time)) {
        $error_message = "Please select a doctor, date, and time.";
    } elseif (!DateTime::createFromFormat('Y-m-d H:i', $appointment_datetime)) {
        $error_message = "Invalid date or time format.";
    } else {
        $appointment_timestamp = strtotime($appointment_datetime);
        $current_timestamp = time();
        $min_booking_time = $current_timestamp + 3600; // 1 hour from now
        $appointment_hour = (int) date('H', $appointment_timestamp);
        $appointment_day = date('N', $appointment_timestamp); // 1 (Mon) to 7 (Sun)

        // Validate appointment time
        if ($appointment_timestamp === false) {
            $error_message = "Invalid date or time provided.";
        } elseif ($appointment_timestamp <= $min_booking_time) {
            $error_message = "Appointment must be booked at least 1 hour in the future.";
        } elseif ($appointment_hour < 8 || $appointment_hour >= 17) {
            $error_message = "Appointments can only be booked between 8:00 AM and 5:00 PM.";
        } elseif ($appointment_day > 5) {
            $error_message = "Appointments are only available Monday through Friday.";
        } else {
            $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status) VALUES (?, ?, ?, 'Pending')");
            if ($stmt) {
                $stmt->bind_param("sss", $UserID, $doctor_id, $appointment_datetime);
                if ($stmt->execute()) {
                    $success_message = "Appointment booked successfully!";
                    $stmt->close();
                    $conn->close();
                    // Redirect to dashboard with success message
                    header("Location: patient_dashboard.php?success=" . urlencode($success_message));
                    exit;
                } else {
                    $error_message = "Error booking appointment: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error_message = "Prepare failed: " . $conn->error;
            }
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
        .alert {
            margin-bottom: 20px;
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
                <form method="POST" action="" id="appointmentForm">
                    <div class="mb-3">
                        <label for="appointment_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="appointment_date" name="appointment_date" 
                               min="<?php echo date('Y-m-d'); ?>" 
                               value="<?php echo htmlspecialchars($form_data['appointment_date']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="appointment_time" class="form-label">Time</label>
                        <input type="time" class="form-control" id="appointment_time" name="appointment_time" 
                               min="08:00" max="17:00" 
                               value="<?php echo htmlspecialchars($form_data['appointment_time']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="doctor_id" class="form-label">Select Doctor</label>
                        <select class="form-control" id="doctor_id" name="doctor_id" required>
                            <option value="">Choose a doctor</option>
                            <?php foreach ($doctors as $doctor) { ?>
                                <option value="<?php echo htmlspecialchars($doctor['UserID']); ?>" 
                                        <?php echo $form_data['doctor_id'] === $doctor['UserID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($doctor['Fullname']); ?>
                                </option>
                            <?php } ?>
                        </select>
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
    <script>
        // Client-side validation
        document.getElementById('appointmentForm')?.addEventListener('submit', function(event) {
            const dateInput = document.getElementById('appointment_date');
            const timeInput = document.getElementById('appointment_time');
            const doctorInput = document.getElementById('doctor_id');
            let error = '';

            // Check date and time
            const selectedDate = new Date(dateInput.value + 'T' + timeInput.value);
            const now = new Date();
            const oneHourFromNow = new Date(now.getTime() + 60 * 60 * 1000);

            if (selectedDate <= oneHourFromNow) {
                error = 'Appointment must be at least 1 hour in the future.';
            } else if (selectedDate.getHours() < 8 || selectedDate.getHours() >= 17) {
                error = 'Appointments can only be booked between 8:00 AM and 5:00 PM.';
            } else if (selectedDate.getDay() === 0 || selectedDate.getDay() === 6) {
                error = 'Appointments are only available Monday through Friday.';
            } else if (!doctorInput.value) {
                error = 'Please select a doctor.';
            }

            if (error) {
                event.preventDefault();
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger';
                alertDiv.textContent = error;
                const card = document.querySelector('.card');
                card.insertBefore(alertDiv, card.querySelector('form'));
                setTimeout(() => alertDiv.remove(), 5000);
            }
        });
    </script>
</body>
</html>