<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lógica para actualizar cantidades si envían el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_cantidades'])) {
    if (isset($_POST['cantidad']) && is_array($_POST['cantidad'])) {
        foreach ($_POST['cantidad'] as $id => $cantidad) {
            $cantidad = intval($cantidad);
            if ($cantidad > 0) {
                if (isset($_SESSION['carrito'][$id])) {
                    // Si guardas el carrito como array con más datos (precio, nombre, cantidad)
                    if (is_array($_SESSION['carrito'][$id])) {
                        $_SESSION['carrito'][$id]['cantidad'] = $cantidad;
                    } else {
                        // Por si acaso en otro lado lo guardabas solo como número
                        $_SESSION['carrito'][$id] = $cantidad;
                    }
                }
            } else {
                // Si ponen 0 o negativo, lo borramos
                unset($_SESSION['carrito'][$id]);
            }
        }
    }
    header("Location: index.php?url=ver_carrito");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito de Compras | Pijamas Harv</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

    <!-- Menú de navegación superior -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand text-muted fs-6" href="index.php?url=dashboard_cliente">Panel de Cliente</a>
            <div class="ms-auto">
                <a href="index.php?url=dashboard_cliente" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> Volver al Catálogo
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <h2 class="mb-4 fw-bold">Tu Carrito de Compras 🛒</h2>

        <?php if (isset($_SESSION['carrito']) && !empty($_SESSION['carrito'])): ?>
            <!-- Formulario apuntando al enrutador -->
            <form action="index.php?url=ver_carrito" method="POST">
                <div class="table-responsive">
                    <table class="table table-bordered bg-white shadow-sm align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Producto</th>
                                <th>Precio Unitario</th>
                                <th style="width: 150px;">Cantidad</th>
                                <th>Subtotal</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_general = 0;
                            foreach ($_SESSION['carrito'] as $id => $item): 
                                // Soporte robusto por si $item es un array o un número simple
                                $nombre_prod = is_array($item) ? ($item['nombre'] ?? 'Producto') : 'Producto';
                                $precio_prod = is_array($item) ? ($item['precio'] ?? 0) : 0;
                                $cant_prod   = is_array($item) ? ($item['cantidad'] ?? 1) : $item;

                                $subtotal = $precio_prod * $cant_prod;
                                $total_general += $subtotal;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($nombre_prod); ?></td>
                                    <td>$<?php echo number_format($precio_prod, 2); ?></td>
                                    <td>
                                        <input type="number" name="cantidad[<?php echo $id; ?>]" value="<?php echo $cant_prod; ?>" min="1" class="form-control text-center">
                                    </td>
                                    <td class="fw-bold text-success">$<?php echo number_format($subtotal, 2); ?></td>
                                    <td>
                                        <!-- Enlace de eliminar pasando por el enrutador -->
                                        <a href="index.php?url=eliminar_del_carrito&id=<?php echo $id; ?>" class="btn btn-danger btn-sm">
                                            <i class="fa-solid fa-trash"></i> Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <button type="submit" name="actualizar_cantidades" class="btn btn-secondary">Actualizar Carrito</button>
                    <h4>Total: <span class="text-success">$<?php echo number_format($total_general, 2); ?></span></h4>
                </div>
            </form>

            <div class="d-flex justify-content-end mt-3 gap-2">
                <a href="index.php?url=dashboard_cliente" class="btn btn-outline-primary">Seguir Comprando</a>
                <a href="index.php?url=procesar_compra" class="btn btn-success">Finalizar Compra</a>
            </div>

        <?php else: ?>
            <div class="alert alert-info text-center py-5 shadow-sm border-0 rounded-4">
                <i class="fa-solid fa-cart-shopping fa-3x text-muted mb-3"></i>
                <h4>Tu carrito está vacío.</h4>
                <p class="text-muted mb-4">Aún no has agregado pijamas o productos para comprar.</p>
                <a href="index.php?url=dashboard_cliente" class="btn btn-primary px-4 py-2">Ver Catálogo</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>