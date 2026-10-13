<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Seguridad: Si no es Admin, para afuera
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Administrador') {
    header("Location: index.php?url=login");
    exit;
}

$nombre_usuario = $_SESSION['usuario_name'] ?? 'Administrador';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Pijamas Harv</title>
    <link rel="icon" href="./frontend/src/public/favicon/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
</head>
<body>

    <div class="admin-dashboard-shell">
        <?php require_once __DIR__ . '/../../../backend/src/controllers/header.php'; ?>

        <main class="dashboard-main">
            <?php
            // Conexión única al principio del main
            require_once __DIR__ . '/../../../backend/src/conexion.php';
            $database = new Database();
            $db = $database->getConnection();

            $stock_critico = [];
            try {
                $stmtStockCritico = $db->prepare("SELECT ID_PRODUCTO, nombre, STOCK, talla FROM productos WHERE STOCK <= :limite ORDER BY STOCK ASC, nombre ASC");
                $stmtStockCritico->execute([':limite' => 5]);
                $stock_critico = $stmtStockCritico->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $stock_critico = [];
            }
            ?>

            <!-- BOTONES DE NAVEGACIÓN Y GESTIÓN -->
            <div class="admin-quick-actions">
                <a href="index.php?url=clientes" class="btn-accion bg-blue">
                    👥 Gestionar Clientes
                </a>

                <a href="index.php?url=ver_inventario" class="btn-accion bg-purple">
                    📋 Ver Inventario
                </a>

                <a href="index.php?url=usuarios" class="btn-accion bg-green">
                    🧑‍💼 Administrar Vendedores
                </a>
                <a href="index.php?url=registrar_pijamas" class="btn-accion bg-green">
                    🛏️ Registrar Pijamas
                </a>
            </div>

            <!-- CONTADORES DE ESTADÍSTICAS -->
            <div class="grid-stats">
              <?php
                $tables = ['clientes', 'productos', 'ventas'];
                foreach ($tables as $t) {
                    $stmt = $db->query("SELECT COUNT(*) FROM $t");
                    $count = $stmt->fetchColumn();
                    
                    if ($t == 'clientes') {
                        $link = 'href="index.php?url=clientes"';
                        $claseClick = 'clickable';
                    } elseif ($t == 'productos') {
                        $link = 'href="index.php?url=ver_inventario"';
                        $claseClick = 'clickable';
                    } else {
                        $link = '';
                        $claseClick = '';
                    }

                    echo "
                    <a $link class='stat-card $claseClick'>
                        <h2>Total $t</h2>
                        <p>$count</p>
                    </a>";
                }
                ?>
            </div>

            <div class="seccion-tablas">
                <h2 class="dashboard-section-title">Stock crítico</h2>
                <p class="dashboard-section-description">Productos con cinco unidades o menos disponibles:</p>
                <table class="tabla-reportes">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Talla</th>
                            <th>Stock disponible</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($stock_critico): ?>
                            <?php foreach ($stock_critico as $producto): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($producto['talla']); ?></td>
                                    <td><?php echo (int) $producto['STOCK']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="table-message">No hay productos en stock crítico.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- SECCIÓN: REPORTE DE VENTAS EN TIEMPO REAL -->
            <div class="seccion-tablas">
                <h2 class="dashboard-section-title">Reporte de Ventas por Vendedor</h2>
                <p class="dashboard-section-description">Rendimiento de ventas en tiempo real:</p>
                <div class="report-filters">
                    <label>Desde <input type="date" id="reporte-desde"></label>
                    <label>Hasta <input type="date" id="reporte-hasta"></label>
                    <button type="button" class="btn-accion bg-blue" onclick="cargarReporteVentas()">Filtrar</button>
                    <a class="btn-accion bg-green" id="exportar-reporte" href="./backend/src/server.php?action=exportar_reporte_ventas">Exportar CSV</a>
                </div>
                
                <table class="tabla-reportes">
                    <thead>
                        <tr>
                            <th>Vendedor</th>
                            <th>Total Vendido</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-reporte">
                        <tr>
                            <td colspan="2" class="table-message">Cargando reporte...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- LISTADO DE TABLAS DE LA BASE DE DATOS -->
            <div class="seccion-tablas">
                <h2 class="dashboard-section-title">Tablas en la Base de Datos</h2>
                <p class="dashboard-section-description">Lista de tablas disponibles en tu sistema:</p>
                
                <div class="database-table-list">
                    <ul class="lista-tablas">
                        <?php
                        try {
                            $query = "SHOW TABLES";
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);

                            if (count($tablas) > 0) {
                                foreach ($tablas as $tabla) {
                                    echo "<li>";
                                    echo "<span class='badge-tabla'>TABLA</span>";
                                    echo "<span class='database-table-name'>" . htmlspecialchars($tabla) . "</span>";
                                    echo "</li>";
                                }
                            } else {
                                echo "<li class='table-empty table-empty--plain'>No hay tablas encontradas.</li>";
                            }
                        } catch (Exception $e) {
                            echo "<li class='table-error table-empty--plain'>Error de conexión: " . $e->getMessage() . "</li>";
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </main>
    </div>

    <script>
        function cargarReporteVentas() {
            // Se usa './server.php' o la ruta absoluta según la raíz del servidor local
            fetch('./backend/src/server.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'obtener_reporte_ventas',
                    desde: document.getElementById('reporte-desde')?.value || '',
                    hasta: document.getElementById('reporte-hasta')?.value || ''
                })
            })
            .then(res => {
                const contentType = res.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    throw new TypeError("El servidor no devolvió un archivo JSON válido.");
                }
                return res.json();
            })
            .then(response => {
                let tbody = document.getElementById('tabla-reporte');
                if (!tbody) return;
                
                tbody.innerHTML = '';
                
                if(response.success) {
                    if(response.data.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="2" class="table-message">No hay ventas registradas aún.</td></tr>`;
                        return;
                    }

                    response.data.forEach(item => {
                        tbody.innerHTML += `<tr>
                            <td>${item.usuario}</td>
                            <td>$${parseFloat(item.vendido).toLocaleString()}</td>
                        </tr>`;
                    });
                } else {
                    tbody.innerHTML = `<tr><td colspan="2" class="table-error table-message">${response.message}</td></tr>`;
                }
            })
            .catch(error => {
                console.error("Error:", error);
                let tbody = document.getElementById('tabla-reporte');
                if (tbody) tbody.innerHTML = `<tr><td colspan="2" class="table-error table-message">Error al conectar con server.php</td></tr>`;
            });
        }

        function actualizarEnlaceExportacion() {
            const parametros = new URLSearchParams({action: 'exportar_reporte_ventas'});
            const desde = document.getElementById('reporte-desde')?.value || '';
            const hasta = document.getElementById('reporte-hasta')?.value || '';
            if (desde) parametros.set('desde', desde);
            if (hasta) parametros.set('hasta', hasta);
            document.getElementById('exportar-reporte').href = `./backend/src/server.php?${parametros}`;
        }

        // Se ejecuta al cargar el Dashboard
        window.addEventListener('DOMContentLoaded', () => {
            document.getElementById('reporte-desde')?.addEventListener('change', actualizarEnlaceExportacion);
            document.getElementById('reporte-hasta')?.addEventListener('change', actualizarEnlaceExportacion);
            actualizarEnlaceExportacion();
            cargarReporteVentas();
        });
    </script>
</body>
</html>