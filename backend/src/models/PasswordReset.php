<?php

class PasswordReset
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function emitirToken(int $userId, string $tokenHash): bool
    {
        $this->conn->beginTransaction();

        try {
            $user = $this->conn->prepare('SELECT id FROM usuarios WHERE id = ? AND is_active = 1 FOR UPDATE');
            $user->execute([$userId]);
            if (!$user->fetchColumn()) {
                $this->conn->commit();
                return false;
            }

            $recent = $this->conn->prepare(
                'SELECT id FROM password_reset_tokens
                 WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                 LIMIT 1'
            );
            $recent->execute([$userId]);
            if ($recent->fetchColumn()) {
                $this->conn->commit();
                return false;
            }

            $delete = $this->conn->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?');
            $delete->execute([$userId]);

            $insert = $this->conn->prepare(
                'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
            );
            $insert->execute([$userId, $tokenHash]);
            $this->conn->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }

    public function cambiarPassword(string $tokenHash, string $passwordHash): bool
    {
        $this->conn->beginTransaction();

        try {
            $token = $this->conn->prepare(
                'SELECT user_id FROM password_reset_tokens
                 WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()
                 LIMIT 1 FOR UPDATE'
            );
            $token->execute([$tokenHash]);
            $userId = $token->fetchColumn();
            if ($userId === false) {
                $this->conn->commit();
                return false;
            }

            $user = $this->conn->prepare('SELECT id FROM usuarios WHERE id = ? AND is_active = 1 FOR UPDATE');
            $user->execute([(int) $userId]);
            if (!$user->fetchColumn()) {
                $this->conn->commit();
                return false;
            }

            $update = $this->conn->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
            $update->execute([$passwordHash, (int) $userId]);

            $markUsed = $this->conn->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE token_hash = ?');
            $markUsed->execute([$tokenHash]);

            $invalidateOthers = $this->conn->prepare(
                'DELETE FROM password_reset_tokens WHERE user_id = ? AND token_hash <> ?'
            );
            $invalidateOthers->execute([(int) $userId, $tokenHash]);
            $this->conn->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }
}
