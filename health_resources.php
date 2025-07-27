<?php
// Define filter and validate
$filter = isset($_GET['type']) && in_array($_GET['type'], ['All', 'Article', 'Video', 'Tip']) ? $_GET['type'] : 'All';

// Static resources array
$resources = [
    // Articles
    [
        'Title' => 'Managing Stress in University Life',
        'Content' => 'University life can be stressful with academic pressures and social challenges. This CDC article offers strategies like mindfulness, exercise, and time management to cope effectively. <a href="https://www.cdc.gov/mentalhealth/stress-coping/cope-with-stress/index.html" target="_blank">Read more</a>.',
        'ResourceType' => 'Article',
        'CreatedByName' => 'Health Team',
        'CreatedDate' => '2025-06-01 10:00:00',
        'Image' => 'https://providencemedicalassociates.org/wp-content/uploads/2023/07/stress-1.jpg'
    ],
    [
        'Title' => 'Nutrition for Busy Students',
        'Content' => 'Maintaining a balanced diet is crucial for energy and focus. Learn tips from Mayo Clinic on quick, healthy meals for students. <a href="https://www.mayoclinic.org/healthy-lifestyle/nutrition-and-healthy-eating/in-depth/nutrition-basics/art-20049426" target="_blank">Read more</a>.',
        'ResourceType' => 'Article',
        'CreatedByName' => 'Health Team',
        'CreatedDate' => '2025-06-05 12:00:00',
        'Image' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd'
    ],
    [
        'Title' => 'Understanding Mental Health',
        'Content' => 'Mental health is vital for academic success. This WHO article discusses signs of mental health issues and how to seek help. <a href="https://www.who.int/news-room/fact-sheets/detail/mental-health-strengthening-our-response" target="_blank">Read more</a>.',
        'ResourceType' => 'Article',
        'CreatedByName' => 'Health Team',
        'CreatedDate' => '2025-06-10 14:00:00',
        'Image' => 'https://miro.medium.com/v2/resize:fit:800/1*zxsxfqpY265ttrOcGC7w1Q.jpeg'
    ],
    [
        'Title' => 'Preventing Common Colds',
        'Content' => 'Frequent colds can disrupt your studies. This NIH article explains how to prevent and manage colds effectively. <a href="https://www.niaid.nih.gov/diseases-conditions/colds" target="_blank">Read more</a>.',
        'ResourceType' => 'Article',
        'CreatedByName' => 'Health Team',
        'CreatedDate' => '2025-06-15 16:00:00',
        'Image' => 'https://post.healthline.com/wp-content/uploads/2021/12/at-home-cold-remedies-1296x807.png'
    ],
    [
        'Title' => 'Managing Anxiety Disorders',
        'Content' => 'Anxiety can affect academic performance. This Harvard Health article provides insights on recognizing and managing anxiety. <a href="https://www.health.harvard.edu/diseases-and-conditions/anxiety-disorders" target="_blank">Read more</a>.',
        'ResourceType' => 'Article',
        'CreatedByName' => 'Health Team',
        'CreatedDate' => '2025-06-20 09:00:00',
        'Image' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644'
    ],
    [
        'Title' => 'Importance of Vaccinations',
        'Content' => 'Vaccinations protect against preventable diseases. Learn more from the CDC about staying up-to-date with vaccines. <a href="https://www.cdc.gov/vaccines/adults/rec-vac/index.html" target="_blank">Read more</a>.',
        'ResourceType' => 'Article',
        'CreatedByName' => 'Health Team',
        'CreatedDate' => '2025-06-25 11:00:00',
        'Image' => 'https://media.springernature.com/lw685/springer-static/image/chp%3A10.1007%2F978-3-031-24942-6_9/MediaObjects/523392_1_En_9_Fig1_HTML.png'
    ],
    // Videos
    [
        'Title' => 'How to Stay Active on Campus',
        'Content' => 'This TED-Ed video explains the benefits of physical activity and simple ways to stay active. <a href="https://www.youtube.com/watch?v=8Kk3tx0qIjo" target="_blank">Watch here</a>.',
        'ResourceType' => 'Video',
        'CreatedByName' => 'Health Team',
        'CreatedDate' => '2025-06-15 09:00:00',
        'Image' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438'
    ],
    [
        'Title' => 'Sleep and Academic Performance',
        'Content' => 'Learn why sleep is essential for learning in this video from Harvard Medical School. <a href="https://www.youtube.com/watch?v=nmZ7eYd3iM" target="_blank">Watch here</a>.',
        'ResourceType' => 'Video',
        'CreatedByName' => 'Health Team',
        'CreatedDate' => '2025-06-20 11:00:00',
        'Image' => 'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91'
    ],
    [
        'Title' => 'Yoga for Stress Relief',
        'Content' => 'This video from Yoga With Adriene offers a 20-minute yoga session to reduce stress. <a href="https://www.youtube.com/watch?v=3n0en2lQzxM" target="_blank">Watch here</a>.',
        'ResourceType' => 'Video',
        'CreatedByName' => 'Health Team',
        'CreatedDate' => '2025-06-25 13:00:00',
        'Image' => 'https://images.unsplash.com/photo-1545205597-3d9d02c29597'
    ],
    [
        'Title' => 'Mindfulness Meditation Guide',
        'Content' => 'This video from Headspace introduces mindfulness meditation for beginners. <a href="https://www.youtube.com/watch?v=6p_yaNFSYao" target="_blank">Watch here</a>.',
        'ResourceType' => 'Video',
        'CreatedByName' => 'Health Team',
        'CreatedDate' => '2025-06-30 10:00:00',
        'Image' => 'https://images.unsplash.com/photo-1506126279646-a697353d3166'
    ],
    [
        'Title' => 'Healthy Eating on a Budget',
        'Content' => 'This video from the American Heart Association provides tips for eating healthy on a student budget. <a href="https://www.youtube.com/watch?v=1C1fZzF6LAQ" target="_blank">Watch here</a>.',
        'ResourceType' => 'Video',
        'CreatedByName' => 'Health Team',
        'CreatedDate' => '2025-07-01 12:00:00',
        'Image' => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352'
    ],
    [
        'Title' => 'Managing Exam Stress',
        'Content' => 'This video from the University of Oxford offers strategies to manage exam-related stress. <a href="https://www.youtube.com/watch?v=2L7k3i0TeyQ" target="_blank">Watch here</a>.',
        'ResourceType' => 'Video',
        'CreatedByName' => 'Health Team',
        'CreatedDate' => '2025-07-02 08:00:00',
        'Image' => 'https://cdn.prod.website-files.com/61cb7a7d475583f9b7aeec64/647a07c5c410a3e52a610b3c_How%20to%20manage%20exam%20stress.png'
    ],
    // Tips
    [
        'Title' => 'Hydration Tip',
        'Content' => 'Drink at least 8 glasses of water daily to stay hydrated, boost energy, and improve focus.',
        'ResourceType' => 'Tip',
        'CreatedByName' => 'Health Team',
        'CreatedDate' => '2025-06-02 08:00:00',
        'Image' => 'https://atlas-ips.com/media/83422/adobestock_337016516.png?width=500&height=500'
    ],
    [
        'Title' => 'Healthy Snacking',
        'Content' => 'Choose nuts, fruits, or yogurt for snacks to maintain energy between classes.',
        'ResourceType' => 'Tip',
        'CreatedByName' => 'Health Team',
        'CreatedDate' => '2025-06-07 10:00:00',
        'Image' => 'https://images.unsplash.com/photo-1496412705862-e0088f16f791'
    ],
    [
        'Title' => 'Mindful Breaks',
        'Content' => 'Take 5-minute breaks every hour to stretch or meditate, reducing stress and improving productivity.',
        'ResourceType' => 'Tip',
        'CreatedByName' => 'Health Team',
        'CreatedDate' => '2025-06-12 15:00:00',
        'Image' => 'https://www.zenya.in/cdn/shop/articles/Tranquil_Indoor_Scene_with_Woman_and_Flowers_40949d75-6862-4559-bb52-5df162a4df9c.jpg?v=1749554711&width=1100'
    ],
    [
        'Title' => 'Posture Tip',
        'Content' => 'Sit up straight and take breaks from prolonged sitting to prevent back pain and improve focus.',
        'ResourceType' => 'Tip',
        'CreatedByName' => 'Health Team',
        'CreatedDate' => '2025-06-17 09:00:00',
        'Image' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c'
    ],
    [
        'Title' => 'Screen Time Management',
        'Content' => 'Follow the 20-20-20 rule: every 20 minutes, look at something 20 feet away for 20 seconds to reduce eye strain.',
        'ResourceType' => 'Tip',
        'CreatedByName' => 'Health Team',
        'CreatedDate' => '2025-06-22 11:00:00',
        'Image' => 'https://images.unsplash.com/photo-1614332287897-cdc485fa562d'
    ],
    [
        'Title' => 'Sleep Hygiene Tip',
        'Content' => 'Avoid screens 30 minutes before bed to improve sleep quality and wake up refreshed.',
        'ResourceType' => 'Tip',
        'CreatedByName' => 'Health Team',
        'CreatedDate' => '2025-06-27 13:00:00',
        'Image' => 'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91'
    ]
];

