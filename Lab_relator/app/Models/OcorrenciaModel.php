<?php
declare(strict_types=1);

// Arquivo: app/Models/OcorrenciaModel.php
// Lab Relator — Projeto Integrador TADS UniEinstein 2026
// Modificado por: Codex — correção QA

namespace App\Models;

use InvalidArgumentException;
use PDO;
use Throwable;

final class OcorrenciaModel extends BaseModel
{
    protected static string $table = 'ocorrencia';

    protected static string $primaryKey = 'id';

    private const STATUS_NAO_ATENDIDA = 'Nao Atendida';

    private const STATUS_EM_EDICAO = 'Em Edicao';

    private const STATUS_EM_ATENDIMENTO = 'Em Atendimento';

    private const STATUS_ENCERRADA = 'Encerrada';

    /** @var array<int, string> */
    private const STATUS_VALIDOS = [
        self::STATUS_NAO_ATENDIDA,
        self::STATUS_EM_EDICAO,
        self::STATUS_EM_ATENDIMENTO,
        self::STATUS_ENCERRADA,
    ];

    /** @var array<string, string> */
    private const STATUS_KEYS = [
        self::STATUS_NAO_ATENDIDA => 'nao_atendida',
        self::STATUS_EM_EDICAO => 'em_edicao',
        self::STATUS_EM_ATENDIMENTO => 'em_atendimento',
        self::STATUS_ENCERRADA => 'encerrada',
    ];

    /**
     * @param array<string, mixed> $filtros
     * @return array{dados: array<int, array<string, mixed>>, total: int, paginas: int}
     */
    public function paginate(
        int $page,
        int $perPage,
        array $filtros,
        ?int $idUsuario,
        string $perfil
    ): array {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        [$whereSql, $params] = $this->buildWhere($filtros, $idUsuario, $perfil);

        $total = (int)$this
            ->query(
                "SELECT COUNT(*)
                 FROM ocorrencia o
                 {$whereSql}",
                $params
            )
            ->fetchColumn();

        $sql = $this->baseSelect() . "
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
    }

    /** @return array<string, mixed>|false */
    public function findById(int|string $id): array|false
    {
        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }

