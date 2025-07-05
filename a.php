<?php
// DB connection
$servername = "localhost";
$username = "root";
$password = ""; // or your password
$database = "mc1";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch one doctor record (you can modify by UserID or ID if needed)
$sql = "SELECT * FROM doctors LIMIT 1";
$result = $conn->query($sql);
$doctor = $result->fetch_assoc(); // fetch first row
// Handle profile update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $national_id = $_POST['national_id'];
    $license_number = $_POST['license_number'];
    $full_name = $_POST['full_name'];
    $specialization = $_POST['specialization'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    
    // Assume doctor_id is stored in session (adjust based on your authentication system)
    $doctor_id = $_SESSION['doctor_id'] ?? null;
    if (!$doctor_id) {
        // Fallback: Fetch first doctor (for testing; replace with proper authentication)
        $sql = "SELECT id FROM doctors LIMIT 1";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $doctor_id = $row['id'];
    }
    
    // Validate inputs (basic server-side validation)
    if (empty($national_id) || empty($full_name) || empty($email) || empty($phone_number)) {
        echo "<script>alert('Please fill in all required fields.');</script>";
    } else {
        // Prepare SQL statement to update doctor details
        $sql = "UPDATE doctors SET UserID = ?, RegNo = ?, Fullname = ?, Specialization = ?, Email = ?, Contact_No = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $national_id, $license_number, $full_name, $specialization, $email, $phone_number, $doctor_id);
        
        if ($stmt->execute()) {
            // Update successful, refresh doctor data
            $sql = "SELECT * FROM doctors WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $doctor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $doctor = $result->fetch_assoc();
            echo "<script>alert('Profile updated successfully');</script>";
        } else {
            echo "<script>alert('Error updating profile: " . addslashes($conn->error) . "');</script>";
        }
        $stmt->close();
    }
}

// Fetch one doctor record (modify by UserID or id if needed)
$sql = "SELECT * FROM doctors WHERE id = ?";
$stmt = $conn->prepare($sql);
$doctor_id = $_SESSION['doctor_id'] ?? null;
if (!$doctor_id) {
    $sql = "SELECT * FROM doctors LIMIT 1"; // Fallback for testing
    $result = $conn->query($sql);
} else {
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
}
$doctor = $result->fetch_assoc();

