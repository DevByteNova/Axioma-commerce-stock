<?php
// backend/src/models/Usuario.php

class Usuario {
    private $conn;
    private $table_name = "usuarios";

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. LOGIN: Buscar por nombre de usuario
    public function buscarPorUsername($username) {
        $query = "SELECT id, usuario, email, password_hash, ID_CLIENTE, ID_VENDEDOR, is_active, rol FROM usuarios WHERE usuario = :username LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function buscarPorIdentificador($identificador) {
        $query = "SELECT id, usuario, email, password_hash, ID_CLIENTE, ID_VENDEDOR, is_active, rol
                  FROM usuarios
                  WHERE usuario = :username OR email = :email
                  ORDER BY CASE WHEN usuario = :username_priority THEN 0 ELSE 1 END, id
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':username' => $identificador,
            ':email' => $identificador,
            ':username_priority' => $identificador,
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function buscarActivoPorEmail($email) {
        $stmt = $this->conn->prepare(
            'SELECT id, email FROM usuarios WHERE email = :email AND is_active = 1 LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarPassword(int $id, string $passwordHash): bool {
        $stmt = $this->conn->prepare('UPDATE usuarios SET password_hash = :password_hash WHERE id = :id');
        return $stmt->execute([':password_hash' => $passwordHash, ':id' => $id]);
    }

    // 2. READ: Obtener todos los usuarios con sus datos vinculados
    public function leerTodos() {
        $query = "SELECT u.id, u.usuario, u.email, u.ID_CLIENTE, u.ID_VENDEDOR, u.is_active,
                         c.NOMBRE as nombre_cliente, c.APELLIDOS as apellidos_cliente,
                         v.NOMBRE as nombre_vendedor
                  FROM usuarios u
                  LEFT JOIN clientes c ON u.ID_CLIENTE = c.ID_CLIENTE
                  LEFT JOIN vendedor v ON u.ID_VENDEDOR = v.ID_VENDEDOR
                  ORDER BY u.id DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. CREATE: Registrar un usuario
    public function registrar($username, $email, $password, $id_cliente = null, $id_vendedor = null) {
        $query = "INSERT INTO usuarios (usuario, email, password_hash, ID_CLIENTE, ID_VENDEDOR) VALUES (:user, :email, :pass, :id_cliente, :id_vendedor)";
        
        $stmt = $this->conn->prepare($query);
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $stmt->bindParam(':user', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':pass', $hashed_password);
        $stmt->bindParam(':id_cliente', $id_cliente);
        $stmt->bindParam(':id_vendedor', $id_vendedor);

        return $stmt->execute();
    }

    // 4. DELETE: Eliminar un usuario
    public function eliminar($id) {
        $query = "DELETE FROM usuarios WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
} 
?>