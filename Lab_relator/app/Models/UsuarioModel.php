<?php
declare(strict_types=1);

namespace App\Models;

use InvalidArgumentException;

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

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, pagina: int, porPagina: int}
     */
    public function paginateByPerfil(
        string $perfil,
        string $busca = '',
        string $status = 'ativos',
        int $pagina = 1,
        int $porPagina = 20
    ): array {
        $this->assertPerfil($perfil);

        $pagina = max(1, $pagina);
        $porPagina = max(1, min(100, $porPagina));
        $offset = ($pagina - 1) * $porPagina;

        $where = ['perfil = :perfil'];
        $params = ['perfil' => $perfil];

        if ($status === 'ativos') {
            $where[] = 'ativo = 1';
        } elseif ($status === 'inativos') {
            $where[] = 'ativo = 0';
        }

        if ($busca !== '') {
            $where[] = '(nome LIKE :busca OR email LIKE :busca)';
            $params['busca'] = '%' . $busca . '%';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $total = (int)$this
            ->query("SELECT COUNT(*) FROM usuarios {$whereSql}", $params)
            ->fetchColumn();

        $items = $this
            ->query(
                "SELECT id, nome, email, perfil, ativo, created_at,
                        0 AS total_ocorrencias,
                        0 AS total_abertos
                 FROM usuarios
                 {$whereSql}
                 ORDER BY nome ASC
                 LIMIT {$porPagina} OFFSET {$offset}",
                $params
            )
            ->fetchAll();

        return [
            'items' => $items,
            'total' => $total,
            'pagina' => $pagina,
            'porPagina' => $porPagina,
        ];
    }

    /** @return array<string, mixed>|false */
    public function findByPerfil(int $id, string $perfil): array|false
    {
        $this->assertPerfil($perfil);

        return $this
            ->query(
                'SELECT * FROM usuarios WHERE id = :id AND perfil = :perfil LIMIT 1',
                ['id' => $id, 'perfil' => $perfil]
            )
            ->fetch();
    }

    /** @param array<string, mixed> $data */
    public function createWithPerfil(array $data, string $perfil): int
    {
        $this->assertPerfil($perfil);

        return $this->insert([
            'nome' => trim((string)$data['nome']),
            'email' => mb_strtolower(trim((string)$data['email']), 'UTF-8'),
            'senha' => password_hash((string)$data['senha'], PASSWORD_BCRYPT),
            'perfil' => $perfil,
            'ativo' => (int)($data['ativo'] ?? 1),
        ]);
    }

    /** @param array<string, mixed> $data */
    public function updatePerfil(int $id, string $perfil, array $data): bool
    {
        $this->assertPerfil($perfil);

        $payload = [
            'nome' => trim((string)$data['nome']),
            'email' => mb_strtolower(trim((string)$data['email']), 'UTF-8'),
            'ativo' => (int)($data['ativo'] ?? 1),
        ];

        if (!empty($data['senha'])) {
            $payload['senha'] = password_hash((string)$data['senha'], PASSWORD_BCRYPT);
        }

        return $this
            ->query(
                'UPDATE usuarios
                 SET nome = :nome, email = :email, ativo = :ativo' . (isset($payload['senha']) ? ', senha = :senha' : '') . '
                 WHERE id = :id AND perfil = :perfil',
                $payload + ['id' => $id, 'perfil' => $perfil]
            )
            ->rowCount() > 0;
    }

    public function toggleActiveByPerfil(int $id, string $perfil): bool
    {
        $user = $this->findByPerfil($id, $perfil);
        if ($user === false) {
            return false;
        }

        $newStatus = (int)$user['ativo'] === 1 ? 0 : 1;

        return $this
            ->query(
                'UPDATE usuarios SET ativo = :ativo WHERE id = :id AND perfil = :perfil',
                ['ativo' => $newStatus, 'id' => $id, 'perfil' => $perfil]
            )
            ->rowCount() > 0;
    }

    private function assertPerfil(string $perfil): void
    {
        if (!in_array($perfil, ['gestor', 'professor', 'tecnico'], true)) {
            throw new InvalidArgumentException('Perfil invalido.');
        }
    }
}
