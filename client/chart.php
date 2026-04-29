<?php
session_start();
if (!isset($_SESSION['farmer_id'])) {
    header("Location: farmer_login.php");
    exit();
}
include 'config.php';

$apiKey = "da9c137b0cf1e5204eea9697bab771bd"; // OpenWeatherMap Key

$city = isset($_GET['city']) ? htmlspecialchars($_GET['city']) : '';
$weatherError = "";

// Data Arrays
$forecastLabels = [];
$forecastValues = [];
$forecastTotal = 0;

$historyLabels = [];
$historyValues = [];
$historyTotal = 0;

if ($city) {
    // ---------------------------------------------------------
    // 1. Geocoding (via Open-Meteo, robust & free)
    // ---------------------------------------------------------
    $geoUrl = "https://geocoding-api.open-meteo.com/v1/search?name=" . urlencode($city) . "&count=1&language=en&format=json";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $geoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $geoResp = curl_exec($ch);
    // curl_close($ch); // Deprecated in PHP 8.5+

    $geoData = json_decode($geoResp, true);
    
    if (isset($geoData['results']) && count($geoData['results']) > 0) {
        $lat = $geoData['results'][0]['latitude'];
        $lon = $geoData['results'][0]['longitude'];
        $cityNameFormatted = $geoData['results'][0]['name'] . ", " . ($geoData['results'][0]['country'] ?? '');

        // ---------------------------------------------------------
        // 2. OpenWeatherMap Forecast (Future 5 days)
        // ---------------------------------------------------------
        $urlForecast = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlForecast);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $respF = curl_exec($ch);
        // curl_close($ch); // Deprecated in PHP 8.5+
        
        $dataF = json_decode($respF, true);
        if (isset($dataF['list'])) {
            foreach ($dataF['list'] as $item) {
                // Forecast data is every 3h
                $date = date('d M H:i', $item['dt']);
                $rain = (isset($item['rain']) && isset($item['rain']['3h'])) ? (float)$item['rain']['3h'] : 0;
                
                $forecastLabels[] = $date;
                $forecastValues[] = $rain;
                $forecastTotal += $rain;
            }
        }

        // ---------------------------------------------------------
        // 3. Open-Meteo Historical Data (Past 30 Days)
        // ---------------------------------------------------------
        $endDate = date('Y-m-d', strtotime('-1 day')); // Yesterday
        $startDate = date('Y-m-d', strtotime('-31 days'));
        
        $urlHistory = "https://archive-api.open-meteo.com/v1/archive?latitude={$lat}&longitude={$lon}&start_date={$startDate}&end_date={$endDate}&daily=rain_sum&timezone=auto";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlHistory);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $respH = curl_exec($ch);
        // curl_close($ch); // Deprecated in PHP 8.5+

        $dataH = json_decode($respH, true);
        
        if (isset($dataH['daily']) && isset($dataH['daily']['time']) && isset($dataH['daily']['rain_sum'])) {
            $times = $dataH['daily']['time'];
            $rains = $dataH['daily']['rain_sum'];
            
            for ($i = 0; $i < count($times); $i++) {
                $historyLabels[] = date('d M', strtotime($times[$i]));
                $historyValues[] = (float)$rains[$i];
                $historyTotal += (float)$rains[$i];
            }
        }

    } else {
        $weatherError = "Location '{$city}' not found. Please try a major city name.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>FlowGuard | Rainfall Analysis</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
  <!-- FONT -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  
  <!-- CHART.JS -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

  <style>
    /* --- Global Bauhaus Theme --- */
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

    /* MAIN CONTENT */
    .page-shell {
        max-width: 1200px;
        width: 100%;
        margin: 0 auto;
        padding: 40px 20px;
        flex: 1;
    }

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

    /* Form */
    .search-form { display: flex; gap: 10px; margin-bottom: 20px; max-width: 600px; }
    .search-input {
        flex: 1;
        padding: 12px 16px;
        border: 2px solid var(--border-color);
        background: var(--bg-body);
        font-family: 'Space Mono', monospace;
        font-weight: 600;
        color: var(--text-main);
        font-size: 1rem;
    }
    .search-input:focus { outline: none; border-color: var(--secondary); background: #fff; }
    
    .action-btn {
        background: var(--text-main);
        color: #fff;
        padding: 12px 24px;
        font-weight: 800;
        text-decoration: none;
        text-transform: uppercase;
        box-shadow: 4px 4px 0px rgba(0,0,0,0.2);
        transition: all 0.2s;
        font-size: 13px;
        border: none;
        cursor: pointer;
    }
    .action-btn:hover {
        background: var(--primary);
        transform: translateY(-2px);
        box-shadow: 6px 6px 0px rgba(0,0,0,0.2);
    }
    
    .back-btn {
        display: inline-block;
        color: var(--text-main);
        padding: 10px 0;
        font-weight: 700;
        text-decoration: none;
        text-transform: uppercase;
        font-size: 13px;
        margin-bottom: 20px;
        border-bottom: 2px solid var(--secondary);
    }

    /* KPI Grid */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    .kpi-stat {
        padding: 15px; 
        background: var(--bg-body); 
        border-left: 4px solid var(--secondary);
    }
    .kpi-label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-light); margin-bottom: 6px; }
    .kpi-val { font-size: 1.6rem; font-weight: 800; color: var(--primary); font-family: 'Space Mono', monospace; }

    /* Chart Containers */
    .chart-box { height: 320px; width: 100%; position: relative; }

    .error-msg { 
        background: #fee; color: #E63946; padding: 16px; 
        border: 2px solid #E63946; font-weight: 700; margin-bottom: 20px; 
    }
    
    .section-title {
        font-size: 1.1rem;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--text-light);
        margin: 30px 0 15px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-title::before { content: ''; display: block; width: 8px; height: 8px; background: var(--secondary); }

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
        .search-form { flex-direction: column; }
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
      <a href="analytics.php">Analytics</a>
      <a href="chart.php" class="active">Charts</a>
    </nav>
  </header>

  <div class="page-shell">
    
    <a href="sens1.php" class="back-btn">← Back to Sensors</a>

    <div class="card" style="border-top-color: var(--accent);">
        <h2>Rainfall Analysis Studio</h2>
        <p>Analyze past weather patterns and future forecasts to optimize irrigation. Powered by Open-Meteo & OpenWeather.</p>
        
        <form class="search-form" method="GET">
            <input type="text" name="city" class="search-input" placeholder="Enter Location (e.g. Pune, Nagpur, Mumbai)" value="<?= htmlspecialchars($city) ?>" required>
            <button type="submit" class="action-btn">Analyze Location</button>
        </form>

        <?php if ($weatherError): ?>
            <div class="error-msg"><?= htmlspecialchars($weatherError) ?></div>
        <?php endif; ?>

        <?php if ($city && !$weatherError): ?>
            <div style="background:var(--bg-body); padding:15px; border-left:4px solid var(--text-main); margin-bottom:20px;">
                <strong style="text-transform:uppercase; color:var(--text-light); font-size:0.8rem;">Analysis Report For:</strong><br>
                <span style="font-size:1.2rem; font-weight:800; color:var(--primary);"><?= htmlspecialchars($cityNameFormatted ?? $city) ?></span>
            </div>

            <!-- FORECAST SECTION -->
            <div class="section-title">5-Day Rainfall Forecast</div>
            <div class="kpi-grid">
                <div class="kpi-stat">
                    <div class="kpi-label">Expected Rain (Total)</div>
                    <div class="kpi-val"><?= number_format($forecastTotal, 1) ?> <span style="font-size:1rem">mm</span></div>
                </div>
                <div class="kpi-stat" style="border-left-color: var(--text-main);">
                    <div class="kpi-label">Forecast Points</div>
                    <div class="kpi-val"><?= count($forecastLabels) ?></div>
                </div>
            </div>
            
            <div class="card" style="margin:0; box-shadow:none; border:1px solid #ddd; border-top: none;">
                <div class="chart-box">
                    <canvas id="forecastChart"></canvas>
                </div>
            </div>

            <!-- PAST HISTORY SECTION -->
            <div class="section-title" style="margin-top:40px;">Past 30 Days Rainfall History</div>
            <div class="kpi-grid">
                <div class="kpi-stat" style="border-left-color: var(--accent);">
                    <div class="kpi-label">Recorded Rain (30 Days)</div>
                    <div class="kpi-val"><?= number_format($historyTotal, 1) ?> <span style="font-size:1rem">mm</span></div>
                </div>
                <div class="kpi-stat" style="border-left-color: var(--accent);">
                    <div class="kpi-label">Max Daily Rain</div>
                    <div class="kpi-val"><?= number_format(!empty($historyValues) ? max($historyValues) : 0, 1) ?> <span style="font-size:1rem">mm</span></div>
                </div>
                <div class="kpi-stat" style="border-left-color: var(--accent);">
                    <div class="kpi-label">Rainy Days</div>
                    <div class="kpi-val"><?= count(array_filter($historyValues, function($v) { return $v > 0.1; })) ?></div>
                </div>
            </div>

            <div class="card" style="margin:0; box-shadow:none; border:1px solid #ddd; border-top:none;">
                <div class="chart-box">
                    <canvas id="historyChart"></canvas>
                </div>
            </div>

        <?php endif; ?>
    </div>
  </div>

  <footer>
    <p>© 2026 FlowGuard. All rights reserved • Smart Water Monitoring</p>
  </footer>

  <?php if ($city && !$weatherError): ?>
  <script>
    Chart.defaults.font.family = 'Inter';
    Chart.defaults.color = '#555';

    // FORECAST LINE CHART
    const ctxF = document.getElementById('forecastChart').getContext('2d');
    new Chart(ctxF, {
      type: 'line',
      data: {
        labels: <?= json_encode($forecastLabels) ?>,
        datasets: [{
          label: 'Forecast Rain (mm/3h)',
          data: <?= json_encode($forecastValues) ?>,
          borderColor: '#1CA7EC',
          backgroundColor: 'rgba(28, 167, 236, 0.15)',
          borderWidth: 2,
          pointBackgroundColor: '#fff',
          pointBorderColor: '#0F4C5C',
          pointRadius: 4,
          tension: 0.3,
          fill: true
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1E1E1E',
                titleFont: { family: 'Inter', size: 13 },
                bodyFont: { family: 'Space Mono', size: 14 },
                padding: 12,
                cornerRadius: 0,
                displayColors: false
            }
        },
        scales: {
          x: { grid: { display: false }, ticks: { maxTicksLimit: 7 } },
          y: { 
              beginAtZero: true, 
              grid: { color: '#eee', borderDash: [5,5] },
              title: { display: true, text: 'Rain (mm)', font: { weight: 'bold' } } 
          }
        }
      }
    });

    // HISTORY BAR CHART
    const ctxH = document.getElementById('historyChart').getContext('2d');
    new Chart(ctxH, {
      type: 'bar',
      data: {
        labels: <?= json_encode($historyLabels) ?>,
        datasets: [{
          label: 'Daily Rainfall (mm)',
          data: <?= json_encode($historyValues) ?>,
          backgroundColor: '#4F772D', // Agricultural Olive
          hoverBackgroundColor: '#36581D',
          borderWidth: 0,
          borderRadius: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1E1E1E',
                titleFont: { family: 'Inter', size: 13 },
                bodyFont: { family: 'Space Mono', size: 14 },
                padding: 12,
                cornerRadius: 0,
                displayColors: false
            }
        },
        scales: {
          x: { grid: { display: false } },
          y: { 
              beginAtZero: true, 
              grid: { color: '#eee', borderDash: [5,5] },
              title: { display: true, text: 'Daily Rain (mm)', font: { weight: 'bold' } } 
          }
        }
      }
    });
  </script>
  <?php endif; ?>

</body>
</html>
