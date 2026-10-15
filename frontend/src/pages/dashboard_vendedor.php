<?php
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
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

$pdo = null;
if (!isset($pdo)) {
    try {
        $pdo = (new Database())->getConnection();
    } catch (Exception $e) {
        $pdo = null;
    }
}

$nombre_usuario = $_SESSION['usuario_name'] ?? 'Vendedor';
$id_vendedor_actual = (int) ($_SESSION['id_vendedor'] ?? 0);
$mensaje_estado_pedido = '';
$estados_pedido = ['Pendiente', 'Pagado', 'Enviado', 'Entregado', 'Cancelado'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'actualizar_estado_venta') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $mensaje_estado_pedido = 'La sesión del formulario expiró. Inténtalo de nuevo.';
    } else {
        $id_venta = filter_var($_POST['id_venta'] ?? null, FILTER_VALIDATE_INT);
        $nuevo_estado = trim((string) ($_POST['estado'] ?? ''));

        if (!$id_venta || !in_array($nuevo_estado, $estados_pedido, true)) {
            $mensaje_estado_pedido = 'El estado del pedido no es válido.';
        } elseif ($pdo) {
            $stmt_owner = $pdo->prepare('SELECT ID_VENDEDOR FROM ventas WHERE ID_VENTAS = :id_venta');
            $stmt_owner->execute([':id_venta' => $id_venta]);
            $vendedor_venta = (int) ($stmt_owner->fetchColumn() ?: 0);

            if ($vendedor_venta !== $id_vendedor_actual) {
                $mensaje_estado_pedido = 'No puedes modificar un pedido que no te pertenece.';
            } else {
                $stmt_update = $pdo->prepare('UPDATE ventas SET estado_pedido = :estado WHERE ID_VENTAS = :id_venta');
                $stmt_update->execute([':estado' => $nuevo_estado, ':id_venta' => $id_venta]);
                $mensaje_estado_pedido = 'Pedido actualizado a "' . htmlspecialchars($nuevo_estado, ENT_QUOTES, 'UTF-8') . '".';
            }
        } else {
            $mensaje_estado_pedido = 'No se pudo actualizar el estado porque no hay conexión a la base de datos.';
        }
    }
}

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
    <title>Dashboard Vendedor | Pijamas Decare</title>
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
        <?php if (isset($_GET['error']) && $_GET['error'] !== ''): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <?php echo htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>
        <?php if ($mensaje_estado_pedido !== ''): ?>
            <div class="alert alert-info alert-dismissible fade show mt-3" role="alert">
                <strong>Estado del pedido:</strong> <?php echo $mensaje_estado_pedido; ?>
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

                                                echo '<option value="' . (int) $id . '" data-precio="' . htmlspecialchars((string) $precioProd, ENT_QUOTES, 'UTF-8') . '" data-stock="' . (int) $stockProd . '">' . htmlspecialchars($nombreProd) . ' - Stock: ' . (int) $stockProd . ' ($' . number_format((float) $precioProd, 2) . ')</option>';
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
                                            $queryCli = $pdo->query("SELECT ID_CLIENTE, CONCAT(NOMBRE, ' ', APELLIDOS) AS nombre FROM clientes");
                                            while ($cli = $queryCli->fetch(PDO::FETCH_ASSOC)) {
                                                echo '<option value="' . (int) $cli['ID_CLIENTE'] . '">' . htmlspecialchars($cli['nombre']) . '</option>';
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
                                    <option value="">Seleccione un método de pago...</option>
                                    <?php
                                    $queryPago = null;
                                    try {
                                        if ($pdo) {
                                            $queryPago = $pdo->query("SELECT ID_PAGO, nombre_metodo FROM metodos_pago WHERE estado = 1 ORDER BY ID_PAGO");
                                            while ($pago = $queryPago->fetch(PDO::FETCH_ASSOC)) {
                                                echo '<option value="' . (int) $pago['ID_PAGO'] . '" data-nombre="' . htmlspecialchars(strtolower($pago['nombre_metodo']), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($pago['nombre_metodo']) . '</option>';
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

                            <div class="card border-primary-subtle bg-light mb-3" aria-live="polite">
                                <div class="card-body">
                                    <h5 class="card-title fw-bold">Resumen del pago</h5>
                                    <div class="d-flex justify-content-between"><span>Subtotal</span><span id="ventaSubtotal">$0,00</span></div>
                                    <div class="d-flex justify-content-between"><span>IVA (19%)</span><span id="ventaIva">$0,00</span></div>
                                    <hr>
                                    <div class="d-flex justify-content-between fw-bold fs-5"><span>Total</span><span id="ventaTotal">$0,00</span></div>
                                </div>
                            </div>

                            <div id="recuadroPagoTarjeta" class="card border-primary mb-3" hidden>
                                <div class="card-body">
                                    <h5 class="card-title fw-bold text-primary">Pago con tarjeta</h5>
                                    <p class="mb-2">Procesa el cobro en la terminal externa y registra aquí la referencia solo después de que sea aprobado.</p>
                                    <div class="mb-2 fw-bold">Total a cobrar: <span id="totalTarjeta">$0,00</span></div>
                                    <label for="referencia_pago" class="form-label">Referencia de autorización</label>
                                    <input type="text" name="referencia_pago" id="referencia_pago" class="form-control" maxlength="80" autocomplete="off" placeholder="Código entregado por la terminal" disabled>
                                    <small class="form-text">No ingreses números de tarjeta ni CVV aquí.</small>
                                </div>
                            </div>

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
                                        <th>Acciones</th>
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

                                        $sqlHistorial = "SELECT v.ID_VENTAS, p.nombre AS producto, CONCAT_WS(' ', c.NOMBRE, c.APELLIDOS) AS cliente,
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
                                                $estadoActual = (string) ($row['estado_pedido'] ?? 'Pagado');
                                                $estadoActual = $estadoActual !== '' ? $estadoActual : 'Pagado';
                                                $badgeClass = match ($estadoActual) {
                                                    'Pendiente' => 'bg-warning text-dark',
                                                    'Pagado' => 'bg-primary',
                                                    'Enviado' => 'bg-info text-dark',
                                                    'Entregado' => 'bg-success',
                                                    'Cancelado' => 'bg-danger',
                                                    default => 'bg-secondary',
                                                };

                                                echo '<tr>';
                                                echo '<td>#' . $row['ID_VENTAS'] . '</td>';
                                                echo '<td>' . htmlspecialchars($row['producto'] ?? 'Desconocido') . '</td>';
                                                echo '<td>' . htmlspecialchars($row['cliente'] ?? 'General') . '</td>';
                                                echo '<td><span class="badge bg-secondary">' . htmlspecialchars($row['pago'] ?? 'Efectivo') . '</span></td>';
                                                echo '<td><span class="badge ' . $badgeClass . '">' . htmlspecialchars($estadoActual) . '</span></td>';
                                                echo '<td>' . $row['cantidad'] . '</td>';
                                                echo '<td class="fw-bold text-success">$' . number_format($row['total'], 2) . '</td>';
                                                echo '<td>' . $row['FECHA_VENTA'] . '</td>';
                                                echo '<td>';
                                                echo '<form method="POST" class="d-flex align-items-center gap-2 flex-wrap">';
                                                echo '<input type="hidden" name="action" value="actualizar_estado_venta">';
                                                echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
                                                echo '<input type="hidden" name="id_venta" value="' . (int) $row['ID_VENTAS'] . '">';
                                                echo '<select name="estado" class="form-select form-select-sm" aria-label="Estado del pedido">';
                                                foreach ($estados_pedido as $estado) {
                                                    $selected = $estado === $estadoActual ? 'selected' : '';
                                                    echo '<option value="' . htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') . '" ' . $selected . '>' . htmlspecialchars($estado) . '</option>';
                                                }
                                                echo '</select>';
                                                echo '<button type="submit" class="btn btn-sm btn-primary">Guardar</button>';
                                                echo '</form>';
                                                echo '<a class="btn btn-sm btn-outline-success mt-2" target="_blank" rel="noopener" href="index.php?url=factura_venta&amp;id=' . (int) $row['ID_VENTAS'] . '">Ver / imprimir factura</a>';
                                                echo '</td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="9" class="text-center text-muted py-3">No hay ventas registradas todavía.</td></tr>';
                                        }
                                    } catch (Exception $e) {
                                        echo '<tr><td colspan="9" class="text-center text-danger">Error al cargar el historial de ventas: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
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
<script>
(() => {
    const producto = document.getElementById('id_producto');
    const cantidad = document.getElementById('cantidad');
    const metodoPago = document.getElementById('id_pago');
    const resumen = {
        subtotal: document.getElementById('ventaSubtotal'),
        iva: document.getElementById('ventaIva'),
        total: document.getElementById('ventaTotal'),
        tarjeta: document.getElementById('totalTarjeta')
    };
    const recuadroTarjeta = document.getElementById('recuadroPagoTarjeta');
    const referenciaPago = document.getElementById('referencia_pago');
    const formatoMoneda = new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    const tarifaIva = 0.19;

    function actualizarResumen() {
        const opcion = producto.selectedOptions[0];
        const precio = Number(opcion?.dataset.precio || 0);
        const unidades = Math.max(0, Number.parseInt(cantidad.value, 10) || 0);
        const subtotal = Math.round((precio * unidades + Number.EPSILON) * 100) / 100;
        const iva = Math.round((subtotal * tarifaIva + Number.EPSILON) * 100) / 100;
        const total = subtotal + iva;

        resumen.subtotal.textContent = formatoMoneda.format(subtotal);
        resumen.iva.textContent = formatoMoneda.format(iva);
        resumen.total.textContent = formatoMoneda.format(total);
        resumen.tarjeta.textContent = formatoMoneda.format(total);

        if (opcion?.dataset.stock) {
            cantidad.max = opcion.dataset.stock;
        } else {
            cantidad.removeAttribute('max');
        }
    }

    function actualizarMetodoPago() {
        const nombreMetodo = metodoPago.selectedOptions[0]?.dataset.nombre || '';
        const requiereTarjeta = nombreMetodo.includes('tarjeta');
        recuadroTarjeta.hidden = !requiereTarjeta;
        referenciaPago.disabled = !requiereTarjeta;
        referenciaPago.required = requiereTarjeta;
    }

    producto.addEventListener('change', actualizarResumen);
    cantidad.addEventListener('input', actualizarResumen);
    metodoPago.addEventListener('change', actualizarMetodoPago);
    actualizarResumen();
    actualizarMetodoPago();
})();
</script>
</html>