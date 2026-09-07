<?php
session_start();
require_once __DIR__ . '/../../../backend/src/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    try {
        $db->beginTransaction();

        // Insertar cliente
        $sql = "INSERT INTO clientes (NOMBRE, APELLIDOS, email, DIRECCION, TELEFONO) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_POST['nombre'] ?? '',
            $_POST['apellido'] ?? '',
            $_POST['email'] ?? '',
            $_POST['direccion'] ?? '',
            $_POST['telefono'] ?? ''
        ]);

        $id_cliente = $db->lastInsertId();

        // Registrar credenciales en usuarios
        $usuario = trim($_POST['usuario'] ?? ($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($usuario) || empty($password)) {
            throw new Exception('Usuario y contraseña son obligatorios.');
        }

        $sqlUser = "INSERT INTO usuarios (usuario, email, password_hash, ID_CLIENTE) VALUES (?, ?, ?, ?)";
        $stmtUser = $db->prepare($sqlUser);
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmtUser->execute([$usuario, $_POST['email'] ?? '', $hashed, $id_cliente]);

        $db->commit();

        header("Location: index.php?url=clientes");
        exit;
    } catch (Exception $e) {
        if ($db && $db->inTransaction()) $db->rollBack();
        $error = 'Error al guardar el cliente: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-sm border border-gray-200">
        <h2 class="text-xl font-bold mb-6">Registrar Nuevo Cliente</h2>

        <?php if(isset($error)) echo "<p class='text-red-500 mb-4'>$error</p>"; ?>

        <form method="post">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Nombre</label>
                <input type="text" name="nombre" required class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Apellido</label>
                <input type="text" name="apellido" required class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" required class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Usuario (login)</label>
                <input type="text" name="usuario" required class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="ejemplo@dominio.com">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700">Contraseña</label>
                <input type="password" name="password" required class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div class="mb-4">
                 <label class="block mb-2 font-bold">Dirección</label>
                <input type="text" name="direccion" class="w-full px-4 py-2 border rounded-lg">
            </div>

            <div class="mb-4">
                <label class="block mb-2 font-bold">Teléfono</label>
                <input type="text" name="telefono" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div class="flex gap-4">
                <a href="index.php?url=clientes" class="w-full text-center py-2 border rounded-lg hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg font-bold hover:bg-blue-700">Guardar</button>
            </div>
        </form>
    </div>
</body>
</html>