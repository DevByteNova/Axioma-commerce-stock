<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = $_SESSION['usuario_id']; // El ID del cliente logueado
    $id_producto = intval($_POST['id_producto']);
    $cantidad = intval($_POST['cantidad']);
    $id_pago = intval($_POST['id_pago']);
    $id_vendedor = 1; // O un vendedor por defecto para compras web automatizadas

    try {
        // 1. Obtener el precio y stock del producto
        $stmtProd = $pdo->prepare("SELECT precio, stock FROM productos WHERE ID_PRODUCTO = ?");
        $stmtProd->execute([$id_producto]);
        $producto = $stmtProd->fetch(PDO::FETCH_ASSOC);

        if (!$producto || $producto['stock'] < $cantidad) {
            die("Stock insuficiente o producto no válido.");
        }

        $total = $producto['precio'] * $cantidad;

        // 2. Insertar la venta vinculando al cliente autenticado
        $sqlVenta = "INSERT INTO ventas (ID_PRODUCTO, ID_VENDEDOR, ID_CLIENTE, ID_PAGO, cantidad, total, FECHA_VENTA) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmtVenta = $pdo->prepare($sqlVenta);
        $stmtVenta->execute([$id_producto, $id_vendedor, $id_cliente, $id_pago, $cantidad, $total]);

        // 3. Descontar el stock
        $nuevoStock = $producto['stock'] - $cantidad;
        $stmtStock = $pdo->prepare("UPDATE productos SET stock = ? WHERE ID_PRODUCTO = ?");
        $stmtStock->execute([$nuevoStock, $id_producto]);

        // Redirigir al panel del cliente con éxito
        header("Location: cliente_dashboard.php?exito=compra_ok");
        exit();

    } catch (Exception $e) {
        die("Error al procesar la compra: " . $e->getMessage());
    }
}