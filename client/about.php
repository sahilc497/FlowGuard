<?php
// about.php
// Description: About page for FlowGuard — matches the same visual theme as contact.php.
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>About — FlowGuard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Bauhaus / Industrial Theme */
    :root {
      --primary: #0F4C5C;      /* Deep Teal */
      --secondary: #1CA7EC;    /* Aqua Blue */
      --accent: #4F772D;       /* Olive Green */
      --bg-body: #F4F4F4;      /* Light Neutral */
      --surface: #FFFFFF;      /* Pure White */
      --text-main: #1E1E1E;    /* Charcoal */
      --text-light: #555555;
      
      --border-width: 2px;
      --border-color: #1E1E1E;
      --radius: 0px;
    }

    *{box-sizing:border-box; margin: 0; padding: 0;}
    html,body{height:100%;font-family:'Poppins',sans-serif;background: var(--bg-body); color: var(--text-main); line-height: 1.6;}
    a{color:var(--primary);text-decoration:none; font-weight: 700;}
    a:hover{text-decoration:underline; color: var(--secondary);}

    /* ---------- NAVBAR ---------- */
    .navbar{
      position:fixed;
      top:0;
      left:0;
      right:0;
      height:72px;
      background: var(--primary);
      color: #fff;
      padding:0 32px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      z-index:1100;
      border-bottom: 4px solid var(--accent);
    }
    .brand{display:flex;align-items:center;gap:12px}
    .brand h1{font-size:24px;font-weight:800;margin:0;color: #fff; letter-spacing: -0.5px; text-transform: uppercase;}
    .nav-links a{color: rgba(255,255,255,0.8);text-decoration:none;margin-left:20px;font-size:14px; font-weight: 700; transition: color 0.2s; text-transform: uppercase;}
    .nav-links a:hover{color: #fff; text-decoration: underline;}

    /* Page layout */
    .page{max-width:1200px;margin:0 auto;margin-top: calc(72px + 40px); padding:0 20px 40px 20px;display:grid;grid-template-columns:1fr 340px;gap:24px}
    @media (max-width:980px){ .page{grid-template-columns:1fr; margin-top: 100px;} }

    .content{display:flex;flex-direction:column;gap:24px}
    .hero{
      background: var(--surface);
      padding:32px; border-radius:var(--radius); 
      border: var(--border-width) solid var(--border-color);
      box-shadow: 8px 8px 0px rgba(0,0,0,0.1);
      display:flex;justify-content:space-between;align-items:center;gap:24px;
    }
    .hero h1{margin:0;color: var(--primary); font-size:2rem; font-weight: 800; text-transform: uppercase;}
    .hero p{margin-top:8px; color: var(--text-light); font-weight: 600;}

    /* cards & sections */
    .card{
        background: var(--surface); 
        padding:32px; 
        border-radius:var(--radius); 
        border: var(--border-width) solid var(--border-color);
        box-shadow: 8px 8px 0px rgba(0,0,0,0.1);
    }
    .two-col{display:grid;grid-template-columns:1fr 1fr;gap:24px}
    @media (max-width:860px){ .two-col{grid-template-columns:1fr} }

    h2{margin:0 0 15px 0; color: var(--text-main); font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid var(--bg-body); display: inline-block; padding-bottom: 5px;}
    h3{margin:0 0 10px 0; color: var(--primary); font-weight: 700; font-size: 1.1rem; text-transform: uppercase;}
    p{margin:0; color: var(--text-light);}

    .feature-list{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-top:20px}
    @media (max-width:700px){ .feature-list{grid-template-columns:1fr} }
    .feature{background: var(--bg-body); border:2px solid #E0E0E0; padding:16px; border-radius:var(--radius);}
    .feature strong { color: var(--text-main); display: block; margin-bottom: 4px; text-transform: uppercase;}

    .tech-list{display:flex;flex-wrap:wrap;gap:12px;margin-top:16px}
    .tech-pill{background: var(--text-main); color: var(--surface); padding:8px 14px; border-radius:var(--radius); font-weight:700; font-size:0.85rem; text-transform: uppercase;}

    /* sidebar */
    .sidebar{display:flex;flex-direction:column;gap:24px}
    .panel{
        background: var(--surface);
        border: var(--border-width) solid var(--border-color);
        padding:24px;
        border-radius:var(--radius);
        box-shadow: 8px 8px 0px rgba(0,0,0,0.1);
    }
    .panel h3 { color: var(--primary); font-weight: 800; margin-bottom: 15px; font-size: 1.1rem; text-transform: uppercase; border-bottom: 2px solid var(--bg-body); padding-bottom: 10px;}
    
    .panel p.muted a { color: var(--text-main); }
    .panel p.muted a:hover { color: var(--primary); }

    .cta{display:inline-flex; align-items: center; justify-content: center; padding:12px 24px; border-radius:var(--radius); background: var(--text-main); color:#fff; font-weight:700; text-decoration:none; transition: all 0.2s; text-transform: uppercase;}
    .cta:hover { background: var(--primary); text-decoration: none; transform: translateY(-2px);}
    
    .btn-action {
        display: block;
        width: 100%;
        padding: 12px;
        background: var(--text-main);
        color: #fff;
        text-align: center;
        text-transform: uppercase;
        font-weight: 700;
        border: none;
        cursor: pointer;
        margin-top: 10px;
        transition: background 0.2s;
        text-decoration: none;
    }
    .btn-action:hover { background: var(--primary); text-decoration: none;}

    /* footer */
    footer{padding: 30px; background: var(--text-main); color: #fff; border-top: 4px solid var(--accent); text-align: center; font-size: 14px; margin-top: auto; font-weight: 600; text-transform: uppercase;}
    footer a { color: #fff; text-decoration: underline; }

    /* utility */
    .muted{color: var(--text-light); font-size: 0.95rem;}
    .meta{font-size: 1rem; color: var(--text-light); margin-bottom: 16px; font-weight: 500;}
    .spacer{height:12px}
    a:focus, button:focus, input:focus{outline:3px solid var(--primary); outline-offset:2px;}
  </style>
</head>
<body>
  <!-- Navbar -->
  <header class="navbar" role="banner">
    <div class="brand">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M12 2C9 5 6 6 4 7v9c0 1 0 3 2 4 2 1 6 1 10 0 3-1 3-4 3-5V7c-2-1-5-2-7-5z" fill="#fff" opacity="0.12"></path>
        <circle cx="12" cy="10" r="3" fill="#fff"></circle>
      </svg>
      <h1>FlowGuard</h1>
    </div>
    <nav class="nav-links" role="navigation">
        <a href="index.php">Home</a>
        <a href="about.php" style="color:#fff; text-decoration: underline;">About</a>
        <a href="contact.php">Contact</a>
        <a href="farmer_login.php">Login</a>
    </nav>
  </header>

  <main class="page" role="main">
    <section class="content">
      <div class="hero" role="region" aria-labelledby="about-heading">
        <div>
          <h1 id="about-heading">About FlowGuard</h1>
          <p>Sustainable irrigation for a water-secure future.</p>
        </div>
      </div>

      <section class="card" aria-labelledby="mission">
        <h2 id="mission">Our Mission</h2>
        <p class="meta">To empower agricultural communities with tools that reduce water waste, provide actionable insights from sensor data, and make irrigation smarter and more sustainable.</p>
        <div class="spacer"></div>
        <div class="two-col">
          <div>
            <h3>Why FlowGuard?</h3>
            <p class="muted">We believe efficient water management is essential for crop health and long-term sustainability. FlowGuard combines simple hardware integration with approachable software so farms of any scale can benefit.</p>
            <div class="feature-list">
              <div class="feature"><strong>Real-time monitoring</strong><div class="muted">Sensor-driven insights for immediate action.</div></div>
              <div class="feature"><strong>Automated alerts</strong><div class="muted">Notifications for motor overruns and low groundwater.</div></div>
              <div class="feature"><strong>Easy integration</strong><div class="muted">Works with common microcontrollers and flow sensors.</div></div>
              <div class="feature"><strong>Open & extensible</strong><div class="muted">Designed to be extended by developers and researchers.</div></div>
            </div>
          </div>

          <div>
            <h3>How it works</h3>
            <p class="muted">Devices stream flow and runtime data to the server. The dashboard aggregates usage, computes daily metrics, and triggers alerts when thresholds are exceeded. Admins can review logs, view groundwater trends, and manage motors.</p>

            <div style="margin-top:12px">
              <strong style="color:var(--primary); text-transform: uppercase;">Core components</strong>
              <div class="tech-list" aria-hidden="false">
                <div class="tech-pill">Sensors (flow, level)</div>
                <div class="tech-pill">Microcontroller (ESP, Arduino)</div>
                <div class="tech-pill">PHP / MySQL backend</div>
                <div class="tech-pill">Responsive dashboard</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="card" aria-labelledby="roadmap">
        <h2 id="roadmap">Roadmap & Priorities</h2>
        <p class="muted">Planned improvements focus on better analytics, user-friendly device onboarding, regional irrigation recommendations, and offline-capable data sync for remote farms.</p>
        <div class="spacer"></div>
        <ol style="margin:0 0 0 18px;color:var(--text-light);line-height:1.6; font-weight: 500;">
          <li>Enhanced analytics & visualizations</li>
          <li>Mobile-friendly interface & push alerts</li>
          <li>Device auto-discovery and secure pairing</li>
          <li>Local caching for intermittent connectivity</li>
        </ol>
      </section>

      <section class="card" aria-labelledby="team">
        <h2 id="team">Team</h2>
        <p class="muted" style="margin:0">FlowGuard is created by a small cross-disciplinary team covering machine learning, web development, documentation and hardware. For individual contact details visit the <a href="contact.php">Contact page</a>.</p>
      </section>
    </section>

    <aside class="sidebar" role="complementary" aria-label="About sidebar">
      <div class="panel" aria-labelledby="support">
        <h3 id="support">Get support</h3>
        <p class="muted" style="margin:0">For questions, access, or collaboration reach out to <a href="mailto:support@flowguard.local" style="font-weight: 700; color: var(--text-main);">support@flowguard.local</a>.</p>
        <a href="mailto:support@flowguard.local" class="btn-action">Email Support</a>
      </div>

      <div class="panel" aria-labelledby="quick-links">
        <h3 id="quick-links">Quick Links</h3>
        <p class="muted" style="margin:0 0 8px 0"><a href="index.php">Home</a></p>
        <p class="muted" style="margin:0 0 8px 0"><a href="contact.php">Contact</a></p>
        <p class="muted" style="margin:0"><a href="#">Project Repo</a></p>
      </div>

      <div class="panel" style="text-align:center">
        <a class="cta" href="contact.php" aria-label="Open contact page">Contact the Team</a>
      </div>
    </aside>
  </main>

  <footer role="contentinfo">
    <div class="wrap">
      <div>© <?= date('Y') ?> FlowGuard • committed to water-smart farming</div>
    </div>
  </footer>
</body>
</html>
