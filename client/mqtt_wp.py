# ESp32_1

import paho.mqtt.client as mqtt
import ssl


# Try to use certifi if available
try:
    import certifi
    CA_CERTS = certifi.where()
    print("🔒 Using certifi CA bundle")
except ImportError:
    CA_CERTS = None
    print("🔒 Using system CA bundle")

# ====== HiveMQ Cloud Credentials (from .env) ======
import os
MQTT_BROKER   = os.getenv("MQTT_BROKER", "42197e61510145d38e4efb73f59a7f6a.s1.eu.hivemq.cloud")
MQTT_PORT     = int(os.getenv("MQTT_PORT", "8883"))
MQTT_USERNAME = os.getenv("MQTT_USERNAME", "")
MQTT_PASSWORD = os.getenv("MQTT_PASSWORD", "")

# ====== MQTT Callbacks ======
def on_connect(client, userdata, flags, rc, properties=None):
    if rc == 0:
        print("✅ Connected to HiveMQ Cloud")
        client.subscribe("esp32_1/#")  # listen to all ESP32 topics
    else:
        print("❌ Connection failed, return code:", rc)

def on_message(client, userdata, msg):
    print(f"📩 {msg.topic}: {msg.payload.decode()}")

# ====== MQTT Client Setup ======
client = mqtt.Client(mqtt.CallbackAPIVersion.VERSION2)
client.username_pw_set(MQTT_USERNAME, MQTT_PASSWORD)
client.on_connect = on_connect
client.on_message = on_message

# Enable TLS
if CA_CERTS:
    client.tls_set(ca_certs=CA_CERTS, tls_version=ssl.PROTOCOL_TLS_CLIENT)
else:
    client.tls_set(tls_version=ssl.PROTOCOL_TLS_CLIENT)

client.tls_insecure_set(False)  # keep certificate verification ON

# ====== Start Connection ======
print("🚀 Connecting to HiveMQ Cloud...")
client.connect(MQTT_BROKER, MQTT_PORT, keepalive=60)
client.loop_forever()
