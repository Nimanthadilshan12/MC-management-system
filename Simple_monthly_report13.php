$query = "SELECT MONTH(DateIssued) as Month, SUM(Amount) as Total FROM billing GROUP BY MONTH(DateIssued)";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    echo "Month: " . $row['Month'] . " | Total: " . $row['Total'] . "<br>";
}
