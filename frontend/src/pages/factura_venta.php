<?php
require_once __DIR__ . '/../../../backend/src/config/security.php';
configure_secure_session();

$rol = $_SESSION['usuario_rol'] ?? '';
if (!in_array($rol, ['Administrador', 'Vendedor'], true)) {
    header('Location: index.php?url=login');
    exit;
}

require_once __DIR__ . '/../../../backend/src/conexion.php';
$idVenta = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$idVenta || $idVenta < 1) {
    http_response_code(404);
    exit('No se encontró la venta solicitada.');
}

try {
    $pdo = (new Database())->getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    foreach ([
        'subtotal' => 'DECIMAL(10,2) NULL AFTER `cantidad`',
        'iva' => 'DECIMAL(10,2) NULL AFTER `subtotal`',
    ] as $column => $definition) {
        $checkColumn = $pdo->prepare('SHOW COLUMNS FROM ventas LIKE ?');
        $checkColumn->execute([$column]);
        if ($checkColumn->rowCount() === 0) {
            $pdo->exec("ALTER TABLE ventas ADD COLUMN `$column` $definition");
        }
    }

    $sql = "SELECT v.ID_VENTAS, v.ID_VENDEDOR, v.cantidad, v.subtotal, v.iva, v.total, v.FECHA_VENTA,
                   p.nombre AS producto, p.talla, p.PRECIO AS precio_actual,
                   CONCAT_WS(' ', c.NOMBRE, c.APELLIDOS) AS cliente,
                   c.DIRECCION AS direccion_cliente, c.TELEFONO AS telefono_cliente,
                   c.email AS email_cliente, mp.nombre_metodo AS metodo_pago,
                   ved.NOMBRE AS vendedor
            FROM ventas v
            LEFT JOIN productos p ON p.ID_PRODUCTO = v.ID_PRODUCTO
            LEFT JOIN clientes c ON c.ID_CLIENTE = v.ID_CLIENTE
            LEFT JOIN metodos_pago mp ON mp.ID_PAGO = v.ID_PAGO
            LEFT JOIN vendedor ved ON ved.ID_VENDEDOR = v.ID_VENDEDOR
            WHERE v.ID_VENTAS = ?";
    $params = [$idVenta];
    if ($rol === 'Vendedor') {
        $sql .= ' AND v.ID_VENDEDOR = ?';
        $params[] = (int) ($_SESSION['id_vendedor'] ?? 0);
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Error al cargar comprobante de venta: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo cargar el comprobante. Inténtalo de nuevo más tarde.');
}

if (!$venta) {
    http_response_code(404);
    exit('No se encontró la venta solicitada.');
}

$cantidad = (int) $venta['cantidad'];
$total = (float) $venta['total'];
$subtotal = $venta['subtotal'] !== null
    ? (float) $venta['subtotal']
    : round((float) $venta['precio_actual'] * $cantidad, 2);
$iva = $venta['iva'] !== null
    ? (float) $venta['iva']
    : max(0, round($total - $subtotal, 2));
$escape = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$money = static fn($value): string => '$' . number_format((float) $value, 2, ',', '.') . ' COP';
$dashboardRoute = 'dashboard';
$storeName = 'Pijamas Decare';
$storeCollection = 'Pijamas y ropa exterior';
require_once __DIR__ . '/../../../backend/src/config/marca.php';
$storeName = MARCA_TIENDA;
$storeCollection = MARCA_COLECCION;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de venta #<?php echo (int) $venta['ID_VENTAS']; ?> | Pijamas Decare</title>
    <link rel="icon" href="./frontend/src/public/favicon/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
    <style>
        :root { color-scheme: light; --invoice-ink: #30263a; --invoice-muted: #766d7f; --invoice-line: #eee8ef; --invoice-accent: #a84f70; --invoice-soft: #fbf6f8; }
        body { background: #f6f2f5; color: var(--invoice-ink); font-family: Arial, sans-serif; }
        .sale-invoice { max-width: 850px; margin: 36px auto; padding: 0; overflow: hidden; background: #fff; border: 1px solid var(--invoice-line); border-radius: 18px; box-shadow: 0 18px 50px rgba(48,38,58,.11); }
        .sale-invoice__masthead { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 30px 38px; background: linear-gradient(120deg, #fff8fb, #f4e9ef); border-bottom: 1px solid var(--invoice-line); }
        .sale-invoice__brand { display: flex; align-items: center; gap: 14px; }
        .sale-invoice__brand-mark { display: grid; width: 52px; height: 52px; place-items: center; border-radius: 16px; background: var(--invoice-accent); color: white; font-family: Georgia, serif; font-size: 1.5rem; font-weight: 700; }
        .sale-invoice__brand-name { margin: 0; color: var(--invoice-ink); font-family: Georgia, serif; font-size: 1.55rem; }
        .sale-invoice__brand-subtitle { margin: 3px 0 0; color: var(--invoice-muted); font-size: .9rem; }
        .sale-invoice__document-title { margin: 0 0 5px; color: var(--invoice-accent); font-size: .78rem; font-weight: 800; letter-spacing: .14em; text-align: right; text-transform: uppercase; }
        .sale-invoice__number { font-size: 1.05rem; font-weight: 700; text-align: right; }
        .sale-invoice__date { margin-top: 5px; color: var(--invoice-muted); font-size: .9rem; text-align: right; }
        .sale-invoice__content { padding: 30px 38px 36px; }
        .sale-invoice__section-title { margin: 0 0 14px; color: var(--invoice-ink); font-size: .88rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .sale-invoice__details { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 28px; margin-bottom: 30px; padding: 18px 20px; border: 1px solid var(--invoice-line); border-radius: 12px; background: #fff; }
        .sale-invoice__detail { min-width: 0; padding: 9px 0; border-bottom: 1px solid #f3eef3; }
        .sale-invoice__detail:nth-last-child(-n+2) { border-bottom: 0; }
        .sale-invoice__label { display: block; margin-bottom: 4px; color: var(--invoice-muted); font-size: .76rem; }
        .sale-invoice__value { overflow-wrap: anywhere; font-size: .94rem; font-weight: 600; }
        .sale-invoice__items { width: 100%; border-collapse: collapse; }
        .sale-invoice__items th { padding: 13px 14px; background: #f8f3f6; color: var(--invoice-muted); font-size: .75rem; letter-spacing: .07em; text-align: left; text-transform: uppercase; }
        .sale-invoice__items td { padding: 17px 14px; border-bottom: 1px solid var(--invoice-line); vertical-align: top; }
        .sale-invoice__items th:last-child, .sale-invoice__items td:last-child { text-align: right; }
        .sale-invoice__product-name { font-weight: 700; }
        .sale-invoice__product-size { display: block; margin-top: 4px; color: var(--invoice-muted); font-size: .82rem; }
        .sale-invoice__totals { width: min(100%, 360px); margin: 22px 0 0 auto; }
        .sale-invoice__totals div { display: flex; justify-content: space-between; gap: 16px; padding: 7px 0; color: var(--invoice-muted); }
        .sale-invoice__totals strong { color: var(--invoice-ink); }
        .sale-invoice__grand-total { margin-top: 9px; padding: 15px 16px !important; border: 0 !important; border-radius: 10px; background: var(--invoice-soft); color: var(--invoice-ink) !important; font-size: 1.08rem; font-weight: 700; }
        .sale-invoice__grand-total span:last-child { color: var(--invoice-accent); font-size: 1.25rem; }
        .sale-invoice__note { margin: 26px 0 0; padding-top: 18px; border-top: 1px solid var(--invoice-line); color: var(--invoice-muted); font-size: .85rem; text-align: center; }
        .sale-invoice__actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
        .sale-invoice__actions .sale-invoice__back { display: inline-flex; align-items: center; justify-content: center; padding: 10px 16px; border: 1px solid #64748b; border-radius: 6px; background: #fff; color: #334155; font-weight: 600; text-decoration: none; }
        .sale-invoice__actions .sale-invoice__back:hover { background: #f1f5f9; color: #0f172a; }
        @media (max-width: 600px) {
            .sale-invoice { margin: 16px; border-radius: 14px; }
            .sale-invoice__masthead { align-items: flex-start; flex-direction: column; padding: 24px; }
            .sale-invoice__document-title, .sale-invoice__number, .sale-invoice__date { text-align: left; }
            .sale-invoice__content { padding: 24px; }
            .sale-invoice__details { grid-template-columns: 1fr; }
            .sale-invoice__detail:nth-last-child(-n+2) { border-bottom: 1px solid #f3eef3; }
            .sale-invoice__detail:last-child { border-bottom: 0; }
            .sale-invoice__actions { flex-direction: column-reverse; }
            .sale-invoice__actions > * { width: 100%; text-align: center; }
        }
        @media print {
            @page { margin: 14mm; }
            body { background: #fff !important; color: #222; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .main-header, .sale-invoice__actions { display: none !important; }
            .sale-invoice { max-width: none; margin: 0; overflow: visible; border: 0; border-radius: 0; box-shadow: none; }
            .sale-invoice__masthead { padding: 20px 24px; }
            .sale-invoice__content { padding: 24px; }
            .sale-invoice__items, .sale-invoice__details, .sale-invoice__totals { break-inside: avoid; }
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../../../backend/src/controllers/header.php'; ?>
<main class="sale-invoice">
    <header class="sale-invoice__masthead">
        <div class="sale-invoice__brand">
            <span class="sale-invoice__brand-mark" aria-hidden="true"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V10"/><path d="M12 14c-3.5 0-6-2.5-6-6 3.5 0 6 2.5 6 6z"/><path d="M12 12c3.5 0 6-2.5 6-6-3.5 0-6 2.5-6 6z"/><path d="M12 9c-1.4-1.4-1.4-3.6 0-5 1.4 1.4 1.4 3.6 0 5z"/></svg></span>
            <div>
                <h1 class="sale-invoice__brand-name"><?php echo $escape($storeName); ?></h1>
                <p class="sale-invoice__brand-subtitle"><?php echo $escape($storeCollection); ?></p>
            </div>
        </div>
        <div>
            <p class="sale-invoice__document-title">Comprobante de venta</p>
            <div class="sale-invoice__number">Venta #<?php echo (int) $venta['ID_VENTAS']; ?></div>
            <div class="sale-invoice__date"><?php echo $escape($venta['FECHA_VENTA']); ?></div>
        </div>
    </header>

    <div class="sale-invoice__content">
        <section aria-labelledby="sale-customer-title">
            <h2 class="sale-invoice__section-title" id="sale-customer-title">Datos de la venta</h2>
            <div class="sale-invoice__details">
                <div class="sale-invoice__detail"><span class="sale-invoice__label">Cliente</span><span class="sale-invoice__value"><?php echo $escape($venta['cliente'] ?: 'Cliente General'); ?></span></div>
                <div class="sale-invoice__detail"><span class="sale-invoice__label">Vendedor</span><span class="sale-invoice__value"><?php echo $escape($venta['vendedor'] ?: '—'); ?></span></div>
                <div class="sale-invoice__detail"><span class="sale-invoice__label">Correo</span><span class="sale-invoice__value"><?php echo $escape($venta['email_cliente'] ?: '—'); ?></span></div>
                <div class="sale-invoice__detail"><span class="sale-invoice__label">Teléfono</span><span class="sale-invoice__value"><?php echo $escape($venta['telefono_cliente'] ?: '—'); ?></span></div>
                <div class="sale-invoice__detail"><span class="sale-invoice__label">Dirección</span><span class="sale-invoice__value"><?php echo $escape($venta['direccion_cliente'] ?: '—'); ?></span></div>
                <div class="sale-invoice__detail"><span class="sale-invoice__label">Método de pago</span><span class="sale-invoice__value"><?php echo $escape($venta['metodo_pago'] ?: '—'); ?></span></div>
            </div>
        </section>

        <section aria-labelledby="sale-items-title">
            <h2 class="sale-invoice__section-title" id="sale-items-title">Detalle</h2>
            <table class="sale-invoice__items">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio unitario</th>
                        <th>Importe</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="sale-invoice__product-name"><?php echo $escape($venta['producto'] ?: 'Producto'); ?></span><span class="sale-invoice__product-size">Talla: <?php echo $escape($venta['talla'] ?: '—'); ?></span></td>
                        <td><?php echo $cantidad; ?></td>
                        <td><?php echo $money($subtotal / max($cantidad, 1)); ?></td>
                        <td><?php echo $money($subtotal); ?></td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="sale-invoice__totals" aria-label="Totales de la venta">
            <div><span>Subtotal</span><strong><?php echo $money($subtotal); ?></strong></div>
            <div><span>IVA (19%)</span><strong><?php echo $money($iva); ?></strong></div>
            <div class="sale-invoice__grand-total"><span>Total pagado</span><span><?php echo $money($total); ?></span></div>
        </section>

        <p class="sale-invoice__note">Gracias por tu compra. Conserva este comprobante como soporte de tu venta.</p>

        <footer class="sale-invoice__actions">
            <a class="sale-invoice__back" href="index.php?url=<?php echo $dashboardRoute; ?>">Volver al dashboard</a>
            <button class="btn btn-primary" type="button" onclick="window.print()">Imprimir comprobante</button>
        </footer>
    </div>
</main>
</body>
</html>
