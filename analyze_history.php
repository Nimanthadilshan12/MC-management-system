<?php
session_start();
if (!isset($_SESSION['UserID']) || !in_array($_SESSION['role'], ['Admin', 'Patient'])) {
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
$role = $_SESSION['role'];

if ($role === 'Patient') {
    $stmt = $conn->prepare("SELECT visit_date, diagnosis, treatment FROM patient_history WHERE patient_id = ?");
    $stmt->bind_param("s", $UserID);
} else { // Admin (can view any patient's history if needed; modify query as per requirement)
    $stmt = $conn->prepare("SELECT visit_date, diagnosis, treatment FROM patient_history WHERE patient_id = ?");
    $stmt->bind_param("s", $UserID); // For now, limit to the logged-in user's ID; adjust for all patients if needed
}
$stmt->execute();
$result = $stmt->get_result();
$history = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// Analyze data
$visitCount = count($history);
$diagnoses = array_count_values(array_column($history, 'diagnosis'));
$commonDiagnosis = !empty($diagnoses) ? array_search(max($diagnoses), $diagnoses) : 'None';
$treatments = array_count_values(array_column($history, 'treatment'));
$commonTreatment = !empty($treatments) ? array_search(max($treatments), $treatments) : 'None';

// Prepare data for charts
$visitsByMonth = [];
foreach ($history as $record) {
    $month = date('Y-m', strtotime($record['visit_date']));
    $visitsByMonth[$month] = ($visitsByMonth[$month] ?? 0) + 1;
}
$chartLabels = array_keys($visitsByMonth);
$chartData = array_values($visitsByMonth);

// Prepare pie chart data (top 5 diagnoses or all if fewer)
$pieLabels = array_keys($diagnoses);
$pieData = array_values($diagnoses);
$pieColors = [
    'rgba(255, 99, 132, 0.6)',
    'rgba(54, 162, 235, 0.6)',
    'rgba(255, 206, 86, 0.6)',
    'rgba(75, 192, 192, 0.6)',
    'rgba(153, 102, 255, 0.6)'
];

// LaTeX content for PDF
$latexContent = <<<LATEX
\documentclass[a4paper,12pt]{article}
\usepackage[utf8]{inputenc}
\usepackage[T1]{fontenc}
\usepackage{geometry}
\geometry{a4paper, margin=1in}
\usepackage{booktabs}
\usepackage{fancyhdr}
\pagestyle{fancy}
\fancyhf{}
\rhead{Medical History Analysis - $role}
\lhead{Generated on: Tuesday, July 01, 2025, 03:04 PM +0530}
\cfoot{\thepage}

\begin{document}

\begin{center}
    \textbf{\Large Medical History Analysis Report}
    \vspace{0.5cm}
    \textit{For User ID: $UserID}
\end{center}

\section*{Summary}
\begin{itemize}
    \item Total Number of Visits: $visitCount
    \item Most Common Diagnosis: $commonDiagnosis
    \item Most Common Treatment: $commonTreatment
\end{itemize}

\end{document}
LATEX;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analyze My History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .summary-item {
            margin-bottom: 15px;
        }
        .chart-container {
            position: relative;
            height: 400px;
            margin-top: 20px;
        }
        .btn-primary {
            background: linear-gradient(to right, #007bff, #00c4b4);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(to right, #0056b3, #00a896);
        }
        .chart-buttons {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Analyze My History</h2>
            </div>
            <?php if (empty($history)) { ?>
                <p>No history available for analysis.</p>
            <?php } else { ?>
                <div class="summary">
                    <div class="summary-item">
                        <strong>Total Visits:</strong> <?php echo htmlspecialchars($visitCount); ?>
                    </div>
                    <div class="summary-item">
                        <strong>Most Common Diagnosis:</strong> <?php echo htmlspecialchars($commonDiagnosis); ?>
                    </div>
                    <div class="summary-item">
                        <strong>Most Common Treatment:</strong> <?php echo htmlspecialchars($commonTreatment); ?>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="analysisChart"></canvas>
                </div>
                <div class="chart-buttons">
                    <button id="showBarChart" class="btn btn-primary me-2"><i class="fas fa-chart-bar"></i> Show in Bar Chart</button>
                    <button id="showPieChart" class="btn btn-primary"><i class="fas fa-chart-pie"></i> Show in Pie Chart</button>
                </div>
            <?php } ?>
            <div class="d-flex justify-content-between mt-4">
                <a href="patient_history.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to History</a>
                <?php if (in_array($role, ['Admin', 'Patient'])) { ?>
                    <form method="post" action="" style="display:inline;">
                        <input type="hidden" name="generate_pdf" value="1">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-file-pdf"></i> Generate PDF</button>
                    </form>
                <?php } ?>
            </div>
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_pdf'])) {
                header('Content-Type: text/latex');
                header('Content-Disposition: attachment; filename="medical_history_analysis.pdf"');
                echo $latexContent;
                exit;
            }
            ?>
        </div>
    </div>
    <script>
        let analysisChart;
        const ctx = document.getElementById('analysisChart').getContext('2d');

        function createBarChart() {
            analysisChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($chartLabels); ?>,
                    datasets: [{
                        label: 'Number of Visits',
                        data: <?php echo json_encode($chartData); ?>,
                        backgroundColor: 'rgba(0, 123, 255, 0.6)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Visits'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        }
                    }
                }
            });
        }

        function createPieChart() {
            analysisChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($pieLabels); ?>,
                    datasets: [{
                        label: 'Diagnosis Distribution',
                        data: <?php echo json_encode($pieData); ?>,
                        backgroundColor: <?php echo json_encode($pieColors); ?>,
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });
        }

        // Initialize with bar chart
        createBarChart();

        document.getElementById('showBarChart').addEventListener('click', function() {
            if (analysisChart) analysisChart.destroy();
            createBarChart();
        });

        document.getElementById('showPieChart').addEventListener('click', function() {
            if (analysisChart) analysisChart.destroy();
            createPieChart();
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>