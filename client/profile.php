<?php
session_start();
if (!isset($_SESSION['farmer_id'])) {
    header("Location: farmer_login.php");
    exit();
}

/* ========== DB CONNECTION ========== */
$conn = new mysqli("localhost", "root", "", "flowguard");
if ($conn->connect_error) {
    die("DB Connection Failed");
}

$farmer_id = intval($_SESSION['farmer_id']);

/* ========== DETECT AVAILABLE COLUMNS IN farmers TABLE ========== */
$available = [];
$colRes = $conn->query("SHOW COLUMNS FROM `farmers`");
if ($colRes) {
    while ($col = $colRes->fetch_assoc()) {
        $available[$col['Field']] = true;
    }
}

/* ========== BUILD SELECT LIST BASED ON COLUMNS DETECTED ========== */
$selectCols = ['id']; // always fetch id
// prefer full_name if exists, else username
if (!empty($available['full_name'])) {
    $selectCols[] = 'full_name';
} elseif (!empty($available['username'])) {
    $selectCols[] = 'username';
}
// email may exist or might be stored in username for older schema
if (!empty($available['email'])) {
    $selectCols[] = 'email';
} elseif (!empty($available['username'])) {
    $selectCols[] = 'username AS email';
}
// phone, role, status, last_login, created_at
if (!empty($available['phone'])) $selectCols[] = 'phone';
if (!empty($available['role'])) $selectCols[] = 'role';
if (!empty($available['status'])) $selectCols[] = 'status';
if (!empty($available['last_login'])) $selectCols[] = 'last_login';
if (!empty($available['created_at'])) $selectCols[] = 'created_at';

// ensure uniqueness
$selectCols = array_unique($selectCols);
$selectSql = implode(', ', $selectCols);

/* ========== FETCH FARMER ROW ========== */
$farmer = [
    "id" => $farmer_id,
    "full_name" => null,
    "username" => "Unknown",
    "email" => "-",
    "phone" => "-",
    "role" => "farmer",
    "status" => "active",
    "last_login" => "-",
    "created_at" => "-"
];

$stmt = $conn->prepare("SELECT $selectSql FROM farmers WHERE id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $farmer_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        // map known fields
        if (isset($row['full_name'])) $farmer['full_name'] = $row['full_name'];
        if (isset($row['username'])) {
            // keep username for backward compatibility
            $farmer['username'] = $row['username'];
            // if full_name missing and username looks like a full name (contains space), use it
            if (empty($farmer['full_name']) && strpos($row['username'], ' ') !== false) {
                $farmer['full_name'] = $row['username'];
            }
        }
        if (isset($row['email'])) $farmer['email'] = $row['email'];
        if (isset($row['phone'])) $farmer['phone'] = $row['phone'];
        if (isset($row['role'])) $farmer['role'] = $row['role'];
        if (isset($row['status'])) $farmer['status'] = $row['status'];
        if (isset($row['last_login'])) $farmer['last_login'] = $row['last_login'];
        if (isset($row['created_at'])) $farmer['created_at'] = $row['created_at'];
    }
    $stmt->close();
}

/* ========== FETCH MOTOR DETAILS (first motor for farmer) ========== */
$motor = [
    "motor_name" => "Not Assigned",
    "status" => "OFF",
    "installation_date" => null,
    "total_runtime_hours" => 0,
    "total_water_used_liters" => 0
];

