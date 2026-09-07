<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rolActual = $_SESSION['usuario_rol'] ?? 'Invitado';
$routeActual = $_GET['url'] ?? 'dashboard';
$rutaInicio = $rolActual === 'Cliente' ? 'cliente_dashboard' : 'dashboard';

$menuItems = [
    ['route' => 'dashboard', 'label' => 'Dashboard', 'roles' => ['Administrador', 'Vendedor'], 'url' => 'index.php?url=dashboard'],
    ['route' => 'clientes', 'label' => 'Clientes', 'roles' => ['Administrador', 'Vendedor'], 'url' => 'index.php?url=clientes'],
    ['route' => 'ver_inventario', 'label' => 'Inventario', 'roles' => ['Administrador', 'Vendedor'], 'url' => 'index.php?url=ver_inventario'],
    ['route' => 'usuarios', 'label' => 'Usuarios', 'roles' => ['Administrador'], 'url' => 'index.php?url=usuarios'],
    ['route' => 'registrar_pijamas', 'label' => 'Registrar Pijamas', 'roles' => ['Administrador', 'Vendedor'], 'url' => 'index.php?url=registrar_pijamas'],
    ['route' => 'registro_vendedor', 'label' => 'Registrar Vendedor', 'roles' => ['Administrador'], 'url' => 'index.php?url=registro_vendedor'],
    ['route' => 'cliente_dashboard', 'label' => 'Mi panel', 'roles' => ['Cliente'], 'url' => 'index.php?url=cliente_dashboard'],
];
?>

<header class="main-header">
    <div class="header-container">
        <a href="index.php?url=<?php echo $rutaInicio; ?>" class="header-brand" aria-label="Ir al dashboard principal">
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

                <a href="index.php?action=logout" class="nav-menu__link nav-menu__link--logout">Cerrar sesión</a>
            </nav>
        <?php endif; ?>
    </div>
</header>