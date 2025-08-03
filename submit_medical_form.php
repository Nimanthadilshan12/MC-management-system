<?php
session_start();
$host = "localhost";
$db = "mc1";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_type = filter_input(INPUT_POST, 'form_type', FILTER_SANITIZE_STRING);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $contact_no = filter_input(INPUT_POST, 'contact_no', FILTER_SANITIZE_STRING);
    $reg_no = filter_input(INPUT_POST, 'reg_no', FILTER_SANITIZE_STRING);
    $certificate_details = filter_input(INPUT_POST, 'certificate_details', FILTER_SANITIZE_STRING);
    $subjects = isset($_POST['subjects']) ? json_encode($_POST['subjects']) : '[]';

    if ($form_type === 'exam') {
        $year = filter_input(INPUT_POST, 'year', FILTER_SANITIZE_STRING);
        $level = filter_input(INPUT_POST, 'level', FILTER_SANITIZE_STRING);
        $semester = filter_input(INPUT_POST, 'semester', FILTER_SANITIZE_STRING);
        $degree_programme = filter_input(INPUT_POST, 'degree_programme', FILTER_SANITIZE_STRING);

        $stmt = $conn->prepare("INSERT INTO certificate_submissions (form_type, name, address, year, level, semester, reg_no, contact_no, degree_programme, subjects, certificate_details) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssss", $form_type, $name, $address, $year, $level, $semester, $reg_no, $contact_no, $degree_programme, $subjects, $certificate_details);
    } else {
        $academic_year = filter_input(INPUT_POST, 'academic_year', FILTER_SANITIZE_STRING);
        $level = filter_input(INPUT_POST, 'level', FILTER_SANITIZE_STRING);
        $semester_year = filter_input(INPUT_POST, 'semester_year', FILTER_SANITIZE_STRING);

        $stmt = $conn->prepare("INSERT INTO certificate_submissions (form_type, name, address, academic_year, level, semester_year, reg_no, contact_no, subjects, certificate_details) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssss", $form_type, $name, $address, $academic_year, $level, $semester_year, $reg_no, $contact_no, $subjects, $certificate_details);
    }

    if ($stmt->execute()) {
        $_SESSION['form_details_data'] = [
            'form_type' => $form_type,
            'name' => $name,
            'address' => $address,
            'contact_no' => $contact_no,
            'reg_no' => $reg_no,
            'certificate_details' => $certificate_details,
            'subjects' => $_POST['subjects'] ?? [],
            'year' => $year ?? null,
            'level' => $level,
            'semester' => $semester ?? null,
            'degree_programme' => $degree_programme ?? null,
            'academic_year' => $academic_year ?? null,
            'semester_year' => $semester_year ?? null
        ];
        header("Location: medical_form.php");
        exit();
    } else {
        die("Error saving data: " . $stmt->error);
    }

    $stmt->close();
}
$conn->close();
?>