if ($stmt2 = $conn->prepare("SELECT motor_name, status, installation_date, total_runtime_hours, total_water_used_liters FROM motors WHERE farmer_id = ? ORDER BY id LIMIT 1")) {
    $stmt2->bind_param("i", $farmer_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    if ($r2 = $res2->fetch_assoc()) {
        $motor['motor_name'] = $r2['motor_name'] ?? $motor['motor_name'];
        $motor['status'] = $r2['status'] ?? $motor['status'];
        $motor['installation_date'] = $r2['installation_date'] ?? null;
        $motor['total_runtime_hours'] = $r2['total_runtime_hours'] ?? 0;
        $motor['total_water_used_liters'] = $r2['total_water_used_liters'] ?? 0;
    }
    $stmt2->close();
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>FlowGuard | Profile</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/modern-design.css">

<style>
:root {
    --primary: #0F4C5C;      /* Deep Teal */
    --secondary: #1CA7EC;    /* Aqua Blue */
    --accent: #4F772D;       /* Olive Green */
    --bg-body: #F4F4F4;      /* Light Neutral */
    --surface: #FFFFFF;      /* Pure White */
    --text-main: #1E1E1E;    /* Charcoal */
    --text-light: #555555;
    
    --status-ok: #2A9D8F;
    --status-err: #E63946;
    --status-warn: #F4A261;
    --status-off: #ADB5BD;
    
    --border-width: 2px;
    --border-color: #1E1E1E;
    --radius: 0px;
}

*{margin:0;padding:0;box-sizing:border-box; font-family:'Inter', sans-serif}
body{
    background-color: var(--bg-body);
    color: var(--text-main);
    min-height:100vh;
    display:flex;
    flex-direction:column;
}

/* NAVBAR */
.navbar{
    position:fixed;
    top:0;left:0;right:0;
    height:72px;
    background: var(--primary);
    color: #fff;
    padding:0 32px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    z-index:1000;
    border-bottom: var(--border-width) solid var(--border-color);
}
.navbar h1{font-size:24px; font-weight:800; color: #fff; letter-spacing: -0.5px; text-transform: uppercase;}
.navbar div a{color: rgba(255,255,255,0.8); text-decoration:none; margin-left:24px; font-weight: 700; transition: color 0.2s; text-transform: uppercase; font-size: 0.9rem;}
.navbar div a:hover { color: #fff; text-decoration: underline;}

/* MAIN */
.main{
    margin-top:100px;
    max-width:1000px;
    width: 100%;
    margin-left:auto;
    margin-right:auto;
    padding:0 20px 40px 20px;
    flex:1;
}

/* CARD */
.card{
    background: var(--surface);
    border-radius:var(--radius);
    padding:40px;
    box-shadow: 8px 8px 0px rgba(0,0,0,0.1);
    border: var(--border-width) solid var(--border-color);
}
.card h2{
    font-size:20px;
    color: var(--text-main);
    margin-bottom:24px;
    font-weight: 800;
    text-transform: uppercase;
    border-bottom: 2px solid var(--border-color);
    display: inline-block;
    padding-bottom: 5px;
}

/* layout rows */
.grid{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:40px;
}
.row{
    display:flex;
    justify-content:space-between;
    padding:16px 0;
    border-bottom: 1px solid #E0E0E0;
}
.row:last-child{border-bottom:none}
.label{color: var(--text-light); font-weight: 600; text-transform: uppercase; font-size: 0.85rem;}
.value{font-weight:700; color: var(--text-main);}

.status-on{
    color: #fff;
    background: var(--status-ok);
    padding:4px 12px;
    border-radius:var(--radius);
    font-weight:700;
    font-size: 12px;
    text-transform: uppercase;
}
.status-off{
    color: #fff;
    background: var(--status-err);
    padding:4px 12px;
    border-radius:var(--radius);
    font-weight:700;
    font-size: 12px;
    text-transform: uppercase;
}

/* small stat boxes */
.stat-container {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    margin-top: 24px;
}
.stat{
    background: var(--bg-body);
    border: 2px solid #E0E0E0;
    border-radius:var(--radius);
    padding:16px 24px;
    font-weight:700;
    color: var(--text-main);
    text-align:center;
    flex: 1;
    min-width: 180px;
}

/* FOOTER */
footer{
    padding: 30px;
    background: var(--text-main);
    color: #fff;
    border-top: var(--border-width) solid var(--border-color);
    text-align: center;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
}
@media (max-width:800px){
    .grid{grid-template-columns:1fr; gap: 20px;}
}
</style>
</head>

<body>

<nav class="navbar">
    <h1>FlowGuard</h1>
    <div>
        <a href="farmer_dashboard.php">Dashboard</a>
        <a href="profile.php">Profile</a>
        <a href="farmer_login.php">Logout</a>
    </div>
</nav>

<main class="main">
    <div class="card">
        <h2>👤 Farmer Profile</h2>

        <div class="grid" style="margin-bottom:18px;">
            <div>
                <div class="row">
                    <div class="label">Full name</div>
                    <div class="value"><?php echo htmlspecialchars($farmer['full_name'] ?? $farmer['username']); ?></div>
                </div>

                <div class="row">
                    <div class="label">Email</div>
                    <div class="value"><?php echo htmlspecialchars($farmer['email']); ?></div>
                </div>

                <div class="row">
                    <div class="label">Phone</div>
                    <div class="value"><?php echo htmlspecialchars($farmer['phone']); ?></div>
                </div>

                <div class="row">
                    <div class="label">Account Created</div>
                    <div class="value"><?php echo htmlspecialchars($farmer['created_at']); ?></div>
                </div>
            </div>

            <div>
                <div class="row">
                    <div class="label">Role</div>
                    <div class="value"><?php echo htmlspecialchars($farmer['role']); ?></div>
                </div>

                <div class="row">
                    <div class="label">Account Status</div>
                    <div class="<?php echo ($farmer['status'] === 'active') ? 'status-on' : 'status-off'; ?>">
                        <?php echo htmlspecialchars($farmer['status']); ?>
                    </div>
                </div>

                <div class="row">
                    <div class="label">Last Login</div>
                    <div class="value"><?php echo htmlspecialchars($farmer['last_login']); ?></div>
                </div>

                <div class="row">
                    <div class="label">Farmer ID</div>
                    <div class="value"><?php echo htmlspecialchars($farmer['id']); ?></div>
                </div>
            </div>
        </div>

        <h2 style="margin-top:8px;">⚙️ Motor Information</h2>

        <div class="row">
            <div class="label">Motor Name</div>
            <div class="value"><?php echo htmlspecialchars($motor['motor_name']); ?></div>
        </div>

        <div class="row">
            <div class="label">Motor Status</div>
            <div class="<?php echo ($motor['status'] === 'ON') ? 'status-on' : 'status-off'; ?>">
                <?php echo htmlspecialchars($motor['status']); ?>
            </div>
        </div>

        <div style="margin-top:12px;">
            <div class="stat-container">
                <div class="stat">Runtime: <?php echo htmlspecialchars(number_format($motor['total_runtime_hours'])); ?> hrs</div>
                <div class="stat">Water used: <?php echo htmlspecialchars(number_format($motor['total_water_used_liters'])); ?> L</div>
                <div class="stat">Installed: <?php echo htmlspecialchars($motor['installation_date'] ?? '-'); ?></div>
            </div>
        </div>

    </div>
</main>

<footer>© 2026 FlowGuard — Smart Water Monitoring</footer>

</body>
</html>
