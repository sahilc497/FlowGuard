-- =====================================================
-- FlowGuard MySQL Database Schema
-- For XAMPP MySQL Server
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS flowguard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE flowguard;

-- Drop existing tables if needed (WARNING: This deletes data)
-- DROP TABLE IF EXISTS motor_history;
-- DROP TABLE IF EXISTS motor_updates;
-- DROP TABLE IF EXISTS alerts;
-- DROP TABLE IF EXISTS motors;
-- DROP TABLE IF EXISTS farmers;
-- DROP TABLE IF EXISTS admins;
-- DROP TABLE IF EXISTS groundwater;

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Farmers table
CREATE TABLE IF NOT EXISTS farmers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    phone VARCHAR(20),
    role VARCHAR(20) DEFAULT 'farmer',
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Motors table
CREATE TABLE IF NOT EXISTS motors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    motor_name VARCHAR(50) NOT NULL,
    status VARCHAR(10) DEFAULT 'OFF' CHECK (status IN ('ON', 'OFF')),
    runtime_seconds INT DEFAULT 0,
    water_used INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Alerts table
CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Motor History table (runtime & usage logs)
CREATE TABLE IF NOT EXISTS motor_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    motor_id INT NOT NULL,
    runtime_seconds INT DEFAULT 0,
    water_used INT DEFAULT 0,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (motor_id) REFERENCES motors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Motor Updates table (Log for admin)
CREATE TABLE IF NOT EXISTS motor_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    motor_id INT NOT NULL,
    farmer_id INT NOT NULL,
    status VARCHAR(10) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (motor_id) REFERENCES motors(id) ON DELETE CASCADE,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Groundwater Data table
CREATE TABLE IF NOT EXISTS groundwater (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    level_per_day FLOAT,
    volume FLOAT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample groundwater data
INSERT INTO groundwater (level_per_day, volume) VALUES
(12.5, 1000),
(13.0, 980),
(12.8, 950);

-- Insert default admin user
-- Email: admin@gmail.com | Password: admin1234
-- Hash generated with: password_hash('admin1234', PASSWORD_DEFAULT)
INSERT INTO admins (username, password) VALUES 
('admin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Show all tables
SHOW TABLES;
