-- Esquema de la demo LAMP en Docker.
-- MySQL ejecuta este archivo automaticamente la primera vez que se
-- inicializa el volumen db_data, porque el compose lo monta en
-- /docker-entrypoint-initdb.d/. Si el volumen ya existe, no se ejecuta.

CREATE TABLE IF NOT EXISTS usuarios (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    email      VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO usuarios (username, email) VALUES
    ('admin',    'admin@test.com'),
    ('usuario1', 'user1@test.com'),
    ('usuario2', 'user2@test.com');
