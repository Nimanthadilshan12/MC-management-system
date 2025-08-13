<?php
session_start();

// Check if user is logged in and is a pharmacist
if (!isset($_SESSION['UserID']) || $_SESSION['role'] !== 'Pharmacist') {
    header("Location: ../index.php");
    exit;
}

$host = "localhost";
$db = "mc1";
$user = "root";
$pass = "";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";

// Function to validate expiry date
function validateExpiryDate($expiry_date) {
    if (empty($expiry_date)) {
        return "Expiry date is required.";
    }
    try {
        $date = new DateTime($expiry_date);
        $formatted_date = $date->format('Y-m-d');
        if ($formatted_date !== $expiry_date) {
            return "Invalid date format. Use YYYY-MM-DD.";
        }
        $current_date = new DateTime('today');
        if ($date <= $current_date) {
            return "Expiry date must be in the future.";
        }
        return true;
    } catch (Exception $e) {
        return "Invalid date format. Use YYYY-MM-DD.";
    }
}

// Handle AJAX search for medicine
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search_medicine'])) {
    $search_term = filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING);
    $stmt = $conn->prepare("
        SELECT id, medication_name, quantity, expiry_date 
        FROM inventory 
        WHERE medication_name LIKE ? AND expiry_date > CURDATE()
    ");
    $search_term = "%$search_term%";
    $stmt->bind_param("s", $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    $medicines = [];
    while ($row = $result->fetch_assoc()) {
        $medicines[] = $row;
    }
    $stmt->close();
    echo json_encode($medicines);
    $conn->close();
    exit;
}

// Handle add medicine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_medicine'])) {
    $med_name = filter_input(INPUT_POST, 'medication_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_SANITIZE_NUMBER_INT);
    $expiry_date = filter_input(INPUT_POST, 'expiry_date', FILTER_SANITIZE_STRING);

    if ($quantity <= 0) {
        $message = "Quantity must be greater than 0.";
    } else {
        $date_validation = validateExpiryDate($expiry_date);
        if ($date_validation !== true) {
            $message = $date_validation;
        } else {
            // Check for existing medicine with same name and expiry date
            $stmt = $conn->prepare("SELECT id FROM inventory WHERE medication_name = ? AND expiry_date = ?");
            $stmt->bind_param("ss", $med_name, $expiry_date);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $message = "Medicine with this name and expiry date already exists.";
            } else {
                $stmt = $conn->prepare("INSERT INTO inventory (medication_name, quantity, expiry_date) VALUES (?, ?, ?)");
                $stmt->bind_param("sis", $med_name, $quantity, $expiry_date);
                if ($stmt->execute()) {
                    $message = "Medicine added successfully!";
                } else {
                    $message = "Error adding medicine: " . $stmt->error;
                }
            }
            $stmt->close();
        }
    }
}

// Handle update medicine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_medicine'])) {
    $medicine_id = filter_input(INPUT_POST, 'medicine_id', FILTER_SANITIZE_NUMBER_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_SANITIZE_NUMBER_INT);
    $expiry_date = filter_input(INPUT_POST, 'expiry_date', FILTER_SANITIZE_STRING);

    if ($quantity < 0) {
        $message = "Quantity cannot be negative.";
    } else {
        $date_validation = validateExpiryDate($expiry_date);
        if ($date_validation !== true) {
            $message = $date_validation;
        } else {
            $stmt = $conn->prepare("UPDATE inventory SET quantity = ?, expiry_date = ?, last_updated = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("isi", $quantity, $expiry_date, $medicine_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $message = "Medicine updated successfully!";
            } else {
                $message = "Error updating medicine or medicine not found.";
            }
            $stmt->close();
        }
    }
}

