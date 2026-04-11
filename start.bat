@echo off
echo ========================================
echo   FlowGuard - Starting All Services
echo ========================================
echo.

REM Check if Python is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Python is not installed or not in PATH!
    echo Please install Python 3.8+ from https://www.python.org/downloads/
    pause
    exit /b 1
)

REM Check if XAMPP MySQL is running (optional check)
echo [INFO] Checking XAMPP MySQL...
netstat -an | findstr :3306 >nul 2>&1
if errorlevel 1 (
    echo [WARNING] MySQL might not be running. Please start XAMPP MySQL!
) else (
    echo [OK] MySQL appears to be running on port 3306
)

echo.
echo [1/3] Starting Flask Server (Port 5000)...
start "FlowGuard - Flask Server" cmd /k "cd /d %~dp0server && python server.py"
timeout /t 2 >nul

echo [2/3] Starting Flask App (Port 5001)...
start "FlowGuard - Flask App" cmd /k "cd /d %~dp0client && python app.py"
timeout /t 2 >nul

echo [3/3] Opening web browser...
timeout /t 1 >nul
start http://localhost/15K/client/

echo.
echo ========================================
echo   All services started successfully!
echo ========================================
echo.
echo Access points:
echo   - Web Interface: http://localhost/15K/client/
echo   - Flask API:     http://localhost:5000/data
echo   - ML UI:         http://localhost:5001/
echo.
echo Press any key to close this window...
echo (Servers will continue running in separate windows)
pause >nul
