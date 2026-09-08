<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Vendedor') {
    header("Location: index.php?url=login");
    exit;
}

$nombre_usuario = $_SESSION['usuario_name'] ?? 'Vendedor';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Vendedor - Axioma</title>
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
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4 d-flex flex-column">
                        <h3 class="card-title mb-4 fw-bold text-dark">Registrar Nueva Venta</h3>

                        <form action="index.php?url=guardar_venta" method="POST">

                            <!-- Seleccionar Producto -->
                            <div class="mb-3">
                                <label for="id_producto" class="form-label fw-semibold">Producto:</label>
                                <select name="id_producto" id="id_producto" class="form-select" required>
                                    <option value="">Seleccione un producto...</option>
                                    <?php
                                    try {
                                        if (!isset($pdo)) {
                                            $pdo = new PDO('mysql:host=localhost;dbname=axioma_bd;charset=utf8mb4', 'root', '');
                                            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                        }

                                        $queryProd = $pdo->query("SELECT * FROM productos WHERE STOCK > 0");
                                        while ($prod = $queryProd->fetch(PDO::FETCH_ASSOC)) {
                                            $id = $prod['ID_PRODUCTO'] ?? $prod['id_producto'] ?? 1;
                                            $nombreProd = $prod['nombre'] ?? 'Sin nombre';
                                            $stockProd = $prod['STOCK'] ?? 0;
                                            $precioProd = $prod['PRECIO'] ?? 0;

                                            echo '<option value="' . $id . '">' . htmlspecialchars($nombreProd) . ' - Stock: ' . $stockProd . ' ($' . number_format($precioProd, 2) . ')</option>';
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
                                    try {
                                        $queryCli = $pdo->query("SELECT ID_CLIENTE, nombre FROM clientes");
                                        while ($cli = $queryCli->fetch(PDO::FETCH_ASSOC)) {
                                            echo '<option value="' . $cli['ID_CLIENTE'] . '">' . htmlspecialchars($cli['nombre']) . '</option>';
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
                                    try {
                                        $queryPago = $pdo->query("SELECT ID_PAGO, nombre_metodo FROM metodos_pago");
                                        while ($pago = $queryPago->fetch(PDO::FETCH_ASSOC)) {
                                            echo '<option value="' . $pago['ID_PAGO'] . '">' . htmlspecialchars($pago['nombre_metodo']) . '</option>';
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
                                        <th>Cantidad</th>
                                        <th>Total</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (!isset($pdo)) {
                                        try {
                                            $pdo = new PDO('mysql:host=localhost;dbname=axioma_bd;charset=utf8mb4', 'root', '');
                                            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                        } catch (Exception $e) {
                                            $pdo = null;
                                        }
                                    }
                                    try {
                                        $sqlHistorial = "SELECT v.ID_VENTAS, p.nombre AS producto, c.nombre AS cliente, 
                                                            mp.nombre_metodo AS pago, v.cantidad, v.total, v.FECHA_VENTA 
                                                     FROM ventas v
                                                     LEFT JOIN productos p ON v.ID_PRODUCTO = p.ID_PRODUCTO
                                                     LEFT JOIN clientes c ON v.ID_CLIENTE = c.ID_CLIENTE
                                                     LEFT JOIN metodos_pago mp ON v.ID_PAGO = mp.ID_PAGO
                                                     ORDER BY v.FECHA_VENTA DESC";
                                        $queryHistorial = $pdo ? $pdo->query($sqlHistorial) : null;

                                        if ($queryHistorial && $queryHistorial->rowCount() > 0) {
                                            while ($row = $queryHistorial->fetch(PDO::FETCH_ASSOC)) {
                                                echo '<tr>';
                                                echo '<td>#' . $row['ID_VENTAS'] . '</td>';
                                                echo '<td>' . htmlspecialchars($row['producto'] ?? 'Desconocido') . '</td>';
                                                echo '<td>' . htmlspecialchars($row['cliente'] ?? 'General') . '</td>';
                                                echo '<td><span class="badge bg-secondary">' . htmlspecialchars($row['pago'] ?? 'Efectivo') . '</span></td>';
                                                echo '<td>' . $row['cantidad'] . '</td>';
                                                echo '<td class="fw-bold text-success">$' . number_format($row['total'], 2) . '</td>';
                                                echo '<td>' . $row['FECHA_VENTA'] . '</td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="7" class="text-center text-muted py-3">No hay ventas registradas todavía.</td></tr>';
                                        }
                                    } catch (Exception $e) {
                                        echo '<tr><td colspan="7" class="text-center text-danger">Error al cargar el historial de ventas.</td></tr>';
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
    // Apuntamos directamente con la ruta completa desde la raíz de localhost
    fetch('/Axioma-Commerce-Store-main/server.php', {
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