// Handle remove medicine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_medicine'])) {
    $medicine_id = filter_input(INPUT_POST, 'medicine_id', FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $medicine_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message = "Medicine removed successfully!";
    } else {
        $message = "Error removing medicine or medicine not found.";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inventory - University Medical Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <style>
        body {
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #e0e7ff, #b9d1ff, #e6f0ff);
            min-height: 100vh;
            padding: 40px 20px;
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
            max-width: 900px;
            margin: 0 auto;
        }
        .card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(240, 245, 255, 0.95));
            border-radius: 20px;
            box-shadow: 0 12px 50px rgba(0, 50, 120, 0.15);
            padding: 40px;
            animation: fadeInUp 0.7s ease-out;
        }
        .card h2 {
            font-size: 2.5rem;
            font-weight: 600;
            background: linear-gradient(to right, #007bff, #00c4b4);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-align: center;
        }
        .btn-action {
            padding: 10px 25px;
            background: linear-gradient(to right, #007bff, #00c4b4);
            color: #fff;
            border-radius: 8px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
        }
        .btn-action:hover {
            background: linear-gradient(to right, #0056b3, #00a896);
            box-shadow: 0 6px 16px rgba(0, 123, 255, 0.3);
        }
        .form-control, .form-select {
            border-radius: 8px;
        }
        .section {
            margin-bottom: 30px;
        }
        .table {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes gentleDrift {
            0% { background-position: 0 0; }
            100% { background-position: 250px 250px; }
        }
        @media (max-width: 768px) {
            .card { padding: 30px; }
            .card h2 { font-size: 2rem; }
            .form-control { width: 100%; margin-bottom: 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>Manage Inventory</h2>
            <div class="d-flex justify-content-end mb-4">
                <a href="pharmacist_dashboard.php" class="btn-action"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
            </div>
            <?php if ($message): ?>
                <div class="alert alert-<?php echo strpos($message, 'successfully') !== false ? 'success' : 'danger'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <!-- Search Medicine -->
            <div class="section">
                <h5>Search Medicine</h5>
                <form>
                    <div class="input-group mb-3">
                        <input type="text" id="medicine-search" class="form-control" placeholder="Type medicine name">
                        <button type="button" id="clear-search" class="btn-action"><i class="fas fa-times me-2"></i>Clear</button>
                    </div>
                </form>
                <div id="search-results" class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Medicine Name</th>
                                <th>Quantity</th>
                                <th>Expiry Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="medicine-table"></tbody>
                    </table>
                </div>
            </div>
            <!-- Add Medicine -->
            <div class="section">
                <h5>Add New Medicine</h5>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Medicine Name</label>
                            <input type="text" name="medication_name" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="add_medicine" class="btn-action"><i class="fas fa-plus me-2"></i>Add Medicine</button>
                        </div>
                    </div>
                </form>
            </div>
            <!-- Update/Remove Medicine -->
            <div class="section">
                <h5>Update/Remove Medicine</h5>
                <form method="POST" id="update-remove-form">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Medicine ID</label>
                            <input type="number" name="medicine_id" id="update-medicine-id" class="form-control" readonly required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Medicine Name</label>
                            <input type="text" id="update-medicine-name" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" id="update-quantity" class="form-control" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" id="update-expiry-date" class="form-control" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="update_medicine" class="btn-action"><i class="fas fa-save me-2"></i>Update</button>
                            <button type="submit" name="remove_medicine" class="btn btn-danger"><i class="fas fa-trash me-2"></i>Remove</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script>
        $(document).ready(function() {
            // Autocomplete for medicine search
            $('#medicine-search').autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: '?search_medicine=true',
                        data: { term: request.term },
                        dataType: 'json',
                        success: function(data) {
                            response(data.map(item => ({
                                label: item.medication_name,
                                value: item.medication_name,
                                id: item.id,
                                quantity: item.quantity,
                                expiry_date: item.expiry_date
                            })));
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    $('#medicine-table').empty();
                    $('#medicine-table').append(`
                        <tr>
                            <td>${ui.item.id}</td>
                            <td>${ui.item.label}</td>
                            <td>${ui.item.quantity}</td>
                            <td>${ui.item.expiry_date}</td>
                            <td>
                                <button class="btn btn-action btn-sm select-medicine" 
                                        data-id="${ui.item.id}" 
                                        data-name="${ui.item.label}" 
                                        data-quantity="${ui.item.quantity}" 
                                        data-expiry="${ui.item.expiry_date}">
                                    <i class="fas fa-edit me-1"></i>Select
                                </button>
                            </td>
                        </tr>
                    `);
                }
            });

            // Handle select medicine for update/remove
            $(document).on('click', '.select-medicine', function() {
                $('#update-medicine-id').val($(this).data('id'));
                $('#update-medicine-name').val($(this).data('name'));
                $('#update-quantity').val($(this).data('quantity'));
                $('#update-expiry-date').val($(this).data('expiry'));
            });

            // Clear search
            $('#clear-search').click(function() {
                $('#medicine-search').val('');
                $('#medicine-table').empty();
                $('#update-remove-form')[0].reset();
            });

            // Client-side date validation
            document.querySelectorAll('input[type="date"]').forEach(input => {
                input.addEventListener('change', function() {
                    const today = new Date().toISOString().split('T')[0];
                    if (this.value <= today) {
                        alert('Expiry date must be in the future.');
                        this.value = '';
                    }
                });
            });
        });
    </script>
</body>
</html>