USE flowguard;

-- Admins
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255) -- store hashed password
);

-- Farmers
CREATE TABLE IF NOT EXISTS farmers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Motors
CREATE TABLE IF NOT EXISTS motors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT,
    motor_name VARCHAR(50),
    status ENUM('ON','OFF') DEFAULT 'OFF',
    runtime_seconds INT DEFAULT 0,
    water_used INT DEFAULT 0,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id)
);

-- Alerts
CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT,
    message VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id)
);

-- History (runtime & usage logs)
CREATE TABLE IF NOT EXISTS motor_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    motor_id INT,
    runtime_seconds INT,
    water_used INT,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (motor_id) REFERENCES motors(id)
);

-- Sensor Readings
CREATE TABLE IF NOT EXISTS sensor_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT,
    motor_id INT,
    device_id VARCHAR(50),
    water_flow FLOAT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id),
    FOREIGN KEY (motor_id) REFERENCES motors(id)
);

CREATE TABLE groundwater (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    level_per_day FLOAT,
    volume FLOAT
);

INSERT INTO groundwater (level_per_day, volume) VALUES
(12.5, 1000),
(13.0, 980),
(12.8, 950);

CREATE TABLE farmers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
