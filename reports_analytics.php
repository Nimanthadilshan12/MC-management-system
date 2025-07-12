<?php
session_start();
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
$report_date = isset($_GET['report_date']) ? $_GET['report_date'] : date('Y-m-d');
$action = isset($_GET['action']) ? $_GET['action'] : '';
$code = isset($_GET['code']) ? $_GET['code'] : '';

// Unique PDF security hash
$secret_salt = 'medicine_purchases_secret_key_2025';
$unique_code = hash('sha256', 'medicine_purchases_' . $report_date . '_' . $secret_salt);

// Handle dispensing logic
if (isset($_POST['dispense_medicine'])) {
    $medicine_id = $_POST['MedicineID'];
    $dispense_quantity = (int)$_POST['DispenseQuantity'];

    if ($dispense_quantity <= 0) {
        $message = "Invalid quantity.";
    } else {
        $stmt = $conn->prepare("SELECT Quantity FROM medicines WHERE MedicineID = ?");
        $stmt->bind_param("i", $medicine_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($dispense_quantity > $row['Quantity']) {
                $message = "Not enough stock.";
            } else {
                $new_qty = $row['Quantity'] - $dispense_quantity;
                $update = $conn->prepare("UPDATE medicines SET Quantity = ? WHERE MedicineID = ?");
                $update->bind_param("ii", $new_qty, $medicine_id);
                $update->execute();
                $update->close();

                $log = $conn->prepare("INSERT INTO dispense_log (MedicineID, QuantityDispensed, DispensedBy,PatientID) VALUES (?, ?, ?,?)");
                $log->bind_param("iiss", $medicine_id, $dispense_quantity, $_SESSION['UserID'], $_POST['PatientID']);
                
                $log->execute();
                $log->close();

                $message = "Successfully dispensed $dispense_quantity units.";
            }
        } else {
            $message = "Medicine not found.";
        }
        $stmt->close();
    }
}

