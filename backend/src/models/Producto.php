<?php
//backend/src/models/Productos.php

class Producto{
    private $conn;

    public function __construct($db){
        $this->conn = $db;
    }
    public function agregar($descripcion, $precio, $stock){
        $query = "INSERT INTO " . $this->table ." (DESCRIPCION, PRECIO, STOCK) VALUES (:desc, :precio, :stock)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':desc', $descripcion);
        $stmt->bindParam(':precio', $precio);
        $stmt->bindParam(':stock', $stock);
        return $stmt->execute();
    }
    public function listarTodos() {
        $query = "SELECT ID_PRODUCTO, nombre, DESCRIPCION, PRECIO, STOCK FROM productos";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}

?>