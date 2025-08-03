<?php
session_start();
if (!isset($_SESSION['UserID']) || !in_array($_SESSION['role'], ['Patient', 'Doctor'])) {
    header("Location: ../login.php");
    exit;
}

$host = "localhost";
$db = "mc1";
$user = "root";
$pass = "";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$UserID = $_SESSION['UserID'];
$role = $_SESSION['role'];
if ($role === 'Patient') {
    $stmt = $conn->prepare("SELECT Fullname, Email, Contact_No, Birth, Blood_Type, Gender, Faculty FROM patients WHERE UserID = ?");
} else {
    $stmt = $conn->prepare("SELECT Fullname, Email, Contact_No, Specialization, RegNo FROM doctors WHERE UserID = ?");
}
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $UserID);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}
$result = $stmt->get_result();
if (!$result) {
    die("Query failed: " . $conn->error);
}
$user = $result->fetch_assoc();
if (!$user) {
    die("No user found with UserID: " . htmlspecialchars($UserID));
}
$conn->close();

// Calculate dynamic date range for the date picker
$today = new DateTime();
$min_date = (clone $today)->modify('-7 days');
$max_date = (clone $today)->modify('+7 days');
$min_date_str = $min_date->format('Y-m-d');
$max_date_str = $max_date->format('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Form Submission - University Medical Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Datepicker CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-color: #e0e7ff;
            --text-color: #1a3556;
            --card-bg: rgba(255, 255, 255, 0.98);
            --primary-color: #007bff;
            --secondary-color: #00c4b4;
            --shadow: 0 12px 50px rgba(0, 50, 120, 0.15);
            --info-item-bg: rgba(240, 245, 255, 0.8);
            --header-bg: #e7f5ff;
        }

        [data-theme="dark"] {
            --bg-color: #1a1a2e;
            --text-color: #e0e0e0;
            --card-bg: rgba(40, 40, 60, 0.95);
            --primary-color: #4dabf7;
            --secondary-color: #26de81;
            --shadow: 0 12px 50px rgba(0, 0, 0, 0.3);
            --info-item-bg: rgba(40, 40, 60, 0.8);
            --header-bg: #2c3e50;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
            padding-top: 60px;
            transition: background 0.3s ease, color 0.3s ease;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://images.unsplash.com/photo-1522441815192-d9f04eb0615c');
            background-repeat: repeat;
            background-size: 250px;
            opacity: 0.04;
            z-index: -1;
            animation: gentleDrift 25s linear infinite;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background: var(--header-bg);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            z-index: 1000;
        }

        .header-branding {
            display: flex;
            align-items: center;
        }

        .medical-center-name {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--text-color);
        }

        .header-right {
            display: flex;
            align-items: center;
        }

        .theme-toggle {
            background: none;
            border: none;
            color: var(--text-color);
            cursor: pointer;
            margin-right: 15px;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .theme-toggle:hover {
            color: var(--primary-color);
        }

        .user-name {
            margin-right: 15px;
            font-weight: 500;
            color: var(--text-color);
        }

        .logout-btn {
            color: #dc3545;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .logout-btn:hover {
            color: #c82333;
        }

        .sidebar-toggle {
            position: fixed;
            top: 10px;
            left: 10px;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            display: none;
            z-index: 1001;
            transition: background 0.3s ease;
        }

        .sidebar-toggle:hover {
            background: var(--secondary-color);
        }

        .sidebar {
            position: fixed;
            top: 60px;
            left: 0;
            width: 250px;
            height: calc(100% - 60px);
            background: var(--card-bg);
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .sidebar.hidden {
            transform: translateX(-100%);
        }

        .sidebar ul {
            list-style: none;
            padding: 20px;
            margin: 0;
        }

        .sidebar li {
            margin-bottom: 10px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--text-color);
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .sidebar a:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }

        .sidebar a i {
            margin-right: 10px;
        }

        .main-content {
            margin-left: 250px;
            padding: 40px 20px;
            transition: margin-left 0.3s ease;
        }

        .main-content.full-width {
            margin-left: 0;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: var(--shadow);
            padding: 40px;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.7s ease-out;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0, 50, 120, 0.2);
        }

        .card-header h2 {
            font-size: 2.5rem;
            font-weight: 600;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-align: center;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 500;
            color: var(--text-color);
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid var(--primary-color);
            background: var(--info-item-bg);
            color: var(--text-color);
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 5px rgba(0, 196, 180, 0.3);
        }

        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: transform 0.2s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
        }

        .table {
            background: var(--card-bg);
            border-radius: 8px;
            overflow: hidden;
        }

        .table th, .table td {
            border: 1px solid var(--info-item-bg);
            padding: 12px;
            color: var(--text-color);
        }

        .table th {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .add-row-btn {
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            margin-left: 10px;
        }

        .add-row-btn:hover {
            background: var(--secondary-color);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes gentleDrift {
            0% { background-position: 0 0; }
            100% { background-position: 250px 250px; }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar-toggle {
                display: block;
            }
            .main-content {
                margin-left: 0;
            }
            .container {
                padding: 0 15px;
            }
            .card {
                padding: 30px;
            }
            .medical-center-name {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                margin-top: 40px;
            }
            .card {
                padding: 20px;
                border-radius: 12px;
            }
            .medical-center-name {
                font-size: 1rem;
            }
        }

        /* Style for datepicker input */
        .datepicker-input {
            width: 100%;
        }
    </style>
</head>
<body data-theme="light">
    <header class="header">
        <div class="header-branding">
            <div class="medical-center-name">University Medical Centre</div>
        </div>
        <div class="header-right">
            <button class="theme-toggle" onclick="toggleTheme()"><i class="fas fa-moon"></i></button>
            <span class="user-name">Welcome, <?php echo htmlspecialchars($user['Fullname']); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>
    <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
    <div class="sidebar" id="sidebar">
        <ul>
            <li><a href="form_details.php"><i class="fas fa-file-alt"></i> Form Details</a></li>
            <li><a href="patient_history.php?edit=<?php echo $role === 'Doctor' ? 'true' : 'false'; ?>"><i class="fas fa-history"></i> Patient History</a></li>
            <?php if ($role === 'Patient') { ?>
                <li><a href="view_prescriptions.php"><i class="fas fa-prescription-bottle-alt"></i> View Prescription</a></li>
                <li><a href="edit_patient_details.php"><i class="fas fa-edit"></i> Edit Details</a></li>
                <li><a href="submit_feedback.php"><i class="fas fa-comment-dots"></i> Submit Feedback</a></li>
            <?php } ?>
            <li><a href="book_appointment.php"><i class="fas fa-calendar-plus"></i> Book Appointment</a></li>
        </ul>
    </div>
    <div class="main-content" id="main-content">
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h2>Medical Certificate Submission</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="formType" class="form-label">Select Form Type</label>
                        <select class="form-control" id="formType" onchange="toggleForm()">
                            <option value="exam">Medical Certificate for Examinations</option>
                            <option value="lecture">Medical Certificate for Lectures/Practical Classes</option>
                        </select>
                    </div>

                    <!-- Exam Form -->
                    <div id="examForm" class="form-section">
                        <h4>Submission of Medical Certificates for Examinations</h4>
                        <form id="examFormElement" action="submit_medical_form.php" method="POST">
                            <input type="hidden" name="form_type" value="exam">
                            <div class="form-group">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['Fullname']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address" required>
                            </div>
                            <div class="form-group">
                                <label for="year" class="form-label">Year</label>
                                <input type="text" class="form-control" id="year" name="year" required>
                            </div>
                            <div class="form-group">
                                <label for="level" class="form-label">Level</label>
                                <select class="form-control" id="level" name="level" required>
                                    <option value="Level 1">Level 1</option>
                                    <option value="Level 2">Level 2</option>
                                    <option value="Level 3">Level 3</option>
                                    <option value="Level 4">Level 4</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="semester" class="form-label">Semester</label>
                                <select class="form-control" id="semester" name="semester" required>
                                    <option value="Semester 1">Semester 1</option>
                                    <option value="Semester 2">Semester 2</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="regNo" class="form-label">Registration Number</label>
                                <input type="text" class="form-control" id="regNo" name="reg_no" placeholder="SC/YYYY/NNNNN" required>
                            </div>
                            <div class="form-group">
                                <label for="contactNo" class="form-label">Contact Number</label>
                                <input type="text" class="form-control" id="contactNo" name="contact_no" value="<?php echo htmlspecialchars($user['Contact_No'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="degreeProgramme" class="form-label">Degree Programme</label>
                                <input type="text" class="form-control" id="degreeProgramme" name="degree_programme" value="<?php echo htmlspecialchars($user['Faculty'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Details of Subjects Covered by the Medical Certificate</label>
                                <table class="table" id="examSubjectTable">
                                    <thead>
                                        <tr>
                                            <th>Name of Subject (Course Unit)</th>
                                            <th>Subject Code</th>
                                            <th>Dates of Medical</th>
                                            <th>Place of Issue</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><input type="text" class="form-control" name="subjects[0][name]" required></td>
                                            <td><input type="text" class="form-control" name="subjects[0][code]" placeholder="IMT321b" required></td>
                                            <td>
                                                <input type="text" class="form-control datepicker-input" data-name="subjects[0][dates]" readonly>
                                                <input type="hidden" name="subjects[0][dates]" class="date-hidden">
                                            </td>
                                            <td><input type="text" class="form-control" name="subjects[0][place]" required></td>
                                            <td><button type="button" class="add-row-btn" onclick="addExamSubjectRow()">+</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="form-group">
                                <label for="certificateDetails" class="form-label">Why do you want certificate</label>
                                <textarea class="form-control" id="certificateDetails" name="certificate_details" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </form>
                    </div>

                    <!-- Lecture/Practical Form -->
                    <div id="lectureForm" class="form-section" style="display: none;">
                        <h4>Submission of Medical Certificates for Lectures/Practical Classes</h4>
                        <form id="lectureFormElement" action="submit_medical_form.php" method="POST">
                            <input type="hidden" name="form_type" value="lecture">
                            <div class="form-group">
                                <label for="lectureName" class="form-label">Name</label>
                                <input type="text" class="form-control" id="lectureName" name="name" value="<?php echo htmlspecialchars($user['Fullname']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address" required>
                            </div>
                            <div class="form-group">
                                <label for="lectureContactNo" class="form-label">Contact Number</label>
                                <input type="text" class="form-control" id="lectureContactNo" name="contact_no" value="<?php echo htmlspecialchars($user['Contact_No'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="lectureRegNo" class="form-label">Registration Number</label>
                                <input type="text" class="form-control" id="lectureRegNo" name="reg_no" placeholder="SC/YYYY/NNNNN" required>
                            </div>
                            <div class="form-group">
                                <label for="academicYear" class="form-label">Academic Year</label>
                                <input type="text" class="form-control" id="academicYear" name="academic_year" placeholder="YYYY/YYYY" required>
                            </div>
                            <div class="form-group">
                                <label for="lectureLevel" class="form-label">Level</label>
                                <select class="form-control" id="lectureLevel" name="level" required>
                                    <option value="Level 1">Level 1</option>
                                    <option value="Level 2">Level 2</option>
                                    <option value="Level 3">Level 3</option>
                                    <option value="Level 4">Level 4</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="semesterYear" class="form-label">Semester and Year</label>
                                <input type="text" class="form-control" id="semesterYear" name="semester_year" placeholder="Semester 1, YYYY" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Details of Subjects Covered by the Medical Certificate</label>
                                <table class="table" id="lectureSubjectTable">
                                    <thead>
                                        <tr>
                                            <th>Name of Subject (Course Unit)</th>
                                            <th>Subject Code</th>
                                            <th>Dates of Medical</th>
                                            <th>Place of Issue</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><input type="text" class="form-control" name="subjects[0][name]" required></td>
                                            <td><input type="text" class="form-control" name="subjects[0][code]" placeholder="IMT321b" required></td>
                                            <td>
                                                <input type="text" class="form-control datepicker-input" data-name="subjects[0][dates]" readonly>
                                                <input type="hidden" name="subjects[0][dates]" class="date-hidden">
                                            </td>
                                            <td><input type="text" class="form-control" name="subjects[0][place]" required></td>
                                            <td><button type="button" class="add-row-btn" onclick="addLectureSubjectRow()">+</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="form-group">
                                <label for="lectureCertificateDetails" class="form-label">Why do you want certificate</label>
                                <textarea class="form-control" id="lectureCertificateDetails" name="certificate_details" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script>
        function toggleSidebar() {
            var sidebar = document.getElementById('sidebar');
            var mainContent = document.getElementById('main-content');
            sidebar.classList.toggle('hidden');
            mainContent.classList.toggle('full-width');
        }

        function toggleTheme() {
            var body = document.body;
            var currentTheme = body.getAttribute('data-theme');
            var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        }

        function toggleForm() {
            var formType = document.getElementById('formType').value;
            document.getElementById('examForm').style.display = formType === 'exam' ? 'block' : 'none';
            document.getElementById('lectureForm').style.display = formType === 'lecture' ? 'block' : 'none';
        }

        let examRowCount = 1;
        function addExamSubjectRow() {
            const table = document.getElementById('examSubjectTable').getElementsByTagName('tbody')[0];
            const row = table.insertRow();
            row.innerHTML = `
                <td><input type="text" class="form-control" name="subjects[${examRowCount}][name]" required></td>
                <td><input type="text" class="form-control" name="subjects[${examRowCount}][code]" placeholder="IMT321b" required></td>
                <td>
                    <input type="text" class="form-control datepicker-input" data-name="subjects[${examRowCount}][dates]" readonly>
                    <input type="hidden" name="subjects[${examRowCount}][dates]" class="date-hidden">
                </td>
                <td><input type="text" class="form-control" name="subjects[${examRowCount}][place]" required></td>
                <td><button type="button" class="add-row-btn" onclick="addExamSubjectRow()">+</button></td>
            `;
            initializeDatepicker($(`input[data-name="subjects[${examRowCount}][dates]"]`));
            examRowCount++;
        }

        let lectureRowCount = 1;
        function addLectureSubjectRow() {
            const table = document.getElementById('lectureSubjectTable').getElementsByTagName('tbody')[0];
            const row = table.insertRow();
            row.innerHTML = `
                <td><input type="text" class="form-control" name="subjects[${lectureRowCount}][name]" required></td>
                <td><input type="text" class="form-control" name="subjects[${lectureRowCount}][code]" placeholder="IMT321b" required></td>
                <td>
                    <input type="text" class="form-control datepicker-input" data-name="subjects[${lectureRowCount}][dates]" readonly>
                    <input type="hidden" name="subjects[${lectureRowCount}][dates]" class="date-hidden">
                </td>
                <td><input type="text" class="form-control" name="subjects[${lectureRowCount}][place]" required></td>
                <td><button type="button" class="add-row-btn" onclick="addLectureSubjectRow()">+</button></td>
            `;
            initializeDatepicker($(`input[data-name="subjects[${lectureRowCount}][dates]"]`));
            lectureRowCount++;
        }

        function initializeDatepicker(element) {
            element.datepicker({
                format: 'yyyy-mm-dd',
                multidate: true,
                startDate: '<?php echo $min_date_str; ?>',
                endDate: '<?php echo $max_date_str; ?>',
                autoclose: false,
                todayHighlight: true
            }).on('changeDate', function(e) {
                const hiddenInput = $(this).next('.date-hidden');
                const selectedDates = e.dates.map(date => {
                    const d = new Date(date);
                    return d.getFullYear() + '-' + 
                           ('0' + (d.getMonth() + 1)).slice(-2) + '-' + 
                           ('0' + d.getDate()).slice(-2);
                });
                hiddenInput.val(selectedDates.join(','));
            });
        }

        // Convert selected dates to comma-separated string before submission
        function updateHiddenDateInputs(form) {
            const datepickers = form.querySelectorAll('.datepicker-input');
            datepickers.forEach(input => {
                const hiddenInput = input.nextElementSibling;
                hiddenInput.value = $(input).val(); // Datepicker already formats as comma-separated
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            var savedTheme = localStorage.getItem('theme') || 'light';
            document.body.setAttribute('data-theme', savedTheme);
            toggleForm(); // Initialize form visibility

            // Initialize datepickers for initial rows
            $('.datepicker-input').each(function() {
                initializeDatepicker($(this));
            });

            // Attach submit event listeners to both forms
            document.getElementById('examFormElement').addEventListener('submit', function(e) {
                updateHiddenDateInputs(this);
            });
            document.getElementById('lectureFormElement').addEventListener('submit', function(e) {
                updateHiddenDateInputs(this);
            });
        });
    </script>
</body>
</html>