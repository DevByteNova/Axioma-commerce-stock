<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_rol'])) {
    header('Location: index.php?url=login');
    exit;
}

require_once __DIR__ . '/../../../backend/src/conexion.php';
require_once __DIR__ . '/../../../backend/src/config/security.php';

$mensajeCuenta = '';
$tipoMensaje = 'info';
$pdo = null;

try {
    $pdo = (new Database())->getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    $pdo = null;
    $mensajeCuenta = 'No se pudo conectar a la base de datos.';
    $tipoMensaje = 'danger';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'actualizar_cuenta') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $mensajeCuenta = 'La sesión expiró. Inténtalo de nuevo.';
        $tipoMensaje = 'warning';
    } elseif ($pdo) {
        $id_usuario = (int) ($_SESSION['usuario_id'] ?? 0);
        $email_actual = trim((string) ($_POST['email'] ?? ''));
        $password_actual = (string) ($_POST['password_actual'] ?? '');
        $nuevo_password = (string) ($_POST['new_password'] ?? '');
        $confirm_password = (string) ($_POST['confirm_password'] ?? '');

        if (!filter_var($email_actual, FILTER_VALIDATE_EMAIL)) {
            $mensajeCuenta = 'El correo electrónico no es válido.';
            $tipoMensaje = 'danger';
        } else {
            $usuario = $pdo->prepare('SELECT id, usuario, email, password_hash, ID_CLIENTE, ID_VENDEDOR FROM usuarios WHERE id = :id LIMIT 1');
            $usuario->execute([':id' => $id_usuario]);
            $user = $usuario->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $mensajeCuenta = 'No se encontró tu usuario.';
                $tipoMensaje = 'danger';
            } else {
                $passwordCorrecta = $password_actual !== '' && password_verify($password_actual, $user['password_hash']);
                if (!$passwordCorrecta) {
                    $mensajeCuenta = 'La contraseña actual es incorrecta.';
                    $tipoMensaje = 'danger';
                } else {
                    $nuevaEmail = $email_actual;
                    $updateUsuario = ['email' => $nuevaEmail];
                    $query = 'UPDATE usuarios SET email = :email';
                    $params = [':email' => $nuevaEmail];

                    if ($nuevo_password !== '' || $confirm_password !== '') {
                        if (strlen($nuevo_password) < 6) {
                            $mensajeCuenta = 'La nueva contraseña debe tener al menos 6 caracteres.';
                            $tipoMensaje = 'danger';
                        } elseif ($nuevo_password !== $confirm_password) {
                            $mensajeCuenta = 'La nueva contraseña y la confirmación no coinciden.';
                            $tipoMensaje = 'danger';
                        } else {
                            $query .= ', password_hash = :password_hash';
                            $params[':password_hash'] = password_hash($nuevo_password, PASSWORD_BCRYPT);
                        }
                    }

                    if ($mensajeCuenta === '') {
                        $query .= ' WHERE id = :id';
                        $params[':id'] = $id_usuario;

                        $stmt = $pdo->prepare($query);
                        $stmt->execute($params);

                        $rol = ucfirst(strtolower(trim((string) ($_SESSION['usuario_rol'] ?? ''))));
                        if ($rol === 'Cliente' && !empty($user['ID_CLIENTE'])) {
                            $stmtCliente = $pdo->prepare('UPDATE clientes SET email = :email WHERE ID_CLIENTE = :id_cliente');
                            $stmtCliente->execute([':email' => $nuevaEmail, ':id_cliente' => (int) $user['ID_CLIENTE']]);
                        } elseif (($rol === 'Vendedor' || $rol === 'Administrador') && !empty($user['ID_VENDEDOR'])) {
                            $stmtVendedor = $pdo->prepare('UPDATE vendedor SET CORREO = :email WHERE ID_VENDEDOR = :id_vendedor');
                            $stmtVendedor->execute([':email' => $nuevaEmail, ':id_vendedor' => (int) $user['ID_VENDEDOR']]);
                        }

                        $_SESSION['usuario_email'] = $nuevaEmail;
                        $mensajeCuenta = 'Tu correo y contraseña fueron actualizados correctamente.';
                        $tipoMensaje = 'success';
                    }
                }
            }
        }
    }
}

$emailActual = $_SESSION['usuario_email'] ?? '';
if ($emailActual === '' && $pdo) {
    $stmtEmail = $pdo->prepare('SELECT email FROM usuarios WHERE id = :id LIMIT 1');
    $stmtEmail->execute([':id' => (int) ($_SESSION['usuario_id'] ?? 0)]);
    $emailActual = (string) ($stmtEmail->fetchColumn() ?: '');
    $_SESSION['usuario_email'] = $emailActual;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi cuenta | Pijamas Harv</title>
    <link rel="icon" href="./frontend/src/public/favicon/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php require_once __DIR__ . '/../../../backend/src/controllers/header.php'; ?>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <span class="text-uppercase small fw-bold text-secondary">Configuración</span>
                                <h2 class="mb-0 fw-bold">Mi cuenta</h2>
                            </div>
                        </div>

                        <?php if ($mensajeCuenta !== ''): ?>
                            <div class="alert alert-<?php echo htmlspecialchars($tipoMensaje, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
                                <?php echo htmlspecialchars($mensajeCuenta, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="index.php?url=mi_cuenta">
                            <input type="hidden" name="action" value="actualizar_cuenta">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Correo electrónico</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($emailActual, ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="password_actual" class="form-label fw-semibold">Contraseña actual</label>
                                <input type="password" id="password_actual" name="password_actual" class="form-control" placeholder="Requerida para guardar cambios" required autocomplete="current-password">
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="new_password" class="form-label fw-semibold">Nueva contraseña</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Opcional">
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label fw-semibold">Confirmar nueva contraseña</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Opcional">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <small class="text-muted">Si dejas la nueva contraseña vacía, no se cambia.</small>
                                <button type="submit" class="btn btn-primary px-4">Guardar cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
