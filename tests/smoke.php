<?php
ob_start();

require_once __DIR__ . '/../backend/src/config/db.php';

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }

    echo "OK: {$message}\n";
}

$db = (new Database())->getConnection();
assert_true($db instanceof PDO, 'la conexión PDO está disponible');

$requiredTables = ['usuarios', 'clientes', 'productos', 'ventas', 'metodos_pago'];
$tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach ($requiredTables as $table) {
    assert_true(in_array($table, $tables, true), "existe la tabla {$table}");
}

$stmt = $db->prepare('SELECT COUNT(*) FROM ventas WHERE ID_VENDEDOR = :id_vendedor');
$stmt->execute([':id_vendedor' => 1]);
assert_true((int) $stmt->fetchColumn() >= 0, 'la consulta de ventas acepta parámetros');

$stmt = $db->prepare('SELECT COUNT(*) FROM productos WHERE STOCK <= :limite');
$stmt->execute([':limite' => 5]);
assert_true((int) $stmt->fetchColumn() >= 0, 'la consulta de stock crítico funciona');

$stmt = $db->query("SHOW COLUMNS FROM ventas LIKE 'estado_pedido'");
assert_true((bool) $stmt->fetch(PDO::FETCH_ASSOC), 'ventas contiene el estado del pedido');

$stmt = $db->query("SELECT COUNT(*) FROM usuarios WHERE password_hash LIKE '\$2y$%'");
assert_true((int) $stmt->fetchColumn() >= 0, 'la consulta de contraseñas cifradas funciona');

$_SESSION = [
    'usuario_rol' => 'Vendedor',
    'usuario_name' => 'Vendendor de prueba',
    'id_vendedor' => 1,
];
ob_start();
require __DIR__ . '/../frontend/src/pages/dashboard_vendedor.php';
$html = ob_get_clean();
assert_true(strpos($html, 'name="estado"') !== false, 'el dashboard del vendedor ofrece cambiar el estado de envío');
assert_true(strpos($html, 'Guardar') !== false, 'el vendedor puede guardar el cambio del estado');

$_SESSION = [
    'usuario_rol' => 'Cliente',
    'usuario_id' => 1,
    'usuario_email' => 'cliente@prueba.com',
];
ob_start();
require __DIR__ . '/../frontend/src/pages/mi_cuenta.php';
$htmlPerfil = ob_get_clean();
assert_true(strpos($htmlPerfil, 'name="email"') !== false, 'la edición de correo está disponible en la cuenta');
assert_true(strpos($htmlPerfil, 'name="new_password"') !== false, 'la edición de contraseña está disponible en la cuenta');

echo "Smoke tests completados.\n";
ob_end_flush();
