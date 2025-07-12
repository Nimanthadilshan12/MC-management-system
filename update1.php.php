<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "medical_center";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize variables
$patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : '';
$patient = null;
$message = '';

if ($patient_id) {
    // Fetch patient details
    $stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = :id");
    $stmt->execute(['id' => $patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        $message = "Patient not found.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contact_number = $_POST['contact_number'];
    $address = $_POST['address'];

    // Validate input (basic validation)
    if (empty($first_name) || empty($last_name) || empty($dob) || empty($gender)) {
        $message = "Please fill in all required fields.";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE patients SET first_name = :first_name, last_name = :last_name, dob = :dob, gender = :gender, contact_number = :contact_number, address = :address WHERE patient_id = :patient_id");
            $stmt->execute([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'dob' => $dob,
                'gender' => $gender,
                'contact_number' => $contact_number,
                'address' => $address,
                'patient_id' => $patient_id
            ]);
            $message = "Patient details updated successfully!";
            
            // Refresh patient data
            $stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = :id");
            $stmt->execute(['id' => $patient_id]);
            $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $message = "Error updating patient: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Patient Details</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-container { max-width: 600px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select, textarea { width: 100%; padding: 8px; }
        .message { color: green; margin-bottom: 10px; }
        .error { color: red; }
        button { padding: 10px 20px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #45a049; }
    </style>
</head>
<body>
    <h2>Update Patient Details</h2>

    <?php if ($message): ?>
        <p class="<?php echo strpos($message, 'Error') === false ? 'message' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <?php if ($patient): ?>
        <form class="form-container" method="POST">
            <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($patient['patient_id']); ?>">
            
            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($patient['first_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($patient['last_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="dob">Date of Birth *</label>
                <input type="date" name="dob" id="dob" value="<?php echo htmlspecialchars($patient['dob']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="gender">Gender *</label>
                <select name="gender" id="gender" required>
                    <option value="Male" <?php echo $patient['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo $patient['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo $patient['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="contact_number">Contact Number</label>
                <input type="text" name="contact_number" id="contact_number" value="<?php echo htmlspecialchars($patient['contact_number']); ?>">
            </div>
            
            <div class="form-group">
                <label for="address">Address</label>
                <textarea name="address" id="address"><?php echo htmlspecialchars($patient['address']); ?></textarea>
            </div>
            
            <button type="submit">Update Patient</button>
        </form>
    <?php else: ?>
        <p>Please provide a valid patient ID in the URL (e.g., update_patient.php?patient_id=123).</p>
    <?php endif; ?>
</body>
</html>

<?php
$conn = null; // Close connection
?>