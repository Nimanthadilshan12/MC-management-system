<?php
// Database connection settings
$servername = "localhost";
$username = "root"; // Replace with your MySQL username
$password = ""; // Replace with your MySQL password
$dbname = "mc1";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch medicines for dropdown
$medicines_result = $conn->query("SELECT id, name FROM medicines ORDER BY name");
$medicines_list = [];
if ($medicines_result) {
    while ($row = $medicines_result->fetch_assoc()) {
        $medicines_list[] = $row;
    }
}

// Initialize variables
$patient_name = $patient_id = $diagnosis = "";
$medicines = [];
$errors = [];
$success = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $patient_name = filter_input(INPUT_POST, 'patient_name', FILTER_SANITIZE_STRING);
    $patient_id = filter_input(INPUT_POST, 'patient_id', FILTER_SANITIZE_STRING);
    $diagnosis = filter_input(INPUT_POST, 'diagnosis', FILTER_SANITIZE_STRING);
    $medicines = $_POST['medicines'] ?? [];

    // Validation
    if (empty($patient_name)) {
        $errors[] = "Patient name is required.";
    }
    if (empty($patient_id)) {
        $errors[] = "Patient ID is required.";
    }
    if (empty($diagnosis)) {
        $errors[] = "Diagnosis is required.";
    }
    if (empty($medicines)) {
        $errors[] = "At least one medicine is required.";
    } else {
        foreach ($medicines as $index => $medicine) {
            if (empty($medicine['id']) || $medicine['id'] === "Select Medicine") {
                $errors[] = "Medicine is required for row #" . ($index + 1) . ".";
            }
            if ($medicine['id'] === "Other" && empty($medicine['other_name'])) {
                $errors[] = "Custom medicine name is required for row #" . ($index + 1) . " when 'Other' is selected.";
            }
            if (empty($medicine['dosage']) || $medicine['dosage'] === "Select Dosage") {
                $errors[] = "Dosage is required for row #" . ($index + 1) . ".";
            }
            if (empty($medicine['frequency']) || $medicine['frequency'] === "Select Frequency") {
                $errors[] = "Frequency is required for row #" . ($index + 1) . ".";
            }
            if (empty($medicine['time']) || $medicine['time'] === "Select Time") {
                $errors[] = "Time is required for row #" . ($index + 1) . ".";
            }
        }
    }

    // If no errors, insert into database
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();
        try {
            // Insert into prescriptions table
            $stmt = $conn->prepare("INSERT INTO prescriptions (patient_name, patient_id, diagnosis) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $patient_name, $patient_id, $diagnosis);
            $stmt->execute();
            $prescription_id = $conn->insert_id;
            $stmt->close();

            // Insert medicines into prescription_medicines table
            $stmt = $conn->prepare("INSERT INTO prescription_medicines (prescription_id, medicine_id, dosage, frequency, time) VALUES (?, ?, ?, ?, ?)");
            $insert_medicine_stmt = $conn->prepare("INSERT INTO medicines (name) VALUES (?)");
            foreach ($medicines as $medicine) {
                $medicine_id = $medicine['id'];
                if ($medicine_id === "Other") {
                    // Insert new medicine into medicines table
                    $other_name = filter_var($medicine['other_name'], FILTER_SANITIZE_STRING);
                    $insert_medicine_stmt->bind_param("s", $other_name);
                    $insert_medicine_stmt->execute();
                    $medicine_id = $conn->insert_id;
                }
                $stmt->bind_param("iisss", $prescription_id, $medicine_id, $medicine['dosage'], $medicine['frequency'], $medicine['time']);
                $stmt->execute();
            }
            $stmt->close();
            $insert_medicine_stmt->close();

            // Commit transaction
            $conn->commit();
            $success = "Prescription saved successfully!";
            // Reset form
            $patient_name = $patient_id = $diagnosis = "";
            $medicines = [];
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error saving prescription: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Prescription</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        .error { color: red; }
        .success { color: green; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 15px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        button { margin: 5px; padding: 5px 10px; }
        .other-medicine { display: none; }
        select, input[type="text"], textarea { width: 100%; padding: 5px; }
    </style>
    <script>
        function addMedicineRow() {
            const table = document.getElementById('medicine-table').getElementsByTagName('tbody')[0];
            const index = table.rows.length;
            const row = table.insertRow();
            row.innerHTML = `
                <td>
                    <select name="medicines[${index}][id]" onchange="toggleOtherMedicine(this, ${index})">
                        <option value="Select Medicine">Select Medicine</option>
                        <?php foreach ($medicines_list as $medicine): ?>
                            <option value="<?php echo $medicine['id']; ?>"><?php echo htmlspecialchars($medicine['name']); ?></option>
                        <?php endforeach; ?>
                        <option value="Other">Other...</option>
                    </select>
                    <input type="text" name="medicines[${index}][other_name]" class="other-medicine" id="other-medicine-${index}" placeholder="Enter medicine name">
                </td>
                <td>
                    <select name="medicines[${index}][dosage]">
                        <option value="Select Dosage">Select Dosage</option>
                        <option value="Once daily">Once daily</option>
                        <option value="Twice daily">Twice daily</option>
                        <option value="Three times daily">Three times daily</option>
                        <option value="As needed">As needed</option>
                    </select>
                </td>
                <td>
                    <select name="medicines[${index}][frequency]">
                        <option value="Select Frequency">Select Frequency</option>
                        <option value="Morning">Morning</option>
                        <option value="Afternoon">Afternoon</option>
                        <option value="Evening">Evening</option>
                        <option value="Bedtime">Bedtime</option>
                    </select>
                </td>
                <td>
                    <select name="medicines[${index}][time]">
                        <option value="Select Time">Select Time</option>
                        <option value="Morning">Morning</option>
                        <option value="Afternoon">Afternoon</option>
                        <option value="Evening">Evening</option>
                        <option value="Bedtime">Bedtime</option>
                    </select>
                </td>
                <td>
                    <button type="button" onclick="this.parentElement.parentElement.remove()">Remove</button>
                </td>
            `;
        }

        function toggleOtherMedicine(select, index) {
            const otherInput = document.getElementById(`other-medicine-${index}`);
            if (select.value === "Other") {
                otherInput.style.display = "block";
            } else {
                otherInput.style.display = "none";
            }
        }
    </script>
</head>
<body>
    <h2>New Prescription</h2>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success">
            <p><?php echo htmlspecialchars($success); ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="patient_name">Patient Name:</label>
            <input type="text" id="patient_name" name="patient_name" value="<?php echo htmlspecialchars($patient_name); ?>">
        </div>

        <div class="form-group">
            <label for="patient_id">Patient ID:</label>
            <input type="text" id="patient_id" name="patient_id" value="<?php echo htmlspecialchars($patient_id); ?>">
        </div>

        <div class="form-group">
            <label for="diagnosis">Illness/Diagnosis:</label>
            <textarea id="diagnosis" name="diagnosis"><?php echo htmlspecialchars($diagnosis); ?></textarea>
        </div>

        <h3>Medicines</h3>
        <table id="medicine-table">
            <thead>
                <tr>
                    <th>Medicine</th>
                    <th>Dosage</th>
                    <th>Frequency</th>
                    <th>Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- Medicine rows will be added here dynamically -->
            </tbody>
        </table>

        <button type="button" onclick="addMedicineRow()">Add Medicine</button>
        <button type="submit">Save Prescription</button>
    </form>

    <script>
        // Add one medicine row by default
        addMedicineRow();
    </script>
</body>
</html>

<?php $conn->close(); ?>