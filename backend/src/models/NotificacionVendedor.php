<?php
// backend/src/models/NotificacionVendedor.php

class NotificacionVendedor
{
    public static function asegurarTabla(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS notificaciones_vendedor (
                ID_NOTIFICACION BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                ID_VENDEDOR INT NOT NULL,
                ID_FACTURA INT NOT NULL,
                FECHA_CREACION TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                LEIDA_EN TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (ID_NOTIFICACION),
                UNIQUE KEY uq_notificacion_vendedor_factura (ID_VENDEDOR, ID_FACTURA),
                KEY idx_notificacion_vendedor_leida (ID_VENDEDOR, LEIDA_EN, FECHA_CREACION)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public static function registrarNuevoPedido(PDO $pdo, int $idVendedor, int $idFactura): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO notificaciones_vendedor (ID_VENDEDOR, ID_FACTURA) VALUES (?, ?)'
        );
        $stmt->execute([$idVendedor, $idFactura]);
    }

    public static function obtenerNoLeidas(PDO $pdo, int $idVendedor): array
    {
        $stmt = $pdo->prepare(
            "SELECT n.ID_NOTIFICACION, n.ID_FACTURA, n.FECHA_CREACION,
                    CONCAT_WS(' ', c.NOMBRE, c.APELLIDOS) AS cliente,
                    COUNT(v.ID_VENTAS) AS lineas_pedido,
                    COALESCE(SUM(v.total), 0) AS total_pedido
             FROM notificaciones_vendedor n
             INNER JOIN facturas f ON f.ID_FACTURA = n.ID_FACTURA
             LEFT JOIN clientes c ON c.ID_CLIENTE = f.ID_CLIENTE
             LEFT JOIN ventas v
                    ON v.ID_FACTURA = n.ID_FACTURA
                   AND v.ID_VENDEDOR = n.ID_VENDEDOR
             WHERE n.ID_VENDEDOR = ? AND n.LEIDA_EN IS NULL
             GROUP BY n.ID_NOTIFICACION, n.ID_FACTURA, n.FECHA_CREACION,
                      c.NOMBRE, c.APELLIDOS
             ORDER BY n.FECHA_CREACION DESC, n.ID_NOTIFICACION DESC"
        );
        $stmt->execute([$idVendedor]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function marcarLeidas(PDO $pdo, int $idVendedor): void
    {
        $stmt = $pdo->prepare(
            'UPDATE notificaciones_vendedor
             SET LEIDA_EN = CURRENT_TIMESTAMP
             WHERE ID_VENDEDOR = ? AND LEIDA_EN IS NULL'
        );
        $stmt->execute([$idVendedor]);
    }
}