// Handle patient search by UserID
$selected_patient = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_userid'])) {
    $search_userid = trim($_POST['search_userid']);
    if (!empty($search_userid)) {
        $sql = "SELECT Fullname, Age, Emergency_Contact, Blood_Type, Any_allergies FROM patients WHERE UserID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $search_userid);
        $stmt->execute();
        $result = $stmt->get_result();
        $selected_patient = $result->fetch_assoc();
        if (!$selected_patient) {
            echo "<script>showToast('No patient found for UserID: " . htmlspecialchars($search_userid) . "', 'warning');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>showToast('Please enter a UserID.', 'warning');</script>";
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Center - Doctor Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
        }

        body.darkmode{
            --primary-color: #3d98d4;
            --secondary-color: #2e8853;
            --dark-color: #191d21;
            --light-color: #021f26;
            --danger-color: #e74c3c;
            --warning-color: #644616;
            background-color: #070808;
            color:white;

        }

        body.darkmode .card,
body.darkmode .form-control,
body.darkmode .form-select,
body.darkmode .dropdown-menu,
body.darkmode .list-group-item,
body.darkmode .table,
body.darkmode .modal-content {
    background-color: #1e1e1e;
    color: white;
    border-color: #444;
}

body.darkmode .form-control::placeholder,
body.darkmode .form-select,
body.darkmode .text-muted {
    color: #ccc;
}

body.darkmode .card-header {
    background-color: #2a2a2a;
    border-bottom-color: #444;
}

body.darkmode .list-group-item {
    background-color: #1e1e1e;
    color: white;
}

body.darkmode .table th,
body.darkmode .table td {
    border-color: #444;
}

       
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color:white;
            color: black;
            transition: background-color 0.3s,color 0.3s;
            
        }
         
        .update-label{ 
            display:block;
            width:200px;
            padding:5px;
            
            border: radius 4px;
            cursor:pointer;
            background: #3498db;
            color:#fff;

        }
        nav {
    display: block !important; /* Ensure sidebar is always visible */
}
#emergency-mail:target ~ nav {
    display: block !important; /* Keep sidebar visible when Emergency Mail is active */
}

        

        .input-image{
            display:none;
        }
        
        

        .font-small{
            font-size:16px;
        }
        .font-large{
            font-size:19px;
        }

         .font-medium{
            font-size:17px;
        }

        .navbar {
            background-color: var(--dark-color);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            
        }
        
        .sidebar {
            
            height: calc(100vh - 56px);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 56px;
            left: 0;
            width: 250px;
            z-index: 100;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link {
            
            border-radius: 0;
            padding: 12px 20px;
            margin: 2px 0;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
            border-left: 4px solid var(--primary-color);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
    
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            font-weight: 600;
            padding: 15px 20px;
        }

        .card profileimg{
            width: 50px;
            height:50px;
            border-radius:10%;
            margin-bottom: 5px;
           
           
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-success {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .profile-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .profile-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            padding-left: 40px;
            border-radius: 20px;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 10px;
            color: #aaa;
        }
        
        .patient-card {
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .patient-card:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .patient-card.active {
            background-color: rgba(52, 152, 219, 0.2);
            border-left: 4px solid var(--primary-color);
        }
        
        .medicine-table tbody tr {
            transition: background-color 0.3s;
        }
        
        .medicine-table tbody tr:hover {
            background-color: rgba(46, 204, 113, 0.1);
        }
        
        .tab-content {
            padding: 20px 0;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                padding: 0;
            }
            
            .sidebar.show {
                width: 250px;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        /* Doctor photo upload styles */
        .profile-photo-container {
            position: relative;
            display: inline-block;
        }
        
        .profile-photo-edit {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid white;
        }
        
        .upload-photo-input {
            display: none;
        }
        
        /* Patient form styles */
        .patient-form-container {
            display: none;
        }
        
        .patient-form-container.show {
            display: block;
        }

        /* Settings styles */
        .settings-section {
            margin-bottom: 30px;
        }

        .settings-section-title {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .settings-card {
            margin-bottom: 20px;
        }

        .settings-card-header {
            
            border-bottom: 1px solid #dee2e6;
            font-weight: 500;
        }

        .settings-form-group {
            margin-bottom: 15px;
        }

        .settings-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .settings-toggle-label {
            margin-right: 15px;
        }

        .settings-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #dee2e6;
        }

        .settings-avatar-container {
            position: relative;
            display: inline-block;
        }

        .settings-avatar-edit {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .settings-security-level {
            height: 10px;
            border-radius: 5px;
            background-color: #e9ecef;
            margin-top: 5px;
            overflow: hidden;
        }

        .settings-security-level-bar {
            height: 100%;
            background-color: var(--secondary-color);
            transition: width 0.3s;
        }

        .settings-password-strength {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }

        .settings-notification-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f1f1f1;
        }

        .settings-notification-item:last-child {
            border-bottom: none;
        }
            .profile button{
            margin-top:20px;
        padding : 5px 10px;
        font-size: 14px;
            cursor:pointer;
             background-color: var(--primary-color);
            color: white;
             border: 2px solid white;
        }

        .profile img{
            object-fit:cover;
            object-position:center;
        }
    </style>
</head>
<body>
   <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="/home">
            <i class="bi bi-heart-pulse-fill me-2"></i>Medical Center - Doctor Portal
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" id="sidebar-toggle" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
             <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
        <span><?php echo htmlspecialchars($doctor['Fullname']); ?></span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
        <<li>
  <a class="dropdown-item" href="/home">
    <i class="bi bi-house-door me-2" aria-hidden="true"></i>Home
  </a>
    </li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="/logout" id="logoutBtn"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Logout</a></li>
    </ul>
</li>

    </div>
</nav>

    <!-- Bootstrap Bundle includes Popper.js -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].forEach(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

        // Sidebar and navbar toggle
        const sidebarToggle = document.getElementById('sidebar-toggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function () {
                const navbarNav = document.getElementById('navbarNav');
                navbarNav.classList.toggle('show');
                const sidebar = document.getElementById('sidebar');
                if (sidebar) {
                    sidebar.classList.toggle('active');
                }
            });
        }

        // Logout confirmation
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (confirm('Are you sure you want to log out?')) {
                    window.location.href = '/logout';
                }
            });
        }
    });
