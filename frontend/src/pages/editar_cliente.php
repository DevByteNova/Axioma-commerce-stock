<?php
session_start();
require_once __DIR__ . '/../../../backend/src/conexion.php';
$database = new Database();
$db = $database->getConnection();

// 1. Si enviaron el formulario (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Actualizamos TODAS las columnas
    $sql = "UPDATE clientes SET NOMBRE = ?, APELLIDOS = ?, email = ?, DIRECCION = ?, TELEFONO = ? WHERE ID_CLIENTE = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $_POST['nombre'], 
        $_POST['apellido'], 
        $_POST['email'], 
        $_POST['direccion'], 
        $_POST['telefono'], 
        $_POST['id']
    ]);
    header("Location: index.php?url=clientes");
    exit;
}

// 2. Cargamos los datos del cliente original (GET)
$stmt = $db->prepare("SELECT * FROM clientes WHERE ID_CLIENTE = ?");
$stmt->execute([$_GET['id']]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cliente - Axioma</title>
    <link rel="icon" href="./frontend/src/public/favicon/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">

    <div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow">
        <h2 class="text-xl font-bold mb-6">Editar Cliente</h2>
        
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $cliente['ID_CLIENTE']; ?>">
            
            <div class="mb-4">
                <label class="block mb-2 font-bold text-gray-700">Nombre</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($cliente['NOMBRE'] ?? ''); ?>" required class="w-full p-2 border border-gray-300 rounded">
            </div>

            <div class="mb-4">
                <label class="block mb-2 font-bold text-gray-700">Apellido</label>
                <input type="text" name="apellido" value="<?php echo htmlspecialchars($cliente['APELLIDOS'] ?? ''); ?>" required class="w-full p-2 border border-gray-300 rounded">
            </div>

            <div class="mb-4">
                <label class="block mb-2 font-bold text-gray-700">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($cliente['email'] ?? ''); ?>" required class="w-full p-2 border border-gray-300 rounded">
            </div>

            <div class="mb-4">
                <label class="block mb-2 font-bold text-gray-700">Dirección</label>
                <input type="text" name="direccion" value="<?php echo htmlspecialchars($cliente['DIRECCION'] ?? ''); ?>" class="w-full p-2 border border-gray-300 rounded">
            </div>

            <div class="mb-6">
                <label class="block mb-2 font-bold text-gray-700">Teléfono</label>
                <input type="text" name="telefono" value="<?php echo htmlspecialchars($cliente['TELEFONO'] ?? ''); ?>" class="w-full p-2 border border-gray-300 rounded">
            </div>

            <div class="flex gap-4">
                <a href="index.php?url=clientes" class="w-full text-center py-2 border border-gray-300 rounded hover:bg-gray-100">Cancelar</a>
                <button type="submit" class="bg-blue-600 text-white w-full py-2 rounded font-bold hover:bg-blue-700">Guardar Cambios</button>
            </div>
        </form>
    </div>

</body>
</html>