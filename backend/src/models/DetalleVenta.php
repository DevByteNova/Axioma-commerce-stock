<?php

class DetalleVenta
{
    public static function registrar(
        PDO $conexion,
        int $idVenta,
        int $idProducto,
        int $cantidad,
        float $precioUnitario
    ): void {
        if ($idVenta <= 0 || $idProducto <= 0 || $cantidad <= 0 || $precioUnitario < 0) {
            throw new InvalidArgumentException('Los datos del detalle de venta no son válidos.');
        }

        $subtotal = round($precioUnitario * $cantidad, 2);
        $stmt = $conexion->prepare(
            'INSERT INTO detalle_venta (ID_VENTAS, ID_PRODUCTO, cantidad, precio_unitario, subtotal_linea)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$idVenta, $idProducto, $cantidad, $precioUnitario, $subtotal]);
    }
}
