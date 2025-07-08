<?php
session_start();
if (!isset($_SESSION['UserID']) || !in_array($_SESSION['role'], ['Admin', 'Patient']) || !isset($_SESSION['report_data'])) {
    header("Location: ../../login.php");
    exit;
}

$reportData = $_SESSION['report_data'];
$UserID = $reportData['UserID'];
$role = $reportData['role'];
$visitCount = $reportData['visitCount'];
$commonDiagnosis = $reportData['commonDiagnosis'];
$commonTreatment = $reportData['commonTreatment'];
$history = $reportData['history'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical History Analysis Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            background: #fff;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 2rem;
            color: #007bff;
        }
        .header h3 {
            font-size: 1.2rem;
            color: #333;
        }
        .summary {
            margin-bottom: 20px;
        }
        .summary ul {
            list-style: none;
            padding: 0;
        }
        .summary li {
            margin-bottom: 10px;
            font-size: 1rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f0f4ff;
            font-weight: 600;
        }
        .instructions {
            background: #e0e7ff;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .btn-primary {
            background: linear-gradient(to right, #007bff, #00c4b4);
            border: none;
            padding: 10px 20px;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary:hover {
            background: linear-gradient(to right, #0056b3, #00a896);
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Medical History Analysis Report</h1>
            <h3>For User ID: <?php echo htmlspecialchars($UserID); ?></h3>
            <p>Generated on: Tuesday, July 08, 2025, 10:09 AM +0530</p>
            <p>Role: <?php echo htmlspecialchars($role); ?></p>
        </div>
        <div class="instructions no-print">
            <p><strong>Instructions:</strong> To save this report as a PDF, click "Print" or press Ctrl+P, then select "Save as PDF" or "Microsoft Print to PDF" in your browser’s print dialog.</p>
        </div>
        <div class="summary">
            <h2>Summary</h2>
            <ul>
                <li><strong>Total Number of Visits:</strong> <?php echo htmlspecialchars($visitCount); ?></li>
                <li><strong>Most Common Diagnosis:</strong> <?php echo htmlspecialchars($commonDiagnosis); ?></li>
                <li><strong>Most Common Treatment:</strong> <?php echo htmlspecialchars($commonTreatment); ?></li>
            </ul>
        </div>
        <h2>Visit History</h2>
        <table>
            <tr>
                <th>Visit Date</th>
                <th>Diagnosis</th>
                <th>Treatment</th>
            </tr>
            <?php foreach ($history as $record) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($record['visit_date']); ?></td>
                    <td><?php echo htmlspecialchars($record['diagnosis']); ?></td>
                    <td><?php echo htmlspecialchars($record['treatment']); ?></td>
                </tr>
            <?php } ?>
        </table>
        <div class="no-print">
            <a href="patient_history.php" class="btn btn-primary">Back to Analysis</a>
        </div>
    </div>
</body>
</html>