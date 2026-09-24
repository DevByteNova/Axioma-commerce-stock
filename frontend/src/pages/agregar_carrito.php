<?php 
session_start();
if (isset($_POST['id_producto'])){
    $id = $_POST['id_producto'];
    $nombre = $_POST['nombre_producto'];
    $precio = $_POST['precio_producto'];
    $cantidad = 1;

    if (isset($_SESSION['carrito'][$id])) {
        $_SESSION['carrito'][$id]['cantidad'] += $cantidad;
    } else {
        $_SESSION['carrito'][$id] = array(
            'nombre' => $nombre,
            'precio' => $precio,
            'cantidad' => $cantidad
        );
    }
}

echo json_encode(["status" => "success", "message" => "Producto agregado al carrito"]);
?>