-- Ejecutar en MariaDB con una cuenta administradora.
-- Contraseña para el entorno local de desarrollo.
CREATE USER IF NOT EXISTS 'axioma_app'@'localhost' IDENTIFIED BY 'Axioma-Stock!2026-Pijamas#Seguras';
ALTER USER 'axioma_app'@'localhost' IDENTIFIED BY 'Axioma-Stock!2026-Pijamas#Seguras';
GRANT SELECT, INSERT, UPDATE, DELETE ON axioma_bd.* TO 'axioma_app'@'localhost';
FLUSH PRIVILEGES;

-- Después actualiza .env:
-- DB_USER=axioma_app
-- DB_PASSWORD=Axioma-Stock!2026-Pijamas#Seguras
