<?php
// backend/src/config/db.php

class Database {
    private $host = "localhost";
    private $db_name = "Axioma_BD";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // Todo en una sola línea continua, sin saltos extraños
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log("Error: " . $exception->getMessage());
            die(json_encode(["success" => false, "message" => "Error de conexión."]));
        }
        return $this->conn;
    }
}
?>