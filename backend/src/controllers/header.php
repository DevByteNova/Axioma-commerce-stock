<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['usuario_rol'])) {
    $_SESSION['usuario_rol'] = ucfirst(strtolower(trim($_SESSION['usuario_rol'])));
}

$rolActual = $_SESSION['usuario_rol'] ?? 'Invitado';
$routeActual = $_GET['url'] ?? 'dashboard';
$rutaInicio = $rolActual === 'Cliente' ? 'cliente_dashboard' : 'dashboard';

$projectRoot = str_replace('\\', '/', dirname(dirname(dirname(__DIR__))));
$documentRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
$baseUrl = '';

if ($documentRoot !== '' && strpos($projectRoot, $documentRoot) === 0) {
    $baseUrl = '/' . ltrim(substr($projectRoot, strlen($documentRoot)), '/');
} else {
    $baseUrl = '/' . ltrim(basename($projectRoot), '/');
}

$menuItems = [
    ['route' => 'dashboard', 'label' => 'Dashboard', 'roles' => ['Administrador', 'Vendedor'], 'url' => $baseUrl . '/index.php?url=dashboard'],
    ['route' => 'clientes', 'label' => 'Clientes', 'roles' => ['Administrador', 'Vendedor'], 'url' => $baseUrl . '/index.php?url=clientes'],
    ['route' => 'ver_inventario', 'label' => 'Inventario', 'roles' => ['Administrador', 'Vendedor'], 'url' => $baseUrl . '/index.php?url=ver_inventario'],
    ['route' => 'usuarios', 'label' => 'Usuarios', 'roles' => ['Administrador'], 'url' => $baseUrl . '/index.php?url=usuarios'],
    ['route' => 'registrar_pijamas', 'label' => 'Registrar Pijamas', 'roles' => ['Administrador', 'Vendedor'], 'url' => $baseUrl . '/index.php?url=registrar_pijamas'],
    ['route' => 'registro_vendedor', 'label' => 'Registrar Vendedor', 'roles' => ['Administrador'], 'url' => $baseUrl . '/index.php?url=registro_vendedor'],
    ['route' => 'cliente_dashboard', 'label' => 'Mi panel', 'roles' => ['Cliente'], 'url' => $baseUrl . '/index.php?url=cliente_dashboard'],
];
?>

<header class="main-header">
    <div class="header-container">
        <a href="<?php echo htmlspecialchars($baseUrl . '/index.php?url=' . $rutaInicio, ENT_QUOTES, 'UTF-8'); ?>" class="header-brand" aria-label="Ir al dashboard principal">
            <span class="header-brand__mark">A</span>
            <span>Axioma</span>
        </a>

        <?php if (in_array($rolActual, ['Administrador', 'Vendedor', 'Cliente'], true)): ?>
            <nav class="nav-menu" aria-label="Navegación principal">
                <?php foreach ($menuItems as $item): ?>
                    <?php if (in_array($rolActual, $item['roles'], true)): ?>
                        <a href="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>"
                           class="nav-menu__link <?php echo ($routeActual === $item['route']) ? 'nav-menu__link--active' : ''; ?>">
                            <?php echo htmlspecialchars($item['label']); ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if ($rolActual === 'Cliente'): ?>
                    <?php
                    $cantidadCarrito = 0;
                    $totalCarrito = 0;
                    if (!empty($_SESSION['carrito'])) {
                        foreach ($_SESSION['carrito'] as $item) {
                            $cantidadCarrito += (int) ($item['cantidad'] ?? 0);
                            $totalCarrito += (float) ($item['precio'] ?? 0) * (int) ($item['cantidad'] ?? 0);
                        }
                    }
                    ?>
                    <div class="cart-dropdown">
                        <a href="<?php echo htmlspecialchars($baseUrl . '/index.php?url=ver_carrito', ENT_QUOTES, 'UTF-8'); ?>"
                           class="nav-menu__link cart-trigger">
                            <i class="fa-solid fa-cart-shopping"></i>
                            Carrito
                            <?php if ($cantidadCarrito > 0): ?>
                                <span class="cart-badge"><?php echo htmlspecialchars((string) $cantidadCarrito, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </a>

                        <div class="cart-menu">
                            <?php if (!empty($_SESSION['carrito'])): ?>
                                <?php foreach ($_SESSION['carrito'] as $idItem => $item): ?>
                                    <div class="cart-item">
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['nombre'] ?? 'Producto', ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <small><?php echo (int) ($item['cantidad'] ?? 0); ?> x $<?php echo number_format((float) ($item['precio'] ?? 0), 2); ?></small>
                                        </div>
                                        <span>$<?php echo number_format((float) ($item['precio'] ?? 0) * (int) ($item['cantidad'] ?? 0), 2); ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <div class="cart-total">Total: $<?php echo number_format($totalCarrito, 2); ?></div>
                                <a href="<?php echo htmlspecialchars($baseUrl . '/index.php?url=ver_carrito', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-sm w-100">Ver carrito</a>
                            <?php else: ?>
                                <p class="cart-empty">Tu carrito está vacío.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <a href="<?php echo htmlspecialchars($baseUrl . '/index.php?action=logout', ENT_QUOTES, 'UTF-8'); ?>" class="nav-menu__link nav-menu__link--logout">Cerrar sesión</a>
            </nav>
        <?php endif; ?>
    </div>
</header>

<style>
    .cart-dropdown {
        position: relative;
        display: inline-block;
    }

    .cart-trigger {
        position: relative;
    }

    .cart-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        border-radius: 999px;
        background: #dc3545;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        margin-left: 6px;
        line-height: 1;
    }

    .cart-menu {
        position: absolute;
        right: 0;
        top: calc(100% + 10px);
        width: 300px;
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        box-shadow: 0 14px 28px rgba(0, 0, 0, 0.12);
        padding: 12px;
        display: none;
        z-index: 999;
    }

    .cart-dropdown:hover .cart-menu,
    .cart-dropdown:focus-within .cart-menu {
        display: block;
    }

    .cart-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #f1f1f1;
    }

    .cart-item strong,
    .cart-item small {
        display: block;
    }

    .cart-item small {
        color: #6c757d;
        margin-top: 4px;
    }

    .cart-total {
        font-weight: 700;
        font-size: 0.95rem;
        color: #111827;
        padding: 12px 0 8px;
        text-align: right;
    }

    .cart-empty {
        margin: 0;
        color: #6c757d;
        padding: 8px 0;
    }
</style>