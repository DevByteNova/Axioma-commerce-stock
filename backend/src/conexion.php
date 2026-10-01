<?php

require_once __DIR__ . '/config/env.php';

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
                $charset = axioma_env('DB_CHARSET', 'utf8mb4');
                $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $charset, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $exception) {
                echo "Error de conexión: " . $exception->getMessage();
            }
            return $this->conn;
        }
    }
}
?>