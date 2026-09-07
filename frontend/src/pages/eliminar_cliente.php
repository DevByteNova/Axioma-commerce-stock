<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../backend/src/conexion.php';
$db = (new Database())->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    try {
        // Comprobar referencias que impiden la eliminación
        $refs = 0;
        $tables = [
            'facturas' => 'ID_CLIENTE',
            'ventas'   => 'ID_CLIENTE',
            'usuarios' => 'ID_CLIENTE'
        ];
        foreach ($tables as $table => $col) {
            $s = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$col} = ?");
            $s->execute([$id]);
            $count = (int)$s->fetchColumn();
            $refs += $count;
        }

        if ($refs > 0) {
            // Intentar eliminar dependencias de forma segura: primero facturas (las ventas se borran por cascade)
            try {
                $db->beginTransaction();
                $delFact = $db->prepare("DELETE FROM facturas WHERE ID_CLIENTE = ?");
                $delFact->execute([$id]);
                $removedFact = $delFact->rowCount();

                // Ahora eliminar el cliente (usuarios quedarán con ID_CLIENTE = NULL según FK)
                $delCli = $db->prepare("DELETE FROM clientes WHERE ID_CLIENTE = ?");
                $delCli->execute([$id]);
                $removedCli = $delCli->rowCount();

                $db->commit();
                $_SESSION['flash_success'] = "Cliente eliminado correctamente. Facturas eliminadas: $removedFact.";
            } catch (Exception $e) {
                if ($db && $db->inTransaction()) $db->rollBack();
                $_SESSION['flash_error'] = 'No fue posible eliminar las dependencias: ' . $e->getMessage();
            }
        } else {
            $stmt = $db->prepare("DELETE FROM clientes WHERE ID_CLIENTE = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_success'] = 'Cliente eliminado correctamente.';
        }
    } catch (Exception $e) {
        $_SESSION['flash_error'] = 'Error al eliminar cliente: ' . $e->getMessage();
    }
}

header("Location: index.php?url=clientes");
exit;