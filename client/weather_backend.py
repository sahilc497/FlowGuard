import ssl
import os
import requests
import paho.mqtt.client as mqtt

# ================= WEATHER CONFIG =================
OPENWEATHER_API_KEY = os.getenv("OPENWEATHER_API_KEY", "")
CITY = os.getenv("WEATHER_CITY", "Paris")

# OpenWeather forecast is 3-hour interval
CHECK_NEXT_HOURS = 1   # 1 entry = next 3 hours

# ================= MQTT (HiveMQ Cloud) =================
MQTT_BROKER = os.getenv("MQTT_BROKER", "f5bc2a85c9c747488d7ab174eaa1faaf.s1.eu.hivemq.cloud")
MQTT_PORT = int(os.getenv("MQTT_PORT", "8883"))
MQTT_USERNAME = os.getenv("MQTT_USERNAME", "")
MQTT_PASSWORD = os.getenv("MQTT_PASSWORD", "")

# 🔴 REQUIRED TOPIC
TOPIC_RELAY_CMD = "esp32_1/relay/control"

# ================= MQTT SETUP =================
client = mqtt.Client(client_id="Weather_Backend_Kolhapur")
client.username_pw_set(MQTT_USERNAME, MQTT_PASSWORD)

client.tls_set(cert_reqs=ssl.CERT_NONE)
client.tls_insecure_set(True)

client.connect(MQTT_BROKER, MQTT_PORT)
print("✅ Connected to HiveMQ Cloud")

# ================= WEATHER FETCH =================
url = (
    "https://api.openweathermap.org/data/2.5/forecast"
    f"?q={CITY}&appid={OPENWEATHER_API_KEY}"
)

response = requests.get(url, timeout=10)
data = response.json()

if "list" not in data:
    print("❌ Weather API error:", data)
    client.disconnect()
    exit()

# ================= RAIN CHECK LOGIC =================
rain_detected = False

for forecast in data["list"][:CHECK_NEXT_HOURS]:
    for w in forecast.get("weather", []):
        if "rain" in w["main"].lower():
            rain_detected = True
            break

# ================= DECISION & MQTT PUBLISH =================
if rain_detected:
    client.publish(TOPIC_RELAY_CMD, "OFF")
    print("🌧 Rain detected → Relay OFF sent to ESP32")
else:
    client.publish(TOPIC_RELAY_CMD, "ON")
    print("☀ No rain detected → Relay ON sent to ESP32")

client.disconnect()
print("🔌 MQTT Disconnected")
