<?php
declare(strict_types=1);

// Arquivo: app/Models/RelatorioModel.php
// Lab Relator — Projeto Integrador TADS UniEinstein 2026
// Modificado por: Codex — correção QA

namespace App\Models;

use PDO;

final class RelatorioModel extends BaseModel
{
    protected static string $table = 'ocorrencia';

    /** @var array<int, string> */
    private const STATUS_VALIDOS = [
        'Nao Atendida',
        'Em Edicao',
        'Em Atendimento',
        'Encerrada',
    ];

    /**
     * @param array<string, mixed> $filtros
     * @return array<int, array{status: string, total: int}>
     */
    public function ocorrenciasPorStatus(array $filtros): array
    {
        // ── INÍCIO CORREÇÃO QA ──
        [$whereSql, $params] = $this->buildWhere($filtros);

        return $this
            ->query(
                "SELECT o.status, COUNT(*) AS total
                 FROM ocorrencia o
                 {$whereSql}
                 GROUP BY o.status
                 ORDER BY FIELD(o.status, 'Nao Atendida', 'Em Edicao', 'Em Atendimento', 'Encerrada')",
                $params
            )
            ->fetchAll();
        // ── FIM CORREÇÃO QA ──
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array<int, array{descricao: string, total: int}>
     */
    public function ocorrenciasPorTipo(array $filtros): array
    {
        // ── INÍCIO CORREÇÃO QA ──
        [$whereSql, $params] = $this->buildWhere($filtros);

        return $this
            ->query(
                "SELECT COALESCE(NULLIF(tp.descricao, ''), tp.nome) AS descricao,
                        COUNT(*) AS total
                 FROM ocorrencia o
                 INNER JOIN tipos_problema tp ON tp.id = o.id_tipo_problema
                 {$whereSql}
                 GROUP BY tp.id, tp.descricao, tp.nome
                 ORDER BY total DESC, descricao ASC",
                $params
            )
            ->fetchAll();
        // ── FIM CORREÇÃO QA ──
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array<int, array{nome: string, total: int}>
     */
    public function ocorrenciasPorLaboratorio(array $filtros): array
    {
        // ── INÍCIO CORREÇÃO QA ──
        [$whereSql, $params] = $this->buildWhere($filtros);

        return $this
            ->query(
                "SELECT l.nome, COUNT(*) AS total
                 FROM ocorrencia o
                 INNER JOIN laboratorios l ON l.id = o.id_laboratorio
                 {$whereSql}
                 GROUP BY l.id, l.nome
                 ORDER BY total DESC, l.nome ASC",
                $params
            )
            ->fetchAll();
        // ── FIM CORREÇÃO QA ──
    }

    /** @param array<string, mixed> $filtros */
    public function tempoMedioResolucao(array $filtros): float
    {
        // ── INÍCIO CORREÇÃO QA ──
        [$whereSql, $params] = $this->buildWhere($filtros, [
            "o.status = 'Encerrada'",
            'o.data_encerramento IS NOT NULL',
        ]);

        $valor = $this
            ->query(
                "SELECT AVG(TIMESTAMPDIFF(HOUR, o.data_criacao, o.data_encerramento))
                 FROM ocorrencia o
                 {$whereSql}",
                $params
            )
            ->fetchColumn();

        return $valor !== null && $valor !== false ? (float)$valor : 0.0;
        // ── FIM CORREÇÃO QA ──
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array{dados: array<int, array<string, mixed>>, total: int, paginas: int}
     */
    public function listagem(array $filtros, int $page = 1, int $perPage = 20): array
    {
        // ── INÍCIO CORREÇÃO QA ──
        $page = max(1, $page);
        $perPage = max(1, min(5000, $perPage));
        $offset = ($page - 1) * $perPage;

        [$whereSql, $params] = $this->buildWhere($filtros);

        $total = (int)$this
            ->query(
                "SELECT COUNT(*)
                 FROM ocorrencia o
                 {$whereSql}",
                $params
            )
            ->fetchColumn();

        $sql = "SELECT
                    o.id,
                    o.status,
                    o.descricao,
                    o.data_criacao,
                    o.data_atualizacao,
                    o.data_encerramento,
                    p.nome AS professor_nome,
                    p.email AS professor_email,
                    t.nome AS tecnico_nome,
                    t.email AS tecnico_email,
                    l.nome AS laboratorio_nome,
                    COALESCE(NULLIF(tp.descricao, ''), tp.nome) AS tipo_problema_desc,
                    tp.nome AS tipo_problema_nome
                FROM ocorrencia o
                INNER JOIN usuarios p ON p.id = o.id_professor
                LEFT JOIN usuarios t ON t.id = o.id_tecnico
                INNER JOIN laboratorios l ON l.id = o.id_laboratorio
                INNER JOIN tipos_problema tp ON tp.id = o.id_tipo_problema
                {$whereSql}
                ORDER BY o.data_criacao DESC, o.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'dados' => $stmt->fetchAll(),
            'total' => $total,
            'paginas' => max(1, (int)ceil($total / $perPage)),
        ];
        // ── FIM CORREÇÃO QA ──
    }

    /**
     * @param array<string, mixed> $filtros
     * @param array<int, string> $condicoesExtras
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildWhere(array $filtros, array $condicoesExtras = []): array
    {
        $condicoes = $condicoesExtras;
        $params = [];

        $dataInicio = $this->dateFilter($filtros['data_inicio'] ?? null);
        if ($dataInicio !== null) {
            $condicoes[] = 'o.data_criacao >= :data_inicio';
            $params['data_inicio'] = $dataInicio . ' 00:00:00';
        }

        $dataFim = $this->dateFilter($filtros['data_fim'] ?? null);
        if ($dataFim !== null) {
            $condicoes[] = 'o.data_criacao <= :data_fim';
            $params['data_fim'] = $dataFim . ' 23:59:59';
        }

        $idLaboratorio = $this->optionalFilterInt($filtros['id_laboratorio'] ?? null);
        if ($idLaboratorio !== null) {
            $condicoes[] = 'o.id_laboratorio = :id_laboratorio';
            $params['id_laboratorio'] = $idLaboratorio;
        }

        $idTipoProblema = $this->optionalFilterInt($filtros['id_tipo_problema'] ?? null);
        if ($idTipoProblema !== null) {
            $condicoes[] = 'o.id_tipo_problema = :id_tipo_problema';
            $params['id_tipo_problema'] = $idTipoProblema;
        }

        $idTecnico = $this->optionalFilterInt($filtros['id_tecnico'] ?? null);
        if ($idTecnico !== null) {
            $condicoes[] = 'o.id_tecnico = :id_tecnico';
            $params['id_tecnico'] = $idTecnico;
        }

        $status = trim((string)($filtros['status'] ?? ''));
        if ($status !== '' && in_array($status, self::STATUS_VALIDOS, true)) {
            $condicoes[] = 'o.status = :status';
            $params['status'] = $status;
        }

        return [
            $condicoes === [] ? '' : 'WHERE ' . implode(' AND ', $condicoes),
            $params,
        ];
    }

    private function optionalFilterInt(mixed $value): ?int
    {
        $value = is_scalar($value) ? trim((string)$value) : '';

        if ($value === '') {
            return null;
        }

        return ctype_digit($value) && (int)$value > 0 ? (int)$value : null;
    }

    private function dateFilter(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string)$value) : '';

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : null;
    }
}
