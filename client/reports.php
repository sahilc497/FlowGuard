<?php
session_start();
if (!isset($_SESSION['farmer_id'])) {
    header("Location: farmer_login.php");
    exit();
}
// We will inline the header logic or just use the structure directly to ensure full control over styles
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>FlowGuard | Reports</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  
  <style>
    /* Bauhaus Theme */
    :root {
      --primary: #0F4C5C;      /* Deep Teal */
      --secondary: #1CA7EC;    /* Aqua Blue */
      --accent: #4F772D;       /* Olive Green */
      --bg-body: #F4F4F4;      /* Light Neutral */
      --surface: #FFFFFF;      /* Pure White */
      --text-main: #1E1E1E;    /* Charcoal */
      --text-light: #555555;
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
      
      background-image: radial-gradient(var(--text-light) 1px, transparent 1px);
      background-size: 20px 20px;
      animation: fadeIn 0.6s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* HEADER */
    .header {
      background: var(--primary);
      color: var(--surface);
      padding: 0 40px;
      height: 70px;
      display: flex;
      justify-content: space-between;
      align-items: stretch;
      border-bottom: 4px solid var(--accent);
    }
    .header div:first-child { display: flex; align-items: center; gap: 14px; }
    .header h1 {
        font-size: 24px;
        font-weight: 800;
        color: var(--surface);
        letter-spacing: -0.5px;
        text-transform: uppercase;
        margin: 0;
    }
    
    .nav-links { display: flex; align-items: stretch; height: 70px; }
    .nav-links a {
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        font-weight: 700;
        padding: 0 20px;
        display: flex;
        align-items: center;
        transition: all 0.2s;
        border-left: 1px solid rgba(255,255,255,0.1);
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.3px;
    }
    .nav-links a:first-child { border-left: none; }
    .nav-links a:hover {
        background: var(--secondary);
        color: var(--text-main);
    }
    .nav-links a.active {
        background: var(--secondary);
        color: var(--text-main);
        font-weight: 800;
    }

    /* MAIN */
    .page-shell {
        max-width: 1200px;
        width: 100%;
        margin: 0 auto;
        padding: 40px 20px;
        flex: 1;
    }

    /* CARDS */
    .card {
      background: var(--surface);
      border: var(--border-width) solid var(--border-color);
      box-shadow: 6px 6px 0px rgba(0,0,0,0.1);
      padding: 30px;
      margin-bottom: 30px;
      position: relative;
      border-top: 6px solid var(--secondary);
      transition: transform 0.2s;
    }
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 8px 8px 0px rgba(0,0,0,0.15);
    }
    
    .card h2 {
        font-size: 1.5rem;
        font-weight: 800;
        text-transform: uppercase;
        margin-bottom: 20px;
        color: var(--primary);
        border-bottom: 2px solid var(--bg-body);
        padding-bottom: 10px;
    }

    /* Controls */
    .controls {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 24px;
    }
    
    .pill-btn {
        background: var(--bg-body);
        color: var(--text-main);
        border: 2px solid var(--border-color);
        padding: 10px 20px;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 4px 4px 0px rgba(0,0,0,0.1);
    }
    .pill-btn:hover {
        transform: translateY(-2px);
        box-shadow: 6px 6px 0px rgba(0,0,0,0.1);
        background: var(--surface);
    }
    .pill-btn.active {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
        box-shadow: inset 2px 2px 5px rgba(0,0,0,0.2);
        transform: none;
    }

    .action-btn {
        margin-left: auto;
        background: var(--accent);
        color: #fff;
        border: none;
        border: 2px solid var(--border-color); /* standard border */
    }
    .action-btn:hover { background: #3d5a22; color: #fff; }

    /* KPI Grid */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .kpi-card {
        background: var(--bg-body);
        border: 2px solid var(--border-color);
        padding: 20px;
        text-align: center;
        position: relative;
    }
    .kpi-label {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-light);
        margin-bottom: 8px;
    }
    .kpi-value {
        font-size: 2rem;
        font-weight: 800;
        color: var(--primary);
        font-family: 'Space Mono', monospace;
    }

    /* Table */
    .table-container {
        overflow-x: auto;
        border: 2px solid var(--border-color);
    }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
        font-family: 'Space Mono', monospace;
    }
    th {
        background: var(--text-main);
        color: #fff;
        text-align: left;
        padding: 14px 20px;
        font-weight: 700;
        text-transform: uppercase;
        font-family: 'Inter', sans-serif;
        font-size: 0.8rem;
    }
    td {
        padding: 12px 20px;
        border-bottom: 1px solid #E0E0E0;
        color: var(--text-main);
    }
    tr:nth-child(even) { background: #FAFAFA; }
    tr:hover { background: #eef; }

    /* Footer */
    footer {
        background: var(--text-main);
        color: var(--surface);
        padding: 24px;
        text-align: center;
        border-top: 4px solid var(--accent);
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
    }

    @media (max-width: 900px) {
        .header { padding: 0 20px; }
        .controls { flex-direction: column; }
        .action-btn { margin-left: 0; }
    }
  </style>
</head>
<body>

  <header class="header">
    <div>
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M12 2C9 5 6 6 4 7v9c0 1 0 3 2 4 2 1 6 1 10 0 3-1 3-4 3-5V7c-2-1-5-2-7-5z" fill="#fff" opacity="0.12"></path>
        <circle cx="12" cy="10" r="3" fill="#fff"></circle>
      </svg>
      <h1>FlowGuard</h1>
    </div>
    <nav class="nav-links">
      <a href="farmer_dashboard.php">Dashboard</a>
      <a href="reports.php" class="active">Reports</a>
      <a href="analytics.php">Analytics</a>
      <a href="chart.php">Charts</a>
    </nav>
  </header>

  <div class="page-shell">
    
    <div style="margin-bottom: 20px;">
        <a href="sens1.php" style="display:inline-block; font-weight:700; text-decoration:none; color:var(--text-main); border-bottom:2px solid var(--secondary);">← BACK TO LIVE SENSORS</a>
    </div>

    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:2px solid var(--bg-body); padding-bottom:10px;">
        <h2 style="margin:0; border:none; padding:0;">📊 Data Reports</h2>
        <span style="font-size:0.8rem; font-weight:700; color:var(--text-light);">DATA SOURCE: SENS1.PHP</span>
      </div>
      
      <div class="controls">
        <button class="pill-btn rpt-btn active" data-range="24h">24-Hour</button>
        <button class="pill-btn rpt-btn" data-range="week">Weekly</button>
        <button class="pill-btn rpt-btn" data-range="all">All Data</button>
        
        <button class="pill-btn action-btn" id="csvBtn">Download CSV</button>
        <button class="pill-btn action-btn" id="printBtn" style="background:var(--text-main);">Print PDF</button>
      </div>

      <!-- KPI Summary -->
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-label">Total Readings</div>
          <div id="r-total" class="kpi-value">--</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Average Flow</div>
          <div id="r-avg" class="kpi-value">--</div>
          <div style="font-size:0.75rem; font-weight:600; color:var(--text-light); margin-top:5px;">LITERS / MIN</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Min Flow</div>
          <div id="r-min" class="kpi-value">--</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Max Flow</div>
          <div id="r-max" class="kpi-value">--</div>
        </div>
      </div>
      
      <!-- Table -->
      <div class="table-container">
        <table id="reportTable">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Water Flow (L/min)</th>
              <th>Soil Moisture</th>
              <th>Temperature</th>
              <th>Humidity</th>
            </tr>
          </thead>
          <tbody>
            <!-- Populated via JS -->
          </tbody>
        </table>
      </div>

    </div>

    <!-- Notes Card -->
    <div class="card" style="border-top-color: var(--accent);">
      <h2 style="font-size:1.1rem; color:var(--text-main);">System Notes</h2>
      <ul style="padding-left:20px; line-height:1.6; color:var(--text-light); font-size:0.9rem;">
        <li><strong>Read-Only Access:</strong> Reports are generated from cached sensor data to ensure optimal system performance.</li>
        <li><strong>Data Filtering:</strong> Filters are applied at the API level based on timestamp ranges.</li>
        <li><strong>Export:</strong> Use the export buttons above to save data for offline analysis or compliance reporting.</li>
      </ul>
    </div>

  </div>

  <footer>
    <p>© 2026 FlowGuard. All rights reserved • Smart Water Monitoring</p>
  </footer>

  <script>
  const apiBase = "sens1.php?data_api=1";
  let currentRange = "24h";
  let currentData = [];

  const cards = {
    total: document.getElementById("r-total"),
    avg: document.getElementById("r-avg"),
    min: document.getElementById("r-min"),
    max: document.getElementById("r-max"),
  };

  document.querySelectorAll(".rpt-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      // Manage active state visually
      document.querySelectorAll(".rpt-btn").forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
      
      currentRange = btn.dataset.range;
      loadReport();
    });
  });

  document.getElementById("csvBtn").addEventListener("click", downloadCsv);
  document.getElementById("printBtn").addEventListener("click", () => window.print());

  function loadReport() {
    // Show loading state
    document.querySelector("#reportTable tbody").innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px;">Loading data...</td></tr>';
    
    fetch(`${apiBase}&range=${encodeURIComponent(currentRange)}`, { credentials: "same-origin" })
      .then(r => r.json())
      .then(json => {
        if (!json || json.ok !== true || !Array.isArray(json.data)) {
          console.warn("Invalid data from sens1.php");
          document.querySelector("#reportTable tbody").innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px; color:red;">Failed to load data.</td></tr>';
          return;
        }
        currentData = json.data.slice().reverse(); // oldest first
        updateCards();
        renderTable();
      })
      .catch(err => {
          console.error(err);
          document.querySelector("#reportTable tbody").innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px; color:red;">Network Error.</td></tr>';
      });
  }

  function updateCards() {
    if (!currentData.length) {
      cards.total.textContent = "0";
      cards.avg.textContent = "--";
      cards.min.textContent = "--";
      cards.max.textContent = "--";
      return;
    }
    const vals = currentData.map(r => Number(r.water_flow) || 0);
    const sum = vals.reduce((a,b)=>a+b,0);
    cards.total.textContent = vals.length;
    cards.avg.textContent = (sum / vals.length).toFixed(2);
    cards.min.textContent = Math.min(...vals).toFixed(2);
    cards.max.textContent = Math.max(...vals).toFixed(2);
  }

  function renderTable() {
    const tbody = document.querySelector("#reportTable tbody");
    tbody.innerHTML = "";
    
    if(currentData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px;">No data found for this range.</td></tr>';
        return;
    }

    currentData.forEach(row => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${row.timestamp}</td>
        <td style="color: var(--primary); font-weight:700;">${row.water_flow ?? "0"}</td>
        <td>${row.soil_moisture ?? "--"}</td>
        <td>${row.temperature ?? "--"}</td>
        <td>${row.humidity ?? "--"}</td>
      `;
      tbody.appendChild(tr);
    });
  }

  function downloadCsv() {
    if (!currentData.length) return;
    const header = ["timestamp","water_flow","soil_moisture","temperature","humidity"];
    const rows = currentData.map(r => [
      r.timestamp,
      r.water_flow ?? "",
      r.soil_moisture ?? "",
      r.temperature ?? "",
      r.humidity ?? ""
    ]);
    const csvLines = [header.join(","), ...rows.map(r => r.join(","))];
    const blob = new Blob([csvLines.join("\n")], { type: "text/csv" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `flowguard_report_${currentRange}.csv`;
    a.click();
    URL.revokeObjectURL(url);
  }

  // Initial load
  loadReport();
  </script>

</body>
</html>
