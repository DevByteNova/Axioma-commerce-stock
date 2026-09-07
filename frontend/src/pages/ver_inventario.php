<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
</head>
<body>
    <?php require_once __DIR__ . '/../components/header.php'; ?>
    <div class="auth-container inventory-container">
    <div class="form-card inventory-card">
        <h2 class="inventory-title">Inventario de Productos</h2>
        <table id="tablaInventario" class="inventory-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Precio</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody id="cuerpoInventario">
                <!-- Los datos se cargarán con JavaScript -->
            </tbody>
        </table>
        <a href="index.php?url=dashboard" class="inventory-back-link">Volver al Dashboard</a>
    </div>
</div>

<script>
async function cargarInventario() {
    try {
        const res = await fetch('./backend/src/server.php?action=listar_inventario');
        const productos = await res.json();
        
        const tbody = document.getElementById('cuerpoInventario');
        if (!Array.isArray(productos) || productos.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="table-message">No hay productos registrados.</td></tr>`;
            return;
        }

        tbody.innerHTML = productos.map(p => {
            const id = p.ID_PRODUCTO ?? p.id ?? '';
            const nombre = p.nombre ?? p.NOMBRE ?? 'Sin nombre';
            const descripcion = p.DESCRIPCION ?? p.descripcion ?? 'Sin descripción';
            const precio = p.PRECIO ?? p.precio ?? 0;
            const stock = p.STOCK ?? p.stock ?? 0;

            return `
                <tr>
                    <td>${id}</td>
                    <td>${nombre}</td>
                    <td>${descripcion}</td>
                    <td>$${parseFloat(precio).toFixed(2)}</td>
                    <td>${stock}</td>
                </tr>
            `;
        }).join('');
    } catch (error) {
        console.error("Error al cargar el inventario:", error);
    }
}

cargarInventario();
</script>
</body>
</html>