// Filter resources
$filtered_resources = $filter === 'All' ? $resources : array_filter($resources, function($resource) use ($filter) {
    return $resource['ResourceType'] === $filter;
});
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Health Resources - University of Ruhuna Medical Centre</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500&family=Roboto:wght@500;700;900&display=swap" rel="stylesheet"> 

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">

    <!-- Inline CSS for Page Header and Dark Mode -->
    <style>
        /* Color Variables */
        :root {
            --primary: rgb(86, 85, 183); /* Purple, added to match login.php, about.html, contact.html */
            --secondary: #ec4899; /* Pink, added to match login.php, about.html */
            --accent: #06b6d4; /* Cyan */
            --success: #10b981; /* Green */
            --error: #ef4444; /* Red */
            --background: #ffffff;
            --text: #000000;
            --light-bg: #f8f9fa;
            --dark-bg: rgb(8, 50, 92);
            --text-light: #ffffff;
        }

        .dark-mode {
            --background: #1a1a1a;
            --text: #e0e0e0;
            --light-bg: #2c2c2c;
            --dark-bg: rgb(56, 41, 150);
            --text-light: #e0e0e0;
        }

        /* Original Page Header Styles */
        .page-header {
            background-image: url('https://thumbs.dreamstime.com/b/top-view-healthy-lifestyles-concept-sport-equipments-fresh-foods-wood-background-web-banner-top-view-healthy-137377739.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .page-header .container {
            position: relative;
            z-index: 2;
        }

        .page-header h1, .page-header .breadcrumb-item a, .page-header .breadcrumb-item {
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        /* Dark Mode Styles */
        body {
            background-color: var(--background);
            color: var(--text);
        }

        .bg-light {
            background-color: var(--light-bg) !important;
        }

        .bg-dark {
            background-color: var(--dark-bg) !important;
        }

        .text-light {
            color: var(--text-light) !important;
        }

        .text-primary {
            color: var(--primary) !important;
        }

        .navbar.bg-white {
            background-color: var(--background) !important;
        }

        .navbar-light .navbar-nav .nav-link {
            color: var(--text);
        }

        .dark-mode .page-header::before {
            background: rgba(0, 0, 0, 0.5); /* Darker overlay for better header contrast */
        }

        .dark-mode .page-header h1, .dark-mode .page-header .breadcrumb-item a, .dark-mode .page-header .breadcrumb-item {
            color: var(--text-light) !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        }

        .dark-mode h1, .dark-mode h4, .dark-mode .border.rounded-pill {
            color: var(--text-light) !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        }

        .dark-mode .service-item {
            background-color: var(--light-bg);
            color: var(--text-light);
        }

        .dark-mode .service-item p, .dark-mode .service-item .text-muted {
            color: var(--text-light) !important;
        }

        .dark-mode .service-item a {
            color: var(--accent);
        }

        .dark-mode .service-item a:hover {
            color: var(--success);
        }

        .dark-mode .btn.btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            color: var(--text-light);
        }

        .dark-mode .btn.btn-primary.active {
            background-color: var(--secondary);
            border-color: var(--secondary);
            color: var(--text-light);
        }

        .dark-mode .btn.btn-outline-light.btn-social {
            background-color: var(--light-bg);
            color: var(--text-light);
        }

        .dark-mode .border {
            border-color: var(--text-light) !important;
        }

        .dark-mode .text-muted {
            color: var(--text-light) !important;
        }

        #darkModeToggle i {
            font-size: 1.2rem;
        }
    </style>
