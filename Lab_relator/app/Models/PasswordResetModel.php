<?php
declare(strict_types=1);

namespace App\Models;

final class PasswordResetModel extends BaseModel
{
    protected static string $table = 'password_resets';

    public function createForUser(int $usuarioId, string $email, string $ipAddress): string
    {
        $this->expireOpenTokens($usuarioId);

        $token = bin2hex(random_bytes(32));
        $this->insert([
            'usuario_id' => $usuarioId,
            'email' => mb_strtolower(trim($email), 'UTF-8'),
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + 3600),
            'ip_address' => $ipAddress,
        ]);

        return $token;
    }

    /** @return array<string, mixed>|false */
    public function findValidByToken(string $token): array|false
    {
        return $this
            ->query(
                "SELECT pr.*, u.nome, u.email AS usuario_email
                 FROM password_resets pr
                 INNER JOIN usuarios u ON u.id = pr.usuario_id
                 WHERE pr.token_hash = :token_hash
                   AND pr.used_at IS NULL
                   AND pr.expires_at >= NOW()
                   AND u.ativo = 1
                 LIMIT 1",
                ['token_hash' => hash('sha256', $token)]
            )
            ->fetch();
    }

    public function markUsed(int $id): bool
    {
        return $this->update($id, ['used_at' => date('Y-m-d H:i:s')]);
    }

    public function countOpen(): int
    {
        return (int)$this
            ->query('SELECT COUNT(*) FROM password_resets WHERE used_at IS NULL AND expires_at >= NOW()')
            ->fetchColumn();
    }

    private function expireOpenTokens(int $usuarioId): void
    {
        $this->query(
            'UPDATE password_resets SET used_at = NOW() WHERE usuario_id = :usuario_id AND used_at IS NULL',
            ['usuario_id' => $usuarioId]
        );
    }
}