// Handle PDF export
if ($action === 'pdf') {
    if ($code !== $unique_code) {
        die("Invalid access code.");
    }

    require_once('fpdf/fpdf.php');
    $stmt = $conn->prepare("
        SELECT DISTINCT pa.UserID, pa.Fullname, pa.Email, pa.Contact_No
        FROM dispense_log d
        INNER JOIN patients pa ON d.PatientID = pa.UserID
        WHERE DATE(d.DispenseDate) = ?
        ORDER BY pa.Fullname ASC
    ");
    $stmt->bind_param("s", $report_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,10, "Purchasers List for " . date("d M Y", strtotime($report_date)), 0, 1, 'C');
    $pdf->Ln(10);

    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(10,10, '#', 1);
    $pdf->Cell(60,10, 'Full Name', 1);
    $pdf->Cell(70,10, 'Email', 1);
    $pdf->Cell(40,10, 'Contact No', 1);
    $pdf->Ln();

    $pdf->SetFont('Arial','',12);
    $counter = 1;
    while ($row = $result->fetch_assoc()) {
        $pdf->Cell(10,10, $counter++, 1);
        $pdf->Cell(60,10, $row['Fullname'], 1);
        $pdf->Cell(70,10, $row['Email'], 1);
        $pdf->Cell(40,10, $row['Contact_No'], 1);
        $pdf->Ln();
    }

    $stmt->close();
    $conn->close();
    $pdf->Output('I', 'Purchasers_' . $report_date . '.pdf');
    exit;
}

// Daily medicine usage report
$stmt = $conn->prepare("
    SELECT m.MedicineID, m.Name, SUM(d.QuantityDispensed) AS TotalUsed
    FROM dispense_log d
    INNER JOIN medicines m ON d.MedicineID = m.MedicineID
    WHERE DATE(d.DispenseDate) = ?
    GROUP BY m.MedicineID, m.Name
    ORDER BY m.Name ASC
");
$stmt->bind_param("s", $report_date);
$stmt->execute();
$usage_result = $stmt->get_result();
$stmt->close();


// Fetch medicines for dropdown
$medicines = $conn->query("SELECT MedicineID, Name, Quantity FROM medicines ORDER BY Name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Reports & Dispensing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Poppins', 'Segoe UI', sans-serif;
        background: linear-gradient(135deg, #829cf0ff, #b9d1ff, #e6f0ff);
        min-height: 100vh;
        overflow-x: hidden;
        position: relative;
        padding: 40px 20px;
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
        max-width: 1000px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
    }

    h2 {
        font-weight: 900;
        font-size: 2.2rem;
        background: linear-gradient(to right, #007bff, #00c4b4);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        animation: fadeInUp 0.7s ease-out;
        margin-bottom: 30px;
        text-align: center;
    }

    .btn-primary {
        display: inline-block;
        padding: 10px 25px;
        background: linear-gradient(to right, #007bff, #00c4b4);
        color: #fff;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        border: none;
        transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
        position: relative;
        overflow: hidden;
    }

    .btn-primary:hover {
        background: linear-gradient(to right, #0056b3, #00a896);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 123, 255, 0.3);
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

    .btn-primary:hover::before {
        left: 100%;
    }

    table {
        width: 100%;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-top: 20px;
        animation: fadeInUp 0.6s ease;
    }

    th, td {
        text-align: left;
        padding: 12px 20px;
        vertical-align: middle;
    }

    thead {
        background: #f0f4ff;
        border-bottom: 2px solid #007bff;
    }

    .form-control {
        max-width: 200px;
        display: inline-block;
        margin-right: 15px;
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size: 1rem;
        transition: border 0.3s ease, box-shadow 0.3s ease;
    }

    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 8px rgba(0, 123, 255, 0.2);
        outline: none;
    }

    .section {
        margin-bottom: 50px;
    }

    /* Animations */
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

    /* Responsive */
    @media (max-width: 768px) {
        h2 {
            font-size: 1.8rem;
        }

        .form-control {
            width: 100%;
            margin: 10px 0;
        }

        table {
            font-size: 0.95rem;
        }
    }

    @media (max-width: 480px) {
        h2 {
            font-size: 1.6rem;
        }

        th, td {
            padding: 10px;
        }

        .btn-primary {
            padding: 10px 20px;
            font-size: 0.95rem;
        }
    }
</style>

</head>
<body>
<div class="container">
    <h2> Dispensing & Reports</h2>
    <div class="d-flex justify-content-end mb-4">
    <a href="pharmacist_dashboard.php" class="btn" style="
        background: linear-gradient(to right, #007bff, #00c4b4);
        border-radius: 20px;
        border-radius: 8px;
        padding: 10px 20px;
        color: white;
        font-weight: 800;
        text-decoration: none;
    ">
        ← Back to Dashboard
    </a>
</div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Dispense Medicine Form -->
    <div class="section">
        <h4>Dispense Medicine</h4>
        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label for="MedicineID" class="form-label">Select Medicine</label>
                <select name="MedicineID" id="MedicineID" class="form-select" required>
                    <option value="" disabled selected>Choose...</option>
                    <?php while ($med = $medicines->fetch_assoc()): ?>
                        <option value="<?= $med['MedicineID'] ?>">
                            <?= htmlspecialchars($med['Name']) ?> (Stock: <?= $med['Quantity'] ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-6">
            <label for="PatientID" class="form-label">Patient ID</label>
            <input type="text" name="PatientID" id="PatientID" class="form-control" required />
        </div>
            <div class="col-md-4">
                <label for="DispenseQuantity" class="form-label">Quantity</label>
                <input type="number" name="DispenseQuantity" id="DispenseQuantity" class="form-control" min="1" required />
            </div>
            <div class="col-md-2 align-self-end">
                <button type="submit" name="dispense_medicine" class="btn btn-primary w-100">Dispense</button>
            </div>
        </form>
    </div>

    <!-- Daily Usage Report -->
    <div class="section">
        <h4>Daily Medicine Usage Report</h4>
        <form method="get" class="d-flex align-items-center gap-3 mb-3">
            <label for="report_date">Select Date:</label>
            <input type="date" name="report_date" id="report_date" class="form-control" value="<?= htmlspecialchars($report_date) ?>" max="<?= date('Y-m-d') ?>" required />
            <button type="submit" class="btn btn-primary">Show Report</button>
        </form>

        <?php if ($usage_result->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Medicine ID</th>
                        <th>Medicine Name</th>
                        <th>Quantity Used</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $usage_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['MedicineID'] ?></td>
                            <td><?= htmlspecialchars($row['Name']) ?></td>
                            <td><?= $row['TotalUsed'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <a href="?report_date=<?= urlencode($report_date) ?>&action=pdf&code=<?= urlencode($unique_code) ?>" class="btn btn-primary mt-3" target="_blank">
                <i class="fas fa-file-pdf"></i> Download Purchasers List (PDF)
            </a>
        <?php else: ?>
            <p>No usage data found for <?= htmlspecialchars($report_date) ?>.</p>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