</script>




    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-3">
            <div class="d-flex align-items-center mb-3">
                <div class="profile">
                    <img id = "profileImage" src="defult.png" alt="ProfilePhoto" class="profile-img me-3"><br>
                    
                    <input type="file" id="fileInput" class="upload-photo-input" accept="image/*" style="display:none">
                    <button onclick="openFile()" id="editbutton">Edit Profile</button>

                </div>
              
  <script>
    function openFile() {
      document.getElementById('fileInput').click();
    }

    document.getElementById('fileInput').addEventListener('change', function(event) {
      const file = event.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = function(e) {
        const dataUrl = e.target.result;
        document.getElementById('profileImage').src = dataUrl;
        localStorage.setItem('profileImage', dataUrl);
      };
      reader.readAsDataURL(file);

});

    // Load saved image
    const saved = localStorage.getItem('profileImage');
    if (saved) {
      document.getElementById('profileImage').src = saved;
    }
  </script>
                
            </div>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="#dashboard" data-bs-toggle="tab">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#patients" data-bs-toggle="tab">
                    <i class="bi bi-people"></i> Patients
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="email.php" >
                    <i class="bi bi-envelope-exclamation"></i> Emergency Mail
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#settings" data-bs-toggle="tab">
                    <i class="bi bi-gear"></i> Settings
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="tab-content">
            <!-- Dashboard Tab -->
            <div class="tab-pane fade show active" id="dashboard">
                <div class="profile-header">
                    <h4><i class="bi bi-person-badge"></i> Doctor Profile</h4>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Personal Information</span>
                                
                                <button class="btn btn-sm btn-outline-primary" id="goToProfileSettingsBtn"><i class="bi bi-pencil"></i> Edit</button>
                                
                            </div>
                            <script>
