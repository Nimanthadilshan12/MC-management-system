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
        'Image' => 'https://images.unsplash.com/photo-1516321310764-2b6c5e48c5e8'
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
        'Image' => 'https://images.unsplash.com/photo-1506459225024-842ab81b1956'
    ],
    [
        'Title' => 'Preventing Common Colds',
        'Content' => 'Frequent colds can disrupt your studies. This NIH article explains how to prevent and manage colds effectively. <a href="https://www.niaid.nih.gov/diseases-conditions/colds" target="_blank">Read more</a>.',
        'ResourceType' => 'Article',
        'CreatedByName' => 'Health Team',
        'CreatedDate' => '2025-06-15 16:00:00',
        'Image' => 'https://images.unsplash.com/photo-1583324116142-9e83b1b3f5bc'
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
        'Image' => 'https://images.unsplash.com/photo-1612278675550-1f4b4a8e301d'
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
        'Image' => 'https://images.unsplash.com/photo-1516321310764-2b6c5e48c5e8'
    ],
    // Tips
    [
        'Title' => 'Hydration Tip',
        'Content' => 'Drink at least 8 glasses of water daily to stay hydrated, boost energy, and improve focus.',
        'ResourceType' => 'Tip',
        'CreatedByName' => 'Health Team',
        'CreatedDate' => '2025-06-02 08:00:00',
        'Image' => 'https://images.unsplash.com/photo-1551218372-5e6c0e7d6076'
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
        'Image' => 'https://images.unsplash.com/photo-1506126279646-a697353d3166'
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Resources - University Medical Centre</title>
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

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #a5b4fc,rgb(198, 168, 249), #22d3ee);
            min-height: 100vh;
            padding: 100px 20px;
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
            text-align: center;
        }

        .filter-buttons {
            margin-bottom: 30px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .filter-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .filter-btn.active, .filter-btn:hover {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
            transform: scale(1.05);
        }

        .resource-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(240, 245, 255, 0.95));
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 50, 120, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            animation: fadeInUp 1.2s ease;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .resource-card img {
            width: 150px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }

        .resource-card img:hover {
            transform: scale(1.1);
        }

        .resource-content {
            flex: 1;
            text-align: left;
        }

        .resource-card h3 {
            font-family: 'Rubik', sans-serif;
            font-size: 1.5rem;
            background: linear-gradient(to right, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .resource-card p {
            font-size: 1rem;
            color: var(--text);
            margin-bottom: 10px;
            animation: popIn 0.5s ease;
        }

        .resource-card .meta {
            font-size: 0.9rem;
            color: var(--text);
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
            margin: 20px auto;
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

        @keyframes popIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        @media (max-width: 768px) {
            body {
                padding: 80px 15px;
            }
            .logo {
                max-width: 150px;
            }
            h1 {
                font-size: 3rem;
            }
            .btn-primary {
                padding: 14px 30px;
                font-size: 1.2rem;
            }
            .resource-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .resource-card img {
                width: 100%;
                height: auto;
                max-height: 150px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 60px 10px;
            }
            .logo {
                max-width: 120px;
            }
            h1 {
                font-size: 2.5rem;
            }
            .btn-primary {
                padding: 12px 25px;
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="https://upload.wikimedia.org/wikipedia/en/2/2e/University_of_Ruhuna_logo.png" alt="University of Ruhuna Logo" class="logo">
        <h1 style="text-align: center;">Health Resources</h1>
        <div class="filter-buttons">
            <a href="?type=All" class="filter-btn <?php echo $filter === 'All' ? 'active' : ''; ?>">All</a>
            <a href="?type=Article" class="filter-btn <?php echo $filter === 'Article' ? 'active' : ''; ?>">Articles</a>
            <a href="?type=Video" class="filter-btn <?php echo $filter === 'Video' ? 'active' : ''; ?>">Videos</a>
            <a href="?type=Tip" class="filter-btn <?php echo $filter === 'Tip' ? 'active' : ''; ?>">Tips</a>
        </div>
        <?php if (empty($filtered_resources)): ?>
            <p style="animation: popIn 0.5s ease;">No resources available.</p>
        <?php else: ?>
            <?php foreach ($filtered_resources as $resource): ?>
                <div class="resource-card">
                    <img src="<?php echo htmlspecialchars($resource['Image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($resource['Title'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="resource-content">
                        <h3><?php echo htmlspecialchars($resource['Title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p><?php echo $resource['Content']; // Content includes safe HTML (links) ?></p>
                        <p class="meta">Type: <?php echo htmlspecialchars($resource['ResourceType'], ENT_QUOTES, 'UTF-8'); ?> | Created by: <?php echo htmlspecialchars($resource['CreatedByName'], ENT_QUOTES, 'UTF-8'); ?> | Date: <?php echo htmlspecialchars($resource['CreatedDate'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <a class="btn btn-primary" href="index.php"><i class="fas fa-arrow-left me-2"></i>Back to Home</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>