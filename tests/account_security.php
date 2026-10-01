<?php
session_save_path(sys_get_temp_dir());
session_id('account-security-' . bin2hex(random_bytes(8)));
session_start();

require_once __DIR__ . '/../backend/src/conexion.php';
require_once __DIR__ . '/../backend/src/config/security.php';

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }

    echo "OK: {$message}\n";
}

function submit_account_form(array $post): string
{
    $_POST = $post;
    ob_start();
    require __DIR__ . '/../frontend/src/pages/mi_cuenta.php';
    return (string) ob_get_clean();
}

$pdo = (new Database())->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$suffix = bin2hex(random_bytes(8));
$username = 'account_security_' . $suffix;
$initialEmail = $username . '@example.invalid';
$emptyAttemptEmail = 'empty_' . $initialEmail;
$wrongAttemptEmail = 'wrong_' . $initialEmail;
$updatedEmail = 'updated_' . $initialEmail;
$currentPassword = bin2hex(random_bytes(12));
$newPassword = bin2hex(random_bytes(12));
$userId = 0;

try {
    $insert = $pdo->prepare("INSERT INTO usuarios (usuario, email, password_hash, ID_CLIENTE, ID_VENDEDOR, is_active, rol) VALUES (?, ?, ?, NULL, NULL, 1, 'Cliente')");
    $insert->execute([$username, $initialEmail, password_hash($currentPassword, PASSWORD_BCRYPT)]);
    $userId = (int) $pdo->lastInsertId();

    $_SESSION = [
        'usuario_id' => $userId,
        'usuario_rol' => 'Cliente',
        'usuario_email' => $initialEmail,
        'csrf_token' => bin2hex(random_bytes(32)),
    ];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $csrfToken = $_SESSION['csrf_token'];

    submit_account_form([
        'action' => 'actualizar_cuenta',
        'csrf_token' => $csrfToken,
        'email' => $emptyAttemptEmail,
        'password_actual' => '',
    ]);
    $check = $pdo->prepare('SELECT email FROM usuarios WHERE id = ?');
    $check->execute([$userId]);
    assert_true($check->fetchColumn() === $initialEmail, 'rechaza el cambio cuando falta la contraseña actual');

    submit_account_form([
        'action' => 'actualizar_cuenta',
        'csrf_token' => $csrfToken,
        'email' => $wrongAttemptEmail,
        'password_actual' => 'contraseña-incorrecta',
    ]);
    $check->execute([$userId]);
    assert_true($check->fetchColumn() === $initialEmail, 'rechaza el cambio cuando la contraseña actual es incorrecta');

    $response = submit_account_form([
        'action' => 'actualizar_cuenta',
        'csrf_token' => $csrfToken,
        'email' => $updatedEmail,
        'password_actual' => $currentPassword,
        'new_password' => $newPassword,
        'confirm_password' => $newPassword,
    ]);
    $check = $pdo->prepare('SELECT email, password_hash FROM usuarios WHERE id = ?');
    $check->execute([$userId]);
    $updatedUser = $check->fetch(PDO::FETCH_ASSOC);
    assert_true($updatedUser['email'] === $updatedEmail, 'permite actualizar el correo con la contraseña correcta');
    assert_true(password_verify($newPassword, $updatedUser['password_hash']), 'permite actualizar la contraseña con la contraseña actual correcta');
    assert_true(strpos($response, 'actualizados correctamente') !== false, 'muestra confirmación después de guardar cambios válidos');
} finally {
    if ($userId > 0) {
        $delete = $pdo->prepare('DELETE FROM usuarios WHERE id = ?');
        $delete->execute([$userId]);
    }
    session_destroy();
}