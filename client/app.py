from flask import Flask, render_template, request
import pandas as pd
import joblib
import os

# -------------------- FIX MATPLOTLIB (WINDOWS SAFE) --------------------
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt

# -------------------- PATH SETUP --------------------
BASE_DIR = os.path.dirname(os.path.abspath(__file__))

MODEL_DIR = os.path.join(BASE_DIR, "model")
UPLOAD_FOLDER = os.path.join(BASE_DIR, "uploads")

MODEL_PATH = os.path.join(MODEL_DIR, "leak_model.pkl")
SCALER_PATH = os.path.join(MODEL_DIR, "scaler.pkl")
STATIC_FOLDER = os.path.join(MODEL_DIR, "static")
PLOT_PATH = os.path.join(STATIC_FOLDER, "plot.png")
TEMPLATE_FOLDER = os.path.join(MODEL_DIR, "templates")

os.makedirs(UPLOAD_FOLDER, exist_ok=True)
os.makedirs(STATIC_FOLDER, exist_ok=True)

# -------------------- FLASK INIT (IMPORTANT PART) --------------------
app = Flask(
    __name__,
    template_folder=TEMPLATE_FOLDER,
    static_folder=STATIC_FOLDER
)

# -------------------- LOAD ML FILES --------------------
model = joblib.load(MODEL_PATH)
scaler = joblib.load(SCALER_PATH)

# -------------------- ROUTES --------------------
@app.route("/")
def home():
    return render_template("upload.html")

@app.route("/predict", methods=["POST"])
def predict():
    if "file" not in request.files:
        return "No file uploaded", 400

    file = request.files["file"]
    
    # OPTIMIZATION: Validate file extension before processing
    if not file.filename or not file.filename.lower().endswith('.csv'):
        return "Invalid file type. Please upload a CSV file.", 400
    
    # OPTIMIZATION: Use secure filename and add timestamp to avoid conflicts
    import uuid
    safe_filename = f"{uuid.uuid4().hex}_{file.filename}"
    file_path = os.path.join(UPLOAD_FOLDER, safe_filename)
    
    try:
        file.save(file_path)

        # -------------------- READ CSV --------------------
        df = pd.read_csv(file_path)

        # OPTIMIZATION: Validate required columns exist
        required_cols = ["timestamp", "flow_rate"]
        if not all(col in df.columns for col in required_cols):
            os.remove(file_path)  # Cleanup
            return f"CSV must contain columns: {', '.join(required_cols)}", 400

        # -------------------- FEATURE ENGINEERING --------------------
        df["timestamp"] = pd.to_datetime(df["timestamp"])
        df["hour"] = df["timestamp"].dt.hour
        df["minute"] = df["timestamp"].dt.minute

        X = df[["flow_rate", "hour", "minute"]]
        X_scaled = scaler.transform(X)

        df["prediction"] = model.predict(X_scaled)

        # -------------------- VISUALIZATION --------------------
        plt.figure(figsize=(12, 5))
        plt.plot(df["timestamp"], df["flow_rate"], label="Flow Rate")
        plt.scatter(
            df[df["prediction"] == 1]["timestamp"],
            df[df["prediction"] == 1]["flow_rate"],
            color="red",
            label="Leak Detected"
        )
        plt.xlabel("Time")
        plt.ylabel("Flow Rate")
        plt.title("24-Hour Leak Detection")
        plt.legend()
        plt.tight_layout()
        plt.savefig(PLOT_PATH)
        plt.close()

        leak_count = int(df["prediction"].sum())

        # OPTIMIZATION: Clean up uploaded file after processing
        try:
            os.remove(file_path)
        except:
            pass  # Ignore cleanup errors

        return render_template(
            "result.html",
            tables=df.tail(20).to_html(classes="table table-striped", index=False),
            leak_count=leak_count,
            plot_path="plot.png"   # served from model/static
        )
    except Exception as e:
        # OPTIMIZATION: Clean up on error
        try:
            if os.path.exists(file_path):
                os.remove(file_path)
        except:
            pass
        return f"Error processing file: {str(e)}", 500

# -------------------- RUN SERVER --------------------
if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5001, debug=True)
