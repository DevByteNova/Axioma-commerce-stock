<?php
require_once __DIR__ . '/../backend/src/config/db.php';
require_once __DIR__ . '/../backend/src/models/PasswordReset.php';
require_once __DIR__ . '/../backend/src/controllers/PasswordResetController.php';

function assert_password_reset(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }

    echo "OK: {$message}\n";
}

$db = (new Database())->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$suffix = bin2hex(random_bytes(8));
$username = 'password_reset_' . $suffix;
$email = $username . '@example.invalid';
$initialPassword = bin2hex(random_bytes(16));
$newPassword = bin2hex(random_bytes(16));
$userId = 0;
$token = bin2hex(random_bytes(32));
$expiredToken = bin2hex(random_bytes(32));

$tableCheck = $db->query("SHOW TABLES LIKE 'password_reset_tokens'");
if (!$tableCheck->fetchColumn()) {
    throw new RuntimeException('Ejecuta database/migracion_recuperacion_contrasena.sql antes de esta prueba.');
}

try {
    $insert = $db->prepare(
        "INSERT INTO usuarios (usuario, email, password_hash, ID_CLIENTE, ID_VENDEDOR, is_active, rol)
         VALUES (?, ?, ?, NULL, NULL, 1, 'Cliente')"
    );
    $insert->execute([$username, $email, password_hash($initialPassword, PASSWORD_BCRYPT)]);
    $userId = (int) $db->lastInsertId();

    $passwordReset = new PasswordReset($db);
    assert_password_reset($passwordReset->emitirToken($userId, hash('sha256', $token)), 'emite un token para una cuenta activa');
    assert_password_reset(!$passwordReset->emitirToken($userId, hash('sha256', bin2hex(random_bytes(32)))), 'limita solicitudes consecutivas');

    $controller = new PasswordResetController($db);
    assert_password_reset(!$controller->restablecer($token, 'corta'), 'rechaza contraseñas con menos de 8 caracteres');
    assert_password_reset($controller->restablecer($token, $newPassword), 'permite cambiar la contraseña con el token vigente');
    assert_password_reset(!$controller->restablecer($token, $initialPassword), 'rechaza reutilizar un token consumido');

    $checkUser = $db->prepare('SELECT password_hash FROM usuarios WHERE id = ?');
    $checkUser->execute([$userId]);
    assert_password_reset(password_verify($newPassword, $checkUser->fetchColumn()), 'guarda la contraseña nueva con hash');

    $expired = $db->prepare(
        'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, DATE_SUB(NOW(), INTERVAL 1 MINUTE))'
    );
    $expired->execute([$userId, hash('sha256', $expiredToken)]);
    assert_password_reset(!$controller->restablecer($expiredToken, $initialPassword), 'rechaza un token vencido');
} finally {
    if ($userId > 0) {
        $delete = $db->prepare('DELETE FROM usuarios WHERE id = ?');
        $delete->execute([$userId]);
    }
}
