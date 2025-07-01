$advice = $_POST['Advice'];
$patientID = $_POST['PatientID'];

$stmt = $conn->prepare("INSERT INTO counseling (PatientID, Advice, DateGiven) VALUES (?, ?, CURDATE())");
$stmt->bind_param("is", $patientID, $advice);
$stmt->execute();
