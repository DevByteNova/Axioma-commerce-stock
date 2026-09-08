<?php
// Conexión a la base de datos (ajusta tus credenciales)
$conexion = new mysqli("localhost", "root", "", "axioma_bd");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener el ID de la factura desde la URL (ej: ver_factura.php?id=1)
$id_factura = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Consulta SQL para obtener los detalles de la factura y la venta relacionada
$sql = "SELECT 
            f.ID_FACTURA,
            f.FECHA AS FECHA_FACTURA,
            CONCAT(c.NOMBRE, ' ', c.APELLIDOS) AS CLIENTE,
            c.DIRECCION AS DIRECCION_CLIENTE,
            c.TELEFONO,
            c.email AS EMAIL_CLIENTE,
            p.nombre AS PRODUCTO,
            p.talla,
            v.cantidad,
            p.PRECIO AS PRECIO_UNITARIO,
            v.total AS SUBTOTAL,
            mp.nombre_metodo AS METODO_PAGO,
            ved.NOMBRE AS VENDEDOR
        FROM facturas f
        INNER JOIN clientes c ON f.ID_CLIENTE = c.ID_CLIENTE
        INNER JOIN ventas v ON f.ID_CLIENTE = v.ID_CLIENTE
        INNER JOIN productos p ON v.ID_PRODUCTO = p.ID_PRODUCTO
        INNER JOIN metodos_pago mp ON v.ID_PAGO = mp.ID_PAGO
        INNER JOIN vendedor ved ON v.ID_VENDEDOR = ved.ID_VENDEDOR
        WHERE f.ID_FACTURA = $id_factura";

$resultado = $conexion->query($sql);
$factura = $resultado->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #<?php echo $id_factura; ?></title>
    <link rel="icon" href="./frontend/src/public/favicon/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../public/css/styles.css">
</head>
<body>

<?php require_once __DIR__ . '/../../../backend/src/controllers/header.php'; ?>

<div class="invoice-box">
    <?php if ($factura): ?>
        <div class="invoice-header">
            <div>
                <h2>Tienda Tecnología / Pijamas</h2>
                <p>Comprobante de Venta</p>
            </div>
            <div>
                <p><strong>Factura N°:</strong> #<?php echo $factura['ID_FACTURA']; ?></p>
                <p><strong>Fecha:</strong> <?php echo $factura['FECHA_FACTURA']; ?></p>
            </div>
        </div>

        <div class="details">
            <p><strong>Cliente:</strong> <?php echo $factura['CLIENTE']; ?></p>
            <p><strong>Dirección:</strong> <?php echo $factura['DIRECCION_CLIENTE']; ?></p>
            <p><strong>Teléfono:</strong> <?php echo $factura['TELEFONO']; ?></p>
            <p><strong>Vendedor que atendió:</strong> <?php echo $factura['VENDEDOR']; ?></p>
            <p><strong>Método de Pago:</strong> <?php echo $factura['METODO_PAGO']; ?></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Talla</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo $factura['PRODUCTO']; ?></td>
                    <td><?php echo $factura['talla']; ?></td>
                    <td><?php echo $factura['cantidad']; ?></td>
                    <td>$<?php echo number_format($factura['PRECIO_UNITARIO'], 2); ?></td>
                    <td>$<?php echo number_format($factura['SUBTOTAL'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            Total a Pagar: $<?php echo number_format($factura['SUBTOTAL'], 2); ?>
        </div>

        <button class="print-btn" onclick="window.print()">Imprimir / Guardar Factura</button>
    <?php else: ?>
        <p class="invoice-error">No se encontró la factura solicitada.</p>
    <?php endif; ?>
</div>

</body>
</html>