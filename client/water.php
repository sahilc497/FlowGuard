<?php
$waterThreshold = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $region = $_POST['region'] ?? '';
    $area = $_POST['area'] ?? '';
    $soil = $_POST['soil'] ?? '';
    $crop = $_POST['crop'] ?? '';

    if ($area && is_numeric($area)) {
        // Simple example formula
        $waterThreshold = ($area * 10) + rand(0, 50);
    } else {
        $errorMessage = "Please enter a valid area.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Water Threshold Predictor</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #0B5ED7;
      --secondary: #20C997;
      --bg-flat: #F8F9FA;
      --surface: #FFFFFF;
      --text-primary: #212529;
      --text-secondary: #495057;
      --text-muted: #6C757D;
      --glass-bg: rgba(11, 94, 215, 0.05);
      --glass-border: rgba(11, 94, 215, 0.1);
    }

    body {
      font-family: 'Inter', sans-serif;
      margin: 0;
      padding: 0;
      background: var(--bg-flat);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      color: var(--text-primary);
    }

    .container {
      background: var(--surface);
      padding: 40px;
      border-radius: 12px;
      width: 420px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.03);
      border: 1px solid #DEE2E6;
      text-align: center;
    }

    h2 {
      color: var(--primary);
      margin-bottom: 24px;
      font-weight: 800;
      letter-spacing: -1px;
    }

    label {
      display: block;
      text-align: left;
      margin: 16px 0 6px;
      font-weight: 600;
      font-size: 0.85rem;
      color: var(--text-secondary);
    }

    input, select, button {
      width: 100%;
      padding: 12px 14px;
      margin-bottom: 8px;
      border-radius: 6px;
      border: 1px solid #DEE2E6;
      font-size: 1rem;
      box-sizing: border-box;
      transition: all 0.2s;
    }

    input:focus, select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(11, 94, 215, 0.1);
    }

    button {
      background: var(--primary);
      color: white;
      border: none;
      cursor: pointer;
      font-weight: 700;
      margin-top: 12px;
      padding: 14px;
    }

    button:hover {
      background: #0a58ca;
    }

    .result {
      margin-top: 24px;
      font-size: 1rem;
      padding: 16px;
      border-radius: 8px;
      font-weight: 700;
    }

    .result.success {
      background: var(--glass-bg);
      border: 1px solid var(--glass-border);
      color: var(--primary);
    }

    .result.error {
      background: rgba(231, 76, 60, 0.05);
      border: 1px solid rgba(231, 76, 60, 0.1);
      color: #E74C3C;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>💧 Water Threshold Predictor</h2>
    <form method="POST">
      <label>Region:</label>
      <select name="region">
        <option <?php if (($region ?? '')=='North') echo 'selected'; ?>>North</option>
        <option <?php if (($region ?? '')=='South') echo 'selected'; ?>>South</option>
        <option <?php if (($region ?? '')=='East') echo 'selected'; ?>>East</option>
        <option <?php if (($region ?? '')=='West') echo 'selected'; ?>>West</option>
      </select>

      <label>Area (in sq. meters):</label>
      <input type="number" name="area" placeholder="Enter area" value="<?php echo htmlspecialchars($area ?? ''); ?>">

      <label>Soil Type:</label>
      <select name="soil">
        <option <?php if (($soil ?? '')=='Sandy') echo 'selected'; ?>>Sandy</option>
        <option <?php if (($soil ?? '')=='Clay') echo 'selected'; ?>>Clay</option>
        <option <?php if (($soil ?? '')=='Loamy') echo 'selected'; ?>>Loamy</option>
      </select>

      <label>Crop Type:</label>
      <select name="crop">
        <option <?php if (($crop ?? '')=='Wheat') echo 'selected'; ?>>Wheat</option>
        <option <?php if (($crop ?? '')=='Rice') echo 'selected'; ?>>Rice</option>
        <option <?php if (($crop ?? '')=='Maize') echo 'selected'; ?>>Maize</option>
      </select>

      <button type="submit">Predict</button>
    </form>

    <div class="result <?php echo $waterThreshold ? 'success' : ($errorMessage ? 'error' : ''); ?>">
      <?php
      if ($waterThreshold) {
          echo "💧 Predicted Water Threshold: $waterThreshold liters";
      } elseif ($errorMessage) {
          echo "⚠️ Error: $errorMessage";
      }
      ?>
    </div>
  </div>
</body>
</html>
