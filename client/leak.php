<?php
// flowguard.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FlowGuard Leak Detection</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/modern-design.css">
    <style>
        :root {
            --primary: #0B5ED7;
            --secondary: #20C997;
            --bg-flat: #F8F9FA;
            --surface: #FFFFFF;
            --text-primary: #212529;
            --text-secondary: #495057;
            --text-muted: #6C757D;
            --status-normal: #2ECC71;
            --status-critical: #E74C3C;
            --glass-bg: rgba(255, 255, 255, 0.65);
            --glass-border: rgba(255, 255, 255, 0.4);
            --shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-flat);
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            color: var(--text-primary);
        }

        h1 {
            margin: 40px 0 20px;
            color: var(--primary);
            font-weight: 800;
            letter-spacing: -1px;
        }

        .container {
            width: 90%;
            max-width: 900px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }

        .card {
            background: var(--surface);
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--shadow);
            border: 1px solid #DEE2E6;
            text-align: center;
        }

        .status {
            font-size: 22px;
            font-weight: 800;
            margin-top: 10px;
            padding: 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .leak {
            color: #fff;
            background: var(--status-critical);
        }

        .safe {
            color: #fff;
            background: var(--status-normal);
        }

        canvas {
            max-width: 100%;
            height: 400px !important;
        }

        .block-title {
            font-size: 14px;
            margin-bottom: 12px;
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        iframe {
            border: none;
            border-radius: 12px;
            box-shadow: var(--shadow);
            width: 100%;
            height: 520px;
            background: transparent;
        }
    </style>
    </style>
</head>
<body>
    <h1>🚰 FlowGuard: Live Leak Detection</h1>

    <!-- Motor 1 -->
    <h1>Motor 1</h1>
    <div class="container">
        <div class="card">
            <div class="block-title">Status</div>
            <div id="status" class="status safe">Loading...</div>
        </div>

        <div class="card">
            <div class="block-title">Live Data Chart</div>
            <canvas id="leakChart"></canvas>
        </div>
    </div>

    <!-- Motor 2 -->
    <h1>Motor 2</h1>
    <div class="container">
        <iframe src="leak2.php"></iframe>
    </div>

    <script>
        let leakShown = false;

        if (Notification.permission !== "granted") {
            Notification.requestPermission();
        }

        const ctx = document.getElementById('leakChart').getContext('2d');
        const leakChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Usage (L)', 'Groundwater (L)', 'Leak Flag'],
                datasets: [{
                    label: 'Live Data',
                    data: [0, 0, 0],
                    backgroundColor: ['#0B5ED7', '#20C997', '#E74C3C'],
                    borderRadius: 6,
                    barThickness: 50
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1000 }
                    }
                }
            }
        });

        async function fetchData() {
            try {
                const res = await fetch('data.php');
                const data = await res.json();

                if (!data.timestamp) return;

                // Update status
                const statusDiv = document.getElementById('status');
                if (data.leak_flag == 1) {
                    statusDiv.textContent = "🚨 Leak Detected!";
                    statusDiv.className = "status leak";

                    if (!leakShown && Notification.permission === "granted") {
                        new Notification("🚨 Leak Detected!", {
                            body: `Usage: ${data.usage_liters} L\nGroundwater: ${data.groundwater_level} L`
                        });
                        leakShown = true;
                    }
                } else {
                    statusDiv.textContent = "✅ Normal Usage";
                    statusDiv.className = "status safe";
                    leakShown = false;
                }

                // Update chart
                leakChart.data.datasets[0].data = [
                    data.usage_liters,
                    data.groundwater_level,
                    data.leak_flag * 100
                ];
                leakChart.update();
            } catch (err) {
                console.error(err);
            }
        }

        setInterval(fetchData, 3000);
    </script>
</body>
</html>
