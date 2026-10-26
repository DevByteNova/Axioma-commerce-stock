<?php
/**
 * Dashboard del Cliente - Pijamas Decare
 * Diseño Original Restaurado + Carrito en Menú Superior
 * (Mejora visual: tipografía, espaciados, cards, tablas y formularios)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rolActualCliente = strtolower(trim((string) ($_SESSION['usuario_rol'] ?? '')));
if (!isset($_SESSION['usuario_id']) || $rolActualCliente !== 'cliente') {
    header("Location: index.php?url=login");
    exit();
}

// Cargar la conexión mediante la clase Database
require_once __DIR__ . '/../../../backend/src/conexion.php';
try {
    $db = new Database();
    $pdo = $db->getConnection();
    // Corrección exacta del atributo y valor de PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Error crítico de conexión a la base de datos.");
}

// Compatibilidad de variables de sesión
$nombre_cliente = $_SESSION['usuario_name'] ?? $_SESSION['nombre_usuario'] ?? 'Cliente';
$id_cliente = $_SESSION['id_cliente'] ?? $_SESSION['usuario_id'] ?? null;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Panel | Pijamas Decare</title>
    <link rel="icon" href="./frontend/src/public/favicon/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="./frontend/src/public/favicon/favicon.ico" sizes="any">
    <link rel="icon" href="./frontend/src/public/favicon/favicon-32x32.png" type="image/png" sizes="32x32">
    <link rel="icon" href="./frontend/src/public/favicon/favicon-16x16.png" type="image/png" sizes="16x16">
    <link rel="apple-touch-icon" href="./frontend/src/public/favicon/apple-touch-icon.png">
    <link rel="manifest" href="./frontend/src/public/favicon/site.webmanifest">
    <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --axioma-primary: #f0787a;
            --axioma-primary-dark: #c9505a;
            --axioma-success: #22a366;
            --axioma-bg: #f5f6fb;
            --axioma-card-border: #eef0f7;
            --axioma-text-muted: #7a7f95;
            --axioma-radius-lg: 1.15rem;
            --axioma-radius-md: 0.85rem;
            --axioma-shadow: 0 10px 30px rgba(31, 32, 65, 0.06);
            --axioma-shadow-hover: 0 14px 34px rgba(31, 32, 65, 0.10);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--axioma-bg);
            color: #2a2c3e;
        }

        h2, h4, .card-title {
            font-family: 'Poppins', 'Inter', sans-serif;
        }

        .client-dashboard-container {
            max-width: 1100px;
        }

        /* Cabecera de bienvenida */
        .client-dashboard-container h2.fw-bold {
            letter-spacing: -0.02em;
            font-size: 1.85rem;
        }

        .client-dashboard-container p.text-muted {
            font-size: 0.98rem;
        }

        /* Cards */
        .card {
            border-radius: var(--axioma-radius-lg) !important;
            border: 1px solid var(--axioma-card-border) !important;
            box-shadow: var(--axioma-shadow);
            transition: box-shadow 0.25s ease, transform 0.25s ease;
        }

        .card:hover {
            box-shadow: var(--axioma-shadow-hover);
        }

        .card-body.p-4 {
            padding: 2rem !important;
        }

        .card-title {
            font-weight: 600;
            letter-spacing: -0.01em;
        }

        .card-title.text-primary {
            color: var(--axioma-primary) !important;
        }

        /* Formulario de compra */
        .form-label.fw-semibold {
            font-size: 0.88rem;
            font-weight: 600;
            color: #4b4d63;
            margin-bottom: 0.45rem;
        }

        .form-select,
        .form-control {
            border-radius: var(--axioma-radius-md);
            border: 1px solid #e2e4ef;
            padding: 0.65rem 0.9rem;
            font-size: 0.95rem;
            background-color: #fbfbfe;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-select:focus,
        .form-control:focus {
            border-color: var(--axioma-primary);
            box-shadow: 0 0 0 0.2rem rgba(240, 120, 122, 0.2);
            background-color: #fff;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--axioma-success), #1c8c58);
            border: none;
            border-radius: var(--axioma-radius-md);
            font-size: 0.95rem;
            letter-spacing: 0.01em;
            box-shadow: 0 6px 16px rgba(34, 163, 102, 0.25);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(34, 163, 102, 0.32);
        }

        .btn-outline-primary {
            border-radius: var(--axioma-radius-md);
            border-color: var(--axioma-primary);
            color: var(--axioma-primary);
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-outline-primary:hover {
            background-color: var(--axioma-primary);
            border-color: var(--axioma-primary);
            transform: translateY(-1px);
        }

        .hero-banner {
            background: linear-gradient(135deg, #fde8e6 0%, #fff6f5 60%, #ffffff 100%);
            border: 1px solid rgba(240, 120, 122, 0.18);
            border-radius: 1.6rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 12px 28px rgba(31, 32, 65, 0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
        }

        .hero-copy {
            max-width: 60%;
        }

        .eyebrow {
            display: inline-block;
            background: rgba(240, 120, 122, 0.14);
            color: var(--axioma-primary-dark);
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .hero-copy h1 {
            font-family: 'Poppins', 'Inter', sans-serif;
            font-size: clamp(2rem, 3vw, 3rem);
            line-height: 1.08;
            font-weight: 700;
            margin: 0.9rem 0 0.7rem;
            color: #1f2432;
            letter-spacing: -0.04em;
        }

        .hero-copy p {
            color: #5d6377;
            font-size: 1rem;
            margin-bottom: 1.25rem;
        }

        .hero-promo {
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
            min-width: 260px;
        }

        .promo-card {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 1.1rem;
            padding: 1rem 1.1rem;
            box-shadow: 0 12px 28px rgba(31, 32, 65, 0.05);
        }

        .promo-card span {
            display: block;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #7b7f94;
            margin-bottom: 0.35rem;
        }

        .promo-card strong {
            color: #1d2332;
            font-size: 1.05rem;
        }

        .promo-card--alt {
            background: rgba(34, 163, 102, 0.08);
            border-color: rgba(34, 163, 102, 0.12);
        }

        .product-card {
            border-radius: 1.2rem !important;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 36px rgba(31, 32, 65, 0.12) !important;
        }

        .product-image-wrap {
            background: linear-gradient(135deg, #f8f8ff, #fdf5f7);
            padding: 0.75rem;
        }

        .product-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-radius: 1rem;
            display: block;
        }

        .product-title {
            font-size: 1rem;
            font-weight: 700;
            color: #2a2c3e;
            margin-bottom: 0.55rem;
            min-height: 48px;
        }

        .product-meta {
            margin-bottom: 0.75rem;
        }

        .stock-badge {
            display: inline-block;
            background: #eef2ff;
            color: #4b4d63;
            border-radius: 999px;
            padding: 0.28rem 0.7rem;
            font-size: 0.76rem;
            font-weight: 600;
        }

        .product-price {
            color: var(--axioma-primary-dark);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .btn-product {
            border-radius: 0.8rem;
            font-weight: 600;
            padding: 0.72rem 0.9rem;
        }

        /* Tablas */
        .table-responsive {
            border-radius: var(--axioma-radius-md);
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
            font-size: 0.93rem;
        }

        .table thead.table-dark th {
            background-color: #2a2c3e;
            border: none;
            font-weight: 600;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.9rem 1rem;
        }

        .table thead.table-light th {
            background-color: #f3f3fa;
            border: none;
            font-weight: 600;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #4b4d63;
            padding: 0.9rem 1rem;
        }

        .table tbody tr {
            transition: background-color 0.15s ease;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #fafafe;
        }

        .table-hover tbody tr:hover {
            background-color: #f1f0fb;
        }

        .table td, .table th {
            padding: 0.85rem 1rem;
            vertical-align: middle;
            border-color: #f0f0f6;
        }

        .badge.bg-secondary {
            background-color: #e5e6f5 !important;
            color: #4b4d63 !important;
            font-weight: 600;
            padding: 0.4em 0.75em;
            border-radius: 0.5rem;
        }

        .text-success.fw-bold {
            color: var(--axioma-success) !important;
        }

        /* Estado vacío */
        .table td.text-center.text-muted {
            font-size: 0.92rem;
            padding: 2.25rem 1rem;
        }

        /* Separación general entre bloques */
        .card.mb-4 {
            margin-bottom: 1.75rem !important;
        }

        @media (max-width: 576px) {
            .card-body.p-4 {
                padding: 1.25rem !important;
            }
        }
    </style>
</head>
<body class="bg-light">


    <?php 
    // Incluir cabecera adicional si el sistema lo requiere
    $headerPath = __DIR__ . '/../../../backend/src/controllers/header.php';
    if (file_exists($headerPath)) {
        require_once $headerPath;
    }
    ?>

    <div class="container py-5 client-dashboard-container">
        <?php if (($_GET['compra'] ?? '') === 'exitoso' && isset($_GET['factura'])): ?>
            <div class="alert alert-success d-flex justify-content-between align-items-center gap-3" role="alert">
                <span><i class="fa-solid fa-circle-check me-2"></i>Compra realizada correctamente.</span>
                <a class="btn btn-success" target="_blank" href="frontend/src/pages/ver_factura.php?id=<?php echo (int) $_GET['factura']; ?>">
                    <i class="fa-solid fa-file-invoice me-1"></i> Ver factura
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Cabecera de Bienvenida -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">Bienvenido, <?php echo htmlspecialchars($nombre_cliente); ?> 🛒</h2>
                <p class="text-muted mb-0">Revisa tus compras, consulta el estado de tus pedidos y descarga tus facturas.</p>
            </div>
        </div>

        <nav class="d-flex flex-wrap gap-2 mb-4" aria-label="Secciones de mi cuenta">
            <a class="btn btn-marca px-3 py-2" href="#mis-compras">
                <i class="fa-solid fa-bag-shopping me-1" aria-hidden="true"></i> Mis compras
            </a>
        </nav>

        <div class="hero-banner">
            <div class="hero-copy">
                <span class="eyebrow">Colección nueva</span>
                <h1>Pijamas que te acompañan cada noche</h1>
                <p>Descubre prendas suaves, modernas y cómodas para dormir con estilo en cada momento del día.</p>
                <a href="#mis-compras" class="btn btn-marca px-3 py-2">Ver mis compras</a>
            </div>

            <div class="hero-promo">
                <div class="promo-card">
                    <span>Hasta 30% OFF</span>
                    <strong>Pijamas de algodón</strong>
                </div>
                <div class="promo-card promo-card--alt">
                    <span>Envío gratis</span>
                    <strong>En compras desde $100.000</strong>
                </div>
            </div>
        </div>

        <!-- Historial de Compras del Cliente (Diseño Original) -->
        <div id="mis-compras" class="card shadow-sm border-0 mb-4 rounded-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <span class="text-uppercase small fw-bold text-secondary">Mi actividad</span>
                        <h4 class="card-title mb-0 fw-bold text-dark">Mis compras</h4>
                    </div>
                    <span class="badge rounded-pill bg-light text-dark border">Historial personal</span>
                </div>
                <?php
                $comprasCliente = [];
                try {
                    $stmtHistorial = $pdo->prepare("SELECT v.ID_VENTAS, p.nombre AS producto, v.cantidad, v.total,
                                                            v.FECHA_VENTA, v.estado_pedido
                                                     FROM ventas v
                                                     LEFT JOIN productos p ON v.ID_PRODUCTO = p.ID_PRODUCTO
                                                     WHERE v.ID_CLIENTE = ?
                                                     ORDER BY v.FECHA_VENTA DESC");
                    $stmtHistorial->execute([$id_cliente]);
                    $comprasCliente = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $comprasCliente = [];
                }
                $totalCompras = count($comprasCliente);
                $totalUnidades = array_sum(array_map(static fn($compra) => (int) $compra['cantidad'], $comprasCliente));
                $totalGastado = array_sum(array_map(static fn($compra) => (float) $compra['total'], $comprasCliente));
                ?>
                <div class="row g-3 mb-4">
                    <div class="col-sm-4">
                        <div class="p-3 rounded-3 bg-light h-100">
                            <span class="small text-secondary d-block">Compras realizadas</span>
                            <strong class="fs-4"><?php echo $totalCompras; ?></strong>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3 rounded-3 bg-light h-100">
                            <span class="small text-secondary d-block">Unidades adquiridas</span>
                            <strong class="fs-4"><?php echo $totalUnidades; ?></strong>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3 rounded-3 bg-light h-100">
                            <span class="small text-secondary d-block">Total gastado</span>
                            <strong class="fs-4 text-success">$<?php echo number_format($totalGastado, 2, ',', '.'); ?></strong>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th># Venta</th>
                                <th>Producto</th>
                                <th>Estado</th>
                                <th>Cantidad</th>
                                <th>Total</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($comprasCliente): ?>
                                <?php foreach ($comprasCliente as $compra): ?>
                                    <tr>
                                        <td class="fw-semibold">#<?php echo (int) $compra['ID_VENTAS']; ?></td>
                                        <td><?php echo htmlspecialchars($compra['producto'] ?? 'Producto no disponible'); ?></td>
                                        <td><span class="badge rounded-pill bg-primary"><?php echo htmlspecialchars($compra['estado_pedido'] ?? 'Pagado'); ?></span></td>
                                        <td><span class="badge bg-secondary"><?php echo (int) $compra['cantidad']; ?></span></td>
                                        <td class="fw-bold text-success">$<?php echo number_format((float) $compra['total'], 2, ',', '.'); ?></td>
                                        <td class="text-muted small"><?php echo htmlspecialchars($compra['FECHA_VENTA']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">Aún no has realizado ninguna compra en el sistema.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Mis Facturas de Compra (Diseño Original) -->
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <h4 class="card-title mb-3 fw-bold text-dark">📄 Mis Facturas de Compra</h4>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>N° Factura</th>
                                <th>Fecha de Emisión</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                if ($id_cliente) {
                                    $stmtFact = $pdo->prepare("SELECT ID_FACTURA, FECHA FROM facturas WHERE ID_CLIENTE = ? ORDER BY FECHA DESC");
                                    $stmtFact->execute([$id_cliente]);
                                    $facturas = $stmtFact->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if (count($facturas) > 0) {
                                        foreach ($facturas as $f) {
                                            echo '<tr>';
                                            echo '<td class="fw-semibold">#' . htmlspecialchars($f['ID_FACTURA']) . '</td>';
                                            echo '<td class="text-muted">' . htmlspecialchars($f['FECHA']) . '</td>';
                                            echo '<td><a class="btn btn-sm btn-outline-primary px-3" target="_blank" href="frontend/src/pages/ver_factura.php?id=' . urlencode($f['ID_FACTURA']) . '">Ver factura</a> <a class="btn btn-sm btn-outline-secondary px-3" target="_blank" href="frontend/src/pages/generar_factura.php?id=' . urlencode($f['ID_FACTURA']) . '">Descargar PDF</a></td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="3" class="text-center text-muted py-3">No hay facturas electrónicas disponibles todavía.</td></tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="3" class="text-center text-warning py-3">Cliente no identificado correctamente en la sesión.</td></tr>';
                                }
                            } catch (Exception $e) {
                                echo '<tr><td colspan="3" class="text-center text-danger py-3">Error al conectar con la base de datos de facturas.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>