<?php
session_start();

// Check if user is logged in and is a pharmacist
if (!isset($_SESSION['UserID']) || $_SESSION['role'] !== 'Pharmacist') {
    header("Location: ../index.php");
    exit;
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "mc1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch pending prescriptions
$sql = "SELECT p.PrescriptionID, p.PatientID, p.DoctorID, p.Medication, p.Dosage, p.DateIssued,
               d.Fullname AS DoctorName, pa.Fullname AS PatientName
        FROM prescriptions p
        LEFT JOIN doctors d ON p.DoctorID = d.UserID
        LEFT JOIN patients pa ON p.PatientID = pa.UserID
        WHERE p.Status = 'Pending'
        ORDER BY p.DateIssued DESC";

$result = $conn->query($sql);
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pending Prescriptions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
        font-family: 'Poppins', 'Segoe UI', sans-serif;
        background: linear-gradient(to right, #e0f7fa, #e3f2fd);
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
        background-image: url('https://images.unsplash.com/photo-1506784983877-45594efa4cbe');
        background-repeat: repeat;
        background-size: 300px;
        opacity: 0.03;
        z-index: -1;
        animation: gentleDrift 30s linear infinite;
    }

    .container {
        margin-top: 80px;
        max-width: 1000px;
        margin-left: auto;
        margin-right: auto;
        padding: 0 20px;
        position: relative;
        z-index: 1;
    }

    .card {
        background: linear-gradient(135deg, #ffffff, #f5f9ff);
        border-radius: 20px;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
        padding: 40px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        animation: fadeInUp 0.6s ease-out;
    }

    .card:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 60px rgba(0, 123, 255, 0.1);
    }

    h2 {
        color: #007bff;
        font-weight: 700;
        font-size: 2.2rem;
        text-align: center;
        margin-bottom: 30px;
        background: linear-gradient(to right, #007bff, #00bcd4);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        animation: textGlow 1.8s ease-in-out infinite alternate;
    }

    .table {
        width: 100%;
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        margin-top: 20px;
    }

    .table th, .table td {
        vertical-align: middle;
        padding: 12px 16px;
        font-size: 0.95rem;
    }

    .badge-pill {
        padding: 6px 14px;
        font-size: 0.85rem;
        border-radius: 30px;
        font-weight: 500;
    }

    .status-pending {
        background-color: #fff3cd;
        color: #856404;
        box-shadow: inset 0 0 5px rgba(255, 223, 100, 0.4);
    }

    .btn-back {
        display: inline-block;
        background: linear-gradient(to right, #007bff, #00c4b4);
        color: white;
        border-radius: 10px;
        text-decoration: none;
        padding: 12px 20px;
        font-size: 1rem;
        font-weight: 500;
        margin-top: 30px;
        transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        box-shadow: 0 4px 10px rgba(0, 123, 255, 0.2);
        position: relative;
        overflow: hidden;
    }

    .btn-back:hover {
        background: linear-gradient(to right, #0056b3, #00a896);
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(0, 123, 255, 0.3);
    }

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

    .btn-back:hover::before {
        left: 100%;
    }

    /* Animations */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes textGlow {
        from { text-shadow: 0 0 5px rgba(0, 123, 255, 0.3); }
        to { text-shadow: 0 0 12px rgba(0, 123, 255, 0.5); }
    }

    @keyframes gentleDrift {
        0% { background-position: 0 0; }
        100% { background-position: 300px 300px; }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .container {
            padding: 0 15px;
        }
        .card {
            padding: 30px;
        }
        h2 {
            font-size: 1.8rem;
        }
        .btn-back {
            width: 100%;
            text-align: center;
            padding: 12px 16px;
            margin-top: 20px;
        }
        .table {
            font-size: 0.9rem;
        }
    }

    @media (max-width: 480px) {
        .card {
            padding: 20px;
        }
        h2 {
            font-size: 1.6rem;
        }
        .btn-back {
            font-size: 0.9rem;
        }
        .table th, .table td {
            padding: 10px 12px;
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <a href="pharmacist_dashboard.php" class="btn-back mb-4 d-inline-block"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
        <div class="card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2><i class="fas fa-prescription-bottle-alt me-2"></i>Pending Prescriptions</h2>
                <span class="badge bg-primary text-white fs-6"><i class="fas fa-clock me-1"></i> <?php echo date("F j, Y"); ?></span>
            </div>
            <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#ID</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Medication</th>
                                <th>Dosage</th>
                                <th>Date Issued</th>
                                
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $row['PrescriptionID']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['PatientName']); ?> <small class="text-muted">(<?php echo $row['PatientID']; ?>)</small></td>
                                    <td><?php echo htmlspecialchars($row['DoctorName']); ?> <small class="text-muted">(<?php echo $row['DoctorID']; ?>)</small></td>
                                    <td><?php echo htmlspecialchars($row['Medication']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Dosage']); ?></td>
                                    <td><?php echo date("d M Y", strtotime($row['DateIssued'])); ?></td>
                                    
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center fs-5 text-muted mt-4"><i class="fas fa-clipboard-list me-2"></i>No pending prescriptions found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
