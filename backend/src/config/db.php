<?php
// backend/src/config/db.php

require_once __DIR__ . '/env.php';

if (!class_exists('Database', false)) {
    class Database {
        private $host;
        private $db_name;
        private $username;
        private $password;
        public $conn;

        public function __construct()
        {
            $this->host = axioma_env('DB_HOST', 'localhost');
            $this->db_name = axioma_env('DB_NAME', 'axioma_bd');
            $this->username = axioma_env('DB_USER', 'root');
            $this->password = axioma_env('DB_PASSWORD');
        }

        public function getConnection() {
            $this->conn = null;
            try {
                // Todo en una sola línea continua, sin saltos extraños
                $charset = axioma_env('DB_CHARSET', 'utf8mb4');
                $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $charset, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $exception) {
                error_log("Error: " . $exception->getMessage());
                die(json_encode(["success" => false, "message" => "Error de conexión."]));
            }
            return $this->conn;
        }
    }
}
?>