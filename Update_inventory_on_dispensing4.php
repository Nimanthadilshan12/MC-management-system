$medicineID = $_POST['MedicineID'];
$dispenseQty = $_POST['Quantity'];

$stmt = $conn->prepare("UPDATE inventory SET Quantity = Quantity - ? WHERE MedicineID = ?");
$stmt->bind_param("ii", $dispenseQty, $medicineID);
$stmt->execute();
