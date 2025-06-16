
<?php
session_start();
if (!isset($_SESSION['UserID']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

$host = "localhost";
$db = "mc1";
$user = "root";
$pass = "";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);


$UserID = $_SESSION['UserID'];
$stmt = $conn->prepare("SELECT Fullname, Email, Contact_No FROM admins WHERE UserID = ?");
$stmt->bind_param("s", $UserID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $table = $_POST['table'] ?? '';
    
    if ($action === 'delete' && isset($_POST['user_id'])) {
        $stmt = $conn->prepare("DELETE FROM $table WHERE UserID = ?");
        $stmt->bind_param("s", $_POST['user_id']);
        $stmt->execute();
    } elseif ($action === 'add') {
        $user_id = uniqid();
        $fullname = $_POST['fullname'];
        $email = $_POST['email'];
        $contact = $_POST['contact'];
        
        $query = "INSERT INTO $table (UserID, Fullname, Email, Contact_No";
        $values = "VALUES (?, ?, ?, ?";
        $types = "ssss";
        $params = [$user_id, $fullname, $email, $contact];
        
        if ($table === 'patients') {
            $query .= ", DOB, Address)";
            $values .= ", ?, ?)";
            $types .= "ss";
            $params[] = $_POST['dob'];
            $params[] = $_POST['address'];
        } elseif ($table === 'doctors') {
            $query .= ", Specialization, License_No)";
            $values .= ", ?, ?)";
            $types .= "ss";
            $params[] = $_POST['specialization'];
            $params[] = $_POST['license_no'];
        } elseif ($table === 'pharmacists') {
            $query .= ", License_No)";
            $values .= ", ?)";
            $types .= "s";
            $params[] = $_POST['license_no'];
        }
        
        $stmt = $conn->prepare("$query $values");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
    }
}

// Fetch users
$patients = $conn->query("SELECT * FROM patients")->fetch_all(MYSQLI_ASSOC);
$doctors = $conn->query("SELECT * FROM doctors")->fetch_all(MYSQLI_ASSOC);
$pharmacists = $conn->query("SELECT * FROM pharmacists")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - University Medical Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f0f4f8, #d9e2ec); }
        .container { margin-top: 50px; }
        .card { box-shadow: 0 0 20px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="card p-4">
           
            
            <div class="mt-4">
                <h5>Manage Users</h5>
                
                <!-- Patients Section -->
                <h6 class="mt-4">Patients</h6>
                <button class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#addModal" onclick="setAddForm('patients')">Add Patient</button>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>DOB</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Blood Type</th>
                            <th>Academic Year</th>
                            <th>Faculty</th>
                            <th>Citizenship</th>
                            <th>Any allergies</th>
                            <th>Emergency Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($patient['Fullname']); ?></td>
                                <td><?php echo htmlspecialchars($patient['Email']); ?></td>
                                <td><?php echo htmlspecialchars($patient['Contact_No']); ?></td>
                                <td><?php echo htmlspecialchars($patient['Birth']); ?></td>
                                <td><?php echo htmlspecialchars($patient['Age']); ?></td>
                                <td><?php echo htmlspecialchars($patient['Gender']); ?></td>
                                <td><?php echo htmlspecialchars($patient['Blood_Type']); ?></td>
                                <td><?php echo htmlspecialchars($patient['Academic_Year']); ?></td>
                                <td><?php echo htmlspecialchars($patient['Faculty']); ?></td>
                                <td><?php echo htmlspecialchars($patient['Citizenship']); ?></td>
                                <td><?php echo htmlspecialchars($patient['Any_allergies']); ?></td>
                                <td><?php echo htmlspecialchars($patient['Emergency_Contact']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="table" value="patients">
                                        <input type="hidden" name="user_id" value="<?php echo $patient['UserID']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Doctors Section -->
                <h6 class="mt-4">Doctors</h6>
                <button class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#addModal" onclick="setAddForm('doctors')">Add Doctor</button>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Specialization</th>
                            <th>Registration No</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($doctors as $doctor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($doctor['Fullname']); ?></td>
                                <td><?php echo htmlspecialchars($doctor['Email']); ?></td>
                                <td><?php echo htmlspecialchars($doctor['Contact_No']); ?></td>
                                <td><?php echo htmlspecialchars($doctor['Specialization']); ?></td>
                                <td><?php echo htmlspecialchars($doctor['RegNo']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="table" value="doctors">
                                        <input type="hidden" name="user_id" value="<?php echo $doctor['UserID']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pharmacists Section -->
                <h6 class="mt-4">Pharmacists</h6>
                <button class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#addModal" onclick="setAddForm('pharmacists')">Add Pharmacist</button>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>License No</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pharmacists as $pharmacist): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pharmacist['Fullname']); ?></td>
                                <td><?php echo htmlspecialchars($pharmacist['Email']); ?></td>
                                <td><?php echo htmlspecialchars($pharmacist['Contact_No']); ?></td>
                                <td><?php echo htmlspecialchars($pharmacist['License_No']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="table" value="pharmacists">
                                        <input type="hidden" name="user_id" value="<?php echo $pharmacist['UserID']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <a href="logout.php" class="btn btn-danger mt-4">Logout</a>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="table" id="modalTable">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="fullname" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact No</label>
                            <input type="text" class="form-control" name="contact">
                        </div>
                        <div class="mb-3" id="dobField" style="display:none;">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="dob">
                        </div>
                        <div class="mb-3" id="addressField" style="display:none;">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address"></textarea>
                        </div>
                        <div class="mb-3" id="specializationField" style="display:none;">
                            <label class="form-label">Specialization</label>
                            <input type="text" class="form-control" name="specialization">
                        </div>
                        <div class="mb-3" id="licenseField" style="display:none;">
                            <label class="form-label">License No</label>
                            <input type="text" class="form-control" name="license_no">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setAddForm(table) {
            document.getElementById('modalTable').value = table;
            document.getElementById('dobField').style.display = table === 'patients' ? 'block' : 'none';
            document.getElementById('addressField').style.display = table === 'patients' ? 'block' : 'none';
            document.getElementById('specializationField').style.display = table === 'doctors' ? 'block' : 'none';
            document.getElementById('licenseField').style.display = table === 'doctors' || table === 'pharmacists' ? 'block' : 'none';
            document.querySelector('.modal-title').textContent = 'Add ' + table.charAt(0).toUpperCase() + table.slice(1, -1);
        }
    </script>
</body>
</html>