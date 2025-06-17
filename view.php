<?php
// Connect to database
$servername = "localhost";
$username = "root";
$password = ""; // Use your DB password
$dbname = "medical_center";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>View Patient Records</h2>";

// View button form
echo '<form method="post">
        <input type="submit" name="view" value="View All Patients">
      </form>';

if (isset($_POST['view'])) {
    $sql = "SELECT * FROM patients";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='10'>
                <tr>
                    <th>ID</th>
                    <th>UserID</th>
                    <th>Fullname</th>
                    <th>Email</th>
                    <th>Contact_No</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Birth</th>
                    <th>Blood_Type</th>
                    <th>Academic_Year</th>
                    <th>Faculty</th>
                    <th>Citizenship</th>
                    <th>Any_allergies</th>
                    <th>Emergency_Contact</th>
                </tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['UserID']}</td>
                    <td>{$row['Fullname']}</td>
                    <td>{$row['Email']}</td>
                    <td>{$row['Contact_No']}</td>
                    <td>{$row['Age']}</td>
                    <td>{$row['Gender']}</td>
                    <td>{$row['Birth']}</td>
                    <td>{$row['Blood_Type']}</td>
                    <td>{$row['Academic_Year']}</td>
                    <td>{$row['Faculty']}</td>
                    <td>{$row['Citizenship']}</td>
                    <td>{$row['Any_allergies']}</td>
                    <td>{$row['Emergency_Contact']}</td>
                </tr>";
        }
        echo "</table>";
    } else {
        echo "No patient records found.";
    }
}

$conn->close();
?>
