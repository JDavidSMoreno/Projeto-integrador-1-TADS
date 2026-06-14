<?php
declare(strict_types=1);

// Arquivo: app/Models/UsuarioModel.php
// Lab Relator — Projeto Integrador TADS UniEinstein 2026
// Modificado por: Codex — correção QA

namespace App\Models;

use InvalidArgumentException;
use PDO;

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

        $where = ['u.perfil = :perfil'];
        $params = ['perfil' => $perfil];

        if ($status === 'ativos') {
            $where[] = 'u.ativo = 1';
        } elseif ($status === 'inativos') {
            $where[] = 'u.ativo = 0';
        }

        if ($busca !== '') {
            $where[] = '(u.nome LIKE :busca OR u.email LIKE :busca)';
            $params['busca'] = '%' . $busca . '%';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $total = (int)$this
            ->query("SELECT COUNT(*) FROM usuarios u {$whereSql}", $params)
            ->fetchColumn();

        // ── INÍCIO CORREÇÃO QA ──
        $contagens = match ($perfil) {
            'professor' => "(SELECT COUNT(*) FROM ocorrencia o WHERE o.id_professor = u.id) AS total_ocorrencias,
                            (SELECT COUNT(*) FROM ocorrencia o
                             WHERE o.id_professor = u.id
                               AND o.status NOT IN ('Encerrada')) AS total_abertos",
            'tecnico' => "(SELECT COUNT(*) FROM ocorrencia o WHERE o.id_tecnico = u.id) AS total_ocorrencias,
                          (SELECT COUNT(*) FROM ocorrencia o
                           WHERE o.id_tecnico = u.id
                             AND o.status = 'Em Atendimento') AS total_abertos",
            default => '0 AS total_ocorrencias, 0 AS total_abertos',
        };

        $sql = "SELECT u.id, u.nome, u.email, u.perfil, u.ativo, u.created_at,
                       {$contagens}
                FROM usuarios u
                {$whereSql}
                ORDER BY u.nome ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll();
        // ── FIM CORREÇÃO QA ──

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

    /** @return array<int, array<string, mixed>> */
    public function listActiveByPerfil(string $perfil): array
    {
        // ── INÍCIO CORREÇÃO QA ──
        $this->assertPerfil($perfil);

        return $this
            ->query(
                'SELECT id, nome, email, perfil
                 FROM usuarios
                 WHERE perfil = :perfil
                   AND ativo = 1
                 ORDER BY nome ASC',
                ['perfil' => $perfil]
            )
            ->fetchAll();
        // ── FIM CORREÇÃO QA ──
    }

    private function assertPerfil(string $perfil): void
    {
        if (!in_array($perfil, ['gestor', 'professor', 'tecnico'], true)) {
            throw new InvalidArgumentException('Perfil invalido.');
        }
    }
}