document.getElementById('goToProfileSettingsBtn').addEventListener('click', function () {
    // Activate the main Settings tab
    const settingsTab = new bootstrap.Tab(document.querySelector('a.nav-link[href="#settings"]'));
    settingsTab.show();

    // Delay needed to allow settings tab content to load before showing subtabs
    setTimeout(() => {
        const profileTab = new bootstrap.Tab(document.querySelector('a.nav-link[href="#profile"]'));
        profileTab.show();
    }, 300); // 300ms delay is usually sufficient
});
</script>
                            <div class="card-body">
                                <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" value="<?php echo $doctor['Fullname']; ?>" readonly>
            </div>
                                <div class="mb-3">
                <label class="form-label">Specialization</label>
                <input type="text" class="form-control" value="<?php echo $doctor['Specialization']; ?>" readonly>
            </div>
                                 <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="<?php echo $doctor['Email']; ?>" readonly>
            </div>
                               <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" class="form-control" value="<?php echo $doctor['Contact_No']; ?>" readonly>
            </div>
                                <div class="mb-3">
                <label class="form-label">License Number</label>
                <input type="text" class="form-control" value="<?php echo $doctor['RegNo']; ?>" readonly>
            </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Profile Information</span>
                               
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
        <label class="form-label">National ID</label>
        <input type="text" class="form-control" value="<?php echo $doctor['UserID']; ?>" readonly>
    </div>
                                
                                
                                
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                   
                    <div class="col-md-4">
                       
                    </div>
                </div>
            </div>
            
            <!-- Patients Tab -->
            <div class="tab-pane fade" id="patients">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4><i class="bi bi-people"></i> Patient Management</h4>
                    <form method="POST" action="a.php" class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control" id="patientSearch" name="search_userid" placeholder="Enter Patient UserID (e.g., pat001)" value="<?php echo isset($_POST['search_userid']) ? htmlspecialchars($_POST['search_userid']) : ''; ?>">
                    </form>
                </div>
                <div class="row">
                    <div class="col-md-8 patient-details-card">
                        <div class="card">
                            <div class="card-header">
                                <span>Patient Details</span>
                            </div>
                            <div class="card-body">
                                <?php if ($selected_patient): ?>
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Name</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($selected_patient['Fullname']); ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Age</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($selected_patient['Age'] ?? ''); ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Emergency Contact</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($selected_patient['Emergency_Contact'] ?? ''); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Blood Type</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($selected_patient['Blood_Type'] ?? ''); ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Allergies</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($selected_patient['Any_allergies'] ?? ''); ?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    
                    
                                
                                <div class="tab-content">
                                    
                                        
                                            
                                                <div >
                                                 <button class="btn btn-primary" id="newPrescriptionBtn">
                                                    <i class="bi bi-plus-circle"></i> New Prescription
                                                </button>

                                                <button class="btn btn-primary" id="patientHistoryBtn">
                                                    <i class="bi bi-file-earmark-text"></i> Patient History
                                                </button>
                                               
                                            </div>
                                        
                                               
    </div> 
                                <?php else: ?>
                                    <p class="text-muted">Enter a valid UserID (e.g., pat001) to view patient details.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                                
            <script>
        // Toast notification function
        function showToast(message, type) {
            const toastContainer = document.querySelector('.toast-container');
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            setTimeout(() => toast.remove(), 5000); // Keep toast visible longer for clarity
        }

        // Auto-submit search form on input
        document.getElementById('patientSearch').addEventListener('input', function() {
            if (this.value.length >= 3) { // Submit only if at least 3 characters
                this.form.submit();
            } else {
                // Clear details if input is too short
                const patientDetails = document.querySelector('.card-body');
                patientDetails.innerHTML = '<p class="text-muted">Enter a valid UserID (e.g., pat001) to view patient details.</p>';
            }
        });
    </script>
                   
                                    
                                    
                                    
                                
                            
                        
                    
                
            
            
            
            
            
            <!-- Settings Tab -->
            <div class="tab-pane fade" id="settings">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4><i class="bi bi-gear"></i> Settings</h4>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card settings-card">
                            <div class="card-header settings-card-header">
                                <h5 class="mb-0">Account Settings</h5>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-pills flex-column" id="settingsTabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="profile-tab" data-bs-toggle="pill" href="#profile" role="tab">
                                            <i class="bi bi-person me-2"></i> Profile
                                        </a>
                                    </li>
                                    
                                    
                                    <li class="nav-item">
                                        <a class="nav-link" id="preferences-tab" data-bs-toggle="pill" href="#preferences" role="tab">
                                            <i class="bi bi-sliders me-2"></i> Preferences
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="tab-content" id="settingsTabContent">
                            <!-- Profile Settings -->
                            <div class="tab-pane fade show active" id="profile" role="tabpanel">
                                <div class="card settings-card">
                                    <div class="card-header settings-card-header">
                                        <h5 class="mb-0">Profile Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="profileForm" action="a.php" method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="update_profile" value="1">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="settings-form-group">
                                                        <label class="form-label">National ID</label>
                                                        <input type="text" class="form-control" name="national_id" value="<?php echo htmlspecialchars($doctor['UserID'] ?? ''); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="settings-form-group">
                                                        <label class="form-label">License Number</label>
                                                        <input type="text" class="form-control" name="license_number" value="<?php echo htmlspecialchars($doctor['RegNo'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="settings-form-group">
                                                <label class="form-label">Full Name</label>
                                                <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($doctor['Fullname'] ?? ''); ?>" required>
                                            </div>
                                            <div class="settings-form-group">
                                                <label class="form-label">Specialization</label>
                                                <select class="form-select" name="specialization" required>
                                                    <option value="Cardiologist" <?php echo ($doctor['Specialization'] ?? '') == 'Cardiologist' ? 'selected' : ''; ?>>Cardiologist</option>
                                                    <option value="Neurologist" <?php echo ($doctor['Specialization'] ?? '') == 'Neurologist' ? 'selected' : ''; ?>>Neurologist</option>
                                                    <option value="Pediatrician" <?php echo ($doctor['Specialization'] ?? '') == 'Pediatrician' ? 'selected' : ''; ?>>Pediatrician</option>
                                                    <option value="General Practitioner" <?php echo ($doctor['Specialization'] ?? '') == 'General Practitioner' ? 'selected' : ''; ?>>General Practitioner</option>
                                                    <option value="Surgeon" <?php echo ($doctor['Specialization'] ?? '') == 'Surgeon' ? 'selected' : ''; ?>>Surgeon</option>
                                                    <option value="Other" <?php echo ($doctor['Specialization'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                            <div class="settings-form-group">
                                                <label class="form-label">Email</label>
                                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($doctor['Email'] ?? ''); ?>" required>
                                            </div>
                                            <div class="settings-form-group">
                                                <label class="form-label">Phone Number</label>
                                                <input type="tel" class="form-control" name="phone_number" value="<?php echo htmlspecialchars($doctor['Contact_No'] ?? ''); ?>" required>
                                            </div>
                                            <div class="d-flex justify-content-end mt-4">
                                                <button type="button" class="btn btn-secondary me-2">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                    
                            
                            
                          
                                     
                            
                            <!-- Preferences Settings -->
                            <div class="tab-pane fade" id="preferences" role="tabpanel">
                                <div class="card settings-card">
                                    <div class="card-header settings-card-header">
                                        <h5 class="mb-0">Application Preferences</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="preferencesForm">
                                            <h6 class="settings-section-title">Display Settings</h6>
                                            
                                            <div class="settings-form-group">
                                                <label class="form-label">Theme</label>
                                                <select class="form-select" id="themeSelector">
                                                    <option value="light">Light</option>
                                                    <option value="dark">Dark</option>
                                                    
                                                </select>
                                            </div>
                                           <script>
    document.addEventListener('DOMContentLoaded', function() {
        const themeSelector = document.getElementById('themeSelector');

        // Load saved theme preference on page load
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            if (savedTheme === 'dark') {
                document.body.classList.add('darkmode');
            } else {
                document.body.classList.remove('darkmode');
            }
            themeSelector.value = savedTheme; // Set the dropdown to the saved value
        }

        // Add event listener for theme change
        themeSelector.addEventListener('change', function() {
            if (this.value === 'dark') {
                document.body.classList.add('darkmode');
                localStorage.setItem('theme', 'dark'); // Save dark mode preference
            } else {
                document.body.classList.remove('darkmode');
                localStorage.setItem('theme', 'light'); // Save light mode preference
            }
        });
    });
</script>

                                            
                                            <div class="settings-form-group">
                                                <label class="form-label">Font Size</label>
                                                <select class="form-select" id="fontSizeSelector">
                                                    <option value="small">Small</option>
                                                    <option value="medium" selected>Medium</option>
                                                    <option value="large">Large</option>
                                                </select>
                                            </div>
      <script>
    document.addEventListener("DOMContentLoaded", function () {
        const fontSizeSelector = document.getElementById("fontSizeSelector");

        // Load saved font size preference on page load
        const savedFontSize = localStorage.getItem("fontSize");
        if (savedFontSize) {
            document.body.classList.add(`font-${savedFontSize}`);
            if (fontSizeSelector) { // Ensure the selector exists before trying to set its value
                fontSizeSelector.value = savedFontSize;
            }
        } else {
            // If no font size is saved, default to medium
            document.body.classList.add("font-medium");
            if (fontSizeSelector) {
                fontSizeSelector.value = "medium";
            }
        }

        // Add event listener for font size change
        if (fontSizeSelector) {
            fontSizeSelector.addEventListener("change", function () {
                document.body.classList.remove("font-small", "font-medium", "font-large");
                const selectedSize = this.value;
                document.body.classList.add(`font-${selectedSize}`);
                localStorage.setItem("fontSize", selectedSize);
            });
        }
    });
</script>




                                            
                                            
                                            <div class="settings-form-group settings-toggle">
                                                
                                            </div>
                                            
                                            <h6 class="settings-section-title mt-4">Workflow Preferences</h6>
                                            
                                            <div class="settings-form-group">
                                                <label class="form-label">Default View</label>
                                                <select class="form-select" id="defaultViewSelect">
                                                    <option value="dashboard">Dashboard</option>
                                                    <option value="patients">Patients</option>
                                                    <option value="emergency-mail">Emergency-mail</option>
                                                </select>
                                            </div>
      <script>
document.addEventListener("DOMContentLoaded", function () {
    // Get saved default view or fallback to dashboard
    const defaultView = localStorage.getItem("defaultView") || "dashboard";

    // Activate correct tab
    const triggerTab = document.querySelector(`a.nav-link[href="#${defaultView}"]`);
    if (triggerTab) {
        new bootstrap.Tab(triggerTab).show();

        // Remove 'active' from all sidebar nav links
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.classList.remove('active');
        });

        // Add 'active' class to the selected tab
        triggerTab.classList.add('active');
    }

    // Set the dropdown to match saved view
    const defaultViewSelect = document.getElementById("defaultViewSelect");
    if (defaultViewSelect) {
        defaultViewSelect.value = defaultView;
        defaultViewSelect.addEventListener("change", function () {
            localStorage.setItem("defaultView", this.value);
        });
    }
});
</script>



                                            
                                            
                                            
                                            
                                           
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container"></div>
    
    <!-- New Prescription Modal -->
    <div class="modal fade" id="newPrescriptionModal" tabindex="-1" aria-labelledby="newPrescriptionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="newPrescriptionModalLabel"><i class="bi bi-clipboard2-pulse me-2"></i>New Prescription</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="newPrescriptionForm">
                        <div class="mb-3">
                            <label for="patientName" class="form-label">Patient Name</label>
                            <input type="text" class="form-control" id="patientName" value="Emma Johnson" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="patientId" class="form-label">Patient ID</label>
                            <input type="text" class="form-control" id="patientId" value="P001" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="illness" class="form-label">Illness/Diagnosis</label>
                            <textarea class="form-control" id="illness" rows="3" placeholder="Describe patient's illness or diagnosis..."></textarea>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered medicine-table">
                                <thead>
                                    <tr>
                                        <th>Medicine</th>
                                        <th>Dosage</th>
                                        <th>Frequency</th>
                                        <th>Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="medicineTableBodyMain">
                                    <tr>
                                        <td>
                                            <select class="form-select">
                                                <option>Select Medicine</option>
                                                <option>Losartan</option>
                                                <option>Metoprolol</option>
                                                <option>Atenolol</option>
                                                <option>Carvedilol</option>
                                                <option>Metformin</option>
                                                <option>Glibenclamide</option>
                                                <option>Glipizide</option>
                                                <option>Other...</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" placeholder="e.g. 50mg">
                                        </td>
                                        <td>
                                            <select class="form-select">
                                                <option>Select Frequency</option>
                                                <option>Once daily</option>
                                                <option>Twice daily</option>
                                                <option>Three times daily</option>
                                                <option>As needed</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select">
                                                <option>Select Time</option>
                                                <option>Morning</option>
                                                <option>Afternoon</option>
                                                <option>Evening</option>
                                                <option>Bedtime</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger remove-row"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" class="btn btn-success" id="addNewMedicineBtn">
                                <i class="bi bi-plus-circle"></i> Add Medicine
                            </button>
                            <button type="button" class="btn btn-primary" id="savePrescriptionBtn">
                                <i class="bi bi-save"></i> Save Prescription
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Prescription Modal -->
    <div class="modal fade" id="uploadPrescriptionModal" tabindex="-1" aria-labelledby="uploadPrescriptionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="uploadPrescriptionModalLabel"><i class="bi bi-upload me-2"></i>Upload Prescription to Pharmacy</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadPrescriptionForm">
                        <div class="mb-3">
                            <label for="pharmacyEmail" class="form-label">Pharmacy Email</label>
                            <input type="email" class="form-control" id="pharmacyEmail" value="pharmacy@medicare.com">
                        </div>
                        <div class="mb-3">
                            <label for="prescriptionNotes" class="form-label">Notes for Pharmacy</label>
                            <textarea class="form-control" id="prescriptionNotes" rows="3">Please prepare these medications for patient Emma Johnson (ID: P001).</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload PDF</label>
                            <input type="file" class="form-control" id="prescriptionFile" accept=".pdf">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmUploadBtn">
                        <i class="bi bi-upload"></i> Upload Prescription
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle
            document.getElementById('sidebar-toggle').addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('show');
            });
            
            // Home button functionality
            document.getElementById('homeBtn').addEventListener('click', function(e) {
                e.preventDefault();
                // Show dashboard tab
                const dashboardTab = new bootstrap.Tab(document.querySelector('#dashboard-tab'));
                dashboardTab.show();
                showToast('Returned to dashboard', 'info');
            });
            
            // Logout button functionality
            document.getElementById('logoutBtn').addEventListener('click', function(e) {
                e.preventDefault();
                showToast('Logging out...', 'info');
                // In a real application, this would redirect to logout endpoint
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 1500);
            });
            
            // Patient search functionality
            document.getElementById('patientSearch').addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                const patientCards = document.querySelectorAll('.patient-card');
                
                patientCards.forEach(card => {
                    const patientName = card.querySelector('h6').textContent.toLowerCase();
                    const patientId = card.querySelector('small').textContent.toLowerCase();
                    
                    if (patientName.includes(searchTerm) || patientId.includes(searchTerm)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
            
            // Emergency patient search functionality
            document.getElementById('emergencyPatientSearch').addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                const patientCards = document.querySelectorAll('.emergency-patient-card');
                
                patientCards.forEach(card => {
                    const patientName = card.querySelector('h6').textContent.toLowerCase();
                    const patientId = card.querySelector('small').textContent.toLowerCase();
                    
                    if (patientName.includes(searchTerm) || patientId.includes(searchTerm)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
            
            // Patient selection
            document.querySelectorAll('.patient-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all cards
                    document.querySelectorAll('.patient-card').forEach(c => {
                        c.classList.remove('active');
                    });
                    
                    // Add active class to clicked card
                    this.classList.add('active');
                    
                    // Get patient ID
                    const patientId = this.getAttribute('data-patient-id');
                    
                    // Show toast notification
                    showToast(`Patient ${this.querySelector('h6').textContent} selected`, 'info');
                });
            });
            
            // Emergency patient selection
            document.querySelectorAll('.emergency-patient-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all cards
                    document.querySelectorAll('.emergency-patient-card').forEach(c => {
                        c.classList.remove('active');
                    });
                    
                    // Add active class to clicked card
                    this.classList.add('active');
                    
                    // Update emergency form with patient details
                    const patientName = this.querySelector('h6').textContent;
                    const patientId = this.getAttribute('data-patient-id');
                    
                    document.getElementById('emergencySubject').value = `URGENT: Patient Emergency - ${patientName} (${patientId})`;
                    
                    // Show toast notification
                    showToast(`Patient ${patientName} selected for emergency mail`, 'warning');
                });
            });
            
            // Add patient button
            document.getElementById('addPatientBtn').addEventListener('click', function() {
                document.getElementById('addPatientForm').classList.add('show');
                this.style.display = 'none';
            });
            
            // Cancel add patient form
            document.getElementById('cancelPatientBtn').addEventListener('click', function() {
                document.getElementById('addPatientForm').classList.remove('show');
                document.getElementById('addPatientBtn').style.display = 'block';
                document.getElementById('patientForm').reset();
            });
            
            // Submit patient form
            document.getElementById('patientForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // In a real application, this would send data to server
                // For demo, we'll just show a success message
                showToast('Patient added successfully', 'success');
                
                // Hide form and show button again
                document.getElementById('addPatientForm').classList.remove('show');
                document.getElementById('addPatientBtn').style.display = 'block';
                this.reset();
            });
            
            // Submit emergency mail form
            document.getElementById('emergencyMailForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const subject = document.getElementById('emergencySubject').value;
                const priority = document.getElementById('emergencyPriority').value;
                
                // In a real application, this would send the email
                showToast(`Emergency mail sent to hospital (Priority: ${priority})`, 'success');
                
                // Reset form
                this.reset();
            });
            
            // Cancel emergency mail
            document.getElementById('cancelEmergencyMailBtn').addEventListener('click', function() {
                document.getElementById('emergencyMailForm').reset();
                showToast('Emergency mail cancelled', 'warning');
            });
            
               //Add_medicine row
         // Already inside document.addEventListener('DOMContentLoaded', ...)
