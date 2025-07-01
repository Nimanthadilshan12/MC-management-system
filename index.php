<?php
$role = $_GET['role'] ?? 'Unknown';
echo "<h1>Welcome!</h1>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Medical Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #e0e7ff, #b9d1ff, #e6f0ff);
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
            font-size: 3.8rem;
            font-weight: 700;
            background: linear-gradient(to right, #007bff, #00c4b4);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: 0.8px;
            margin-bottom: 20px;
            animation: fadeInUp 1s ease, textGlow 2s ease-in-out infinite alternate;
        }

        p {
            font-size: 1.6rem;
            color: #2d3748;
            font-weight: 400;
            line-height: 1.7;
            max-width: 650px;
            margin: 0 auto 30px;
            animation: fadeInUp 1.2s ease 0.2s;
        }

        .btn-primary {
            display: inline-block;
            padding: 16px 40px;
            background: linear-gradient(to right, #007bff, #00c4b4);
            color: #fff;
            text-decoration: none;
            border-radius: 12px;
            font-size: 1.3rem;
            font-weight: 500;
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 6px 16px rgba(0, 123, 255, 0.2);
            position: relative;
            overflow: hidden;
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
            background: linear-gradient(to right, #0056b3, #00a896);
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 123, 255, 0.3);
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
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
            color: #007bff;
            margin-bottom: 15px;
        }

        .feature-card h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1a3556;
            margin-bottom: 10px;
        }

        .feature-card p {
            font-size: 1rem;
            color: #4a5568;
            margin: 0;
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes textGlow {
            from { text-shadow: 0 0 5px rgba(0, 123, 255, 0.3); }
            to { text-shadow: 0 0 12px rgba(0, 123, 255, 0.5); }
        }

        @keyframes zoomInOut {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
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
            .feature-section {
                margin-top: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="https://upload.wikimedia.org/wikipedia/en/2/2e/University_of_Ruhuna_logo.png" alt="University of Ruhuna Logo" class="logo">
        <h1 style="display: none;">Welcome!</h1> <!-- Hidden PHP-generated h1 -->
        <h1>University of Ruhuna Medical Centre</h1>
        <p>Your health is our priority. Access top-notch medical services and manage your health with ease.</p>
        <a class="btn btn-primary" href="login.php"><i class="fas fa-sign-in-alt me-2"></i>Go to Login/Register</a>

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
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

    