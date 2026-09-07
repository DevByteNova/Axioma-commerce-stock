<?php
session_start();

// Conexión directa para asegurar que procese la venta con éxito
try {
    $pdo = new PDO('mysql:host=localhost;dbname=axioma_bd;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Error de conexión a la BD: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_producto = intval($_POST['id_producto'] ?? 0);
    $cantidad = intval($_POST['cantidad'] ?? 0);
    $id_vendedor = intval($_POST['id_vendedor'] ?? 1);

    if ($id_producto <= 0 || $cantidad <= 0) {
        die("Datos de venta inválidos.");
    }

    try {
        // 1. Obtener precio y stock actual
        $stmt = $pdo->prepare("SELECT PRECIO, STOCK FROM productos WHERE ID_PRODUCTO = ?");
        $stmt->execute([$id_producto]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            die("El producto seleccionado no existe.");
        }

        if ($producto['STOCK'] < $cantidad) {
            die("Stock insuficiente para completar la venta.");
        }

        $total_venta = $producto['PRECIO'] * $cantidad;

        // 2. Iniciar transacción segura
        $pdo->beginTransaction();

        // Registrar la venta en la tabla ventas
        $sqlVenta = "INSERT INTO ventas (ID_PRODUCTO, ID_VENDEDOR, cantidad, total, FECHA_VENTA) VALUES (?, ?, ?, ?, NOW())";
        $stmtVenta = $pdo->prepare($sqlVenta);
        $stmtVenta->execute([$id_producto, $id_vendedor, $cantidad, $total_venta]);

        // Descontar el stock del producto
        $sqlStock = "UPDATE productos SET STOCK = STOCK - ? WHERE ID_PRODUCTO = ?";
        $stmtStock = $pdo->prepare($sqlStock);
        $stmtStock->execute([$cantidad, $id_producto]);

        $pdo->commit();

        // Redirigir de regreso al dashboard del vendedor
        header("Location: index.php?url=dashboard&exito=venta_ok");
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "Error al procesar la venta: " . $e->getMessage();
    }
} else {
    header("Location: index.php?url=dashboard");
    exit();
}
?>