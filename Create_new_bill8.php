$patientID = $_POST['PatientID'];
$amount = $_POST['Amount'];

$stmt = $conn->prepare("INSERT INTO billing (PatientID, Amount, DateIssued) VALUES (?, ?, CURDATE())");
$stmt->bind_param("id", $patientID, $amount);
$stmt->execute();
