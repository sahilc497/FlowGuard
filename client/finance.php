<?php
session_start();
include "config.php";

// 🔒 Require farmer login
if (!isset($_SESSION['farmer_id'])) {
    header("Location: farmer_login.php");
    exit();
}

$farmer_id = $_SESSION['farmer_id'];
$message = "";
$msg_type = ""; // success or error
$calculation_result = null;

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $crop_name = trim($_POST['crop_name'] ?? '');
    $area_size = floatval($_POST['area_size'] ?? 0);
    $cost_seeds = floatval($_POST['cost_seeds'] ?? 0);
    $cost_fertilizer = floatval($_POST['cost_fertilizer'] ?? 0);
    $cost_water = floatval($_POST['cost_water'] ?? 0);
    $cost_transport = floatval($_POST['cost_transport'] ?? 0);
    $yield_amount = floatval($_POST['yield_amount'] ?? 0);
    $market_price = floatval($_POST['market_price'] ?? 0);

    if (empty($crop_name) || $area_size <= 0) {
        $message = "Please provide valid Crop Name and Area Size.";
        $msg_type = "error";
    } else {
        // Calculate
        $total_cost = $cost_seeds + $cost_fertilizer + $cost_water + $cost_transport;
        $total_revenue = $yield_amount * $market_price;
        $net_profit = $total_revenue - $total_cost;

        // Save to DB
        $stmt = $conn->prepare("INSERT INTO profit_loss (farmer_id, crop_name, area_size, cost_seeds, cost_fertilizer, cost_water, cost_transport, market_price, yield_amount, total_cost, total_revenue, net_profit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdddddddddd", $farmer_id, $crop_name, $area_size, $cost_seeds, $cost_fertilizer, $cost_water, $cost_transport, $market_price, $yield_amount, $total_cost, $total_revenue, $net_profit);

        if ($stmt->execute()) {
            $message = "Calculation Saved Successfully!";
            $msg_type = "success";
            $calculation_result = [
                'net_profit' => $net_profit,
                'total_cost' => $total_cost,
                'total_revenue' => $total_revenue
            ];
        } else {
            $message = "Error saving data: " . $conn->error;
            $msg_type = "error";
        }
        $stmt->close();
    }
}

