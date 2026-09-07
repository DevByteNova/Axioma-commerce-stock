<?php

$codigo_error = isset($_GET['error']) ? $_GET['error'] : '404';

$mensajes = [
    '403' => ['titulo' => 'Acceso Denegado', 'descripcion' => 'Lo Sentimos, no cuentas con los permisos necesarios para acceder a esta seccion.'],
    '404' => ['titulo' => 'Pagina no encontrada', 'descripcion' => 'La pagina que estas buscano pudo haber sido movida, eliminada o la direccion es incorrecta.'],
    '500' => ['titulo' => 'Error interno del servidor', 'descripcion' => 'Ocurrió un problema en el servidor. Por favor, inténtalo de nuevo más tarde.']
];

$info = isset($mensajes[$codigo_error]) ? $mensajes[$codigo_error] : $mensajes['404']
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?php echo htmlspecialchars($codigo_error, ENT_QUOTES, 'UTF-8'); ?> - Axioma</title>
    <?php $rutaCss = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/frontend/src/pages/') !== false
        ? '../public/css/styles.css'
        : './frontend/src/public/css/styles.css'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($rutaCss, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="error-page">
    <main class="error-container" aria-labelledby="error-title">
        <div class="error-mark" aria-hidden="true">!</div>
        <p class="error-code"><?php echo htmlspecialchars($codigo_error, ENT_QUOTES, 'UTF-8'); ?></p>
        <h1 id="error-title" class="error-title"><?php echo htmlspecialchars($info['titulo'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="error-desc"><?php echo htmlspecialchars($info['descripcion'], ENT_QUOTES, 'UTF-8'); ?></p>
        <a href="index.php" class="btn-home">Volver al inicio</a>
    </main>
</body>
</html>