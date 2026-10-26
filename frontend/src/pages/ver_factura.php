<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../backend/src/conexion.php';
require_once __DIR__ . '/../../../backend/src/config/marca.php';
require_once __DIR__ . '/../../../backend/src/models/Factura.php';

$id_factura = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

$db = new Database();
$pdo = $db->getConnection();
$modelo = new Factura($pdo);
$factura = $id_factura > 0 ? $modelo->encabezado($id_factura) : null;

if ($factura && !Factura::puedeVer($factura)) {
    http_response_code(403);
    $factura = null;
    $mensajeError = 'No tienes permiso para ver esta factura.';
}
$items = $factura ? $modelo->detalle($id_factura) : [];
$total = array_sum(array_map('floatval', array_column($items, 'total')));
$metodos = implode(', ', array_unique(array_filter(array_column($items, 'METODO_PAGO'))));
$vendedores = implode(', ', array_unique(array_filter(array_column($items, 'VENDEDOR'))));
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura #<?php echo (int) $id_factura; ?> | <?php echo $e(MARCA_TIENDA); ?></title>
    <link rel="icon" href="./frontend/src/public/favicon/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="./frontend/src/public/favicon/favicon.ico" sizes="any">
    <link rel="icon" href="./frontend/src/public/favicon/favicon-32x32.png" type="image/png" sizes="32x32">
    <link rel="icon" href="./frontend/src/public/favicon/favicon-16x16.png" type="image/png" sizes="16x16">
    <link rel="apple-touch-icon" href="./frontend/src/public/favicon/apple-touch-icon.png">
    <link rel="manifest" href="./frontend/src/public/favicon/site.webmanifest">
    <link rel="stylesheet" href="../public/css/styles.css">
</head>
<body>

<?php require_once __DIR__ . '/../../../backend/src/controllers/header.php'; ?>

<div class="invoice-box">
    <?php if ($factura): ?>
        <div class="invoice-header">
            <div>
                <h2><?php echo $e(MARCA_TIENDA); ?></h2>
                <p>Comprobante de venta</p>
            </div>
            <div>
                <p><strong>Factura N°:</strong> #<?php echo (int) $factura['ID_FACTURA']; ?></p>
                <p><strong>Fecha:</strong> <?php echo $e($factura['FECHA']); ?></p>
            </div>
        </div>

        <div class="details">
            <p><strong>Cliente:</strong> <?php echo $e($factura['CLIENTE']); ?></p>
            <p><strong>Dirección de entrega:</strong> <?php echo $e($factura['DIRECCION_ENTREGA'] ?: $factura['DIRECCION']); ?></p>
            <p><strong>Teléfono:</strong> <?php echo $e($factura['TELEFONO']); ?></p>
            <p><strong>Atendido por:</strong> <?php echo $e($vendedores ?: 'Tienda en línea'); ?></p>
            <p><strong>Método de pago:</strong> <?php echo $e($metodos ?: 'No registrado'); ?></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Talla</th>
                    <th>Cantidad</th>
                    <th>Precio unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo $e($item['PRODUCTO']); ?></td>
                        <td><?php echo $e($item['talla']); ?></td>
                        <td><?php echo (int) $item['cantidad']; ?></td>
                        <td><?php echo precio_cop($item['PRECIO_UNITARIO']); ?></td>
                        <td><?php echo precio_cop($item['total']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$items): ?>
                    <tr><td colspan="5">Esta factura no tiene productos asociados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="total-section">
            Total a pagar: <?php echo precio_cop($total); ?>
        </div>

        <button class="print-btn" onclick="window.print()">Imprimir / Guardar factura</button>
        <a href="generar_factura.php?id=<?php echo (int) $factura['ID_FACTURA']; ?>" target="_blank" class="invoice-download">
            Descargar factura en PDF
        </a>
    <?php else: ?>
        <p class="invoice-error"><?php echo $e($mensajeError ?? 'No se encontró la factura solicitada.'); ?></p>
    <?php endif; ?>
</div>

</body>
</html>