        return $this
            ->query(
                $this->baseSelect() . '
                 WHERE o.id = :id
                 LIMIT 1',
                ['id' => $id]
            )
            ->fetch();
    }

    /** @param array<string, mixed> $dados */
    public function create(array $dados): int
    {
        $idProfessor = $this->positiveInt($dados['id_professor'] ?? null, 'Professor');
        $idLaboratorio = $this->positiveInt($dados['id_laboratorio'] ?? null, 'Laboratorio');
        $idTipoProblema = $this->positiveInt($dados['id_tipo_problema'] ?? null, 'Tipo de problema');
        $idEquipamento = $this->nullablePositiveInt($dados['id_equipamento'] ?? null, 'Equipamento');
        $descricao = trim((string)($dados['descricao'] ?? ''));

        if ($descricao === '') {
            throw new InvalidArgumentException('Descricao e obrigatoria.');
        }

        try {
            $this->pdo->beginTransaction();

            $this->query(
                'INSERT INTO ocorrencia
                    (id_professor, id_tecnico, id_laboratorio, id_equipamento, id_tipo_problema, descricao, status)
                 VALUES
                    (:id_professor, NULL, :id_laboratorio, :id_equipamento, :id_tipo_problema, :descricao, :status)',
                [
                    'id_professor' => $idProfessor,
                    'id_laboratorio' => $idLaboratorio,
                    'id_equipamento' => $idEquipamento,
                    'id_tipo_problema' => $idTipoProblema,
                    'descricao' => $descricao,
                    'status' => self::STATUS_NAO_ATENDIDA,
                ]
            );

            $idOcorrencia = (int)$this->pdo->lastInsertId();
            $this->insertHistorico(
                $idOcorrencia,
                $idProfessor,
                null,
                self::STATUS_NAO_ATENDIDA,
                'Ocorrencia registrada.'
            );

            $this->pdo->commit();

            return $idOcorrencia;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    /** @param array<string, mixed> $dados */
    public function update(int|string $id, array $dados): bool
    {
        $idOcorrencia = (int)$id;
        if ($idOcorrencia <= 0) {
            throw new InvalidArgumentException('Ocorrencia invalida.');
        }

        $ocorrencia = $this->findById($idOcorrencia);
        if ($ocorrencia === false) {
            return false;
        }

        if ((string)$ocorrencia['status'] !== self::STATUS_NAO_ATENDIDA) {
            throw new InvalidArgumentException('A ocorrencia so pode ser editada enquanto estiver Nao Atendida.');
        }

        $idUsuario = (int)($dados['id_usuario'] ?? $ocorrencia['id_professor']);
        $idLaboratorio = $this->positiveInt($dados['id_laboratorio'] ?? $ocorrencia['id_laboratorio'], 'Laboratorio');
        $idTipoProblema = $this->positiveInt(
            $dados['id_tipo_problema'] ?? $ocorrencia['id_tipo_problema'],
            'Tipo de problema'
        );
        $idEquipamento = $this->nullablePositiveInt($dados['id_equipamento'] ?? null, 'Equipamento');
        $descricao = trim((string)($dados['descricao'] ?? $ocorrencia['descricao'] ?? ''));

        if ($descricao === '') {
            throw new InvalidArgumentException('Descricao e obrigatoria.');
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->query(
                'UPDATE ocorrencia
                 SET id_laboratorio = :id_laboratorio,
                     id_equipamento = :id_equipamento,
                     id_tipo_problema = :id_tipo_problema,
                     descricao = :descricao,
                     data_atualizacao = NOW()
                 WHERE id = :id
                   AND status = :status',
                [
                    'id_laboratorio' => $idLaboratorio,
                    'id_equipamento' => $idEquipamento,
                    'id_tipo_problema' => $idTipoProblema,
                    'descricao' => $descricao,
                    'id' => $idOcorrencia,
                    'status' => self::STATUS_NAO_ATENDIDA,
                ]
            );

            if ($stmt->rowCount() === 0) {
                $stillEditable = $this->query(
                    'SELECT COUNT(*) FROM ocorrencia WHERE id = :id AND status = :status',
                    ['id' => $idOcorrencia, 'status' => self::STATUS_NAO_ATENDIDA]
                )->fetchColumn();

                if ((int)$stillEditable === 0) {
                    $this->pdo->rollBack();

                    return false;
                }
            }

            $this->insertHistorico(
                $idOcorrencia,
                $idUsuario,
                self::STATUS_NAO_ATENDIDA,
                self::STATUS_NAO_ATENDIDA,
                'Dados da ocorrencia atualizados.'
            );

            $this->pdo->commit();

            return true;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function changeStatus(
        int $idOcorrencia,
        string $novoStatus,
        int $idUsuario,
        string $observacao = ''
    ): bool {
        $novoStatus = $this->normalizeStatus($novoStatus);
        $ocorrencia = $this->findById($idOcorrencia);

        if ($ocorrencia === false) {
            return false;
        }

        $perfilUsuario = $this->perfilUsuario($idUsuario);
        if ($perfilUsuario === null) {
            throw new InvalidArgumentException('Usuario responsavel nao encontrado.');
        }

        $statusAtual = (string)$ocorrencia['status'];
        $this->assertTransitionAllowed($statusAtual, $novoStatus, $perfilUsuario, $idUsuario, $ocorrencia);

        $sql = 'UPDATE ocorrencia
                SET status = :status,
                    data_atualizacao = NOW()';
        $params = [
            'status' => $novoStatus,
            'id' => $idOcorrencia,
        ];

        // ── INÍCIO CORREÇÃO QA ──
        if ($novoStatus === self::STATUS_EM_ATENDIMENTO) {
            $sql .= ', id_tecnico = :id_tecnico';
            $params['id_tecnico'] = $idUsuario;
        }
        // ── FIM CORREÇÃO QA ──

        if ($novoStatus === self::STATUS_ENCERRADA) {
            $sql .= ', data_encerramento = NOW()';
        } elseif ($novoStatus === self::STATUS_NAO_ATENDIDA) {
            $sql .= ', data_encerramento = NULL';
            if ($statusAtual === self::STATUS_EM_ATENDIMENTO) {
                $sql .= ', id_tecnico = NULL';
            }
        }

        $sql .= ' WHERE id = :id';

        try {
            $this->pdo->beginTransaction();

            $updated = $this->query($sql, $params)->rowCount() > 0;
            if ($updated) {
                $this->insertHistorico(
                    $idOcorrencia,
                    $idUsuario,
                    $statusAtual,
                    $novoStatus,
                    $observacao
                );
            }

            $this->pdo->commit();

            return $updated;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function getHistorico(int $idOcorrencia): array
    {
        if ($idOcorrencia <= 0) {
            return [];
        }

        return $this
            ->query(
                'SELECT h.*, u.nome AS usuario_nome, u.perfil AS usuario_perfil
                 FROM ocorrencia_historico h
                 INNER JOIN usuarios u ON u.id = h.id_usuario
                 WHERE h.id_ocorrencia = :id_ocorrencia
                 ORDER BY h.criado_em ASC, h.id ASC',
                ['id_ocorrencia' => $idOcorrencia]
            )
            ->fetchAll();
    }

    /** @return array<string, int> */
    public function stats(?int $idUsuario, string $perfil): array
    {
        $stats = [
            'total' => 0,
            'nao_atendida' => 0,
            'em_edicao' => 0,
            'em_atendimento' => 0,
            'encerrada' => 0,
        ];

        $where = [];
        $params = [];

        if ($perfil === 'professor' && $idUsuario !== null && $idUsuario > 0) {
            $where[] = 'id_professor = :id_usuario';
            $params['id_usuario'] = $idUsuario;
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
        $rows = $this
            ->query(
                "SELECT status, COUNT(*) AS total
                 FROM ocorrencia
                 {$whereSql}
                 GROUP BY status",
                $params
            )
            ->fetchAll();

        foreach ($rows as $row) {
            $status = (string)$row['status'];
            $total = (int)$row['total'];
            $key = self::STATUS_KEYS[$status] ?? null;

            if ($key !== null) {
                $stats[$key] = $total;
                $stats['total'] += $total;
            }
        }

        return $stats;
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array<int, array<string, mixed>>
     */
    public function monitorList(array $filtros, ?int $idUsuario, string $perfil): array
    {
        $where = [
            "(o.status <> 'Encerrada' OR o.data_encerramento >= (NOW() - INTERVAL 7 DAY))",
        ];
        $params = [];

        if ($perfil === 'tecnico' && $idUsuario !== null && $idUsuario > 0) {
            $where[] = "(o.status = 'Nao Atendida' OR o.id_tecnico = :id_usuario)";
            $params['id_usuario'] = $idUsuario;
        }

        $idLaboratorio = $this->optionalFilterInt($filtros['id_laboratorio'] ?? null);
        if ($idLaboratorio !== null) {
            $where[] = 'o.id_laboratorio = :id_laboratorio';
            $params['id_laboratorio'] = $idLaboratorio;
        }

        return $this
            ->query(
                $this->baseSelect() . '
                 WHERE ' . implode(' AND ', $where) . '
                 ORDER BY
                    FIELD(o.status, "Nao Atendida", "Em Edicao", "Em Atendimento", "Encerrada"),
                    o.data_criacao ASC,
                    o.id ASC',
                $params
            )
            ->fetchAll();
    }

    private function baseSelect(): string
    {
        return "SELECT
                    o.*,
                    p.nome AS professor_nome,
                    p.email AS professor_email,
                    t.nome AS tecnico_nome,
                    t.email AS tecnico_email,
                    l.nome AS laboratorio_nome,
                    l.bloco AS laboratorio_bloco,
                    e.nome AS equipamento_nome,
                    e.patrimonio AS equipamento_patrimonio,
                    tp.nome AS tipo_problema_nome,
                    tp.descricao AS tipo_problema_desc
                FROM ocorrencia o
                INNER JOIN usuarios p ON p.id = o.id_professor
                LEFT JOIN usuarios t ON t.id = o.id_tecnico
                INNER JOIN laboratorios l ON l.id = o.id_laboratorio
                LEFT JOIN equipamentos e ON e.id = o.id_equipamento
                INNER JOIN tipos_problema tp ON tp.id = o.id_tipo_problema";
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildWhere(array $filtros, ?int $idUsuario, string $perfil): array
    {
        $where = [];
        $params = [];

        if ($perfil === 'professor' && $idUsuario !== null && $idUsuario > 0) {
            $where[] = 'o.id_professor = :id_usuario';
            $params['id_usuario'] = $idUsuario;
        }

        $status = trim((string)($filtros['status'] ?? ''));
        if ($status !== '') {
            $status = $this->normalizeStatus($status);
            $where[] = 'o.status = :status';
            $params['status'] = $status;
        }

        $idLaboratorio = $this->optionalFilterInt($filtros['id_laboratorio'] ?? null);
        if ($idLaboratorio !== null) {
            $where[] = 'o.id_laboratorio = :id_laboratorio';
            $params['id_laboratorio'] = $idLaboratorio;
        }

        $dataInicio = $this->dateFilter($filtros['data_inicio'] ?? null);
        if ($dataInicio !== null) {
            $where[] = 'o.data_criacao >= :data_inicio';
            $params['data_inicio'] = $dataInicio . ' 00:00:00';
        }

        $dataFim = $this->dateFilter($filtros['data_fim'] ?? null);
        if ($dataFim !== null) {
            $where[] = 'o.data_criacao <= :data_fim';
            $params['data_fim'] = $dataFim . ' 23:59:59';
        }

        return [
            $where === [] ? '' : 'WHERE ' . implode(' AND ', $where),
            $params,
        ];
    }

    private function normalizeStatus(string $status): string
    {
        $status = trim($status);

        if (!in_array($status, self::STATUS_VALIDOS, true)) {
            throw new InvalidArgumentException('Status de ocorrencia invalido.');
        }

        return $status;
    }

    /** @param array<string, mixed> $ocorrencia */
    private function assertTransitionAllowed(
        string $statusAtual,
        string $novoStatus,
        string $perfilUsuario,
        int $idUsuario,
        array $ocorrencia
    ): void {
        $isProfessorDono = $perfilUsuario === 'professor'
            && (int)$ocorrencia['id_professor'] === $idUsuario;
        $isTecnicoOuGestor = in_array($perfilUsuario, ['tecnico', 'gestor'], true);

        $allowed = match (true) {
            $statusAtual === self::STATUS_NAO_ATENDIDA
                && $novoStatus === self::STATUS_EM_EDICAO => $isProfessorDono,
            $statusAtual === self::STATUS_NAO_ATENDIDA
                && $novoStatus === self::STATUS_EM_ATENDIMENTO => $isTecnicoOuGestor,
            $statusAtual === self::STATUS_EM_EDICAO
                && $novoStatus === self::STATUS_NAO_ATENDIDA => $isProfessorDono,
            $statusAtual === self::STATUS_EM_EDICAO
                && $novoStatus === self::STATUS_EM_ATENDIMENTO => $isTecnicoOuGestor,
            $statusAtual === self::STATUS_EM_ATENDIMENTO
                && $novoStatus === self::STATUS_ENCERRADA => $isTecnicoOuGestor,
            $statusAtual === self::STATUS_EM_ATENDIMENTO
                && $novoStatus === self::STATUS_NAO_ATENDIDA => $perfilUsuario === 'gestor',
            default => false,
        };

        if (!$allowed) {
            throw new InvalidArgumentException('Transicao de status nao permitida.');
        }
    }

    private function perfilUsuario(int $idUsuario): ?string
    {
        if ($idUsuario <= 0) {
            return null;
        }

        $perfil = $this
            ->query(
                'SELECT perfil FROM usuarios WHERE id = :id AND ativo = 1 LIMIT 1',
                ['id' => $idUsuario]
            )
            ->fetchColumn();

        return is_string($perfil) && $perfil !== '' ? $perfil : null;
    }

    private function insertHistorico(
        int $idOcorrencia,
        int $idUsuario,
        ?string $statusAnterior,
        string $statusNovo,
        string $observacao
    ): void {
        $observacao = trim($observacao);
        if ($observacao !== '') {
            $observacao = function_exists('mb_substr')
                ? mb_substr($observacao, 0, 500, 'UTF-8')
                : substr($observacao, 0, 500);
        }

        $this->query(
            'INSERT INTO ocorrencia_historico
                (id_ocorrencia, id_usuario, status_anterior, status_novo, observacao)
             VALUES
                (:id_ocorrencia, :id_usuario, :status_anterior, :status_novo, :observacao)',
            [
                'id_ocorrencia' => $idOcorrencia,
                'id_usuario' => $idUsuario,
                'status_anterior' => $statusAnterior,
                'status_novo' => $statusNovo,
                'observacao' => $observacao !== '' ? $observacao : null,
            ]
        );
    }

    private function positiveInt(mixed $value, string $label): int
    {
        $value = is_scalar($value) ? trim((string)$value) : '';

        if ($value === '' || !ctype_digit($value) || (int)$value <= 0) {
            throw new InvalidArgumentException($label . ' invalido.');
        }

        return (int)$value;
    }

    private function nullablePositiveInt(mixed $value, string $label): ?int
    {
        $value = is_scalar($value) ? trim((string)$value) : '';

        if ($value === '') {
            return null;
        }

        if (!ctype_digit($value) || (int)$value <= 0) {
            throw new InvalidArgumentException($label . ' invalido.');
        }

        return (int)$value;
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
