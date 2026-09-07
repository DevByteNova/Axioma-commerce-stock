<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_rol']) || !in_array($_SESSION['usuario_rol'], ['Administrador', 'Vendedor'], true)) {
    header("Location: index.php?url=login");
    exit;
}

$mensaje = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../../backend/src/conexion.php';
    $database = new Database();
    $db = $database->getConnection();

    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $talla = trim($_POST['talla'] ?? '');
    $precio = trim($_POST['precio'] ?? '');
    $stock = trim($_POST['stock'] ?? '');

    if (!empty($nombre) && !empty($descripcion) && !empty($talla) && !empty($precio) && !empty($stock)) {
        try {
            $query = "INSERT INTO productos (nombre, DESCRIPCION, talla, precio, stock) VALUES (:nombre, :descripcion, :talla, :precio, :stock)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':nombre' => $nombre,
                ':descripcion' => $descripcion,
                ':talla' => $talla,
                ':precio' => $precio,
                ':stock' => $stock
            ]);
            $mensaje = "¡Pijama registrada exitosamente!";
        } catch (Exception $e) {
            $error = "Error al registrar: " . $e->getMessage();
        }
    } else {
        $error = "Por favor completa todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Pijama - Axioma</title>
    <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
</head>
<body>
    <div class="container product-form-container">
        <div class="header-actions">
            <h1>Registrar Nueva Pijama</h1>
            <a href="index.php?url=dashboard" class="btn btn-primary">← Volver al Dashboard</a>
        </div>

        <div class="card product-form-card">
            <?php if (!empty($mensaje)): ?>
                <div class="alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="index.php?url=registrar_pijamas" method="POST">
                <div class="form-group">
                    <label for="nombre">Nombre o Modelo de la Pijama:</label>
                    <input type="text" id="nombre" name="nombre" required placeholder="Ej: Pijama de franela ositos">
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <input type="text" id="descripcion" name="descripcion" required placeholder="Ej: Pijama de franela con estampado de ositos">
                </div>

                <div class="form-group">
                    <label for="talla">Talla:</label>
                    <select id="talla" name="talla" required>
                        <option value="">Seleccione una talla</option>
                        <option value="S">S</option>
                        <option value="M">M</option>
                        <option value="L">L</option>
                        <option value="XL">XL</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="precio">Precio ($):</label>
                    <input type="number" step="0.01" id="precio" name="precio" required placeholder="0.00">
                </div>

                <div class="form-group">
                    <label for="stock">Cantidad en Stock:</label>
                    <input type="number" id="stock" name="stock" required placeholder="0">
                </div>

                <button type="submit" class="btn btn-primary full-width-button">Guardar Pijama</button>
            </form>
        </div>
    </div>
</body>
</html>