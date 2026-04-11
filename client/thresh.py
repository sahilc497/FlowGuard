from flask import Flask, render_template, request, jsonify
import pandas as pd
import joblib
import os
import math

# -------------------- PATH SETUP --------------------
BASE_DIR = os.path.dirname(os.path.abspath(__file__))

MODEL_DIR = os.path.join(BASE_DIR, "model")
MODEL_PATH = os.path.join(MODEL_DIR, "water_threshold_model_v2.pkl")

TEMPLATE_FOLDER = os.path.join(MODEL_DIR, "templates")
STATIC_FOLDER = os.path.join(MODEL_DIR, "static")

# -------------------- FLASK INIT --------------------
app = Flask(
    __name__,
    template_folder=TEMPLATE_FOLDER,
    static_folder=STATIC_FOLDER
)

# -------------------- LOAD ML MODEL --------------------
model = joblib.load(MODEL_PATH)

# -------------------- KC VALUES --------------------
KC_VALUES = {
    "Sugarcane": [(1,45,0.75),(46,90,1.10),(91,120,1.30),(121,150,1.20)],
    "Rice": [(1,30,0.80),(31,60,1.05),(61,90,1.20),(91,120,1.10)],
    "Wheat": [(1,30,0.70),(31,60,1.00),(61,90,1.15),(91,120,1.00)]
}

# -------------------- ROUTES --------------------
@app.route("/")
def home():
    return render_template("index.html")

@app.route("/predict", methods=["POST"])
def predict():
    data = request.json

    crop = data["crop"]
    season = data["season"]
    soil = data["soil"]
    duration = int(data["duration"])
    growth_day = int(data["growth"])
    temperature = float(data["temperature"])
    humidity = float(data["humidity"])
    area = float(data["area"])  # acres

    # ---------- ET0 (FAO-56 simplified) ----------
    et0 = 0.0023 * (temperature + 17.8) * math.sqrt(100 - humidity)

    # ---------- KC ----------
    kc = 1.0
    for start, end, k in KC_VALUES.get(crop, []):
        if start <= growth_day <= end:
            kc = k
            break

    # ---------- ETA ----------
    eta = et0 * kc

    # ---------- ML INPUT ----------
    input_df = pd.DataFrame([{
        "crop_name": crop,
        "season": season,
        "soil_type": soil,
        "crop_duration_days": duration,
        "growth_day": growth_day,
        "temperature": temperature,
        "humidity": humidity,
        "et0_mm_day": et0,
        "kc": kc,
        "eta_mm_day": eta
    }])

    # ---------- PREDICTION ----------
    water_per_acre = model.predict(input_df)[0]
    total_water = water_per_acre * area

    return jsonify({
        "et0_mm_day": round(et0, 2),
        "kc": kc,
        "eta_mm_day": round(eta, 2),
        "water_per_acre_liters": round(water_per_acre, 2),
        "area_acres": area,
        "total_water_liters": round(total_water, 2)
    })

# -------------------- RUN SERVER --------------------
if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5002, debug=True)
