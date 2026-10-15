<?php
require_once __DIR__ . '/../../../backend/src/config/security.php';
configure_secure_session();

if (($_SESSION['usuario_rol'] ?? '') !== 'Administrador') {
    header('Location: index.php?url=login');
    exit;
}

require_once __DIR__ . '/../../../backend/src/conexion.php';
$db = (new Database())->getConnection();
$flash = $_SESSION['vendedores_flash'] ?? null;
unset($_SESSION['vendedores_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect = 'Location: index.php?url=usuarios';

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $_SESSION['vendedores_flash'] = ['type' => 'error', 'message' => 'La solicitud expiró. Recarga la página e inténtalo de nuevo.'];
        header($redirect);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'guardar') {
        $idVendedor = (int) ($_POST['id_vendedor'] ?? 0);
        $editingId = $idVendedor;
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $direccion = trim((string) ($_POST['direccion'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $usuario = trim((string) ($_POST['usuario'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($nombre === '' || $email === '' || $usuario === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['vendedores_flash'] = ['type' => 'error', 'message' => 'Completa el nombre, usuario y un correo válido.'];
            header($redirect . ($idVendedor > 0 ? '&editar=' . $idVendedor : ''));
            exit;
        }

        try {
            $db->beginTransaction();
            $cuenta = false;
            if ($idVendedor > 0) {
                $exists = $db->prepare('SELECT CORREO FROM vendedor WHERE ID_VENDEDOR = ?');
                $exists->execute([$idVendedor]);
                $vendorRecord = $exists->fetch(PDO::FETCH_ASSOC);
                if (!$vendorRecord) {
                    throw new RuntimeException('No se encontró el vendedor que intentas editar.');
                }

                $cuentaQuery = $db->prepare("SELECT id, is_active FROM usuarios
                    WHERE ID_VENDEDOR = ? OR (ID_VENDEDOR IS NULL AND rol = 'Vendedor' AND email = ?)
                    ORDER BY CASE WHEN ID_VENDEDOR = ? THEN 0 ELSE 1 END, id
                    LIMIT 1");
                $cuentaQuery->execute([$idVendedor, $vendorRecord['CORREO'], $idVendedor]);
                $cuenta = $cuentaQuery->fetch(PDO::FETCH_ASSOC);
            }

            if (!$cuenta && $password === '') {
                $db->rollBack();
                $_SESSION['vendedores_flash'] = ['type' => 'error', 'message' => 'La contraseña es obligatoria para crear el acceso del vendedor.'];
                header($redirect . ($idVendedor > 0 ? '&editar=' . $idVendedor : ''));
                exit;
            }

            if ($idVendedor > 0) {
                $saveVendor = $db->prepare('UPDATE vendedor SET NOMBRE = ?, DIRECCION = ?, CORREO = ? WHERE ID_VENDEDOR = ?');
                $saveVendor->execute([$nombre, $direccion, $email, $idVendedor]);
            } else {
                $saveVendor = $db->prepare('INSERT INTO vendedor (NOMBRE, DIRECCION, CORREO) VALUES (?, ?, ?)');
                $saveVendor->execute([$nombre, $direccion, $email]);
                $idVendedor = (int) $db->lastInsertId();
            }

            if ($cuenta) {
                $saveUser = $db->prepare('UPDATE usuarios SET usuario = ?, email = ?, ID_VENDEDOR = ?, rol = ? WHERE id = ?');
                $saveUser->execute([$usuario, $email, $idVendedor, 'Vendedor', $cuenta['id']]);
                if ($password !== '') {
                    $savePassword = $db->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ? AND ID_VENDEDOR = ?');
                    $savePassword->execute([password_hash($password, PASSWORD_DEFAULT), $cuenta['id'], $idVendedor]);
                }
            } else {
                $saveUser = $db->prepare('INSERT INTO usuarios (usuario, email, password_hash, ID_VENDEDOR, is_active, rol) VALUES (?, ?, ?, ?, 1, ?)');
                $saveUser->execute([$usuario, $email, password_hash($password, PASSWORD_DEFAULT), $idVendedor, 'Vendedor']);
            }

            $db->commit();
            $_SESSION['vendedores_flash'] = [
                'type' => 'success',
                'message' => $editingId > 0
                    ? 'Los datos del vendedor se actualizaron correctamente.'
                    : 'El vendedor y su acceso se crearon correctamente.'
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Error al guardar vendedor: ' . $e->getMessage());
            $_SESSION['vendedores_flash'] = [
                'type' => 'error',
                'message' => $e instanceof PDOException && $e->getCode() === '23000'
                    ? 'Ese nombre de usuario o correo ya está registrado.'
                    : 'No se pudo guardar el vendedor. Revisa los datos e inténtalo de nuevo.'
            ];
            header($redirect . ($editingId > 0 ? '&editar=' . $editingId : ''));
            exit;
        }

        header($redirect);
        exit;
    }

    if ($action === 'cambiar_acceso') {
        $idVendedor = (int) ($_POST['id_vendedor'] ?? 0);
        try {
            $access = $db->prepare("SELECT u.id, u.is_active FROM usuarios u INNER JOIN vendedor v
                ON v.ID_VENDEDOR = u.ID_VENDEDOR
                OR (u.ID_VENDEDOR IS NULL AND u.rol = 'Vendedor' AND u.email = v.CORREO)
                WHERE v.ID_VENDEDOR = ?
                ORDER BY CASE WHEN u.ID_VENDEDOR = v.ID_VENDEDOR THEN 0 ELSE 1 END, u.id
                LIMIT 1");
            $access->execute([$idVendedor]);
            $account = $access->fetch(PDO::FETCH_ASSOC);
            if (!$account) {
                throw new RuntimeException('El vendedor no tiene una cuenta vinculada.');
            }

            $newStatus = (int) $account['is_active'] === 1 ? 0 : 1;
            $setStatus = $db->prepare('UPDATE usuarios SET is_active = ? WHERE id = ?');
            $setStatus->execute([$newStatus, $account['id']]);
            $_SESSION['vendedores_flash'] = [
                'type' => 'success',
                'message' => $newStatus === 1 ? 'Se habilitó el acceso del vendedor.' : 'Se deshabilitó el acceso del vendedor.'
            ];
        } catch (Throwable $e) {
            error_log('Error al cambiar acceso del vendedor: ' . $e->getMessage());
            $_SESSION['vendedores_flash'] = ['type' => 'error', 'message' => 'No se pudo cambiar el acceso del vendedor.'];
        }

        header($redirect);
        exit;
    }

    $_SESSION['vendedores_flash'] = ['type' => 'error', 'message' => 'La acción solicitada no es válida.'];
    header($redirect);
    exit;
}

$editing = null;
$editId = (int) ($_GET['editar'] ?? 0);
if ($editId > 0) {
    $editQuery = $db->prepare("SELECT v.ID_VENDEDOR, v.NOMBRE, v.DIRECCION, v.CORREO, u.usuario, u.email, u.is_active
        FROM vendedor v
        LEFT JOIN usuarios u ON u.ID_VENDEDOR = v.ID_VENDEDOR
            OR (u.ID_VENDEDOR IS NULL AND u.rol = 'Vendedor' AND u.email = v.CORREO)
        WHERE v.ID_VENDEDOR = ?
        ORDER BY u.id
        LIMIT 1");
    $editQuery->execute([$editId]);
    $editing = $editQuery->fetch(PDO::FETCH_ASSOC) ?: null;
}

$vendors = $db->query("SELECT v.ID_VENDEDOR, v.NOMBRE, v.DIRECCION, v.CORREO, u.usuario, u.email, u.is_active
    FROM vendedor v
    LEFT JOIN usuarios u ON u.ID_VENDEDOR = v.ID_VENDEDOR
        OR (u.ID_VENDEDOR IS NULL AND u.rol = 'Vendedor' AND u.email = v.CORREO)
    ORDER BY v.NOMBRE, u.id")->fetchAll(PDO::FETCH_ASSOC);
$e = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Vendedores | Pijamas Decare</title>
    <link rel="icon" href="./frontend/src/public/favicon/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
</head>
<body>
<?php require_once __DIR__ . '/../../../backend/src/controllers/header.php'; ?>

<main class="container">
    <div class="header-actions">
        <div>
            <h1>Administrar vendedores</h1>
            <p class="page-description">Crea vendedores, actualiza sus datos y controla el acceso al sistema.</p>
        </div>
        <a href="index.php?url=dashboard" class="btn btn-primary">Volver al Dashboard</a>
    </div>

    <?php if ($flash): ?>
        <div class="<?php echo $flash['type'] === 'success' ? 'alert-success' : 'alert-error'; ?>" role="status">
            <?php echo $e($flash['message']); ?>
        </div>
    <?php endif; ?>

    <section class="card user-form-card">
        <h2><?php echo $editing ? 'Editar vendedor' : 'Registrar vendedor'; ?></h2>
        <?php if ($editId > 0 && !$editing): ?>
            <p class="alert-error">No se encontró el vendedor solicitado.</p>
        <?php else: ?>
            <form method="post" action="index.php?url=usuarios<?php echo $editing ? '&editar=' . (int) $editing['ID_VENDEDOR'] : ''; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $e(csrf_token()); ?>">
                <input type="hidden" name="action" value="guardar">
                <input type="hidden" name="id_vendedor" value="<?php echo (int) ($editing['ID_VENDEDOR'] ?? 0); ?>">

                <div class="form-group">
                    <label for="nombre">Nombre completo</label>
                    <input type="text" id="nombre" name="nombre" maxlength="100" required value="<?php echo $e($editing['NOMBRE'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="usuario">Nombre de usuario</label>
                    <input type="text" id="usuario" name="usuario" maxlength="50" required value="<?php echo $e($editing['usuario'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" maxlength="100" required value="<?php echo $e($editing['email'] ?? $editing['CORREO'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="direccion">Dirección</label>
                    <input type="text" id="direccion" name="direccion" maxlength="255" value="<?php echo $e($editing['DIRECCION'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password"><?php echo $editing ? 'Nueva contraseña (opcional)' : 'Contraseña'; ?></label>
                    <input type="password" id="password" name="password" autocomplete="new-password" <?php echo $editing && !empty($editing['usuario']) ? '' : 'required'; ?>>
                    <?php if ($editing && !empty($editing['usuario'])): ?>
                        <small>Déjala vacía para conservar la contraseña actual.</small>
                    <?php endif; ?>
                </div>
                <div class="user-form-actions">
                    <button type="submit" class="btn btn-primary"><?php echo $editing ? 'Guardar cambios' : 'Crear vendedor'; ?></button>
                    <?php if ($editing): ?>
                        <a href="index.php?url=usuarios" class="btn">Cancelar edición</a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <section class="card user-table-card">
        <h2>Vendedores registrados</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Usuario y correo</th>
                        <th>Dirección</th>
                        <th>Acceso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$vendors): ?>
                    <tr><td colspan="5" class="table-message">Todavía no hay vendedores registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($vendors as $vendor): ?>
                        <?php $hasAccount = $vendor['usuario'] !== null; ?>
                        <tr>
                            <td><?php echo $e($vendor['NOMBRE']); ?></td>
                            <td>
                                <?php if ($hasAccount): ?>
                                    @<?php echo $e($vendor['usuario']); ?><br>
                                    <span class="user-email"><?php echo $e($vendor['email']); ?></span>
                                <?php else: ?>
                                    <span class="user-email">Sin cuenta de acceso</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $e($vendor['DIRECCION']); ?></td>
                            <td>
                                <?php if (!$hasAccount): ?>
                                    Sin cuenta
                                <?php else: ?>
                                    <span class="user-role-badge <?php echo (int) $vendor['is_active'] === 1 ? 'user-role-badge--vendedor' : ''; ?>">
                                        <?php echo (int) $vendor['is_active'] === 1 ? 'Activo' : 'Deshabilitado'; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="table-actions">
                                <a class="user-edit-button" href="index.php?url=usuarios&amp;editar=<?php echo (int) $vendor['ID_VENDEDOR']; ?>">Editar</a>
                                <?php if ($hasAccount): ?>
                                    <form method="post" action="index.php?url=usuarios" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo $e(csrf_token()); ?>">
                                        <input type="hidden" name="action" value="cambiar_acceso">
                                        <input type="hidden" name="id_vendedor" value="<?php echo (int) $vendor['ID_VENDEDOR']; ?>">
                                        <button type="submit" class="user-delete-button">
                                            <?php echo (int) $vendor['is_active'] === 1 ? 'Deshabilitar acceso' : 'Habilitar acceso'; ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
