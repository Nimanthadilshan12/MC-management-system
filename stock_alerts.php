<?php
session_start();
if (!isset($_SESSION['UserID']) || $_SESSION['role'] !== 'Pharmacist') {
    header("Location: ../index.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "mc1");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = isset($_GET['message']) ? urldecode($_GET['message']) : "";

// Delete medicine
if (isset($_POST['delete_medicine'])) {
    $id = $_POST['MedicineID'];
    $stmt = $conn->prepare("DELETE FROM medicines WHERE MedicineID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: pharmacist_dashboard.php?message=" . urlencode("Medicine deleted successfully!"));
    exit;
}

// Dispense medicine
if (isset($_POST['dispense_medicine'])) {
    $id = $_POST['MedicineID'];
    $qty = trim($_POST['DispenseQuantity']);

    if (!is_numeric($qty) || $qty <= 0) {
        $message = "Invalid quantity.";
    } else {
        $stmt = $conn->prepare("SELECT Name, Quantity FROM medicines WHERE MedicineID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $available = $row['Quantity'];
        $medName = $row['Name'];

        if ($qty > $available) {
            $message = "Only $available units in stock.";
        } else {
            // Update stock
            $newQty = $available - $qty;
            $stmt = $conn->prepare("UPDATE medicines SET Quantity=? WHERE MedicineID=?");
            $stmt->bind_param("ii", $newQty, $id);
            $stmt->execute();
            $stmt->close();

            // Log dispense activity
            $stmt = $conn->prepare("INSERT INTO dispense_log (MedicineID, QuantityDispensed, DispensedBy) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $id, $qty, $_SESSION['UserID']);
            $stmt->execute();
            $stmt->close();


            // Update dosage in prescriptions
            $stmt = $conn->prepare("UPDATE prescriptions SET Dosage = GREATEST(Dosage - ?, 0) WHERE Medication = ? AND Status = 'Pending'");
            $stmt->bind_param("is", $qty, $medName);
            $stmt->execute();
            $stmt->close();

            $message = "Dispensed $qty units and updated prescription dosages.";
        }
    }
    header("Location: pharmacist_dashboard.php?message=" . urlencode($message));
    exit;
}

// Fetch data
$medicines = $conn->query("SELECT * FROM medicines ORDER BY Name");
$prescriptions = $conn->query("SELECT p.*, d.Fullname AS DoctorName, pa.Fullname AS PatientName
    FROM prescriptions p
    LEFT JOIN doctors d ON p.DoctorID = d.UserID
    LEFT JOIN patients pa ON p.PatientID = pa.UserID
    WHERE p.Status = 'Pending' ORDER BY p.DateIssued DESC");

// Alerts
$alerts = [];
$tomorrow = date("Y-m-d", strtotime("+1 day"));
$out = $conn->query("SELECT Name FROM medicines WHERE Quantity = 0");
while ($r = $out->fetch_assoc()) $alerts[] = "🔴 <strong>{$r['Name']}</strong> is out of stock!";
$low = $conn->query("SELECT Name, Quantity FROM medicines WHERE Quantity > 0 AND Quantity <= 50");
while ($r = $low->fetch_assoc()) $alerts[] = "🟠 <strong>{$r['Name']}</strong> is low ({$r['Quantity']} left).";
$exp = $conn->query("SELECT Name, ExpiryDate FROM medicines WHERE ExpiryDate = '$tomorrow'");
while ($r = $exp->fetch_assoc()) $alerts[] = "⚠ <strong>{$r['Name']}</strong> expires tomorrow ({$r['ExpiryDate']}).";
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pharmacist Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Poppins', 'Segoe UI', sans-serif;
        background: linear-gradient(to right, rgb(186, 233, 239), #e3f2fd);
        min-height: 100vh;
        overflow-x: hidden;
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
        padding: 40px 20px;
        max-width: 1100px;
        margin: auto;
        position: relative;
        z-index: 1;
    }

    .card {
        background: linear-gradient(135deg, rgba(156, 151, 151, 0.05), #f5f9ff);
        border-radius: 20px;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
        padding: 40px;
        margin-bottom: 50px;
        animation: fadeInUp 0.6s ease;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 60px rgba(0, 50, 120, 0.2), 0 6px 20px rgba(0, 0, 0, 0.08);
    }

    h2 {
        background: linear-gradient(to right, #007bff, #00bcd4);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        font-weight: 700;
        text-align: center;
        margin-bottom: 20px;
        font-size: 2.2rem;
        animation: textGlow 2s ease-in-out infinite alternate;
    }

    .alert ul {
        padding-left: 1.2rem;
    }

    .alert li {
        margin-bottom: 6px;
        line-height: 1.5;
    }

    .table {
        background: white;
        border-radius: 10px;
        margin-top: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        width: 100%;
        overflow-x: auto;
        animation: fadeInUp 0.5s ease;
    }

    .table th, .table td {
        padding: 12px 16px;
        vertical-align: middle;
    }

    .dispense-form {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .dispense-input {
        width: 70px;
        padding: 5px 8px;
        border-radius: 8px;
        border: 1px solid #ccc;
        transition: border 0.3s ease, box-shadow 0.3s ease;
    }

    .dispense-input:focus {
        border-color: #007bff;
        box-shadow: 0 0 8px rgba(0, 123, 255, 0.2);
        outline: none;
    }

    .btn-primary, .btn-danger, .btn-back {
        border: none;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 500;
        transition: 0.3s;
        padding: 8px 16px;
        position: relative;
        overflow: hidden;
    }

    .btn-primary {
        background: linear-gradient(to right, #007bff, #00c4b4);
        color: #fff;
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
    }

    .btn-primary:hover {
        background: linear-gradient(to right, #0056b3, #00a896);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 123, 255, 0.3);
    }

    .btn-primary::before,
    .btn-danger::before,
    .btn-back::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.4s ease;
    }

    .btn-primary:hover::before,
    .btn-danger:hover::before,
    .btn-back:hover::before {
        left: 100%;
    }

    .btn-danger {
        background: linear-gradient(to right, #dc3545, #c82333);
        color: #fff;
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
    }

    .btn-danger:hover {
        background: linear-gradient(to right, #c82333, #a71d2a);
        transform: scale(1.05);
    }

    .btn-back {
        background: linear-gradient(to right, #007bff, #00c4b4);
        color: white;
        padding: 10px 20px;
        margin-bottom: 20px;
        display: inline-block;
        text-decoration: none;
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
    }

    .btn-back:hover {
        background: linear-gradient(to right, #0056b3, #00a896);
        transform: translateY(-2px);
    }

    .status-pending {
        background-color: rgb(237, 187, 21);
        color: rgb(133, 4, 21);
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 0.85rem;
        font-weight: 500;
        animation: fadeIn 0.4s ease;
    }

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
        h2 {
            font-size: 1.9rem;
        }

        .btn-primary, .btn-danger, .btn-back {
            padding: 10px 15px;
        }

        .dispense-input {
            width: 60px;
        }
    }

    @media (max-width: 480px) {
        .container {
            padding: 20px 15px;
        }

        h2 {
            font-size: 1.7rem;
        }

        .card {
            padding: 20px;
        }

        .btn-primary, .btn-danger, .btn-back {
            font-size: 0.85rem;
        }

        .dispense-form {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }

        .dispense-input {
            width: 50px;
        }
    }
</style>

</head>
<body>
<div class="container">
    <a href="pharmacist_dashboard.php" class="btn-back">
        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
    </a>

    <?php if (!empty($alerts)): ?>
        <div class="alert alert-warning shadow-sm p-3 rounded-4 mb-4">
            <h5 class="text-warning"><i class="fas fa-bell me-2"></i>Attention Required</h5>
            <ul>
                <?php foreach ($alerts as $a): ?><li><?php echo $a; ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-pills me-2"></i>Medicine Stock</h2>
        <?php if ($message): ?>
            <div class="alert alert-<?php echo (strpos($message, 'successfully') !== false || strpos($message, 'Dispensed') !== false) ? 'success' : 'danger'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <?php if ($medicines->num_rows > 0): ?>
            <table class="table table-bordered table-hover">
                <thead>
                    <tr><th>ID</th><th>Name</th><th>Quantity</th><th>Expiry</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php while ($row = $medicines->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['MedicineID']; ?></td>
                            <td><?php echo htmlspecialchars($row['Name']); ?></td>
                            <td><?php echo $row['Quantity']; ?></td>
                            <td><?php echo $row['ExpiryDate'] ?: 'N/A'; ?></td>
                            <td>
                                <form method="post" class="dispense-form d-inline">
                                    <input type="hidden" name="MedicineID" value="<?php echo $row['MedicineID']; ?>">
                                    <input type="number" name="DispenseQuantity" class="dispense-input" min="1" placeholder="Qty" required>
                                    <button type="submit" name="dispense_medicine" class="btn btn-primary btn-sm">Dispense</button>
                                </form>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="MedicineID" value="<?php echo $row['MedicineID']; ?>">
                                    <button type="submit" name="delete_medicine" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No medicines found in the stock.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2><i class="fas fa-prescription-bottle-alt me-2"></i>Pending Prescriptions</h2>
        <?php if ($prescriptions->num_rows > 0): ?>
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#ID</th><th>Patient</th><th>Doctor</th>
                        <th>Medication</th><th>Dosage</th><th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $prescriptions->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $row['PrescriptionID']; ?></strong></td>
                            <td><?php echo htmlspecialchars($row['PatientName']); ?></td>
                            <td><?php echo htmlspecialchars($row['DoctorName']); ?></td>
                            <td><?php echo htmlspecialchars($row['Medication']); ?></td>
                            <td><?php echo htmlspecialchars($row['Dosage']); ?>
                            <?php if (isset($row['DosageAmount'])): ?>
                            <br><small class="text-muted">(Remaining: <?php echo $row['DosageAmount']; ?>)</small>
                            <?php endif; ?>
                            </td>
                            <td><?php echo date("d M Y", strtotime($row['DateIssued'])); ?></td>
                           
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted text-center"><i class="fas fa-clipboard-list me-2"></i>No pending prescriptions found.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
