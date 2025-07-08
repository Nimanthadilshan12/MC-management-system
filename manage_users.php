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
    <title>Manage Users - University Medical Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #7c3aed;
            --secondary: #ec4899;
            --accent: #06b6d4;
            --text: #1e293b;
            --background: #f1f5f9;
            --success: #10b981;
            --error: #ef4444;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #a5b4fc, rgb(198, 168, 249), #22d3ee);
            position: relative;
            min-height: 100vh;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://media.istockphoto.com/id/1440001176/photo/administrator-with-medical-team.jpg?s=612x612&w=0&k=20&c=TwUTSZTb1HCu9J8r8hVEEInXKf1-GvIVsUhtlNYEho4=');
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
            opacity: 0.1;
            z-index: -1;
            animation: zoomInOut 20s ease-in-out infinite;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at top center, rgba(255, 255, 255, 0.35), transparent 60%);
            z-index: -1;
        }

        .container {
            margin-top: 80px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }

        .card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(240, 245, 255, 0.95));
            border-radius: 20px;
            box-shadow: 0 12px 50px rgba(0, 50, 120, 0.15), 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 40px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.7s ease-out;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0, 50, 120, 0.2), 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(124, 58, 237, 0.1), transparent 60%);
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: -1;
        }

        .card:hover::before {
            opacity: 0.3;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .card-header h2 {
            font-family: 'Rubik', sans-serif;
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(to right, var(--primary), var(--secondary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin: 0;
            animation: textPop 1.5s ease-in-out infinite alternate;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-family: 'Rubik', sans-serif;
            font-size: 1.4rem;
            font-weight: 600;
            background: linear-gradient(to right, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-top: 30px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 10px;
            color: var(--accent);
        }

        .table {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .table thead {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
        }

        .table th, .table td {
            padding: 15px;
            vertical-align: middle;
        }

        .table tbody tr {
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .table tbody tr:hover {
            background: var(--background);
            transform: translateX(5px);
        }

        .btn-primary {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.4s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #6d28d9, #db2777);
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(124, 58, 237, 0.5);
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:active {
            transform: scale(1);
            box-shadow: 0 0 10px rgba(124, 58, 237, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(90deg, var(--text), #4b5563);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-secondary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.4s ease;
        }

        .btn-secondary:hover {
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(30, 41, 59, 0.5);
        }

        .btn-secondary:hover::before {
            left: 100%;
        }

        .btn-danger {
            background: linear-gradient(90deg, var(--error), #b91c1c);
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            font-weight: 500;
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-danger::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.4s ease;
        }

        .btn-danger:hover {
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.5);
        }

        .btn-danger:hover::before {
            left: 100%;
        }

        .modal-content {
            border-radius: 16px;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(240, 245, 255, 0.95));
            box-shadow: 0 12px 40px rgba(0, 50, 120, 0.2);
            animation: fadeInUp 0.5s ease-out;
        }

        .modal-header {
            border-bottom: none;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }

        .modal-title {
            font-weight: 600;
        }

        .modal-body {
            padding: 20px;
        }

        .form-label {
            font-weight: 500;
            color: var(--text);
        }

        .form-control, .form-control:focus {
            border-radius: 8px;
            border: 1px solid #d1d9e6;
            background: #f8fafc;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 8px rgba(124, 58, 237, 0.2);
        }

        .modal-footer {
            border-top: none;
            padding: 15px 20px;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes gentleDrift {
            0% { background-position: 0 0; }
            100% { background-position: 250px 250px; }
        }

        @keyframes textPop {
            from { transform: scale(1); text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
            to { transform: scale(1.02); text-shadow: 0 3px 6px rgba(0, 0, 0, 0.15); }
        }

        @media (max-width: 992px) {
            .container {
                max-width: 100%;
                padding: 0 15px;
            }
            .card {
                padding: 30px;
            }
            .table th, .table td {
                padding: 10px;
            }
        }

        @media (max-width: 768px) {
            .container {
                margin-top: 60px;
            }
            .card {
                border-radius: 16px;
            }
            .card-header h2 {
                font-size: 2rem;
            }
            .table {
                font-size: 0.9rem;
            }
            .btn-primary, .btn-secondary, .btn-danger {
                padding: 8px 15px;
            }
        }

        @media (max-width: 576px) {
            .container {
                margin-top: 40px;
            }
            .card {
                padding: 20px;
                border-radius: 12px;
            }
            .card-header {
                flex-direction: column;
                gap: 15px;
            }
            .card-header h2 {
                font-size: 1.8rem;
            }
            .table-responsive {
                border-radius: 8px;
            }
            .modal-dialog {
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Manage Users</h2>
                <a href="admin_dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
            
            <!-- Patients Section -->
            <h6 class="section-title"><i class="fas fa-user-injured"></i>Patients</h6>
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal" onclick="setAddForm('patients')"><i class="fas fa-plus"></i> Add Patient</button>
            <div class="table-responsive">
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
                            <th>Allergies</th>
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
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Doctors Section -->
            <h6 class="section-title"><i class="fas fa-user-md"></i>Doctors</h6>
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal" onclick="setAddForm('doctors')"><i class="fas fa-plus"></i> Add Doctor</button>
            <div class="table-responsive">
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
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pharmacists Section -->
            <h6 class="section-title"><i class="fas fa-prescription-bottle-alt"></i>Pharmacists</h6>
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal" onclick="setAddForm('pharmacists')"><i class="fas fa-plus"></i> Add Pharmacist</button>
            <div class="table-responsive">
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
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <a href="logout.php" class="btn btn-danger mt-4"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
                            <label class="form-label"><i class="fas fa-user me-2"></i>Full Name</label>
                            <input type="text" class="form-control" name="fullname" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-envelope me-2"></i>Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-phone me-2"></i>Contact No</label>
                            <input type="text" class="form-control" name="contact">
                        </div>
                        <div class="mb-3" id="dobField" style="display:none;">
                            <label class="form-label"><i class="fas fa-calendar-alt me-2"></i>Date of Birth</label>
                            <input type="date" class="form-control" name="dob">
                        </div>
                        <div class="mb-3" id="addressField" style="display:none;">
                            <label class="form-label"><i class="fas fa-map-marker-alt me-2"></i>Address</label>
                            <textarea class="form-control" name="address"></textarea>
                        </div>
                        <div class="mb-3" id="specializationField" style="display:none;">
                            <label class="form-label"><i class="fas fa-stethoscope me-2"></i>Specialization</label>
                            <input type="text" class="form-control" name="specialization">
                        </div>
                        <div class="mb-3" id="licenseField" style="display:none;">
                            <label class="form-label"><i class="fas fa-id-card me-2"></i>License No</label>
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
