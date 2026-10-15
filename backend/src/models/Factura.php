<?php
// backend/src/models/Factura.php
// Consulta de una factura con sus ventas y control de acceso por rol.

class Factura
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /** Encabezado de la factura con los datos del cliente, o null si no existe. */
    public function encabezado(int $idFactura): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT f.ID_FACTURA, f.FECHA, f.ID_CLIENTE, f.DIRECCION_ENTREGA,
                    CONCAT(c.NOMBRE, ' ', c.APELLIDOS) AS CLIENTE, c.DIRECCION, c.TELEFONO, c.email
             FROM facturas f
             INNER JOIN clientes c ON c.ID_CLIENTE = f.ID_CLIENTE
             WHERE f.ID_FACTURA = ?"
        );
        $stmt->execute([$idFactura]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    /** Ventas (líneas) que pertenecen a la factura. */
    public function detalle(int $idFactura): array
    {
        $stmt = $this->conn->prepare(
            "SELECT p.nombre AS PRODUCTO, p.talla, v.cantidad, v.total,
                    ROUND(v.total / NULLIF(v.cantidad, 0), 2) AS PRECIO_UNITARIO,
                    mp.nombre_metodo AS METODO_PAGO, ve.NOMBRE AS VENDEDOR
             FROM ventas v
             INNER JOIN productos p ON p.ID_PRODUCTO = v.ID_PRODUCTO
             LEFT JOIN metodos_pago mp ON mp.ID_PAGO = v.ID_PAGO
             LEFT JOIN vendedor ve ON ve.ID_VENDEDOR = v.ID_VENDEDOR
             WHERE v.ID_FACTURA = ?
             ORDER BY v.ID_VENTAS"
        );
        $stmt->execute([$idFactura]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * El administrador y los vendedores pueden ver cualquier factura;
     * un cliente solo las suyas.
     */
    public static function puedeVer(array $encabezado): bool
    {
        $rol = ucfirst(strtolower(trim((string) ($_SESSION['usuario_rol'] ?? ''))));
        if (in_array($rol, ['Administrador', 'Vendedor'], true)) {
            return true;
        }
        return $rol === 'Cliente' && (int) ($_SESSION['id_cliente'] ?? 0) === (int) $encabezado['ID_CLIENTE'];
    }
}
