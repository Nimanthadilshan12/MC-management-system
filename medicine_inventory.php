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
$UserID = $_SESSION['UserID'];
$message = '';

// Fetch admin details
$stmt = $conn->prepare("SELECT Fullname, Email, Contact_No FROM admins WHERE UserID = ?");
if (!$stmt) die("Prepare failed: " . $conn->error);
$stmt->bind_param("s", $UserID);
if (!$stmt->execute()) die("Execute failed: " . $stmt->error);
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if (!$user) die("No admin found with UserID: " . htmlspecialchars($UserID));
$stmt->close();

// Handle add inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $medication_name = $_POST['medication_name'];
    $quantity = $_POST['quantity'];
    $expiry_date = $_POST['expiry_date'] ?: null;
    $stmt = $conn->prepare("INSERT INTO inventory (medication_name, quantity, expiry_date) VALUES (?, ?, ?)");
    if (!$stmt) {
        $message = "Prepare failed: " . $conn->error;
    } else {
        $stmt->bind_param("sis", $medication_name, $quantity, $expiry_date);
        if ($stmt->execute()) {
            $message = "Medication added successfully!";
        } else {
            $message = "Execute failed: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle edit inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id = $_POST['edit_id'];
    $medication_name = $_POST['medication_name'];
    $quantity = $_POST['quantity'];
    $expiry_date = $_POST['expiry_date'] ?: null;
    $stmt = $conn->prepare("UPDATE inventory SET medication_name = ?, quantity = ?, expiry_date = ? WHERE id = ?");
    if (!$stmt) {
        $message = "Prepare failed: " . $conn->error;
    } else {
        $stmt->bind_param("sisi", $medication_name, $quantity, $expiry_date, $edit_id);
        if ($stmt->execute()) {
            $message = "Medication updated successfully!";
        } else {
            $message = "Execute failed: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle delete inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
    if (!$stmt) {
        $message = "Prepare failed: " . $conn->error;
    } else {
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $message = "Medication deleted successfully!";
        } else {
            $message = "Execute failed: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch inventory data
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT id, medication_name AS name, quantity, expiry_date FROM inventory";
if ($search) {
    $query .= " WHERE medication_name LIKE ?";
}
$stmt = $conn->prepare($query);
if (!$stmt) die("Prepare failed: " . $conn->error);
if ($search) {
    $search_term = "%$search%";
    $stmt->bind_param("s", $search_term);
}
if (!$stmt->execute()) die("Execute failed: " . $stmt->error);
$result = $stmt->get_result();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Inventory - University Medical Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #e0e7ff, #b9d1ff, #e6f0ff);
            min-height: 100vh;
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://images.unsplash.com/photo-1522441815192-d9f04eb0615c');
            background-repeat: repeat;
            background-size: 250px;
            opacity: 0.04;
            z-index: -1;
            animation: gentleDrift 25s linear infinite;
        }
        .container {
            margin-top: 80px;
            max-width: 1100px;
            padding: 0 20px;
        }
        .logo {
            display: block;
            max-width: 150px;
            margin: 0 auto 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 50, 120, 0.1);
            transition: transform 0.3s ease;
        }
        .logo:hover {
            transform: scale(1.05);
        }
        .card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(240, 245, 255, 0.95));
            border-radius: 20px;
            box-shadow: 0 12px 50px rgba(0, 50, 120, 0.15);
            padding: 40px;
            transition: transform 0.3s ease;
            animation: fadeInUp 0.7s ease-out;
        }
        .card:hover {
            transform: translateY(-8px);
        }
        .card-header h2 {
            font-size: 2.5rem;
            font-weight: 600;
            background: linear-gradient(to right, #007bff, #00c4b4);
            -webkit-background-clip: text;
            color: transparent;
            text-align: center;
            animation: textGlow 2s ease-in-out infinite alternate;
        }
        .welcome-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(235, 245, 255, 0.95));
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 30px rgba(0, 50, 120, 0.1);
            margin-bottom: 30px;
            border: 2px solid transparent;
        }
        .welcome-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 16px;
            padding: 2px;
            background: linear-gradient(45deg, #007bff, #00c4b4, #007bff);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: destination-out;
            mask-composite: exclude;
            z-index: -1;
        }
        .message { color: green; text-align: center; }
        .error { color: red; text-align: center; }
        .btn-primary {
            background: linear-gradient(to right, #007bff, #00c4b4);
            border: none;
            transition: transform 0.2s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(to right, #0056b3, #00a896);
            transform: translateY(-4px);
        }
        .btn-danger {
            background: linear-gradient(to right, #dc3545, #c82333);
            border: none;
        }
        .btn-danger:hover {
            background: linear-gradient(to right, #c82333, #a71d2a);
        }
        .table th {
            background: linear-gradient(to right, #007bff, #00c4b4);
            color: white;
        }
        .no-data { text-align: center; color: #666; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes textGlow {
            from { text-shadow: 0 0 5px rgba(0, 123, 255, 0.3); }
            to { text-shadow: 0 0 10px rgba(0, 123, 255, 0.5); }
        }
        @keyframes gentleDrift {
            0% { background-position: 0 0; }
            100% { background-position: 250px 250px; }
        }
        @media (max-width: 768px) {
            .container { margin-top: 60px; padding: 0 15px; }
            .card { padding: 30px; }
            .card-header h2 { font-size: 2rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="card">
            <div class="card-header">
                <h2>Medicine Inventory Management</h2>
            </div>
            <div class="welcome-card position-relative">
                <h4 class="text-center mb-4">Welcome, <?php echo htmlspecialchars($user['Fullname']); ?>!</h4>
            </div>
            <?php if ($message): ?>
                <div class="<?php echo strpos($message, 'failed') !== false ? 'error' : 'message'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <!-- Add Inventory Form -->
            <h5 class="mb-3">Add New Medication</h5>
            <form method="POST" class="mb-4">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <input type="text" name="medication_name" class="form-control" placeholder="Medication Name" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <input type="number" name="quantity" class="form-control" placeholder="Quantity" min="0" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <input type="date" name="expiry_date" class="form-control" placeholder="Expiry Date">
                    </div>
                    <div class="col-md-2 mb-3">
                        <button type="submit" name="add" class="btn btn-primary w-100">Add</button>
                    </div>
                </div>
            </form>
            <!-- Search Form -->
            <form method="GET" class="mb-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by medication name" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>
            <!-- Back Button -->
            <?php if ($search): ?>
                <a href="medicine_inventory.php" class="btn btn-primary mb-4"><i class="fas fa-arrow-left"></i> Back to Full Inventory</a>
            <?php endif; ?>
            <!-- Inventory Table -->
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Quantity</th><th>Expiry Date</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($row['expiry_date'] ?? 'N/A'); ?></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this medication?');">
                                        <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editModalLabel<?php echo $row['id']; ?>">Edit Medication</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="edit_id" value="<?php echo $row['id']; ?>">
                                                <div class="mb-3">
                                                    <label for="medication_name<?php echo $row['id']; ?>" class="form-label">Medication Name</label>
                                                    <input type="text" name="medication_name" id="medication_name<?php echo $row['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($row['name']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="quantity<?php echo $row['id']; ?>" class="form-label">Quantity</label>
                                                    <input type="number" name="quantity" id="quantity<?php echo $row['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($row['quantity']); ?>" min="0" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="expiry_date<?php echo $row['id']; ?>" class="form-label">Expiry Date</label>
                                                    <input type="date" name="expiry_date" id="expiry_date<?php echo $row['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($row['expiry_date']); ?>">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="no-data">No inventory records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <a href="admin_dashboard.php" class="btn btn-primary mt-3">Back to Dashboard</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