const addMedicineBtn = document.getElementById('addMedicineBtn');
const tableBody = document.getElementById('medicineTableBodyModal');

if (addMedicineBtn && tableBody) {
    addMedicineBtn.addEventListener('click', function () {
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <select class="form-select">
                    <option>Select Medicine</option>
                    <option>Losartan</option>
                    <option>Metoprolol</option>
                    <option>Atenolol</option>
                    <option>Carvedilol</option>
                    <option>Metformin</option>
                    <option>Glibenclamide</option>
                    <option>Glipizide</option>
                    <option>Other...</option>
                </select>
            </td>
            <td><input type="text" class="form-control" placeholder="e.g. 50mg"></td>
            <td>
                <select class="form-select">
                    <option>Select Frequency</option>
                    <option>Once daily</option>
                    <option>Twice daily</option>
                    <option>Three times daily</option>
                    <option>As needed</option>
                </select>
            </td>
            <td>
                <select class="form-select">
                    <option>Select Time</option>
                    <option>Morning</option>
                    <option>Afternoon</option>
                    <option>Evening</option>
                    <option>Bedtime</option>
                </select>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger remove-row">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tableBody.appendChild(newRow);

        newRow.querySelector('.remove-row').addEventListener('click', function () {
            this.closest('tr').remove();
        });
    });
}


            
            // Add event listeners to existing remove buttons
            document.querySelectorAll('.remove-row').forEach(button => {
                button.addEventListener('click', function() {
                    this.closest('tr').remove();
                });
            });
            
            // New Prescription button
            document.getElementById('newPrescriptionBtn').addEventListener('click', function() {
                const newPrescriptionModal = new bootstrap.Modal(document.getElementById('newPrescriptionModal'));
                newPrescriptionModal.show();
            });
            
            // Add Medicine in the new prescription modal
            document.getElementById('addNewMedicineBtn').addEventListener('click', function() {
                const medicineTableBody = document.getElementById('medicineTableBody');
                const newRow = document.createElement('tr');
                
                newRow.innerHTML = `
                    <td>
                        <select class="form-select">
                            <option>Select Medicine</option>
                            <option>Losartan</option>
                            <option>Metoprolol</option>
                            <option>Atenolol</option>
                            <option>Carvedilol</option>
                            <option>Metformin</option>
                            <option>Glibenclamide</option>
                            <option>Glipizide</option>
                            <option>Other...</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control" placeholder="e.g. 50mg">
                    </td>
                    <td>
                        <select class="form-select">
                            <option>Select Frequency</option>
                            <option>Once daily</option>
                            <option>Twice daily</option>
                            <option>Three times daily</option>
                            <option>As needed</option>
                        </select>
                    </td>
                    <td>
                        <select class="form-select">
                            <option>Select Time</option>
                            <option>Morning</option>
                            <option>Afternoon</option>
                            <option>Evening</option>
                            <option>Bedtime</option>
                        </select>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger remove-row"><i class="bi bi-trash"></i></button>
                    </td>
                `;
                
                medicineTableBody.appendChild(newRow);
                
                // Add event listener to the new remove button
                newRow.querySelector('.remove-row').addEventListener('click', function() {
                    this.closest('tr').remove();
                });
            });
            
            // Save prescription button
            document.getElementById('savePrescriptionBtn').addEventListener('click', function() {
                const illness = document.getElementById('illness').value;
                
                if (!illness.trim()) {
                    showToast('Please describe the patient\'s illness or diagnosis', 'warning');
                    return;
                }
                
                // Check if at least one medicine is added
                const medicineSelects = document.querySelectorAll('#medicineTableBody select');
                let hasMedicine = false;
                
                for (let i = 0; i < medicineSelects.length; i++) {
                    if (medicineSelects[i].value !== 'Select Medicine') {
                        hasMedicine = true;
                        break;
                    }
                }
                
                if (!hasMedicine) {
                    showToast('Please add at least one medicine', 'warning');
                    return;
                }
                
                // Close modal
                const newPrescriptionModal = bootstrap.Modal.getInstance(document.getElementById('newPrescriptionModal'));
                newPrescriptionModal.hide();
                
                // Show success message
                showToast('Prescription saved successfully', 'success');
            });
            
            // Upload prescription button
            document.getElementById('uploadPrescriptionBtn').addEventListener('click', function() {
                const uploadPrescriptionModal = new bootstrap.Modal(document.getElementById('uploadPrescriptionModal'));
                uploadPrescriptionModal.show();
            });
            
            // Confirm upload button
            document.getElementById('confirmUploadBtn').addEventListener('click', function() {
                const uploadPrescriptionModal = bootstrap.Modal.getInstance(document.getElementById('uploadPrescriptionModal'));
                uploadPrescriptionModal.hide();
                
                showToast('Prescription uploaded to pharmacy', 'success');
            });
            
            // Generate PDF button
            document.getElementById('generatePdfBtn').addEventListener('click', function() {
                // Using jsPDF for PDF generation
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();
                
                // Add title
                doc.setFontSize(18);
                </body>
                </html>
<?php
$conn->close();
?>