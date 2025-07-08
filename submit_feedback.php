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
$success_message = '';
$error_message = '';
$feedbackCount = 0;

// Handle feedback submission (Patients only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'Patient') {
    $feedback = filter_input(INPUT_POST, 'feedback', FILTER_SANITIZE_STRING);
    if (empty($feedback)) {
        $error_message = "Feedback is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, feedback_text, submit_date) VALUES (?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("ss", $UserID, $feedback);
            if ($stmt->execute()) {
                $success_message = "Feedback submitted successfully!";
            } else {
                $error_message = "Error submitting feedback: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Prepare failed: " . $conn->error;
        }
    }
}

// Fetch feedback count and history
if ($role === 'Patient') {
    // Patients: Count and fetch their own feedback
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM feedback WHERE user_id = ?");
    $stmt->bind_param("s", $UserID);
    $stmt->execute();
    $result = $stmt->get_result();
    $feedbackCount = $result->fetch_assoc()['count'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT user_id, feedback_text, submit_date FROM feedback WHERE user_id = ? ORDER BY submit_date DESC");
    $stmt->bind_param("s", $UserID);
} else {
    // Admins: Count and fetch all feedback
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM feedback");
    $stmt->execute();
    $result = $stmt->get_result();
    $feedbackCount = $result->fetch_assoc()['count'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT user_id, feedback_text, submit_date FROM feedback ORDER BY submit_date DESC");
}
$stmt->execute();
$result = $stmt->get_result();
$feedbacks = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        .form-label {
            font-weight: 500;
            color: #1a3556;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #ced4da;
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
        .feedback-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: none;
        }
        #feedbackCount {
            margin-top: 10px;
            font-weight: bold;
        }
        #feedbackHistory {
            margin-top: 20px;
        }
        @media (max-width: 768px) {
            .container {
                margin-top: 60px;
                padding: 0 15px;
            }
            .card {
                padding: 30px;
            }
            .card-header h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Submit Feedback</h2>
            </div>
            <?php if ($success_message) { ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php } ?>
            <?php if ($error_message) { ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php } ?>
            <?php if ($role === 'Patient') { ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="feedback" class="form-label">Feedback</label>
                        <textarea class="form-control" id="feedback" name="feedback" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Feedback</button>
                </form>
            <?php } ?>
            <div id="feedbackCount">Total Feedback Submitted: <?php echo htmlspecialchars($feedbackCount); ?></div>
            <?php if (in_array($role, ['Admin', 'Patient'])) { ?>
                <button id="viewHistory" class="btn btn-primary mt-3"><i class="fas fa-history"></i> View Feedback History</button>
                <div id="feedbackHistory">
                    <?php if (empty($feedbacks)) { ?>
                        <p>No feedback submitted yet.</p>
                    <?php } else { ?>
                        <?php foreach ($feedbacks as $feedback) { ?>
                            <div class="feedback-item" id="feedbackItem_<?php echo htmlspecialchars($feedback['user_id'] . '_' . strtotime($feedback['submit_date'])); ?>">
                                <p><strong>User ID:</strong> <?php echo htmlspecialchars($feedback['user_id']); ?></p>
                                <p><strong>Feedback:</strong> <?php echo htmlspecialchars($feedback['feedback_text']); ?></p>
                                <p><strong>Date:</strong> <?php echo htmlspecialchars($feedback['submit_date']); ?></p>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
            <?php } ?>
            <a href="patient_dashboard.php" class="btn btn-primary mt-4"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('viewHistory')?.addEventListener('click', function() {
            const historyDiv = document.getElementById('feedbackHistory');
            const items = historyDiv.getElementsByClassName('feedback-item');
            const isHidden = items[0] && (items[0].style.display === 'none' || items[0].style.display === '');
            for (let item of items) {
                item.style.display = isHidden ? 'block' : 'none';
            }
            this.textContent = isHidden ? 'Hide Feedback History' : 'View Feedback History';
            this.innerHTML = isHidden ? '<i class="fas fa-eye-slash"></i> Hide Feedback History' : '<i class="fas fa-history"></i> View Feedback History';
        });
    </script>
</body>
</html>
