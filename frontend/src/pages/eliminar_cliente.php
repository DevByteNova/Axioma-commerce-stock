<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../backend/src/conexion.php';
$db = (new Database())->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    try {
        $db->beginTransaction();

        $references = [
            'facturas' => 'DELETE FROM facturas WHERE ID_CLIENTE = ?',
            'ventas'   => 'DELETE FROM ventas WHERE ID_CLIENTE = ?',
            'usuarios' => 'DELETE FROM usuarios WHERE ID_CLIENTE = ?'
        ];

        $removed = [];
        foreach ($references as $table => $query) {
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            $removed[$table] = $stmt->rowCount();
        }

        $deleteClient = $db->prepare('DELETE FROM clientes WHERE ID_CLIENTE = ?');
        $deleteClient->execute([$id]);

        if ($deleteClient->rowCount() === 0) {
            throw new RuntimeException('No se encontró el cliente solicitado.');
        }

        $db->commit();

        $_SESSION['flash_success'] = sprintf(
            'Cliente eliminado correctamente. Registros removidos: facturas=%d, ventas=%d, usuarios=%d.',
            $removed['facturas'],
            $removed['ventas'],
            $removed['usuarios']
        );
    } catch (Exception $e) {
        if ($db && $db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['flash_error'] = 'Error al eliminar cliente: ' . $e->getMessage();
    }
}

header("Location: index.php?url=clientes");
exit;