<?php
session_start();
if (!isset($_SESSION['UserID']) || $_SESSION['role'] !== 'Patient') {
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
$message = "";

// Fetch current patient details
$stmt = $conn->prepare("SELECT Fullname, Email, Contact_No, Age, Gender, Birth, Blood_Type, Academic_Year, Faculty, Citizenship, Any_allergies, Emergency_Contact FROM patients WHERE UserID = ?");
$stmt->bind_param("s", $UserID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $Fullname = $_POST['Fullname'];
    $Email = $_POST['Email'];
    $Contact_No = $_POST['Contact_No'];
    $Age = $_POST['Age'];
    $Gender = $_POST['Gender'];
    $Birth = $_POST['Birth'];
    $Blood_Type = $_POST['Blood_Type'];
    $Academic_Year = $_POST['Academic_Year'];
    $Faculty = $_POST['Faculty'];
    $Citizenship = $_POST['Citizenship'];
    $Any_allergies = $_POST['Any_allergies'];
    $Emergency_Contact = $_POST['Emergency_Contact'];

    $stmt = $conn->prepare("UPDATE patients SET Fullname = ?, Email = ?, Contact_No = ?, Age = ?, Gender = ?, Birth = ?, Blood_Type = ?, Academic_Year = ?, Faculty = ?, Citizenship = ?, Any_allergies = ?, Emergency_Contact = ? WHERE UserID = ?");
    $stmt->bind_param("sssssssssssss", $Fullname, $Email, $Contact_No, $Age, $Gender, $Birth, $Blood_Type, $Academic_Year, $Faculty, $Citizenship, $Any_allergies, $Emergency_Contact, $UserID);
    if ($stmt->execute()) {
        $message = "Details updated successfully!";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();

    // Refresh user data
    $stmt = $conn->prepare("SELECT Fullname, Email, Contact_No, Age, Gender, Birth, Blood_Type, Academic_Year, Faculty, Citizenship, Any_allergies, Emergency_Contact FROM patients WHERE UserID = ?");
    $stmt->bind_param("s", $UserID);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient Details - University Medical Centre</title>
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
        .message {
            text-align: center;
            color: #28a745;
            margin-bottom: 15px;
        }
        .error {
            color: #dc3545;
        }
        input, select, textarea {
            width: 100%;
            padding: 12px;
            margin: 8px 0 16px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
        }
        .btn-primary {
            background: linear-gradient(to right, #007bff, #00c4b4);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(to right, #0056b3, #00a896);
        }
        .btn-secondary {
            background: #6c757d;
            border: none;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        @media (max-width: 768px) {
            .container {
                margin-top: 60px;
                padding: 0 15px;
            }
            .card {
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Edit Patient Details</h2>
            </div>
            <?php if ($message) { ?>
                <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : ''; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php } ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="Fullname" class="form-control" value="<?php echo htmlspecialchars($user['Fullname']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="Email" class="form-control" value="<?php echo htmlspecialchars($user['Email']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="Contact_No" class="form-control" value="<?php echo htmlspecialchars($user['Contact_No']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Age</label>
                    <input type="number" name="Age" class="form-control" value="<?php echo htmlspecialchars($user['Age']); ?>" min="0">
                </div>
                <div class="mb-3">
                    <label class="form-label">Gender</label>
                    <select name="Gender" class="form-control">
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo $user['Gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $user['Gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="Birth" class="form-control" value="<?php echo htmlspecialchars($user['Birth']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Blood Type</label>
                    <select name="Blood_Type" class="form-control">
                        <option value="">Select Blood Type</option>
                        <option value="A+" <?php echo $user['Blood_Type'] === 'A+' ? 'selected' : ''; ?>>A+</option>
                        <option value="A-" <?php echo $user['Blood_Type'] === 'A-' ? 'selected' : ''; ?>>A-</option>
                        <option value="B+" <?php echo $user['Blood_Type'] === 'B+' ? 'selected' : ''; ?>>B+</option>
                        <option value="B-" <?php echo $user['Blood_Type'] === 'B-' ? 'selected' : ''; ?>>B-</option>
                        <option value="AB+" <?php echo $user['Blood_Type'] === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                        <option value="AB-" <?php echo $user['Blood_Type'] === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                        <option value="O+" <?php echo $user['Blood_Type'] === 'O+' ? 'selected' : ''; ?>>O+</option>
                        <option value="O-" <?php echo $user['Blood_Type'] === 'O-' ? 'selected' : ''; ?>>O-</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Academic Year</label>
                    <input type="text" name="Academic_Year" class="form-control" value="<?php echo htmlspecialchars($user['Academic_Year']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Faculty</label>
                    <input type="text" name="Faculty" class="form-control" value="<?php echo htmlspecialchars($user['Faculty']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Citizenship</label>
                    <input type="text" name="Citizenship" class="form-control" value="<?php echo htmlspecialchars($user['Citizenship']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Allergies</label>
                    <textarea name="Any_allergies" class="form-control"><?php echo htmlspecialchars($user['Any_allergies']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Emergency Contact</label>
                    <input type="text" name="Emergency_Contact" class="form-control" value="<?php echo htmlspecialchars($user['Emergency_Contact']); ?>">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Changes</button>
                <a href="patient_dashboard.php" class="btn btn-primary"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>