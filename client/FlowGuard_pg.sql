-- Create sequence and tables for FlowGuard in PostgreSQL

-- Drop existing tables if needed (WARNING: This deletes data)
-- DROP TABLE IF EXISTS motor_history;
-- DROP TABLE IF EXISTS alerts;
-- DROP TABLE IF EXISTS motors;
-- DROP TABLE IF EXISTS farmers;
-- DROP TABLE IF EXISTS admins;
-- DROP TABLE IF EXISTS groundwater;

-- Admins
CREATE TABLE admins (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- Farmers
CREATE TABLE farmers (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    phone VARCHAR(20),
    role VARCHAR(20) DEFAULT 'farmer',
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Motors
CREATE TABLE motors (
    id SERIAL PRIMARY KEY,
    farmer_id INT REFERENCES farmers(id) ON DELETE CASCADE,
    motor_name VARCHAR(50) NOT NULL,
    status VARCHAR(10) DEFAULT 'OFF' CHECK (status IN ('ON', 'OFF')),
    runtime_seconds INT DEFAULT 0,
    water_used INT DEFAULT 0
);

-- Alerts
CREATE TABLE alerts (
    id SERIAL PRIMARY KEY,
    farmer_id INT REFERENCES farmers(id) ON DELETE CASCADE,
    message VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- History (runtime & usage logs)
CREATE TABLE motor_history (
    id SERIAL PRIMARY KEY,
    motor_id INT REFERENCES motors(id) ON DELETE CASCADE,
    runtime_seconds INT DEFAULT 0,
    water_used INT DEFAULT 0,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Motor Updates (Log for admin)
CREATE TABLE motor_updates (
    id SERIAL PRIMARY KEY,
    motor_id INT REFERENCES motors(id) ON DELETE CASCADE,
    farmer_id INT REFERENCES farmers(id) ON DELETE CASCADE,
    status VARCHAR(10) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Groundwater Data
CREATE TABLE groundwater (
    id SERIAL PRIMARY KEY,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    level_per_day REAL,
    volume REAL
);

-- Initial Data
INSERT INTO groundwater (level_per_day, volume) VALUES
(12.5, 1000),
(13.0, 980),
(12.8, 950);

-- Initial Admin Credentials
-- Email: admin@gmail.com | Password: admin1234
-- Note: Run admin_seed.php to regenerate hash if needed
INSERT INTO admins (username, password) VALUES 
('admin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- Hash for 'admin1234'