</head>

<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-grow text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

    

    <!-- Navbar Start -->
    <nav class="navbar navbar-expand-lg bg-white navbar-light sticky-top p-0 wow fadeIn" data-wow-delay="0.1s">
        <a href="index.php" class="navbar-brand d-flex align-items-center px-4 px-lg-5">
            <h1 class="m-0 text-primary"><i class="far fa-hospital me-3"></i>Medical Centre - University of Ruhuna</h1>
        </a>
        <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav ms-auto p-4 p-lg-0">
                <a href="index.php" class="nav-item nav-link ">Home</a>
                <a href="about.php" class="nav-item nav-link">About</a>
                <a href="health_resources.php" class="nav-item nav-link active">Health Resources</a>
                <a href="feature.php" class="nav-item nav-link">Opening Information</a>
                <a href="contact.php" class="nav-item nav-link">Contact</a>
                <button id="darkModeToggle" class="btn btn-primary rounded-circle ms-3" style="width: 40px; height: 40px;">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            <a href="login.php" class="btn btn-primary rounded-0 py-4 px-lg-5 d-none d-lg-block">LogIn/SignUp<i class="fa fa-arrow-right ms-3"></i></a>
        </div>
    </nav>
    <!-- Navbar End -->

    <!-- Page Header Start -->
    <div class="container-fluid page-header py-5 mb-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <h1 class="display-3 text-white mb-3 animated slideInDown">Health Resources</h1>
            <nav aria-label="breadcrumb animated slideInDown">
                
            </nav>
        </div>
    </div>
    <!-- Page Header End -->

    <!-- Health Resources Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                <p class="d-inline-block border rounded-pill py-1 px-4">Health Resources</p>
                <h1>Explore Our Health Resources</h1>
            </div>
            <div class="row g-4 mb-5">
                <div class="col-12 text-center">
                    <div class="d-flex justify-content-center gap-3">
                        <a href="?type=All" class="btn btn-primary py-2 px-4 <?php echo $filter === 'All' ? 'active' : ''; ?>">All</a>
                        <a href="?type=Article" class="btn btn-primary py-2 px-4 <?php echo $filter === 'Article' ? 'active' : ''; ?>">Articles</a>
                        <a href="?type=Video" class="btn btn-primary py-2 px-4 <?php echo $filter === 'Video' ? 'active' : ''; ?>">Videos</a>
                        <a href="?type=Tip" class="btn btn-primary py-2 px-4 <?php echo $filter === 'Tip' ? 'active' : ''; ?>">Tips</a>
                    </div>
                </div>
            </div>
            <?php if (empty($filtered_resources)): ?>
                <div class="text-center">
                    <p>No resources available.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($filtered_resources as $resource): ?>
                        <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                            <div class="service-item rounded overflow-hidden">
                                <img class="img-fluid" src="<?php echo htmlspecialchars($resource['Image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($resource['Title'], ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="p-4">
                                    <h4 class="mb-3"><?php echo htmlspecialchars($resource['Title'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                    <p><?php echo $resource['Content']; // Content includes safe HTML (links) ?></p>
                                    <p class="text-muted">Type: <?php echo htmlspecialchars($resource['ResourceType'], ENT_QUOTES, 'UTF-8'); ?> | Created by: <?php echo htmlspecialchars($resource['CreatedByName'], ENT_QUOTES, 'UTF-8'); ?> | Date: <?php echo htmlspecialchars($resource['CreatedDate'], ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Health Resources End -->

    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-light footer mt-5 pt-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-3 col-md-6">
                    <h5 class="text-light mb-4">Address</h5>
                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>University of Ruhuna, Matara, Sri Lanka</p>
                    <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+94 41 2222681</p>
                    <p class="mb-2"><i class="fa fa-envelope me-3"></i>medicalcentre@ruh.ac.lk</p>
                    <div class="d-flex pt-2">
                        <a class="btn btn-outline-light btn-social rounded-circle" href=""><i class="fab fa-twitter"></i></a>
                        <a class="btn btn-outline-light btn-social rounded-circle" href=""><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-outline-light btn-social rounded-circle" href=""><i class="fab fa-youtube"></i></a>
                        <a class="btn btn-outline-light btn-social rounded-circle" href=""><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <h5 class="text-light mb-4">Quick Links</h5>
                    <a class="btn btn-link" href="login.php">LogIn</a>
                    <a class="btn btn-link" href="about.html">About Us</a>
                    <a class="btn btn-link" href="health_resources.php">Health Resources</a>
                    <a class="btn btn-link" href="feature.php">Opening Information</a>
                    <a class="btn btn-link" href="contact.html">Contact Us</a>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="copyright">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        © <a class="border-bottom" href="#">Medical Centre-UOR</a>, All Right Reserved.
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->

    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square rounded-circle back-to-top"><i class="bi bi-arrow-up"></i></a>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/counterup/counterup.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
    <script>
        // Dark Mode Toggle Script
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const body = document.documentElement;

            // Check for saved preference
            if (localStorage.getItem('darkMode') === 'enabled') {
                body.classList.add('dark-mode');
                darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            }

            darkModeToggle.addEventListener('click', function() {
                body.classList.toggle('dark-mode');
                if (body.classList.contains('dark-mode')) {
                    darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                    localStorage.setItem('darkMode', 'enabled');
                } else {
                    darkModeToggle.innerHTML = '<i class="fas fa-moon"></i>';
                    localStorage.setItem('darkMode', 'disabled');
                }
            });
        });
    </script>
</body>
</html>
