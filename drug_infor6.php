$drug = $_GET['DrugID'];
$query = "SELECT * FROM drug_info WHERE DrugID = $drug";
$result = $conn->query($query);
$info = $result->fetch_assoc();
echo "<h3>Usage:</h3>" . $info['Usage'];
