<?php
// Tienda pública: portada + catálogo con filtros. Accesible sin iniciar sesión.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../backend/src/conexion.php';
require_once __DIR__ . '/../../../backend/src/config/marca.php';

$db = new Database();
$pdo = $db->getConnection();
if (!$pdo) {
    http_response_code(500);
    $_GET['error'] = '500';
    require __DIR__ . '/error.php';
    exit;
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$rutaCatalogo = 'catalogo';
$puedeComprar = ($_SESSION['usuario_rol'] ?? '') === 'Cliente';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de pijamas | <?php echo htmlspecialchars(MARCA_TIENDA); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars(MARCA_ESLOGAN); ?>. Pijamas para mujer, hombre, niños y fechas especiales.">
    <link rel="icon" href="./frontend/src/public/favicon/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="./frontend/src/public/favicon/favicon.ico" sizes="any">
    <link rel="icon" href="./frontend/src/public/favicon/favicon-32x32.png" type="image/png" sizes="32x32">
    <link rel="icon" href="./frontend/src/public/favicon/favicon-16x16.png" type="image/png" sizes="16x16">
    <link rel="apple-touch-icon" href="./frontend/src/public/favicon/apple-touch-icon.png">
    <link rel="manifest" href="./frontend/src/public/favicon/site.webmanifest">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
    <link rel="stylesheet" href="./frontend/src/public/css/catalogo.css">
</head>
<body class="tienda-page">
    <?php require_once __DIR__ . '/../../../backend/src/controllers/header.php'; ?>

    <main class="tienda">
        <section class="portada">
            <div>
                <span class="portada__eyebrow"><?php echo htmlspecialchars(MARCA_COLECCION); ?></span>
                <h1>Transforma tus noches con <em>estilo y comodidad</em></h1>
                <p>Pijamas en satín, algodón y franela para mujer, hombre y niños, además de diseños temáticos para parejas y fechas especiales.</p>
                <div class="portada__acciones">
                    <a href="#catalogo" class="btn btn-marca">Ver catálogo</a>
                    <?php if (!isset($_SESSION['usuario_id'])): ?>
                        <a href="index.php?url=registro" class="btn btn-marca-outline">Crear mi cuenta</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="portada__imagen" aria-hidden="true">
                <img src="frontend/src/public/img/categorias/mujer.svg" alt="">
                <img src="frontend/src/public/img/categorias/hombre.svg" alt="">
            </div>
        </section>

        <section class="beneficios" aria-label="Beneficios">
            <div class="beneficio">
                <i class="fa-solid fa-truck-fast" aria-hidden="true"></i>
                <div><strong>Envíos nacionales</strong><span>Recibe tu pedido en todo Colombia.</span></div>
            </div>
            <div class="beneficio">
                <i class="fa-solid fa-ruler" aria-hidden="true"></i>
                <div><strong>Tallas S a XL</strong><span>Filtra el catálogo por tu talla.</span></div>
            </div>
            <div class="beneficio">
                <i class="fa-solid fa-file-invoice" aria-hidden="true"></i>
                <div><strong>Factura en PDF</strong><span>Descárgala desde tu panel de cliente.</span></div>
            </div>
        </section>

        <?php require __DIR__ . '/partials/catalogo_productos.php'; ?>
    </main>

    <footer class="pie-tienda">
        <p class="mb-1"><strong><?php echo htmlspecialchars(MARCA_TIENDA); ?></strong> · <?php echo htmlspecialchars(MARCA_COLECCION); ?></p>
        <p class="mb-0">Síguenos en <a href="<?php echo htmlspecialchars(MARCA_INSTAGRAM); ?>" target="_blank" rel="noopener">Instagram @creacionesdecare</a></p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
