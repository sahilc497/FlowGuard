from flask import Flask, render_template, request, jsonify
import pandas as pd
import joblib
import math

app = Flask(__name__)

# Load model
model = joblib.load("water_model.pkl")

# KC values
KC_VALUES = {
    "Rice": [(1,30,0.8),(31,60,1.05),(61,90,1.2),(91,120,1.1)],
    "Wheat": [(1,30,0.7),(31,60,1.0),(61,90,1.15),(91,120,1.0)]
}

# Hargreaves + time factor
def calculate_et0(temp, humidity, hour):
    tmax = temp + 3
    tmin = temp - 3
    tmean = (tmax + tmin) / 2
    td = tmax - tmin

    if 6 <= hour <= 18:
        ra = 15 * math.sin(math.pi * (hour - 6) / 12)
    else:
        ra = 2

    et0 = 0.0023 * ra * math.sqrt(td) * (tmean + 17.8)

    if 6 <= hour <= 18:
        factor = 1 + (50 - humidity)/100
    else:
        factor = 0.5 + (50 - humidity)/200

    return et0 * factor

@app.route("/")
def home():
    return render_template("app.html")

@app.route("/predict", methods=["POST"])
def predict():

    data = request.json

    crop = data["crop"]
    duration = int(data["duration"])
    growth = int(data["growth"])
    temp = float(data["temperature"])
    hum = float(data["humidity"])
    area = float(data["area"])

    # KC
    kc = 1.0
    for s,e,k in KC_VALUES.get(crop, []):
        if s <= growth <= e:
            kc = k

    # Time series
    time = list(range(24))
    water_series = []

    for h in time:
        t_var = temp + 5 * math.sin((h-6)*math.pi/12)
        h_var = hum - 15 * math.sin((h-6)*math.pi/12)

        et0 = calculate_et0(t_var, h_var, h)
        eta = et0 * kc

        df = pd.DataFrame([{
            "temperature": t_var,
            "humidity": h_var,
            "growth_day": growth,
            "crop_duration_days": duration,
            "et0": et0,
            "kc": kc,
            "eta": eta
        }])

        water = model.predict(df)[0]
        water_series.append(round(water,2))

    # Current values
    et0_now = calculate_et0(temp, hum, 12)
    eta_now = et0_now * kc

    df_now = pd.DataFrame([{
        "temperature": temp,
        "humidity": hum,
        "growth_day": growth,
        "crop_duration_days": duration,
        "et0": et0_now,
        "kc": kc,
        "eta": eta_now
    }])

    water_per_acre = model.predict(df_now)[0]
    total_water = water_per_acre * area

    return jsonify({
        "et0": round(et0_now,2),
        "kc": kc,
        "eta": round(eta_now,2),
        "water_per_acre": round(water_per_acre,2),
        "total_water": round(total_water,2),
        "time": time,
        "water_series": water_series
    })

if __name__ == "__main__":
    app.run(debug=True, port=5002)