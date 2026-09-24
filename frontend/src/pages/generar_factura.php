<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../backend/src/conexion.php';

use Dompdf\Dompdf;

$id_factura = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_factura) {
    exit('Factura no válida.');
}

$db = new Database();
$pdo = $db->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare("SELECT f.ID_FACTURA, f.FECHA, CONCAT(c.NOMBRE, ' ', c.APELLIDOS) AS CLIENTE,
                              p.nombre AS PRODUCTO, v.cantidad, p.PRECIO, v.total
                       FROM facturas f
                       INNER JOIN clientes c ON c.ID_CLIENTE = f.ID_CLIENTE
                       INNER JOIN ventas v ON v.ID_CLIENTE = f.ID_CLIENTE
                       INNER JOIN productos p ON p.ID_PRODUCTO = v.ID_PRODUCTO
                       WHERE f.ID_FACTURA = ?");
$stmt->execute([$id_factura]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$items) {
    exit('No se encontró la factura solicitada.');
}

$factura = $items[0];
$total_factura = array_sum(array_column($items, 'total'));
$filas = '';
foreach ($items as $item) {
    $filas .= '<tr><td>' . htmlspecialchars($item['PRODUCTO']) . '</td><td>' . (int) $item['cantidad'] . '</td><td>$' . number_format((float) $item['PRECIO'], 2) . '</td><td>$' . number_format((float) $item['total'], 2) . '</td></tr>';
}

$dompdf = new Dompdf();

$html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #2c3e50; margin: 0; font-size: 24px; }
        .header p { color: #7f8c8d; font-size: 14px; margin: 5px 0; }
        .info-table, .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 5px; font-size: 14px; }
        .items-table th { background-color: #34495e; color: white; padding: 10px; font-size: 14px; text-align: left; }
        .items-table td { padding: 10px; border-bottom: 1px solid #dddddd; font-size: 13px; }
        .total-section { text-align: right; font-size: 16px; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Pijamas Harv</h1>
        <p>Comprobante de Factura Electrónica</p>
        <p>Fecha: ' . htmlspecialchars($factura['FECHA']) . '</p>
    </div>

    <table class="info-table">
        <tr>
            <td><strong>Cliente:</strong> ' . htmlspecialchars($factura['CLIENTE']) . '</td>
            <td><strong>Nro Factura:</strong> #' . (int) $factura['ID_FACTURA'] . '</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            ' . $filas . '
        </tbody>
    </table>

    <div class="total-section">
        Total a Pagar: $' . number_format($total_factura, 2) . ' COP
    </div>

</body>
</html>
';

$dompdf->loadHtml($html);
// Se configura el tamaño del papel y la orientación
$dompdf->setPaper('A4', 'portrait');
// Renderiza el HTML como PDF
$dompdf->render();

$dompdf->stream("factura_pijamas_harv.pdf", ["Attachment" => true]);
?>