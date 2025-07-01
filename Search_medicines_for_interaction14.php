$term = $_GET['term'];
$query = "SELECT * FROM drug_info WHERE DrugName LIKE '%$term%'";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    echo $row['DrugName'] . "<br>";
}
