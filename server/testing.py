import requests

url = "http://127.0.0.1:5000/predict"
data = {"timestamp": "2025-09-24 10:00:00", "usage_liters": 180}
res = requests.post(url, json=data)
print(res.json())
