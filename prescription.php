<?php
// Database connection configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mc1";

try {
    // Create database connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Get form data
        $patient_name = filter_input(INPUT_POST, 'patient_name', FILTER_SANITIZE_STRING);
        $patient_id = filter_input(INPUT_POST, 'patient_id', FILTER_SANITIZE_STRING);
        $diagnosis = filter_input(INPUT_POST, 'diagnosis', FILTER_SANITIZE_STRING);
        $medicines = $_POST['medicine'] ?? [];
        $dosages = $_POST['dosage'] ?? [];
        $frequencies = $_POST['frequency'] ?? [];
        $times = $_POST['time'] ?? [];

        // Validate required fields
        if (empty($patient_name) || empty($patient_id) || empty($diagnosis)) {
            throw new Exception("All required fields must be filled.");
        }

        // Begin transaction
        $conn->beginTransaction();

        // Insert prescription header
        $stmt = $conn->prepare("INSERT INTO prescriptions (patient_id, patient_name, diagnosis, doctor_id, prescription_date) VALUES (:patient_id, :patient_name, :diagnosis, :doctor_id, NOW())");
        $stmt->execute([
            ':patient_id' => $patient_id,
            ':patient_name' => $patient_name,
            ':diagnosis' => $diagnosis,
            ':doctor_id' => 1 // Assuming doctor_id is known (e.g., from session)
        ]);

        $prescription_id = $conn->lastInsertId();

        // Insert medicines
        for ($i = 0; $i < count($medicines); $i++) {
            if (!empty($medicines[$i])) {
                $stmt = $conn->prepare("INSERT INTO prescription_medicines (prescription_id, medicine, dosage, frequency, time) VALUES (:prescription_id, :medicine, :dosage, :frequency, :time)");
                $stmt->execute([
                    ':prescription_id' => $prescription_id,
                    ':medicine' => filter_var($medicines[$i], FILTER_SANITIZE_STRING),
                    ':dosage' => filter_var($dosages[$i], FILTER_SANITIZE_STRING),
                    ':frequency' => filter_var($frequencies[$i], FILTER_SANITIZE_STRING),
                    ':time' => filter_var($times[$i], FILTER_SANITIZE_STRING)
                ]);
            }
        }

        // Commit transaction
        $conn->commit();
        $response = ["status" => "success", "message" => "Prescription saved successfully"];
        echo json_encode($response);
    }
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollBack();
    }
    $response = ["status" => "error", "message" => "Error: " . $e->getMessage()];
    echo json_encode($response);
}

// Close connection
$conn = null;
?>