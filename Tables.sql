CREATE TABLE sensors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(23) NOT NULL UNIQUE,
    mac_address CHAR(17) NOT NULL UNIQUE,
    ip_address VARCHAR(15),
    first_seen DATETIME NOT NULL
);

CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    floor INT NOT NULL,
    room_name VARCHAR(100) NOT NULL
);

CREATE TABLE sensor_room_map (
    sensor_id INT NOT NULL,
    room_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME DEFAULT NULL,
    PRIMARY KEY (sensor_id, start_time),
    FOREIGN KEY (sensor_id) REFERENCES sensors(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

CREATE TABLE sensor_readings (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    sensor_id INT NOT NULL,
    timestamp INT UNSIGNED NOT NULL,
    raw_value SMALLINT UNSIGNED NOT NULL,
    temperature_celsius DECIMAL(5,2) NOT NULL,
    FOREIGN KEY (sensor_id) REFERENCES sensors(id) ON DELETE CASCADE,
    INDEX idx_sensor_time (sensor_id, timestamp)
);