// Fetch History
$history = [];
$stmt = $conn->prepare("SELECT * FROM profit_loss WHERE farmer_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $history[] = $row;
}
$stmt->close();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>FlowGuard | Finance Calculator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

    <style>
        /* Shared Palette (Bauhaus) */
        :root {
            --primary: #0F4C5C;
            --secondary: #1CA7EC;
            --accent: #4F772D;
            --bg-body: #F4F4F4;
            --surface: #FFFFFF;
            --text-main: #1E1E1E;
            --text-light: #555555;
            --status-ok: #2A9D8F;
            --status-err: #E63946;
            --border-width: 2px;
            --border-color: #1E1E1E;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-body); color: var(--text-main); display: grid; grid-template-rows: auto 1fr auto; min-height: 100vh; }

        /* Dot Pattern Background */
        body {
            background-image: radial-gradient(var(--text-light) 1px, transparent 1px);
            background-size: 20px 20px;
        }

        /* Header */
        .header {
            background: var(--primary);
            color: var(--surface);
            padding: 0 40px;
            height: 70px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 4px solid var(--accent);
        }
        .header h1 { font-size: 24px; font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: -0.5px; }
        
        .nav-links a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-weight: 700;
            padding: 0 20px;
            text-transform: uppercase;
            font-size: 14px;
            transition: 0.2s;
        }
        .nav-links a:hover { color: var(--secondary); }

        /* Main Layout */
        .page-shell {
            max-width: 1200px;
            width: 100%;
            margin: 40px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        /* Bauhaus Cards */
        .card {
            background: var(--surface);
            border: 4px solid var(--border-color);
            padding: 30px;
            box-shadow: 10px 10px 0px rgba(15, 76, 92, 0.2);
            position: relative;
        }

        .card-header h2 {
            font-size: 1.5rem;
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 25px;
            color: var(--primary);
            border-bottom: 3px solid var(--secondary);
            display: inline-block;
            padding-bottom: 5px;
        }

        /* Form Styling */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .full-width { grid-column: span 2; }
        
        .input-group label {
            display: block;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            margin-bottom: 8px;
            color: var(--text-light);
        }
        
        .cool-input {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--text-main);
            background: var(--bg-body);
            font-family: 'Space Mono', monospace;
            font-weight: 600;
            font-size: 1rem;
            transition: 0.2s;
        }
        .cool-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 4px 4px 0px var(--secondary);
        }

        .btn-calc {
            width: 100%;
            padding: 15px;
            background: var(--text-main);
            color: var(--surface);
            font-weight: 800;
            text-transform: uppercase;
            border: none;
            cursor: pointer;
            margin-top: 20px;
            box-shadow: 6px 6px 0px var(--accent);
            transition: 0.2s;
            font-size: 1rem;
        }
        .btn-calc:hover {
            transform: translate(-2px, -2px);
            box-shadow: 8px 8px 0px var(--accent);
            background: var(--primary);
        }

        /* Result Box */
        .result-box {
            text-align: center;
            padding: 30px;
            border: 3px solid var(--text-main);
            margin-bottom: 30px;
            background: var(--bg-body);
        }
        .profit-val {
            font-size: 2.5rem;
            font-weight: 900;
            font-family: 'Space Mono', monospace;
            margin: 10px 0;
            display: block;
        }
        .text-green { color: var(--status-ok); }
        .text-red { color: var(--status-err); }

        /* Table */
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; margin-top: 10px; }
        th { text-align: left; background: var(--primary); color: white; padding: 12px; text-transform: uppercase; font-size: 0.8rem; }
        td { padding: 12px; border-bottom: 1px solid #ddd; font-family: 'Space Mono', monospace; }
        
        /* Footer */
        footer {
            background: var(--text-main);
            color: var(--surface);
            padding: 24px;
            text-align: center;
            border-top: 4px solid var(--accent);
            font-size: 0.9rem;
            margin-top: auto;
        }

        @media (max-width: 900px) {
            .page-shell { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="header">
    <div style="display:flex;align-items:center;gap:14px">
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="white" stroke-width="2"/><path d="M12 6v12M6 12h12" stroke="white" stroke-width="2"/></svg>
        <h1>FlowGuard <span style="opacity:0.7; font-size:0.6em; vertical-align:middle;">FINANCE</span></h1>
    </div>
    <nav class="nav-links">
        <a href="farmer_dashboard.php">Dashboard</a>
        <a href="farmer_login.php">Logout</a>
    </nav>
</header>

<div class="page-shell">

    <!-- Left Column: Input Form -->
    <div class="card">
        <div class="card-header">
            <h2>Calculate Profit/Loss</h2>
        </div>
        
        <?php if ($message): ?>
            <div style="padding:15px; margin-bottom:20px; background:<?= $msg_type === 'success' ? 'var(--status-ok)' : 'var(--status-err)' ?>; color:white; font-weight:700;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="form-grid">
            <div class="full-width input-group">
                <label>Crop Name</label>
                <input type="text" name="crop_name" class="cool-input" placeholder="e.g. Wheat, Rice" required>
            </div>

            <div class="input-group">
                <label>Area Size (Acres)</label>
                <input type="number" step="0.01" name="area_size" class="cool-input" placeholder="0.0" required>
            </div>

            <div class="input-group">
                <label>Yield Amount (Kg/Ton)</label>
                <input type="number" step="0.01" name="yield_amount" class="cool-input" placeholder="0.0" required>
            </div>
            
            <div class="input-group">
                <label>Market Price (per Unit)</label>
                <input type="number" step="0.01" name="market_price" class="cool-input" placeholder="₹ 0.0" required>
            </div>

            <!-- Costs Section -->
            <div class="full-width" style="margin-top:10px; border-top:2px dashed #ccc; padding-top:10px;">
                <label style="color:var(--primary); font-weight:800; text-transform:uppercase;">Expenses</label>
            </div>

            <div class="input-group">
                <label>Seeds Cost</label>
                <input type="number" step="0.01" name="cost_seeds" class="cool-input" placeholder="₹ 0.0">
            </div>
            
            <div class="input-group">
                <label>Fertilizer Cost</label>
                <input type="number" step="0.01" name="cost_fertilizer" class="cool-input" placeholder="₹ 0.0">
            </div>

            <div class="input-group">
                <label>Water/Irrigation Cost</label>
                <input type="number" step="0.01" name="cost_water" class="cool-input" placeholder="₹ 0.0">
            </div>

            <div class="input-group">
                <label>Transport/Labor Cost</label>
                <input type="number" step="0.01" name="cost_transport" class="cool-input" placeholder="₹ 0.0">
            </div>

            <button type="submit" class="btn-calc full-width">Calculate & Save</button>
        </form>
    </div>

    <!-- Right Column: Results & History -->
    <div>
        <!-- Result Display -->
        <?php if ($calculation_result): ?>
        <div class="card" style="border-color: var(--secondary); margin-bottom:30px;">
            <div class="result-box">
                <h3 style="text-transform:uppercase; color:var(--text-light); font-size:0.9rem;">Net Outcome</h3>
                <span class="profit-val <?= $calculation_result['net_profit'] >= 0 ? 'text-green' : 'text-red' ?>">
                    <?= $calculation_result['net_profit'] >= 0 ? '+' : '' ?> ₹<?= number_format($calculation_result['net_profit'], 2) ?>
                </span>
                <div style="font-size:0.9rem; color:var(--text-light); display:flex; justify-content:space-around; margin-top:15px;">
                    <span><strong>Revenue:</strong> ₹<?= number_format($calculation_result['total_revenue'], 2) ?></span>
                    <span><strong>Cost:</strong> ₹<?= number_format($calculation_result['total_cost'], 2) ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- History Table -->
        <div class="card">
            <div class="card-header">
                <h2>History (Last 10)</h2>
            </div>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Crop</th>
                            <th>Date</th>
                            <th>Profit/Loss</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history)): ?>
                            <tr><td colspan="3" style="text-align:center; color:var(--text-light);">No records found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($history as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['crop_name']) ?></td>
                                <td><?= date("d M Y", strtotime($row['created_at'])) ?></td>
                                <td style="font-weight:700; color: <?= $row['net_profit'] >= 0 ? 'var(--status-ok)' : 'var(--status-err)' ?>">
                                    <?= $row['net_profit'] >= 0 ? '+' : '' ?> <?= number_format($row['net_profit']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Footer -->
<footer>
  <p>© 2026 FlowGuard. All rights reserved • Promoting Sustainable Water Use in Agriculture</p>
</footer>

</body>
</html>
