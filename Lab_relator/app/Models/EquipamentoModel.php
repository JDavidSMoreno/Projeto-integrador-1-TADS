<?php
declare(strict_types=1);

namespace App\Models;

final class EquipamentoModel extends BaseModel
{
    protected static string $table = 'equipamentos';

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, pagina: int, porPagina: int}
     */
    public function paginate(
        ?int $laboratorioId = null,
        string $busca = '',
        string $status = 'ativos',
        int $pagina = 1,
        int $porPagina = 20
    ): array {
        $pagina = max(1, $pagina);
        $porPagina = max(1, min(100, $porPagina));
        $offset = ($pagina - 1) * $porPagina;

        $where = [];
        $params = [];

        if ($status === 'ativos') {
            $where[] = 'e.ativo = 1';
        } elseif ($status === 'inativos') {
            $where[] = 'e.ativo = 0';
        }

        if ($laboratorioId !== null && $laboratorioId > 0) {
            $where[] = 'e.laboratorio_id = :laboratorio_id';
            $params['laboratorio_id'] = $laboratorioId;
        }

        if ($busca !== '') {
            $where[] = '(e.nome LIKE :busca OR e.patrimonio LIKE :busca OR e.tipo LIKE :busca OR l.nome LIKE :busca)';
            $params['busca'] = '%' . $busca . '%';
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
        $total = (int)$this
            ->query(
                "SELECT COUNT(*)
                 FROM equipamentos e
                 INNER JOIN laboratorios l ON l.id = e.laboratorio_id
                 {$whereSql}",
                $params
            )
            ->fetchColumn();

        $items = $this
            ->query(
                "SELECT e.*, l.nome AS laboratorio_nome
                 FROM equipamentos e
                 INNER JOIN laboratorios l ON l.id = e.laboratorio_id
                 {$whereSql}
                 ORDER BY e.ativo DESC, l.nome ASC, e.nome ASC
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

    /** @return array<int, array<string, mixed>> */
    public function listActiveByLaboratorio(int $laboratorioId): array
    {
        return $this
            ->query(
                "SELECT id, nome, patrimonio, tipo, status
                 FROM equipamentos
                 WHERE laboratorio_id = :laboratorio_id
                   AND ativo = 1
                 ORDER BY nome ASC",
                ['laboratorio_id' => $laboratorioId]
            )
            ->fetchAll();
    }

    public function toggleActive(int $id): bool
    {
        $equipamento = $this->findById($id);
        if ($equipamento === false) {
            return false;
        }

        return $this->update($id, [
            'ativo' => (int)$equipamento['ativo'] === 1 ? 0 : 1,
        ]);
    }
}
