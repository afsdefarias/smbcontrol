CREATE DATABASE IF NOT EXISTS smbcontrol;
USE smbcontrol;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Senha padrão do admin: 'admin' (bcrypt)
INSERT IGNORE INTO users (username, password) VALUES ('admin', '$2y$12$IwIiUY5We6dr51exhcZ0MOoB8lGBo14fq1OLy5MrLHoMohW.batLq');

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario VARCHAR(50) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    acao VARCHAR(255) NOT NULL,
    arquivo TEXT NULL
);
