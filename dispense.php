<?php
session_start();
if (!isset($_SESSION['UserID']) || $_SESSION['role'] !== 'Pharmacist') {
    header("Location: index.php");
    exit;
}

$host = "localhost";
$db = "mc1";
$user = "root";
$pass = "";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (isset($_GET['id'])) {
    $prescription_id = $_GET['id'];
    $stmt = $conn->prepare("UPDATE prescriptions SET Status = 'Dispensed' WHERE PrescriptionID = ?");
    $stmt->bind_param("i", $prescription_id);
    if ($stmt->execute()) {
        header("Location: dashboards/pharmacist_dashboard.php?message=Prescription+dispensed+successfully");
    } else {
        header("Location: dashboards/pharmacist_dashboard.php?message=Error+dispensing+prescription");
    }
} else {
    header("Location: dashboards/pharmacist_dashboard.php?message=Invalid+prescription+ID");
}
?>