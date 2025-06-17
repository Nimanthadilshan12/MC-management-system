<?php
include 'db_connect.php';
$id = $_GET['id'];

$sql = "SELECT * FROM patients WHERE id=$id";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Patient</title>
</head>
<body>
    <h2>Edit Patient Details</h2>
    <form action="update_patient.php" method="post">
        <input type="hidden" name="id" value="<?= $row['id'] ?>">

        Full Name: <input type="text" name="Fullname" value="<?= $row['Fullname'] ?>"><br><br>
        Email: <input type="email" name="Email" value="<?= $row['Email'] ?>"><br><br>
        Contact No: <input type="text" name="Contact_No" value="<?= $row['Contact_No'] ?>"><br><br>
        Gender: 
        <select name="Gender">
            <option value="Male" <?= $row['Gender']=='Male'?'selected':'' ?>>Male</option>
            <option value="Female" <?= $row['Gender']=='Female'?'selected':'' ?>>Female</option>
        </select><br><br>
        
        <input type="submit" value="Update">
    </form>
</body>
</html>