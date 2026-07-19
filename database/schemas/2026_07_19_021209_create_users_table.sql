CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) NOT NULL,
    nama VARCHAR(255),
    email VARCHAR(255),
    password_hash VARCHAR(255),
    photo VARCHAR(255),
    kode INT(4),
    role VARCHAR(255) DEFAULT 'staf',
    dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    diedit TIMESTAMP,
    dihapus TIMESTAMP,
    INDEX (uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
