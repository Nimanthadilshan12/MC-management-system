<?php
$host = "localhost";
$db = "mc1";
$user = "root";
$pass = "";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch settings
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} else {
    die("Settings query failed: " . $conn->error);
}
$conn->close();

// Convert operation days to readable format
$days = ['1' => 'Monday', '2' => 'Tuesday', '3' => 'Wednesday', '4' => 'Thursday', '5' => 'Friday', '6' => 'Saturday', '7' => 'Sunday'];
$operation_days = explode(',', $settings['operation_days']);
$operation_days_text = array_map(function($day) use ($days) { return $days[$day] ?? ''; }, $operation_days);
$operation_days_text = implode(', ', array_filter($operation_days_text));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Medical Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #7c3aed;
            --secondary: #ec4899;
            --accent: #06b6d4;
            --text: #1e293b;
            --background: #f1f5f9;
            --success: #10b981;
            --error: #ef4444;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #a5b4fc,rgb(198, 168, 249), #22d3ee);
            text-align: center;
            padding: 100px 20px;
            position: relative;
            min-height: 100vh;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://images.unsplash.com/photo-1505751172876-fa1923c5c528');
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
            opacity: 0.1;
            z-index: -1;
            animation: zoomInOut 20s ease-in-out infinite;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, rgba(255, 255, 255, 0.4), transparent 70%);
            z-index: -1;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .logo {
            display: block;
            max-width: 200px;
            margin: 0 auto 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 50, 120, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 1s ease;
        }

        .logo:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(0, 50, 120, 0.15);
        }

        h1 {
            font-family: 'Rubik', sans-serif;
            font-size: 3.8rem;
            font-weight: 700;
            background: linear-gradient(to right, var(--primary), var(--secondary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: 0.8px;
            margin-bottom: 20px;
            animation: textPop 1.5s ease-in-out infinite alternate;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        p {
            font-size: 1.6rem;
            color: var(--text);
            font-weight: 400;
            line-height: 1.7;
            max-width: 650px;
            margin: 0 auto 30px;
            animation: popIn 0.5s ease;
        }

        .btn-primary {
            display: inline-block;
            padding: 16px 40px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: #fff;
            text-decoration: none;
            border-radius: 12px;
            font-size: 1.3rem;
            font-weight: 500;
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 0 20px rgba(124, 58, 237, 0.5);
            position: relative;
            overflow: hidden;
            margin: 10px;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.4s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #6d28d9, #db2777);
            transform: translateY(-4px);
            box-shadow: 0 0 20px rgba(124, 58, 237, 0.5);
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 0 10px rgba(124, 58, 237, 0.3);
        }

        .feature-section {
            margin-top: 60px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .feature-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(240, 245, 255, 0.95));
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 50, 120, 0.1);
            padding: 20px;
            width: 250px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 1.4s ease 0.4s;
        }

        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 40px rgba(0, 50, 120, 0.15);
        }

        .feature-card i {
            font-size: 2.5rem;
            color: var(--accent);
            margin-bottom: 15px;
        }

        .feature-card h3 {
            font-family: 'Rubik', sans-serif;
            font-size: 1.3rem;
            font-weight: 600;
            background: linear-gradient(to right, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 10px;
        }

        .feature-card p {
            font-size: 1rem;
            color: var(--text);
            margin: 0;
        }

        .settings-section {
            margin-top: 40px;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(240, 245, 255, 0.95));
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 50, 120, 0.15), 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
            animation: fadeInUp 1.6s ease 0.6s;
            position: relative;
            overflow: hidden;
        }

        .settings-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            z-index: 1;
        }

        .settings-section h3 {
            font-family: 'Rubik', sans-serif;
            font-size: 1.7rem;
            font-weight: 600;
            background: linear-gradient(to right, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: textPop 1.5s ease-in-out infinite alternate;
        }

        .settings-section h3 i {
            font-size: 2rem;
            color: var(--accent);
            animation: pulseIcon 1.5s ease-in-out infinite;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 0 10px;
        }

        .settings-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 10px;
            border: 1px solid rgba(0, 123, 255, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
        }

        .settings-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 50, 120, 0.1);
            background: rgba(0, 123, 255, 0.05);
        }

        .settings-item i {
            font-size: 1.5rem;
            color: var(--accent);
            transition: transform 0.3s ease;
        }

        .settings-item:hover i {
            transform: scale(1.2);
        }

        .settings-content {
            flex: 1;
        }

        .settings-label {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 5px;
        }

        .settings-value {
            font-size: 1.1rem;
            color: var(--text);
            font-weight: 400;
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes textPop {
            from { transform: scale(1); text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
            to { transform: scale(1.02); text-shadow: 0 3px 6px rgba(0, 0, 0, 0.15); }
        }

        @keyframes zoomInOut {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes pulseIcon {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        @keyframes popIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                padding: 80px 15px;
            }
            .logo {
                max-width: 150px;
                margin-bottom: 15px;
            }
            h1 {
                font-size: 3rem;
            }
            p {
                font-size: 1.4rem;
                max-width: 90%;
            }
            .btn-primary {
                padding: 14px 30px;
                font-size: 1.2rem;
            }
            .feature-card {
                width: 100%;
                max-width: 300px;
            }
            .settings-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            .settings-section {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 60px 10px;
            }
            .logo {
                max-width: 120px;
                margin-bottom: 10px;
            }
            h1 {
                font-size: 2.5rem;
            }
            p {
                font-size: 1.2rem;
            }
            .btn-primary {
                padding: 12px 25px;
                font-size: 1.1rem;
            }
            .feature-section, .settings-section {
                margin-top: 40px;
            }
            .settings-section h3 {
                font-size: 1.5rem;
            }
            .settings-item i {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="https://upload.wikimedia.org/wikipedia/en/2/2e/University_of_Ruhuna_logo.png" alt="University of Ruhuna Logo" class="logo">
        <h1 style="display:">Welcome!</h1>
        <h1>University of Ruhuna Medical Centre</h1>
        <p>Your health is our priority. Access top-notch medical services and manage your health with ease.</p>
        <a class="btn btn-primary" href="login.php"><i class="fas fa-sign-in-alt me-2"></i>Go to Login/Register</a>
        <a class="btn btn-primary" href="aboutus.html"><i class="fas fa-info-circle me-2"></i>About Us</a>
        <a class="btn btn-primary" href="health_resources.php"><i class="fas fa-book-medical me-2"></i>Health Resources</a>
        
        <div class="feature-section">
            <div class="feature-card">
                <i class="fas fa-user-md"></i>
                <h3>Expert Care</h3>
                <p>Connect with experienced doctors.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-prescription-bottle-alt"></i>
                <h3>Pharmacy Services</h3>
                <p>Easy access to medications and prescriptions.</p>
            </div>
        </div>

        <div class="settings-section">
            <h3><i class="fas fa-info-circle"></i> Medical Centre Information</h3>
            <div class="settings-grid">
                <div class="settings-item">
                    <i class="fas fa-clock"></i>
                    <div class="settings-content">
                        <div class="settings-label">Opening Hours</div>
                        <div class="settings-value"><?php echo htmlspecialchars($settings['opening_time'] . ' - ' . $settings['closing_time']); ?></div>
                    </div>
                </div>
                <div class="settings-item">
                    <i class="fas fa-calendar-alt"></i>
                    <div class="settings-content">
                        <div class="settings-label">Days of Operation</div>
                        <div class="settings-value"><?php echo htmlspecialchars($operation_days_text); ?></div>
                    </div>
                </div>
                <div class="settings-item">
                    <i class="fas fa-phone-alt"></i>
                    <div class="settings-content">
                        <div class="settings-label">Emergency Contact</div>
                        <div class="settings-value"><?php echo htmlspecialchars($settings['emergency_contact_number']); ?></div>
                    </div>
                </div>
                <?php if ($settings['maintenance_mode'] === '1'): ?>
                    <div class="settings-item">
                        <i class="fas fa-tools"></i>
                        <div class="settings-content">
                            <div class="settings-label">Maintenance Mode</div>
                            <div class="settings-value">Enabled - <?php echo htmlspecialchars($settings['maintenance_message']); ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
