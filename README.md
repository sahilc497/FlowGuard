# FlowGuard - Water Monitoring & Irrigation Management System

A comprehensive water monitoring system for farmers with leak detection, motor control, and groundwater tracking.

## 🏗️ Project Architecture

- **Frontend**: PHP (XAMPP)
- **Backend APIs**: Python Flask (multiple servers)
- **Database**: MySQL
- **Real-time**: MQTT
- **ML**: Leak detection models (scikit-learn)

---

## 📋 Prerequisites

### Required Software:
1. **XAMPP** (PHP 7.4+, MySQL, Apache)
   - Download: https://www.apachefriends.org/
   
2. **Python 3.8+**
   - Download: https://www.python.org/downloads/
   
3. **Composer** (PHP dependency manager)
   - Download: https://getcomposer.org/

4. **Git** (optional, for cloning)

### Python Packages:
- Flask
- pandas
- numpy
- scikit-learn
- joblib
- matplotlib
- paho-mqtt (for MQTT clients)

---

## 🚀 Installation & Setup

### Step 1: Clone/Download Project

```bash
# If using Git
git clone <repository-url>
cd 15K

# Or extract ZIP to: C:\xampp\htdocs\15K
```

### Step 2: Setup XAMPP

1. **Install XAMPP** (if not already installed)
2. **Start Apache and MySQL** from XAMPP Control Panel
3. **Verify** Apache is running on `http://localhost`
4. **Verify** MySQL is running (port 3306)

### Step 3: Setup Database

1. Open **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Create a new database:
   ```sql
   CREATE DATABASE flowguard;
   ```
3. Import the SQL schema:
   - Go to phpMyAdmin → Select `flowguard` database
   - Click **Import** tab
   - Choose file: `client/FlowGuard.sql`
   - Click **Go**

   OR run manually:
   ```bash
   mysql -u root -p flowguard < client/FlowGuard.sql
   ```

4. **Verify tables created**:
   - `admins`
   - `farmers`
   - `motors`
   - `sensor_readings`
   - `groundwater`
   - `alerts`

### Step 4: Configure Database Connection

Edit `client/config.php`:

```php
$host = "localhost";
$user = "root";        // Your MySQL username
$pass = "";            // Your MySQL password (empty for XAMPP default)
$db   = "flowguard";
```

### Step 5: Install PHP Dependencies

Open terminal in project root and run:

```bash
cd client
composer install
```

This installs PHP dependencies (currently minimal, Firebase removed).

### Step 6: Install Python Dependencies

Open terminal and run:

```bash
# Install required packages
pip install flask pandas numpy scikit-learn joblib matplotlib paho-mqtt

# Or create requirements.txt and install:
pip install -r requirements.txt
```

