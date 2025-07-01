$query = "SELECT * FROM prescriptions WHERE Status = 'Pending'";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    echo "Medication: " . $row['Medication'] . "<br>";
}
