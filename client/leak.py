import pandas as pd
import joblib
import time
from datetime import datetime
import sys

# Ensure Unicode output in Windows terminals
sys.stdout.reconfigure(encoding='utf-8')

# Load model and scaler
model = joblib.load(r"model\leak_model.pkl")
scaler = joblib.load(r"model\scaler.pkl")

# Groundwater settings
GROUNDWATER_CAPACITY = 10000
current_groundwater = GROUNDWATER_CAPACITY

def update_groundwater(usage):
    """Decrease groundwater level after each reading."""
    global current_groundwater
    current_groundwater = max(current_groundwater - usage, 0)
    return current_groundwater

def predict_leak(usage, ts):
    """Run ML + rule-based leak detection."""
    df = pd.DataFrame([{
        "timestamp": ts,
        "usage_liters": usage,
        "hour": ts.hour,
        "dayofweek": ts.weekday(),  # fixed here
        "rolling_mean": usage,      # placeholder
        "daily_cum": usage          # placeholder
    }])

    X_scaled = scaler.transform(
        df[['usage_liters', 'hour', 'dayofweek', 'rolling_mean', 'daily_cum']]
    )

    anomaly_ml = model.predict(X_scaled)[0]
    anomaly_ml = 1 if anomaly_ml == -1 else 0

    threshold = 200
    anomaly_rule = 1 if usage > threshold else 0

    leak_flag = 1 if (anomaly_ml == 1 and anomaly_rule == 1) else 0
    groundwater_level = update_groundwater(usage)

    return {
        "timestamp": str(ts),
        "usage_liters": usage,
        "anomaly_ml": anomaly_ml,
        "anomaly_rule": anomaly_rule,
        "leak_flag": leak_flag,
        "groundwater_level": groundwater_level
    }


# Simulate continuous sensor readings from CSV
csv_path = r"DataSet/groundwater.csv"
df = pd.read_csv(csv_path)

print("🚀 Starting automatic leak detection...")

for i, row in df.iterrows():
    usage = float(row["usage_liters"])
    ts = datetime.now()

    result = predict_leak(usage, ts)

    if result["leak_flag"] == 1:
        print(f"🚨 Leak detected at {result['timestamp']} | Usage: {usage} L | Groundwater: {result['groundwater_level']} L")
    else:
        print(f"✅ Normal usage: {usage} L | Groundwater: {result['groundwater_level']} L")

    # Simulate time gap (e.g., 5 seconds between readings)
    time.sleep(5)