**Create `requirements.txt`** (if it doesn't exist):
```txt
Flask==2.3.0
pandas==2.0.3
numpy==1.24.3
scikit-learn==1.3.0
joblib==1.3.1
matplotlib==3.7.2
paho-mqtt==1.6.1
```

### Step 8: Prepare Data Files

1. **Create DataSet folder** (if missing):
   ```bash
   mkdir DataSet
   ```

2. **Add groundwater.csv** to `DataSet/` folder:
   - Should contain columns: `timestamp`, `usage_liters`
   - Sample format:
     ```csv
     timestamp,usage_liters
     2024-01-01 00:00:00,150.5
     2024-01-01 01:00:00,180.2
     ```

3. **Verify ML model files exist**:
   - `server/leak_model.pkl`
   - `server/scaler.pkl`
   - `client/model/leak_model.pkl`
   - `client/model/scaler.pkl`

---

## 🎯 Running the Application

### Option 1: Development Setup (Recommended)

#### Terminal 1: Start Flask Server (Leak Detection API)
```bash
cd server
python server.py
```
**Server runs on**: `http://localhost:5000`

#### Terminal 2: Start Flask App (ML Prediction UI)
```bash
cd client
python app.py
```
**Server runs on**: `http://localhost:5001`

#### Terminal 3: Start MQTT Subscriber (Optional - for IoT data)
```bash
cd client
php mqtt_subscribe.php
```

#### Terminal 4: Access Web Interface
- Open browser: `http://localhost/15K/client/`
- Or: `http://localhost/15K/client/index.php`

### Option 2: Quick Start Script (Windows)

Create `start.bat`:
```batch
@echo off
echo Starting FlowGuard System...

echo Starting Flask Server (Port 5000)...
start "Flask Server" cmd /k "cd server && python server.py"

timeout /t 3

echo Starting Flask App (Port 5001)...
start "Flask App" cmd /k "cd client && python app.py"

timeout /t 2

echo Opening browser...
start http://localhost/15K/client/

echo.
echo All servers started!
echo Press any key to exit...
pause
```

---

## 🔧 Configuration

### Port Configuration

| Service | Port | File |
|---------|------|------|
| Apache (PHP) | 80 | XAMPP Config |
| MySQL | 3306 | XAMPP Config |
| Flask Server | 5000 | `server/server.py` |
| Flask App | 5001 | `client/app.py` |
| Flask Threshold | 5002 | `client/thresh.py` |

### MQTT Configuration

Edit `client/mqtt_subscribe.php`:
```php
$server   = "broker.hivemq.com";  // Or your MQTT broker
$port     = 1883;                  // 8883 for TLS
$username = "";                    // Your MQTT username
$password = "";                    // Your MQTT password
```

---

## 📱 Access Points

### Web Interfaces:
- **Home Page**: `http://localhost/15K/client/index.php`
- **Admin Login**: `http://localhost/15K/client/admin_login.php`
- **Farmer Login**: `http://localhost/15K/client/farmer_login.php`
- **Farmer Register**: `http://localhost/15K/client/farmer_register.php`

### API Endpoints:
- **Leak Detection API**: `http://localhost:5000/data`
- **ML Prediction UI**: `http://localhost:5001/`
- **Data Endpoint**: `http://localhost/15K/client/data.php`

---

## 👤 Default Accounts

### Create Admin Account

Run `client/admin_seed.php` in browser:
```
http://localhost/15K/client/admin_seed.php
```

Or manually insert:
```sql
INSERT INTO admins (username, password) 
VALUES ('admin', '$2y$10$hashed_password');
```

**Note**: Use `client/hash.php` to generate password hash.

### Create Test Farmer

1. Register via: `http://localhost/15K/client/farmer_register.php`
2. Or insert directly into database

---

## 🐛 Troubleshooting

### Issue: "Database Connection Failed"
- ✅ Check MySQL is running in XAMPP
- ✅ Verify credentials in `client/config.php`
- ✅ Ensure database `flowguard` exists

### Issue: "Module not found" (Python)
- ✅ Install missing packages: `pip install <package-name>`
- ✅ Check Python version: `python --version` (should be 3.8+)

### Issue: "Composer dependencies not found"
- ✅ Run: `cd client && composer install`
- ✅ Check `composer.json` exists

### Issue: "Model file not found"
- ✅ Verify `.pkl` files exist in `server/` and `client/model/`
- ✅ Check file paths in code match actual locations

### Issue: "Port already in use"
- ✅ Change port in Python files (e.g., `port=5003`)
- ✅ Or kill process using the port:
  ```bash
  # Windows
  netstat -ano | findstr :5000
  taskkill /PID <PID> /F
  ```

### Issue: "CSV file not found"
- ✅ Create `DataSet/` folder in project root
- ✅ Add `groundwater.csv` with required columns

---

## 📁 Project Structure

```
15K/
├── client/                 # PHP frontend
│   ├── config.php         # Database & MQTT config
│   ├── *.php              # PHP pages
│   ├── model/             # ML models & templates
│   ├── config/            # Composer dependencies
│   └── tempalate/         # Images/assets
├── server/                # Python Flask backend
│   ├── server.py         # Main leak detection API
│   ├── leak_model.pkl    # ML model
│   └── scaler.pkl        # Data scaler
├── DataSet/              # CSV data files
├── composer.json         # PHP dependencies
└── FlowGuard.sql        # Database schema
```

---

## 🔄 Development Workflow

1. **Start XAMPP** (Apache + MySQL)
2. **Start Flask servers** (Terminal 1 & 2)
3. **Access web interface** in browser
4. **Check logs** in terminal windows for errors

### Hot Reload:
- **PHP**: Changes reflect immediately (Apache auto-reloads)
- **Python Flask**: Auto-reloads on code changes (debug mode)

---

## 📊 Testing

### Test Database Connection:
```bash
# Browser
http://localhost/15K/client/test_db.php
```

### Test API Endpoints:
```bash
# Leak Detection API
curl http://localhost:5000/data

# Data Endpoint
curl http://localhost/15K/client/data.php
```

### Test MQTT:
1. Start `mqtt_subscribe.php`
2. Publish test message to `flowguard/#` topic
3. Check terminal output

---

## 🚀 Production Deployment

### For Production:

1. **Disable debug mode** in Flask:
   ```python
   app.run(debug=False, host="0.0.0.0", port=5000)
   ```

2. **Use production WSGI server**:
   ```bash
   pip install gunicorn
   gunicorn -w 4 -b 0.0.0.0:5000 server:app
   ```

3. **Configure Apache virtual host** for PHP
4. **Set up SSL certificates**
5. **Use environment variables** for sensitive config
6. **Enable database connection pooling**
7. **Set up monitoring and logging**

---

## 📝 Notes

- **Windows Paths**: Code uses Windows-style paths (`\`). For Linux/Mac, update paths to use `/`
- **MQTT**: Required only if using IoT sensors
- **ML Models**: Pre-trained models included; retrain as needed

---

## 🤝 Support

For issues or questions:
1. Check `OPTIMIZATION_REPORT.md` for performance tips
2. Review error logs in terminal
3. Verify all prerequisites are installed
4. Check database connection and file paths

---

## 📄 License

Created by Team VectorOne

---

**Last Updated**: 2024
**Version**: 1.0
