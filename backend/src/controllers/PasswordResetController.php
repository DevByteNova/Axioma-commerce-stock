<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/PasswordReset.php';
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

class PasswordResetController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function solicitar(string $email): void
    {
        $email = trim($email);
        if ($email === '' || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $usuario = (new Usuario($this->db))->buscarActivoPorEmail($email);
        if (!$usuario) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $passwordReset = new PasswordReset($this->db);
        if (!$passwordReset->emitirToken((int) $usuario['id'], $tokenHash)) {
            return;
        }

        try {
            $this->enviarCorreo((string) $usuario['email'], $token);
        } catch (Throwable $exception) {
            try {
                $invalidate = $this->db->prepare('DELETE FROM password_reset_tokens WHERE token_hash = ?');
                $invalidate->execute([$tokenHash]);
            } catch (Throwable $invalidateException) {
                error_log('No se pudo invalidar un token de recuperación sin entregar: ' . $invalidateException->getMessage());
            }
            error_log('No se pudo enviar un correo de recuperación de contraseña: ' . $exception->getMessage());
        }
    }

    public function restablecer(string $token, string $password): bool
    {
        if (!preg_match('/\A[a-f0-9]{64}\z/i', $token) || strlen($password) < 8 || strlen($password) > 72) {
            return false;
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        if ($passwordHash === false) {
            throw new RuntimeException('No se pudo proteger la contraseña nueva.');
        }

        return (new PasswordReset($this->db))->cambiarPassword(hash('sha256', $token), $passwordHash);
    }

    private function enviarCorreo(string $email, string $token): void
    {
        $host = axioma_env('SMTP_HOST');
        $fromEmail = axioma_env('SMTP_FROM_EMAIL');
        $fromName = axioma_env('SMTP_FROM_NAME', 'Axioma');
        $appUrl = rtrim(axioma_env('APP_URL'), '/');
        $port = filter_var(axioma_env('SMTP_PORT', '587'), FILTER_VALIDATE_INT);
        $encryption = strtolower(axioma_env('SMTP_ENCRYPTION', 'tls'));
        $username = axioma_env('SMTP_USERNAME');
        $password = axioma_env('SMTP_PASSWORD');

        if ($host === '' || $fromEmail === '' || !$port || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Configura SMTP_HOST, SMTP_PORT y SMTP_FROM_EMAIL en .env.');
        }
        if (($username === '') !== ($password === '')) {
            throw new RuntimeException('SMTP_USERNAME y SMTP_PASSWORD deben configurarse juntos.');
        }
        if (!in_array($encryption, ['tls', 'ssl'], true)) {
            throw new RuntimeException('SMTP_ENCRYPTION debe ser tls o ssl.');
        }

        $urlParts = parse_url($appUrl);
        if (!$urlParts || !isset($urlParts['scheme'], $urlParts['host'])
            || !in_array(strtolower($urlParts['scheme']), ['http', 'https'], true)
            || isset($urlParts['user'], $urlParts['pass'], $urlParts['query'], $urlParts['fragment'])) {
            throw new RuntimeException('Configura APP_URL con la dirección base de la aplicación.');
        }

        $resetUrl = $appUrl . '/index.php?url=recuperar_contrasena&token=' . rawurlencode($token);
        $mailer = new PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = $host;
        $mailer->Port = $port;
        $mailer->Timeout = 15;
        $mailer->SMTPAuth = $username !== '';
        if ($mailer->SMTPAuth) {
            $mailer->Username = $username;
            $mailer->Password = $password;
        }
        $mailer->SMTPSecure = $encryption;
        $mailer->CharSet = 'UTF-8';
        $mailer->setFrom($fromEmail, $fromName);
        $mailer->addAddress($email);
        $mailer->isHTML(true);
        $mailer->Subject = 'Recupera tu contraseña';
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $mailer->Body = '<p>Recibimos una solicitud para cambiar la contraseña de tu cuenta.</p>'
            . '<p><a href="' . $safeUrl . '">Crear una contraseña nueva</a></p>'
            . '<p>El enlace vence en 30 minutos. Si no solicitaste este cambio, puedes ignorar este correo.</p>';
        $mailer->AltBody = "Abre este enlace para cambiar tu contraseña (vence en 30 minutos):\n" . $resetUrl;
        $mailer->send();
    }
}
