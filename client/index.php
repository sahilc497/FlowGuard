<?php
// PHP logic
$site_title = "FlowGuard - Water Conservation & Agriculture";
$page_title = "Home";

$article_info = [
    "Drip Irrigation Techniques",
    "Rainwater Harvesting Best Practices",
    "Soil Moisture Sensors Explained",
    "Sustainable Farming in Arid Regions"
];

$related_links = [
    "Government Schemes",
    "Agricultural News",
    "Expert Contact",
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title . " | " . $site_title; ?></title>
    
    <!-- Fonts & Styles -->
    <style>
        /* Bauhaus / Industrial Theme */
        :root {
            --primary: #0F4C5C;      /* Deep Teal */
            --secondary: #1CA7EC;    /* Aqua Blue */
            --accent: #4F772D;       /* Olive Green (Agri) */
            --bg-body: #F4F4F4;      /* Light Neutral */
            --surface: #FFFFFF;      /* Pure White */
            --text-main: #1E1E1E;    /* Charcoal */
            --text-light: #555555;
            
            /* Status Colors */
            --status-ok: #2A9D8F;    /* Success Green */
            --status-warn: #F4A261;  /* Amber */
            --status-err: #E63946;   /* Alert Red */
            --status-off: #999999;
            
            /* Borders & spacing */
            --border-width: 2px;
            --border-color: #1E1E1E; /* High contrast border */
            --radius: 0px;           /* Sharp corners */
        }

        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
        }

        /* Header */
        header {
            background: var(--primary);
            color: var(--surface);
            padding: 24px 32px;
            border-bottom: 4px solid var(--accent);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }

        header nav a {
            color: var(--surface);
            margin-left: 32px;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        header nav a:hover {
            color: var(--secondary);
        }

        /* Hero Section */
        .hero-section {
            background: var(--primary);
            color: var(--surface);
            padding: 80px 24px;
            text-align: center;
            border-bottom: 2px solid var(--border-color);
        }

        .hero-section h2 {
            font-size: 2.5rem;
            margin: 0 0 24px 0;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: -1px;
        }

        .hero-section p {
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto 32px;
            line-height: 1.8;
            color: rgba(255,255,255,0.9);
        }

        /* Features Grid */
        .features-grid {
            max-width: 1200px;
            margin: 48px auto;
            padding: 0 24px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
        }

        .feature-card {
            background: var(--surface);
            border: var(--border-width) solid var(--border-color);
            padding: 32px 24px;
            text-align: center;
            box-shadow: 6px 6px 0px rgba(0,0,0,0.1);
            transition: transform 0.1s;
        }

        .feature-card:hover {
            transform: translateY(-2px);
        }

        .feature-card h3 {
            font-size: 1.25rem;
            color: var(--primary);
            margin: 16px 0;
            font-weight: 700;
            text-transform: uppercase;
        }

        .feature-card p {
            color: var(--text-light);
            font-size: 0.95rem;
            line-height: 1.6;
            margin: 0;
        }

        /* CTA Section */
        .cta-section {
            background: var(--surface);
            padding: 48px 24px;
            text-align: center;
            border-top: 2px solid var(--border-color);
            border-bottom: 2px solid var(--border-color);
        }

        .cta-section h2 {
            font-size: 1.75rem;
            margin: 0 0 24px 0;
            font-weight: 800;
            color: var(--primary);
            text-transform: uppercase;
        }

        .cta-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .cta-buttons a {
            padding: 12px 28px;
            background: var(--primary);
            color: var(--surface);
            text-decoration: none;
            font-weight: 700;
            border: 2px solid var(--primary);
            transition: all 0.1s;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        .cta-buttons a:hover {
            background: var(--surface);
            color: var(--primary);
        }

        /* Content Container */
        .content-container {
            max-width: 1200px;
            margin: 48px auto;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 32px;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .main-content > div {
            background: var(--surface);
            border: var(--border-width) solid var(--border-color);
            padding: 24px;
            box-shadow: 6px 6px 0px rgba(0,0,0,0.1);
        }

        .main-content h3 {
            margin: 0 0 16px 0;
            font-size: 1.1rem;
            color: var(--primary);
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 2px solid var(--accent);
            padding-bottom: 12px;
        }

        .main-content p {
            margin: 0;
            color: var(--text-light);
            line-height: 1.8;
        }

        .sidebar-content {
            display: flex; 
            flex-direction: column;
            gap: 24px;
        }

        .sidebar-content > div {
            background: var(--surface);
            border: var(--border-width) solid var(--border-color);
            padding: 24px;
            box-shadow: 6px 6px 0px rgba(0,0,0,0.1);
        }

        .sidebar-content h3 {
            margin: 0 0 16px 0;
            font-size: 1rem;
            color: var(--primary);
            font-weight: 700;
            text-transform: uppercase;
        }

        .sidebar-content a {
            display: block;
            margin: 8px 0;
            color: var(--secondary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .sidebar-content a:hover {
            color: var(--primary);
        }

        /* Footer */
        footer {
            background: var(--text-main);
            color: var(--surface);
            padding: 48px 24px;
            text-align: center;
            border-top: 4px solid var(--accent);
            margin-top: 48px;
        }

        footer p {
            margin: 0;
            opacity: 0.8;
            font-size: 0.9rem;
        }

        @media (max-width: 900px) {
            .content-container {
                grid-template-columns: 1fr;
            }

            header {
                flex-direction: column;
                gap: 16px;
            }

            header nav a {
                margin-left: 16px;
            }

            .hero-section h2 {
                font-size: 1.75rem;
            }

            .cta-buttons {
                flex-direction: column;
            }

            .cta-buttons a {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <header>
        <h1>FlowGuard</h1>
        <nav>
            <a href="admin_login.php">Admin Panel</a>
            <a href="farmer_login.php">Farmer Login</a>
        </nav>
    </header>


    <!-- Hero Section -->
    <section class="hero-section">
        <h2>Sustainable Agriculture & Water Management</h2>
        <p>FlowGuard provides farmers with cutting-edge tools, real-time data, and expert knowledge to maximize crop production while minimizing water wastage.</p>
    </section>

    <!-- Features Grid -->
    <section class="features-grid">
        <div class="feature-card">
            <h3>Real-Time Monitoring</h3>
            <p>Track water usage and motor performance with live updates and instant alerts.</p>
        </div>
        <div class="feature-card">
            <h3>Smart Analysis</h3>
            <p>AI-powered insights to optimize irrigation patterns and reduce water waste by up to 30%.</p>
        </div>
        <div class="feature-card">
            <h3>Expert Support</h3>
            <p>Access agricultural expertise and government subsidy information in one place.</p>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <h2>Start Optimizing Your Water Usage Today</h2>
        <div class="cta-buttons">
            <a href="farmer_login.php">Farmer Login</a>
            <a href="about.php">Learn More</a>
        </div>
    </section>

    <!-- Main Content -->
    <div class="content-container">
        <main class="main-content">
            <div>
                <h3>Latest Articles</h3>
                <p><strong>The Future of Smart Irrigation:</strong> Discover how IoT and AI are revolutionizing water efficiency in farming and reducing water usage by up to 30% through precise control.</p>
                <p><strong>Conservation Success Stories:</strong> Profiles of farmers who have dramatically reduced their water footprint by adopting rainwater collection and efficient storage solutions.</p>
            </div>

            <div>
                <h3>Focus Areas</h3>
                <p>
                    • Drip Irrigation Techniques<br>
                    • Rainwater Harvesting Best Practices<br>
                    • Soil Moisture Sensors Explained<br>
                    • Sustainable Farming in Arid Regions
                </p>
            </div>

            <div>
                <h3>Resources</h3>
                <p>
                    • Government Schemes<br>
                    • Agricultural News<br>
                    • Expert Contact
                </p>
            </div>
        </main>

        <aside class="sidebar-content">
            <div>
                <h3>Quick Search</h3>
                <p>Search our database for articles and resources on water conservation, irrigation techniques, and sustainable farming practices.</p>
            </div>

            <div>
                <h3>Live News</h3>
                <p><strong>Subsidies:</strong> New government subsidy for micro-irrigation systems.</p>
                <p><strong>Alert:</strong> Expert warns of water scarcity, urging proactive measures.</p>
                <p><strong>Success:</strong> Village achieves 100% water self-sufficiency.</p>
            </div>

            <div>
                <h3>Get Started</h3>
                <a href="farmer_login.php">→ Farmer Login</a>
                <a href="admin_login.php">→ Admin Panel</a>
                <a href="about.php">→ About Us</a>
            </div>
        </aside>
    </div>

    <footer>
        <p>&copy; <?= date('Y') ?> FlowGuard — Smart Water Monitoring System. All rights reserved.</p>
    </footer>
        </div>
    </footer>

</body>
</html>