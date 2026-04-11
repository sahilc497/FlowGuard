<?php
session_start();
if (!isset($_SESSION['farmer_id'])) {
    header("Location: farmer_login.php");
    exit();
}
// Using independent header/footer logic for full style control
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>FlowGuard | Analytics</title>
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

    /* CONTROLS */
    .controls { display: flex; gap: 12px; margin-bottom: 20px; }
    .filter-btn {
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
    .filter-btn:hover {
        transform: translateY(-2px);
        box-shadow: 6px 6px 0px rgba(0,0,0,0.15);
    }
    .filter-btn.active {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
        box-shadow: inset 2px 2px 5px rgba(0,0,0,0.2);
        transform: none;
    }

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
    }
    .kpi-label { font-size: 0.8rem; font-weight: 700; color: var(--text-light); text-transform: uppercase; margin-bottom: 8px; }
    .kpi-value { font-size: 2rem; font-weight: 800; color: var(--primary); font-family: 'Space Mono', monospace; }

    /* CHARTS */
    .chart-wrapper {
        position: relative;
        height: 350px;
        width: 100%;
        margin-bottom: 20px;
    }

    .grid-two {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }

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
        .grid-two { grid-template-columns: 1fr; }
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
      <a href="reports.php">Reports</a>
      <a href="analytics.php" class="active">Analytics</a>
      <a href="chart.php">Charts</a>
    </nav>
  </header>

  <div class="page-shell">
    
    <div style="margin-bottom: 20px;">
        <a href="sens1.php" style="display:inline-block; font-weight:700; text-decoration:none; color:var(--text-main); border-bottom:2px solid var(--secondary);">← BACK TO SENSORS</a>
    </div>

    <!-- Summary Section -->
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2 style="margin:0; border:none; padding:0;">📈 Live Metrics</h2>
            <div class="controls" style="margin:0;">
                <button class="filter-btn active" data-range="24h">24h</button>
                <button class="filter-btn" data-range="week">Week</button>
                <button class="filter-btn" data-range="all">All</button>
            </div>
        </div>
        <hr style="margin:20px 0; border:0; border-top:2px solid var(--bg-body);">

        <div class="kpi-grid">
            <div class="kpi-card">
              <div class="kpi-label">Readings Count</div>
              <div id="totalReadings" class="kpi-value">--</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-label">Avg Flow (L/min)</div>
              <div id="avgValue" class="kpi-value">--</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-label">Min Flow (L/min)</div>
              <div id="minValue" class="kpi-value">--</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-label">Max Flow (L/min)</div>
              <div id="maxValue" class="kpi-value">--</div>
            </div>
        </div>
    </div>
    
    <!-- Charts Grid -->
    <div class="grid-two">
        <div class="card">
            <h2>Water Flow Timeline</h2>
            <div class="chart-wrapper">
                <canvas id="lineChart"></canvas>
            </div>
        </div>

        <div class="card" style="border-top-color: var(--accent);">
            <h2>Usage Aggregation</h2>
            <div class="chart-wrapper">
                <canvas id="barChart"></canvas>
            </div>
        </div>
    </div>

  </div>

  <footer>
    <p>© 2026 FlowGuard. All rights reserved • Smart Water Monitoring</p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script>
    const apiBase = "sens1.php?data_api=1";
    let currentRange = "24h";
    let lineChart, barChart;

    const cards = {
        total: document.getElementById("totalReadings"),
        avg: document.getElementById("avgValue"),
        min: document.getElementById("minValue"),
        max: document.getElementById("maxValue")
    };

    document.querySelectorAll(".filter-btn").forEach(btn => {
        btn.addEventListener("click", () => {
        document.querySelectorAll(".filter-btn").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        currentRange = btn.dataset.range;
        fetchAndRender();
        });
    });

    function formatLabel(ts, range) {
        const d = new Date(ts);
        if (range === "24h") {
        return d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
        }
        return d.toLocaleDateString();
    }

    function aggregateForBar(data) {
        const buckets = {};
        data.forEach(row => {
        const label = formatLabel(row.timestamp, currentRange);
        if (!buckets[label]) buckets[label] = [];
        const val = Number(row.water_flow) || 0;
        buckets[label].push(val);
        });
        const labels = Object.keys(buckets);
        const values = labels.map(l => {
        const arr = buckets[l];
        return arr.reduce((a,b)=>a+b,0) / Math.max(1, arr.length);
        });
        return { labels, values };
    }

    function updateCards(values) {
        if (!values.length) {
        cards.total.textContent = "0";
        cards.avg.textContent = "--";
        cards.min.textContent = "--";
        cards.max.textContent = "--";
        return;
        }
        const sum = values.reduce((a,b)=>a+b,0);
        const avg = sum / values.length;
        cards.total.textContent = values.length;
        cards.avg.textContent = avg.toFixed(2);
        cards.min.textContent = Math.min(...values).toFixed(2);
        cards.max.textContent = Math.max(...values).toFixed(2);
    }

    function renderCharts(readings) {
        const labels = readings.map(r => formatLabel(r.timestamp, currentRange));
        const values = readings.map(r => Number(r.water_flow) || 0);
        updateCards(values);

        // Chart Config
        Chart.defaults.font.family = 'Inter';
        Chart.defaults.color = '#555';

        const ctxLine = document.getElementById("lineChart").getContext("2d");
        if (lineChart) lineChart.destroy();
        lineChart = new Chart(ctxLine, {
        type: "line",
        data: {
            labels,
            datasets: [{
            label: "Water Flow (L/min)",
            data: values,
            borderColor: "#0F4C5C",
            backgroundColor: "rgba(15, 76, 92, 0.1)",
            pointBackgroundColor: "#1CA7EC",
            pointBorderColor: "#fff",
            pointRadius: 4,
            borderWidth: 2,
            tension: 0.35,
            fill: true
            }]
        },
        options: { 
            responsive:true, 
            maintainAspectRatio:false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display:false } },
                y: { grid: { color: "#eee", borderDash: [5,5] }, beginAtZero: true }
            }
        }
        });

        const agg = aggregateForBar(readings);
        const ctxBar = document.getElementById("barChart").getContext("2d");
        if (barChart) barChart.destroy();
        barChart = new Chart(ctxBar, {
        type: "bar",
        data: {
            labels: agg.labels,
            datasets: [{
            label: "Avg Flow (L/min)",
            data: agg.values,
            backgroundColor: "#1CA7EC",
            borderColor: "#1E1E1E",
            borderWidth: 2,
            borderRadius: 0,
            barPercentage: 0.6
            }]
        },
        options: { 
            responsive:true, 
            maintainAspectRatio:false, 
            plugins:{ legend:{display:false} },
            scales: {
                x: { grid: { display:false } },
                y: { grid: { color: "#eee", borderDash: [5,5] }, beginAtZero: true }
            }
        }
        });
    }

    function fetchAndRender() {
        fetch(`${apiBase}&range=${encodeURIComponent(currentRange)}`, { credentials: "same-origin" })
        .then(r => r.json())
        .then(json => {
            if (!json || json.ok !== true || !Array.isArray(json.data)) {
            console.warn("Invalid data from sens1.php");
            return;
            }
            const sorted = json.data.slice().reverse(); // oldest first for line continuity
            renderCharts(sorted);
        })
        .catch(err => console.error("Fetch error", err));
    }

    fetchAndRender();
    setInterval(fetchAndRender, 10000); // 10s refresh
  </script>

</body>
</html>
