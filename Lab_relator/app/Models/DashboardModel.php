<?php
declare(strict_types=1);

namespace App\Models;

final class DashboardModel extends BaseModel
{
    protected static string $table = 'usuarios';

    /** @return array<string, int> */
    public function occurrenceStatsForRole(string $perfil, int $usuarioId): array
    {
        $empty = [
            'total' => 0,
            'nao_atendida' => 0,
            'em_atendimento' => 0,
            'encerrada' => 0,
        ];

        if (!$this->tableExists('ocorrencia')) {
            return $empty;
        }

        $where = '';
        $params = [];

        if ($perfil === 'professor') {
            $where = 'WHERE id_professor = :usuario_id';
            $params['usuario_id'] = $usuarioId;
        } elseif ($perfil === 'tecnico') {
            $where = "WHERE id_tecnico = :usuario_id OR status = 'Nao Atendida'";
            $params['usuario_id'] = $usuarioId;
        }

        $row = $this
            ->query(
                "SELECT
                    COUNT(*) AS total,
                    SUM(status = 'Nao Atendida') AS nao_atendida,
                    SUM(status = 'Em Atendimento') AS em_atendimento,
                    SUM(status = 'Encerrada') AS encerrada
                 FROM ocorrencia
                 {$where}",
                $params
            )
            ->fetch();

        if (!$row) {
            return $empty;
        }

        return [
            'total' => (int)$row['total'],
            'nao_atendida' => (int)$row['nao_atendida'],
            'em_atendimento' => (int)$row['em_atendimento'],
            'encerrada' => (int)$row['encerrada'],
        ];
    }

    public function tableExists(string $table): bool
    {
        $row = $this
            ->query(
                'SELECT COUNT(*)
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                   AND table_name = :table_name',
                ['table_name' => $table]
            )
            ->fetchColumn();

        return (int)$row > 0;
    }
}
