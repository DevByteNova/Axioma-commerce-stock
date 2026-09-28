<?php

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

echo "Smoke tests completados.\n";
