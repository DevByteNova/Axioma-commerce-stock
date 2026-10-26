<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_rol']) || !in_array($_SESSION['usuario_rol'], ['Administrador', 'Vendedor'], true)) {
    header("Location: index.php?url=login");
    exit;
}

require_once __DIR__ . '/../../../backend/src/conexion.php';
$database = new Database();
$db = $database->getConnection();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes | Pijamas Decare</title>
    <link rel="icon" href="./frontend/src/public/favicon/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="./frontend/src/public/favicon/favicon.ico" sizes="any">
    <link rel="icon" href="./frontend/src/public/favicon/favicon-32x32.png" type="image/png" sizes="32x32">
    <link rel="icon" href="./frontend/src/public/favicon/favicon-16x16.png" type="image/png" sizes="16x16">
    <link rel="apple-touch-icon" href="./frontend/src/public/favicon/apple-touch-icon.png">
    <link rel="manifest" href="./frontend/src/public/favicon/site.webmanifest">
    <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
</head>
<body>

<?php require_once __DIR__ . '/../../../backend/src/controllers/header.php'; ?>
<div class="container">
    <?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-spaced"><?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-spaced"><?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>
    <a href="index.php?url=dashboard" class="btn btn-primary action-spaced">← Volver al Dashboard</a>

    <div class="header-actions">
        <h1>Lista de Clientes</h1>
        <a href="index.php?url=nuevo_cliente" class="btn btn-primary">➕ Agregar Cliente</a>
    </div>

    <div class="busqueda-container">
        <input type="text" id="busqueda" placeholder="Buscar cliente...">
    </div>

    <table>
        <thead>
            <tr>
                <th>NOMBRE COMPLETO</th>
                <th>EMAIL</th>
                <th>DIRECCIÓN</th>
                <th>TELÉFONO</th>
                <th>ACCIONES</th>
            </tr>
        </thead>
        <tbody id="cuerpoTabla">
            <?php
            $stmt = $db->query("SELECT ID_CLIENTE, NOMBRE, APELLIDOS, email, DIRECCION, TELEFONO FROM clientes");

            if ($stmt->rowCount() > 0) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars(($row['NOMBRE'] ?? '') . ' ' . ($row['APELLIDOS'] ?? '')) . "</td>";
                    echo "<td>" . htmlspecialchars($row['email'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($row['DIRECCION'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($row['TELEFONO'] ?? '') . "</td>";
                    echo "<td>";
                    echo "<a href='index.php?url=editar_cliente&id=" . (int)$row['ID_CLIENTE'] . "'>Editar</a>";
                    echo "<a href='index.php?url=eliminar_cliente&id=" . (int)$row['ID_CLIENTE'] . "' onclick='return confirm(\"¿Estás seguro?\")'>Eliminar</a>";
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5' class='table-empty'>No hay clientes registrados.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<script>
    const inputBusqueda = document.getElementById('busqueda');
    const cuerpoTabla = document.getElementById('cuerpoTabla');

    inputBusqueda.addEventListener('keyup', function(){
        const filtro = inputBusqueda.value.toLowerCase();
        const filas = cuerpoTabla.getElementsByTagName('tr');

        for (let i = 0; i < filas.length; i++) {
            const textoFila = filas[i].textContent.toLowerCase();
            filas[i].style.display = textoFila.includes(filtro) ? '' : 'none';
        }
    });
</script>
</body>
</html>