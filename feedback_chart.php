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

$role = $_SESSION['role'];

// Fetch feedback count by user_id or date
$stmt = $conn->prepare("SELECT user_id, COUNT(*) as count, DATE(submit_date) as submit_date FROM feedback GROUP BY user_id, DATE(submit_date) ORDER BY submit_date DESC");
$stmt->execute();
$result = $stmt->get_result();
$feedbackData = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// Prepare chart data
$chartLabels = array_map(function($row) {
    return $row['user_id'] . ' (' . $row['submit_date'] . ')';
}, $feedbackData);
$chartData = array_column($feedbackData, 'count');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Chart</title>
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
        .chart-container {
            position: relative;
            height: 400px;
            margin-top: 20px;
        }
        .btn-primary {
            background: linear-gradient(to right, #007bff, #00c4b4);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
        }
        .btn-primary:hover {
            background: linear-gradient(to right, #0056b3, #00a896);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Feedback Chart</h2>
            </div>
            <?php if (empty($feedbackData)) { ?>
                <p>No feedback data available for charting.</p>
            <?php } else { ?>
                <div class="chart-container">
                    <canvas id="feedbackChart"></canvas>
                </div>
            <?php } ?>
            <a href="submit_feedback.php" class="btn btn-primary mt-4"><i class="fas fa-arrow-left"></i> Back to Feedback</a>
        </div>
    </div>
    <script>
        const ctx = document.getElementById('feedbackChart').getContext('2d');
        const feedbackChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'Number of Feedback Submissions',
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
                            text: 'Number of Feedback'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'User ID (Date)'
                        }
                    }
                }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>