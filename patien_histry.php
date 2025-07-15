<?php
// Database connection configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hospital_db";

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Create database and table if not exists
try {
    $sql = "CREATE DATABASE IF NOT EXISTS hospital_db";
    $conn->exec($sql);
    
    $sql = "USE hospital_db";
    $conn->exec($sql);
    
    $sql = "CREATE TABLE IF NOT EXISTS patient_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_name VARCHAR(100) NOT NULL,
        date_of_visit DATE NOT NULL,
        diagnosis TEXT NOT NULL,
        treatment TEXT NOT NULL,
        doctor_name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
} catch(PDOException $e) {
    die("Error creating table: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] == 'add') {
                $stmt = $conn->prepare("INSERT INTO patient_history (patient_name, date_of_visit, diagnosis, treatment, doctor_name) 
                    VALUES (:patient_name, :date_of_visit, :diagnosis, :treatment, :doctor_name)");
                $stmt->execute([
                    'patient_name' => $_POST['patient_name'],
                    'date_of_visit' => $_POST['date_of_visit'],
                    'diagnosis' => $_POST['diagnosis'],
                    'treatment' => $_POST['treatment'],
                    'doctor_name' => $_POST['doctor_name']
                ]);
                $message = "Record added successfully!";
            } elseif ($_POST['action'] == 'update' && isset($_POST['id'])) {
                $stmt = $conn->prepare("UPDATE patient_history SET 
                    patient_name = :patient_name,
                    date_of_visit = :date_of_visit,
                    diagnosis = :diagnosis,
                    treatment = :treatment,
                    doctor_name = :doctor_name
                    WHERE id = :id");
                $stmt->execute([
                    'patient_name' => $_POST['patient_name'],
                    'date_of_visit' => $_POST['date_of_visit'],
                    'diagnosis' => $_POST['diagnosis'],
                    'treatment' => $_POST['treatment'],
                    'doctor_name' => $_POST['doctor_name'],
                    'id' => $_POST['id']
                ]);
                $message = "Record updated successfully!";
            } elseif ($_POST['action'] == 'delete' && isset($_POST['id'])) {
                $stmt = $conn->prepare("DELETE FROM patient_history WHERE id = :id");
                $stmt->execute(['id' => $_POST['id']]);
                $message = "Record deleted successfully!";
            }
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Fetch all patient records
$stmt = $conn->prepare("SELECT * FROM patient_history ORDER BY created_at DESC");
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient History Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .reset-button {
            background-color: #f44336;
        }
        .reset-button:hover {
            background-color: #da190b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .error {
            background-color: #f2dede;
            color: #a94442;
        }
        .action-buttons button {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Patient History Management</h2>
        
        <?php if (isset($message)): ?>
            <div class="message <?php echo strpos($message, 'Error') === false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <form method="POST" id="patientForm">
            <input type="hidden" name="id" id="record_id">
            <input type="hidden" name="action" id="action" value="add">
            
            <div class="form-group">
                <label for="patient_name">Patient Name</label>
                <input type="text" name="patient_name" id="patient_name" required>
            </div>
            
            <div class="form-group">
                <label for="date_of_visit">Date of Visit</label>
                <input type="date" name="date_of_visit" id="date_of_visit" required>
            </div>
            
            <div class="form-group">
                <label for="diagnosis">Diagnosis</label>
                <textarea name="diagnosis" id="diagnosis" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="treatment">Treatment</label>
                <textarea name="treatment" id="treatment" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="doctor_name">Doctor Name</label>
                <input type="text" name="doctor_name" id="doctor_name" required>
            </div>
            
            <button type="submit">Save Record</button>
            <button type="button" class="reset-button" onclick="resetForm()">Reset</button>
        </form>

        <!-- Patient Records Table -->
        <table>
            <thead>
                <tr>
                    
                    <th>Patient Name</th>
                    <th>Date of Visit</th>
                    <th>Diagnosis</th>
                    <th>Treatment</th>
                    <th>Doctor</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record): ?>
                    <tr>
                    
                        <td><?php echo htmlspecialchars($record['patient_name']); ?></td>
                        <td><?php echo htmlspecialchars($record['date_of_visit']); ?></td>
                        <td><?php echo htmlspecialchars($record['diagnosis']); ?></td>
                        <td><?php echo htmlspecialchars($record['treatment']); ?></td>
                        <td><?php echo htmlspecialchars($record['doctor_name']); ?></td>
                        <td><?php echo htmlspecialchars($record['created_at']); ?></td>
                        <td class="action-buttons">
                            <button onclick="editRecord(<?php echo htmlspecialchars(json_encode($record)); ?>)">Edit</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $record['id']; ?>">
                                <button type="submit" onclick="return confirm('Are you sure you want to delete this record?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        function resetForm() {
            const form = document.getElementById('patientForm');
            form.reset(); // Reset all form fields
            document.getElementById('record_id').value = ''; // Clear hidden ID field
            document.getElementById('action').value = 'add'; // Set action back to 'add'
        }

        function editRecord(record) {
            document.getElementById('record_id').value = record.id;
            document.getElementById('action').value = 'update';
            document.getElementById('patient_name').value = record.patient_name;
            document.getElementById('date_of_visit').value = record.date_of_visit;
            document.getElementById('diagnosis').value = record.diagnosis;
            document.getElementById('treatment').value = record.treatment;
            document.getElementById('doctor_name').value = record.doctor_name;
        }
    </script>
</body>
</html>