<?php
session_start();
$role = $_GET['role'] ?? ($_SESSION['role'] ?? 'Guest'); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($role) ?> Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: #f5f5f5;
    }

    header {
      background: #007bff;
      color: white;
      padding: 20px;
      text-align: center;
    }

    .container {
      max-width: 960px;
      margin: 30px auto;
      padding: 20px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    h2 {
      color: #333;
    }

    .card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }

    .card {
      background: #e9f0ff;
      border: 1px solid #d0e0ff;
      padding: 20px;
      border-radius: 8px;
      transition: transform 0.2s ease;
      text-align: center;
    }

    .card:hover {
      transform: scale(1.03);
    }

    .logout-btn {
      display: inline-block;
      padding: 10px 20px;
      margin-top: 20px;
      background: #dc3545;
      color: white;
      text-decoration: none;
      border-radius: 5px;
    }

    @media (max-width: 600px) {
      header, .container {
        padding: 15px;
      }

      .card-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

<header>
  <h1><?= htmlspecialchars($role) ?> Dashboard</h1>
</header>

<div class="container">
  <h2>Welcome <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>!</h2>

  <?php if ($role == 'Admin'): ?>
  
   

  <?php elseif ($role == 'Doctor'): ?>
  
    </div>

  <?php elseif ($role == 'Pharmacist'): ?>
    

  <?php elseif ($role == 'Patient'): ?>
   

  <?php else: ?>
    <p>You are not logged in. Please <a href="login.php">login here</a>.</p>
  <?php endif; ?>

  <?php if ($role !== 'Guest'): ?>
    <a class="logout-btn" href="logout.php">Logout</a>
  <?php endif; ?>
</div>

</body>
</html>
