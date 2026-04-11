from flask import Flask, jsonify, send_file
import pandas as pd
import numpy as np
import joblib
import time
import threading
from datetime import datetime
import os
from collections import deque

app = Flask(__name__)

# Load model and scaler (cached at startup)
# Use os.path.join for cross-platform compatibility
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
MODEL_PATH = os.path.join(BASE_DIR, "leak_model.pkl")
SCALER_PATH = os.path.join(BASE_DIR, "scaler.pkl")
model = joblib.load(MODEL_PATH)
scaler = joblib.load(SCALER_PATH)

GROUNDWATER_CAPACITY = 10000
current_groundwater = GROUNDWATER_CAPACITY
latest_result = {}

# Cache for rolling statistics (optimization)
usage_history = deque(maxlen=100)  # Keep last 100 values for rolling mean

def update_groundwater(usage):
    global current_groundwater
    current_groundwater = max(current_groundwater - usage, 0)
    return current_groundwater

def predict_leak(usage, ts):
    """Optimized prediction using numpy arrays instead of DataFrame"""
    # Use numpy array directly - much faster than creating DataFrame
    hour = ts.hour
    dayofweek = ts.weekday()
    
    # Calculate rolling mean from history
    usage_history.append(usage)
    rolling_mean = np.mean(usage_history) if usage_history else usage
    daily_cum = usage  # Simplified - could be enhanced with actual daily tracking
    
    # Create feature array directly (faster than DataFrame)
    features = np.array([[usage, hour, dayofweek, rolling_mean, daily_cum]])
    X_scaled = scaler.transform(features)
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

def auto_predict():
    """Optimized auto-predict using vectorized operations"""
    global latest_result
    # Use os.path.join for cross-platform compatibility
    project_root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    csv_path = os.path.join(project_root, "DataSet", "groundwater.csv")
    
    # Load CSV once and convert to numpy array for faster access
    df = pd.read_csv(csv_path)
    usage_values = df["usage_liters"].values  # Convert to numpy array (faster)
    total_rows = len(usage_values)
    current_index = 0

    while True:
        # Use modulo to cycle through data efficiently
        usage = float(usage_values[current_index % total_rows])
        ts = datetime.now()
        latest_result = predict_leak(usage, ts)
        print(latest_result)
        current_index += 1
        time.sleep(5)

@app.route("/data")
def get_data():
    return jsonify(latest_result)

@app.route("/leak")
def leak_page():
    return send_file(os.path.join(os.path.dirname(__file__), "leak.php"))

if __name__ == "__main__":
    t = threading.Thread(target=auto_predict)
    t.daemon = True
    t.start()
    app.run(debug=True, port=5000)
