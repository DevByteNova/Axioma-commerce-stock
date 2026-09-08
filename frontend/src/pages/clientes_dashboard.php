<?php
// Parche integrado solicitado por el usuario, preservando fixes previos.
// HACK: Esta es una solución rápida para que el dashboard de cliente funcione
// correctamente en el código actual; debería refactorizarse a futuro.
// Asegurar que la sesión está activa y validar permisos de cliente
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'Cliente') {
    header("Location: index.php?url=login");
    exit();
}

// Cargar la conexión mediante la clase Database de tu proyecto
// Nota: ruta corregida desde frontend/src/pages hacia backend/src/conexion.php
require_once __DIR__ . '/../../../backend/src/conexion.php';
$db = new Database();
$pdo = $db->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Compatibilidad con claves de sesión antiguas y nuevas
$nombre_cliente = $_SESSION['usuario_name'] ?? $_SESSION['nombre_usuario'] ?? 'Cliente';
$id_cliente = $_SESSION['id_cliente'] ?? $_SESSION['usuario_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Panel - Tienda Axioma</title>
    <link rel="icon" href="./frontend/src/public/favicon/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php require_once __DIR__ . '/../../../backend/src/controllers/header.php'; ?>
    <div class="container py-5 client-dashboard-container">
        <h2 class="fw-bold text-dark mb-4">Bienvenido, <?php echo htmlspecialchars($nombre_cliente); ?> 🛒</h2>

        <!-- Formulario de Compra -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <h4 class="card-title mb-3 fw-bold text-dark">Catálogo de Productos Disponibles</h4>
                
                <form action="index.php?url=procesar_compra_cliente" method="POST">
                    <div class="mb-3">
                        <label for="id_producto" class="form-label fw-semibold">Selecciona un Producto:</label>
                        <select name="id_producto" id="id_producto" class="form-select" required>
                            <option value="">Elige...</option>
                            <?php
                            try {
                                // Traemos los productos. Si quieres probar sin el filtro de stock de momento, puedes quitar el WHERE
                                $stmtProd = $pdo->query("SELECT * FROM productos WHERE STOCK > 0");
                                while ($p = $stmtProd->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . $p['ID_PRODUCTO'] . '">' . htmlspecialchars($p['nombre'] ?? $p['NOMBRE']) . ' - $' . number_format($p['precio'] ?? $p['PRECIO'], 2) . ' (Stock: ' . ($p['stock'] ?? $p['STOCK']) . ')</option>';
                                }
                            } catch (Exception $e) {
                                echo '<option value="">Error al cargar productos</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="cantidad" class="form-label fw-semibold">Cantidad:</label>
                        <input type="number" name="cantidad" id="cantidad" class="form-control" value="1" min="1" required>
                    </div>

                    <div class="mb-3">
                        <label for="id_pago" class="form-label fw-semibold">Método de Pago:</label>
                        <select name="id_pago" id="id_pago" class="form-select" required>
                            <?php
                            try {
                                $stmtPago = $pdo->query("SELECT * FROM metodos_pago");
                                while ($mp = $stmtPago->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . $mp['ID_PAGO'] . '">' . htmlspecialchars($mp['nombre_metodo']) . '</option>';
                                }
                            } catch (Exception $e) {
                                echo '<option value="1">Efectivo</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success px-4">Comprar Ahora</button>
                </form>
            </div>
        </div>

        <!-- Historial de Compras del Cliente -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h4 class="card-title mb-3 fw-bold text-dark">Mis Compras Realizadas</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th># Venta</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Total</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmtHistorial = $pdo->prepare("SELECT v.ID_VENTAS, p.nombre AS producto, v.cantidad, v.total, v.FECHA_VENTA 
                                                                FROM ventas v
                                                                LEFT JOIN productos p ON v.ID_PRODUCTO = p.ID_PRODUCTO
                                                                WHERE v.ID_CLIENTE = ?
                                                                ORDER BY v.FECHA_VENTA DESC");
                                $stmtHistorial->execute([$id_cliente]);
                                
                                if ($stmtHistorial->rowCount() > 0) {
                                    while ($compra = $stmtHistorial->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<tr>';
                                        echo '<td>#' . $compra['ID_VENTAS'] . '</td>';
                                        echo '<td>' . htmlspecialchars($compra['producto'] ?? 'Producto') . '</td>';
                                        echo '<td>' . $compra['cantidad'] . '</td>';
                                        echo '<td class="fw-bold text-success">$' . number_format($compra['total'], 2) . '</td>';
                                        echo '<td>' . $compra['FECHA_VENTA'] . '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="5" class="text-center text-muted py-3">Aún no has realizado ninguna compra.</td></tr>';
                                }
                            } catch (Exception $e) {
                                echo '<tr><td colspan="5" class="text-center text-danger">Error al cargar el historial.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Mis Facturas de Compra -->
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4">
                <h4 class="card-title mb-3 fw-bold text-dark">Mis Facturas de Compra</h4>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>N° Factura</th>
                                <th>Fecha</th>
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
                                            echo '<td>#' . htmlspecialchars($f['ID_FACTURA']) . '</td>';
                                            echo '<td>' . htmlspecialchars($f['FECHA']) . '</td>';
                                            echo '<td><a class="btn btn-sm btn-outline-primary" target="_blank" href="frontend/src/pages/ver_factura.php?id=' . urlencode($f['ID_FACTURA']) . '">Ver Factura</a></td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="3" class="text-center text-muted">No hay facturas disponibles.</td></tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="3" class="text-center text-warning">Cliente no identificado.</td></tr>';
                                }
                            } catch (Exception $e) {
                                echo '<tr><td colspan="3" class="text-center text-danger">Error al cargar facturas.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</body>
</html>