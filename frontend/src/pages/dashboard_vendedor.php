<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Vendedor') {
    header("Location: index.php?url=login");
    exit;
}

require_once __DIR__ . '/../../../backend/src/conexion.php';
require_once __DIR__ . '/../../../backend/src/config/security.php';

function ensureEstadoPedidoColumn(PDO $pdo): void
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM ventas LIKE 'estado_pedido'");
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE ventas ADD COLUMN estado_pedido VARCHAR(30) NOT NULL DEFAULT 'Pagado' AFTER ID_PAGO");
    }
}

$nombre_usuario = $_SESSION['usuario_name'] ?? 'Vendedor';

if (!isset($pdo)) {
    try {
        $pdo = (new Database())->getConnection();
    } catch (Exception $e) {
        $pdo = null;
    }
}

$resumen_ventas = [
    'cantidad_ventas' => 0,
    'unidades' => 0,
    'total_vendido' => 0,
    'total_efectivo' => 0
];

if ($pdo) {
    try {
        $stmtResumen = $pdo->prepare("SELECT COUNT(*) AS cantidad_ventas,
                                             COALESCE(SUM(v.cantidad), 0) AS unidades,
                                             COALESCE(SUM(v.total), 0) AS total_vendido,
                                             COALESCE(SUM(CASE WHEN LOWER(mp.nombre_metodo) LIKE '%efectivo%' THEN v.total ELSE 0 END), 0) AS total_efectivo
                                      FROM ventas v
                                      LEFT JOIN metodos_pago mp ON v.ID_PAGO = mp.ID_PAGO
                                      WHERE v.ID_VENDEDOR = :id_vendedor");
        $stmtResumen->execute([':id_vendedor' => (int) ($_SESSION['id_vendedor'] ?? 0)]);
        $resumen_ventas = $stmtResumen->fetch(PDO::FETCH_ASSOC) ?: $resumen_ventas;
    } catch (Exception $e) {
        // La tabla mantiene los valores cero si no se puede consultar el resumen.
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Vendedor | Pijamas Harv</title>
    <link rel="icon" href="./frontend/src/public/favicon/favicon.ico" type="image/x-icon">
    <!-- Incluyendo Bootstrap CSS para que los formularios y botones luzcan profesionales -->
    <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php require_once __DIR__ . '/../../../backend/src/controllers/header.php'; ?>
    <div class="container seller-dashboard-container">
        <?php if (isset($_GET['exito']) && $_GET['exito'] === 'venta_ok'): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <strong>Éxito:</strong> La venta se registró correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>
        
        <!-- Tarjeta de bienvenida y accesos rápidos -->
        <div class="card shadow-sm border-0 mb-4 seller-welcome-card">
            <p class="seller-welcome-text">
                Bienvenido, <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>
            </p>
            <div class="seller-quick-actions">
                <a href="index.php?url=ver_inventario" class="btn btn-primary quick-action quick-action--inventory">📋 Ver Inventario y Stock</a>
                <a href="index.php?url=clientes" class="btn btn-primary quick-action quick-action--clients">👥 Gestionar Clientes</a>
                <a href="index.php?url=registrar_pijamas" class="btn btn-primary quick-action quick-action--products">🛏️ Registrar Pijamas</a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h3 class="card-title mb-3 fw-bold text-dark">Resumen de ventas</h3>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ventas realizadas</th>
                                        <th>Unidades vendidas</th>
                                        <th>Total vendido</th>
                                        <th>Total en efectivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?php echo (int) $resumen_ventas['cantidad_ventas']; ?></td>
                                        <td><?php echo (int) $resumen_ventas['unidades']; ?></td>
                                        <td class="fw-bold text-success">$<?php echo number_format((float) $resumen_ventas['total_vendido'], 2); ?></td>
                                        <td class="fw-bold text-primary">$<?php echo number_format((float) $resumen_ventas['total_efectivo'], 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4 d-flex flex-column">
                        <h3 class="card-title mb-4 fw-bold text-dark">Registrar Nueva Venta</h3>

                        <form action="index.php?url=guardar_venta" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

                            <!-- Seleccionar Producto -->
                            <div class="mb-3">
                                <label for="id_producto" class="form-label fw-semibold">Producto:</label>
                                <select name="id_producto" id="id_producto" class="form-select" required>
                                    <option value="">Seleccione un producto...</option>
                                    <?php
                                    $queryProd = null;
                                    try {
                                        if ($pdo) {
                                            ensureEstadoPedidoColumn($pdo);
                                            $queryProd = $pdo->query("SELECT * FROM productos WHERE STOCK > 0");
                                        } else {
                                            echo '<option value="">Error de conexión a la BD</option>';
                                        }

                                        if ($pdo && $queryProd) {
                                            while ($prod = $queryProd->fetch(PDO::FETCH_ASSOC)) {
                                                $id = $prod['ID_PRODUCTO'] ?? $prod['id_producto'] ?? 1;
                                                $nombreProd = $prod['nombre'] ?? 'Sin nombre';
                                                $stockProd = $prod['STOCK'] ?? 0;
                                                $precioProd = $prod['PRECIO'] ?? 0;

                                                echo '<option value="' . $id . '">' . htmlspecialchars($nombreProd) . ' - Stock: ' . $stockProd . ' ($' . number_format($precioProd, 2) . ')</option>';
                                            }
                                        }
                                    } catch (Exception $e) {
                                        echo '<option value="">Error de conexión a la BD</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Seleccionar Cliente -->
                            <div class="mb-3">
                                <label for="id_cliente" class="form-label fw-semibold">Cliente:</label>
                                <select name="id_cliente" id="id_cliente" class="form-select" required>
                                    <?php
                                    $queryCli = null;
                                    try {
                                        if ($pdo) {
                                            $queryCli = $pdo->query("SELECT ID_CLIENTE, nombre FROM clientes");
                                            while ($cli = $queryCli->fetch(PDO::FETCH_ASSOC)) {
                                                echo '<option value="' . $cli['ID_CLIENTE'] . '">' . htmlspecialchars($cli['nombre']) . '</option>';
                                            }
                                        } else {
                                            echo '<option value="1">Cliente General</option>';
                                        }
                                    } catch (Exception $e) {
                                        echo '<option value="1">Cliente General</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Seleccionar Método de Pago -->
                            <div class="mb-3">
                                <label for="id_pago" class="form-label fw-semibold">Método de Pago:</label>
                                <select name="id_pago" id="id_pago" class="form-select" required>
                                    <?php
                                    $queryPago = null;
                                    try {
                                        if ($pdo) {
                                            $queryPago = $pdo->query("SELECT ID_PAGO, nombre_metodo FROM metodos_pago");
                                            while ($pago = $queryPago->fetch(PDO::FETCH_ASSOC)) {
                                                echo '<option value="' . $pago['ID_PAGO'] . '">' . htmlspecialchars($pago['nombre_metodo']) . '</option>';
                                            }
                                        } else {
                                            echo '<option value="1">Efectivo</option>';
                                        }
                                    } catch (Exception $e) {
                                        echo '<option value="1">Efectivo</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Cantidad -->
                            <div class="mb-3">
                                <label for="cantidad" class="form-label fw-semibold">Cantidad:</label>
                                <input type="number" name="cantidad" id="cantidad" class="form-control" value="1" min="1" required>
                            </div>

                            <!-- ID del vendedor actual en sesión -->
                            <input type="hidden" name="id_vendedor" value="<?php echo $_SESSION['id_vendedor'] ?? 1; ?>">

                            <!-- Botón de envío -->
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-success btn-lg">Completar Venta</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <!-- Historial de Ventas (columna derecha) -->
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4">
                        <h3 class="card-title mb-3 fw-bold text-dark">Historial de Ventas Realizadas</h3>
                        <div class="table-responsive seller-history-table">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th># Venta</th>
                                        <th>Producto</th>
                                        <th>Cliente</th>
                                        <th>Método de Pago</th>
                                        <th>Estado</th>
                                        <th>Cantidad</th>
                                        <th>Total</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (!isset($pdo)) {
                                        try {
                                            $pdo = (new Database())->getConnection();
                                        } catch (Exception $e) {
                                            $pdo = null;
                                        }
                                    }
                                    try {
                                        if ($pdo) {
                                            ensureEstadoPedidoColumn($pdo);
                                        }

                                        $sqlHistorial = "SELECT v.ID_VENTAS, p.nombre AS producto, c.nombre AS cliente,
                                                            mp.nombre_metodo AS pago, v.estado_pedido, v.cantidad, v.total, v.FECHA_VENTA
                                                     FROM ventas v
                                                     LEFT JOIN productos p ON v.ID_PRODUCTO = p.ID_PRODUCTO
                                                     LEFT JOIN clientes c ON v.ID_CLIENTE = c.ID_CLIENTE
                                                     LEFT JOIN metodos_pago mp ON v.ID_PAGO = mp.ID_PAGO
                                                     WHERE v.ID_VENDEDOR = :id_vendedor
                                                     ORDER BY v.FECHA_VENTA DESC";
                                        $queryHistorial = null;
                                        if ($pdo) {
                                            $queryHistorial = $pdo->prepare($sqlHistorial);
                                            $queryHistorial->execute([':id_vendedor' => (int) ($_SESSION['id_vendedor'] ?? 0)]);
                                        }

                                        if ($queryHistorial && $queryHistorial->rowCount() > 0) {
                                            while ($row = $queryHistorial->fetch(PDO::FETCH_ASSOC)) {
                                                echo '<tr>';
                                                echo '<td>#' . $row['ID_VENTAS'] . '</td>';
                                                echo '<td>' . htmlspecialchars($row['producto'] ?? 'Desconocido') . '</td>';
                                                echo '<td>' . htmlspecialchars($row['cliente'] ?? 'General') . '</td>';
                                                echo '<td><span class="badge bg-secondary">' . htmlspecialchars($row['pago'] ?? 'Efectivo') . '</span></td>';
                                                echo '<td><span class="badge bg-success">' . htmlspecialchars($row['estado_pedido'] ?? 'Pagado') . '</span></td>';
                                                echo '<td>' . $row['cantidad'] . '</td>';
                                                echo '<td class="fw-bold text-success">$' . number_format($row['total'], 2) . '</td>';
                                                echo '<td>' . $row['FECHA_VENTA'] . '</td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="8" class="text-center text-muted py-3">No hay ventas registradas todavía.</td></tr>';
                                        }
                                    } catch (Exception $e) {
                                        echo '<tr><td colspan="8" class="text-center text-danger">Error al cargar el historial de ventas: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</body>
<!-- Bootstrap JS (necesario para alert dismissible) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function cargarReporteVentas() {
    // Ruta relativa: la página se carga a través de index.php en la raíz del proyecto
    fetch('./backend/src/server.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'obtener_reporte_ventas' })
    })
    .then(res => res.json())
    .then(response => {
        if(response.success) {
            let tbody = document.getElementById('tabla-reporte');
            if (!tbody) return; 
            
            tbody.innerHTML = '';
            
            if(response.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="2" class="text-center">No hay ventas registradas aún.</td></tr>`;
                return;
            }

            response.data.forEach(item => {
                tbody.innerHTML += `<tr>
                    <td>${item.usuario}</td>
                    <td>$${parseFloat(item.vendido).toLocaleString()}</td>
                </tr>`;
            });
        } else {
            console.error(response.message);
        }
    })
    .catch(error => console.error("Error de conexión:", error));
}
</script>
</html>