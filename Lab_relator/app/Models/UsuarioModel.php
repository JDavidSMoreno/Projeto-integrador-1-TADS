<?php
declare(strict_types=1);

namespace App\Models;

final class UsuarioModel extends BaseModel
{
    protected static string $table = 'usuarios';

    /** @return array<string, mixed>|false */
    public function findActiveByEmail(string $email): array|false
    {
        return $this
            ->query(
                'SELECT * FROM usuarios WHERE email = :email AND ativo = 1 LIMIT 1',
                ['email' => mb_strtolower(trim($email), 'UTF-8')]
            )
            ->fetch();
    }

    /** @return array<string, int> */
    public function countByPerfil(): array
    {
        $rows = $this
            ->query('SELECT perfil, COUNT(*) AS total FROM usuarios WHERE ativo = 1 GROUP BY perfil')
            ->fetchAll();

        $totals = ['gestor' => 0, 'professor' => 0, 'tecnico' => 0];
        foreach ($rows as $row) {
            $perfil = (string)$row['perfil'];
            if (array_key_exists($perfil, $totals)) {
                $totals[$perfil] = (int)$row['total'];
            }
        }

        return $totals;
    }

    public function countActive(): int
    {
        return (int)$this
            ->query('SELECT COUNT(*) FROM usuarios WHERE ativo = 1')
            ->fetchColumn();
    }

    public function updatePassword(int $id, string $plainPassword): bool
    {
        return $this->update($id, [
            'senha' => password_hash($plainPassword, PASSWORD_BCRYPT),
        ]);
    }
}
