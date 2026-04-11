<?php
// ===================== LOAD ENVIRONMENT VARIABLES =====================
require_once __DIR__ . '/env-loader.php';

// ===================== CONFIG - Loaded from .env =====================
$ACCOUNT_SID = getEnv('TWILIO_ACCOUNT_SID');
$AUTH_TOKEN  = getEnv('TWILIO_AUTH_TOKEN');
$FROM_NUMBER = getEnv('TWILIO_FROM_NUMBER');
$TO_NUMBER   = "+919765635635";    // Number you want to call

$status = "";

// ===================== CALL TRIGGER =====================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $twiml = '<Response>
                <Say voice="alice">
                    Alert! Water Threshold Reached. Your water usage has exceeded the set limit. Please check your farm immediately.
                </Say>
              </Response>';

    $url = "https://api.twilio.com/2010-04-01/Accounts/$ACCOUNT_SID/Calls.json";

    $data = http_build_query([
        "To"    => $TO_NUMBER,
        "From"  => $FROM_NUMBER,
        "Twiml" => $twiml
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_USERPWD, $ACCOUNT_SID . ":" . $AUTH_TOKEN);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $status = "❌ cURL Error: " . curl_error($ch);
    } elseif ($httpCode >= 200 && $httpCode < 300) {
        $status = "✅ Water Threshold Alert Call Successfully Triggered!";
    } else {
        $status = "❌ Twilio Error: " . htmlspecialchars($response);
    }

    curl_close($ch);
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Water Threshold Alert - Twilio Call</title>
<style>
body {
    background:#020617;
    color:#fff;
    font-family:Arial;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}
.box {
    background:#020617;
    padding:30px;
    border-radius:12px;
    box-shadow:0 0 30px rgba(0,255,255,.3);
    text-align:center;
}
button {
    padding:12px 25px;
    font-size:16px;
    background:linear-gradient(135deg,#22d3ee,#0ea5e9);
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-weight:bold;
}
</style>
</head>
<body>

<div class="box">
    <h2>� Water Threshold Alert Call</h2>
    <p>Calling: <b><?= $TO_NUMBER ?></b></p>
    <form method="POST">
        <button type="submit">Call Now</button>
    </form>
    <p><?= $status ?></p>
</div>

</body>
</html>
