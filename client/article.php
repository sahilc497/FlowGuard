<?php
// article.php
// Articles page for FlowGuard — same theme as About & Contact pages
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Articles — FlowGuard</title>
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

    /* Layout */
    .page{max-width:1200px;margin:0 auto;margin-top: calc(72px + 40px); padding:0 20px 40px 20px;display:grid;grid-template-columns:1fr 340px;gap:24px}
    @media (max-width:980px){ .page{grid-template-columns:1fr; margin-top: 100px;} }

    .content{display:flex;flex-direction:column;gap:24px}
    .hero{
      background: var(--surface);
      padding:32px; border-radius:var(--radius);
      box-shadow: 8px 8px 0px rgba(0,0,0,0.1);
      border: var(--border-width) solid var(--border-color);
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:24px;
    }
    .hero h1{margin:0;color: var(--primary); font-size: 2rem; font-weight: 800; text-transform: uppercase;}
    .hero p{margin-top:8px; color: var(--text-light); font-weight: 600;}

    .card{
      background: var(--surface);
      padding:32px;
      border-radius:var(--radius);
      box-shadow: 8px 8px 0px rgba(0,0,0,0.1);
      border: var(--border-width) solid var(--border-color);
    }

    .article{
      padding-bottom:24px;
      margin-bottom:24px;
      border-bottom: 2px solid var(--bg-body);
    }
    .article:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0}

    .article h3{margin:0 0 10px 0; color: var(--text-main); font-size: 20px; font-weight: 800; text-transform: uppercase;}
    .article p{margin:0; color: var(--text-light); line-height:1.6}
    .article .meta{font-size:0.85rem; color: var(--text-light); margin-top:12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: #EBF1FF; display: inline-block; padding: 4px 8px; border-radius: var(--radius); border: 1px solid #D0DFFF;}

    /* Sidebar */
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
    
    .cta{
      display:inline-flex;
      align-items: center;
      justify-content: center;
      padding:12px 24px;
      border-radius:var(--radius);
      background: var(--text-main);
      color:#fff;
      font-weight:700;
      text-decoration:none;
      transition: all 0.2s;
      text-transform: uppercase;
    }
    .cta:hover { background: var(--primary); text-decoration: none; transform: translateY(-2px); }

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

    /* Footer */
    footer{padding: 30px; background: var(--text-main); color: #fff; border-top: 4px solid var(--accent); text-align: center; font-size: 14px; margin-top: auto; font-weight: 600; text-transform: uppercase;}

    .muted{color: var(--text-light); font-size: 0.95rem;}
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
      <a href="about.php">About</a>
      <a href="article.php" style="color:#fff; text-decoration: underline;">Articles</a>
      <a href="farmer_login.php">Login</a>
  </nav>
</header>

<main class="page">
  <section class="content">
    <div class="hero">
      <div>
        <h1>Articles & Insights</h1>
        <p>Curated knowledge on irrigation, groundwater conservation, sensors, and smart farming.</p>
      </div>
    </div>

    <section class="card">
      <article class="article">
        <h3>Drip Irrigation Techniques</h3>
        <p>
          Drip irrigation delivers water directly to plant roots, minimizing evaporation and runoff.
          It is one of the most efficient irrigation methods for water-scarce regions.
        </p>
        <div class="meta">Category: Irrigation • Updated: 2025</div>
      </article>

      <article class="article">
        <h3>Rainwater Harvesting Best Practices</h3>
        <p>
          Harvesting rainwater helps recharge groundwater and reduces dependency on borewells.
          Proper filtration and storage are key to maintaining water quality.
        </p>
        <div class="meta">Category: Sustainability • Updated: 2025</div>
      </article>

      <article class="article">
        <h3>Soil Moisture Sensors Explained</h3>
        <p>
          Soil moisture sensors enable precision irrigation by measuring water availability in soil.
          Integrating sensors with automated motors prevents over-irrigation.
        </p>
        <div class="meta">Category: Sensors • Updated: 2025</div>
      </article>

      <article class="article">
        <h3>Groundwater Depletion and Its Impact</h3>
        <p>
          Excessive groundwater extraction leads to falling water tables and long-term agricultural risk.
          Monitoring usage and adopting smart irrigation is essential for sustainability.
        </p>
        <div class="meta">Category: Groundwater • Updated: 2025</div>
      </article>
    </section>
  </section>

  <aside class="sidebar">
    <div class="panel">
      <h3>Quick Links</h3>
      <p class="muted"><a href="index.php">Home</a></p>
      <p class="muted"><a href="about.php">About</a></p>
      <p class="muted"><a href="contact.php">Contact</a></p>
    </div>

    <div class="panel">
      <h3>Why Read These?</h3>
      <p class="muted">
        These articles help farmers, students, and researchers understand modern irrigation challenges
        and adopt data-driven water management.
      </p>
    </div>

    <div class="panel" style="text-align:center">
      <a class="cta" href="contact.php">Contact the Team</a>
    </div>
  </aside>
</main>

<footer>
  <div class="wrap">
    <div>© <?= date('Y') ?> FlowGuard • Knowledge for sustainable farming</div>
  </div>
</footer>

</body>
</html>
    