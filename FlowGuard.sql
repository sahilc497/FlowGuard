USE flowguard;

-- =========================
-- ADMINS TABLE
-- =========================
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- FARMERS TABLE
-- =========================
CREATE TABLE IF NOT EXISTS farmers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- MOTORS TABLE
-- =========================
CREATE TABLE IF NOT EXISTS motors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    motor_name VARCHAR(50) NOT NULL,
    status ENUM('ON','OFF') DEFAULT 'OFF',
    runtime_seconds INT DEFAULT 0,
    water_used FLOAT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_motor_farmer
        FOREIGN KEY (farmer_id)
        REFERENCES farmers(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =========================
-- MOTOR HISTORY
-- =========================
CREATE TABLE IF NOT EXISTS motor_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    motor_id INT NOT NULL,
    runtime_seconds INT NOT NULL,
    water_used FLOAT NOT NULL,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_history_motor
        FOREIGN KEY (motor_id)
        REFERENCES motors(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =========================
-- ALERTS TABLE
-- =========================
CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    alert_type ENUM('LEAK','OVERUSE','SYSTEM','WARNING') DEFAULT 'SYSTEM',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_alert_farmer
        FOREIGN KEY (farmer_id)
        REFERENCES farmers(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =========================
-- GROUNDWATER TABLE
-- =========================
CREATE TABLE IF NOT EXISTS groundwater (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    level_per_day FLOAT,
    volume FLOAT,

    CONSTRAINT fk_groundwater_farmer
        FOREIGN KEY (farmer_id)
        REFERENCES farmers(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- =========================
-- SAMPLE DATA
-- =========================
INSERT INTO groundwater (level_per_day, volume) VALUES
(12.5, 1000),
(13.0, 980),
(12.8, 950);