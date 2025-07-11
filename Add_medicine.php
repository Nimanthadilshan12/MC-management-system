<?php
// Database connection
require_once('db_connect.php'); // Include database connection

try {
    // Check if the form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrieve and sanitize form data
        $prescription_id = isset($_POST['prescription_id']) ? htmlspecialchars($_POST['prescription_id']) : '';
        $medicine_name = isset($_POST['medicine']) ? htmlspecialchars($_POST['medicine']) : '';
        $dosage = isset($_POST['dosage']) ? htmlspecialchars($_POST['dosage']) : '';
        $frequency = isset($_POST['frequency']) ? htmlspecialchars($_POST['frequency']) : '';
        $time = isset($_POST['time']) ? htmlspecialchars($_POST['time']) : '';

        // Validate required fields
        if (empty($prescription_id) || empty($medicine_name) || empty($frequency) || empty($time)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }

        // Validate ENUM values for frequency and time
        $valid_frequencies = ['Once daily', 'Twice daily', 'Three times daily', 'As needed'];
        $valid_times = ['Morning', 'Afternoon', 'Evening', 'Bedtime'];
        if (!in_array($frequency, $valid_frequencies) || !in_array($time, $valid_times)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid frequency or time value']);
            exit;
        }

        // Prepare SQL query to insert medication
        $query = "INSERT INTO medications (prescription_id, medicine_name, dosage, frequency, time) 
                  VALUES (:prescription_id, :medicine_name, :dosage, :frequency, :time)";
        $stmt = $pdo->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':prescription_id', $prescription_id);
        $stmt->bindParam(':medicine_name', $medicine_name);
        $stmt->bindParam(':dosage', $dosage);
        $stmt->bindParam(':frequency', $frequency);
        $stmt->bindParam(':time', $time);

        // Execute the query
        if ($stmt->execute()) {
            echo json_encode(['success' => 'Medication added successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add medication']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>