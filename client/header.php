<?php
// Shared header for analytics and reports (green theme preserved)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>FlowGuard | Analytics</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Bauhaus / Industrial Theme */
    :root {
      /* Palette */
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

    * { box-sizing: border-box; margin: 0; padding: 0; }
    
    body {
      background-color: var(--bg-body);
      color: var(--text-main);
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* Navbar - Bold, flat, high contrast */
    .navbar {
      background: var(--primary);
      color: var(--surface);
      padding: 0;
      display: flex;
      justify-content: space-between;
      align-items: stretch;
      border-bottom: 4px solid var(--accent);
    }
    
    .nav-brand {
      font-weight: 800;
      font-size: 24px;
      letter-spacing: -0.5px;
      background: var(--text-main);
      color: var(--surface);
      padding: 20px 32px;
      text-transform: uppercase;
    }

    .nav-links {
      display: flex;
      align-items: stretch;
    }

    .nav-links a {
      color: rgba(255,255,255,0.8);
      text-decoration: none;
      font-weight: 600;
      padding: 0 24px;
      display: flex;
      align-items: center;
      transition: all 0.2s;
      border-left: 1px solid rgba(255,255,255,0.1);
      text-transform: uppercase;
      font-size: 14px;
      letter-spacing: 0.5px;
    }
    
    .nav-links a:hover,
    .nav-links a.active {
      background: var(--secondary);
      color: var(--text-main);
      font-weight: 800;
    }

    .page-shell {
      width: 100%;
      max-width: 1200px;
      margin: 40px auto;
      flex: 1;
      padding: 0 20px 40px 20px;
    }

    /* Cards - Geometric, flat, bordered */
    .card {
      background: var(--surface);
      border: var(--border-width) solid var(--border-color);
      border-radius: var(--radius);
      padding: 24px;
      margin-bottom: 24px;
      box-shadow: 4px 4px 0px rgba(0,0,0,0.1); /* Hard shadow */
    }
    
    .card h2 {
      margin-bottom: 20px;
      color: var(--primary);
      font-weight: 800;
      text-transform: uppercase;
      font-size: 18px;
      border-bottom: 2px solid var(--bg-body);
      padding-bottom: 12px;
    }

    /* KPI Cards - Bold geometric blocks */
    .card.kpi-summary {
      background: var(--surface);
      border: var(--border-width) solid var(--border-color);
      box-shadow: none;
      position: relative;
    }
    .card.kpi-summary::after {
      content: '';
      position: absolute;
      top: 0; left: 0; bottom: 0;
      width: 6px;
      background: var(--secondary);
    }
    
    .kpi-summary div:first-child {
      color: var(--text-light);
      font-weight: 700;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 8px;
    }

    /* Buttons - Rectangular */
    .pill-btn {
      background: var(--text-main);
      color: var(--surface);
      border: none;
      border-radius: var(--radius);
      padding: 12px 24px;
      font-weight: 700;
      text-transform: uppercase;
      cursor: pointer;
      transition: transform 0.1s;
      font-size: 13px;
      letter-spacing: 0.5px;
    }
    .pill-btn:hover {
      background: var(--primary);
      transform: translateY(-2px);
    }
    .pill-btn.secondary {
      background: transparent;
      color: var(--text-main);
      border: 2px solid var(--text-main);
    }
    .pill-btn.secondary:hover {
      background: var(--text-main);
      color: var(--surface);
    }
    
    /* Grid */
    .grid { display: grid; gap: 24px; }
    .grid.two { grid-template-columns: 1fr 1fr; }
    .grid.four { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
    
    @mediaWith(max-width: 900px) {
      .grid.two { grid-template-columns: 1fr; }
      .navbar { flex-direction: column; }
      .nav-links { flex-direction: column; }
      .nav-links a { padding: 16px; border-left: none; border-top: 1px solid rgba(255,255,255,0.1); }
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="nav-brand">FlowGuard</div>
    <div class="nav-links">
      <a href="farmer_dashboard.php">Dashboard</a>
      <a href="analytics.php">Analytics</a>
      <a href="reports.php">Reports</a>
    </div>
  </nav>
  <div class="page-shell">
