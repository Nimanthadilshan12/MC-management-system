$medicine = $_POST['MedicineName'];
$qty = $_POST['Quantity'];
$action = $_POST['Action'];

$stmt = $conn->prepare("INSERT INTO controlled_log (MedicineName, Quantity, Action, DateLogged) VALUES (?, ?, ?, CURDATE())");
$stmt->bind_param("sis", $medicine, $qty, $action);
$stmt->execute();
