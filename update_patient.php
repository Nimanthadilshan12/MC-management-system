<?php
include 'db_connect.php';

$id = $_POST['id'];
$fullname = $_POST['Fullname'];
$email = $_POST['Email'];
$contact = $_POST['Contact_No'];
$gender = $_POST['Gender'];

$sql = "UPDATE patients SET 
        Fullname='$fullname',
        Email='$email',
        Contact_No='$contact',
        Gender='$gender'
        WHERE id=$id";

if ($conn->query($sql) === TRUE) {
    echo "Record updated successfully.<br>";
    echo "<a href='index.php'>Go Back to Dashboard</a>";
} else {
    echo "Error updating record: " . $conn->error;
}

$conn->close();
?>
