<?php
session_start();
require_once __DIR__ . '/config/db.php';
// Asegúrate de incluir tu autoload de Composer si usas Dompdf
// require_once __DIR__ . '/vendor/autoload.php'; 
// use Dompdf\Dompdf;

// 1. Validar autenticación
if (!isset($_SESSION['usuario_rol'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Acceso denegado.");
}

$id_pedido = intval($_GET['id'] ?? 0);
$id_usuario_actual = $_SESSION['usuario_id'] ?? 0;
$rol_actual = $_SESSION['usuario_rol'];
$id_cliente_actual = $_SESSION['id_cliente'] ?? 0;

$database = new Database();
$db = $database->getConnection();

// 2. Consultar el pedido y verificar propiedad (Control de seguridad IDOR)
$sql = "SELECT p.*, c.NOMBRE, c.APELLIDOS, c.email, c.DIRECCION 
        FROM pedido p 
        JOIN clientes c ON p.ID_CLIENTE = c.ID_CLIENTE 
        WHERE p.ID_PEDIDO = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$id_pedido]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    exit("Factura no encontrada.");
}

// Blindaje de seguridad: Si es Cliente, solo puede ver SU propia factura
if ($rol_actual === 'Cliente' && intval($pedido['ID_CLIENTE']) !== intval($id_cliente_actual)) {
    header("HTTP/1.1 403 Forbidden");
    exit("No tienes permisos para visualizar este documento.");
}

// Consultar los productos del detalle de este pedido
$sqlDetalles = "SELECT dp.*, pr.DESCRIPCION 
                FROM detalle_pedido dp 
                JOIN productos pr ON dp.ID_PRODUCTO = pr.ID_PRODUCTO 
                WHERE dp.ID_PEDIDO = ?";
$stmtDet = $db->prepare($sqlDetalles);
$stmtDet->execute([$id_pedido]);
$detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

// 3. Renderizar HTML para el PDF
$html = '
<style>
    body { font-family: sans-serif; color: #333; }
    .header { text-align: center; margin-bottom: 20px; }
    .info { margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 14px; }
    th { background-color: #f4f6f9; }
    .total { text-align: right; font-weight: bold; margin-top: 15px; font-size: 16px; }
</style>
<div class="header">
    <h2>Pijamas Harv - Factura de Venta</h2>
    <p>Comprobante Oficial de Transacción</p>
</div>
<div class="info">
    <strong>Factura N°:</strong> ' . $pedido['ID_PEDIDO'] . '<br>
    <strong>Fecha:</strong> ' . $pedido['FECHA'] . '<br>
    <strong>Cliente:</strong> ' . htmlspecialchars($pedido['NOMBRE'] . ' ' . $pedido['APELLIDOS']) . '<br>
    <strong>Método de Pago:</strong> ' . htmlspecialchars($pedido['METODO_PAGO']) . '
</div>
<table>
    <tr>
        <th>Producto</th>
        <th>Cantidad</th>
        <th>Precio U.</th>
        <th>Subtotal</th>
    </tr>';

foreach ($detalles as $det) {
    $html .= '<tr>
        <td>' . htmlspecialchars($det['DESCRIPCION']) . '</td>
        <td>' . $det['CANTIDAD'] . '</td>
        <td>$' . number_format($det['PRECIO_UNITARIO'], 2) . '</td>
        <td>$' . number_format($det['SUBTOTAL'], 2) . '</td>
    </tr>';
}

$html .= '</table>
<div class="total">
    Total Pagado: $' . number_format($pedido['TOTAL'], 2) . '
</div>';

 
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("factura_" . $id_pedido . ".pdf", ["Attachment" => true]);

// Salida temporal en HTML puro para pruebas si aún no instalas Dompdf:
echo $html;