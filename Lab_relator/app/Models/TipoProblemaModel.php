<?php
declare(strict_types=1);

namespace App\Models;

final class TipoProblemaModel extends BaseModel
{
    protected static string $table = 'tipos_problema';

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, pagina: int, porPagina: int}
     */
    public function paginate(string $busca = '', string $status = 'ativos', int $pagina = 1, int $porPagina = 20): array
    {
        $pagina = max(1, $pagina);
        $porPagina = max(1, min(100, $porPagina));
        $offset = ($pagina - 1) * $porPagina;

        $where = [];
        $params = [];

        if ($status === 'ativos') {
            $where[] = 'ativo = 1';
        } elseif ($status === 'inativos') {
            $where[] = 'ativo = 0';
        }

        if ($busca !== '') {
            $where[] = '(nome LIKE :busca OR descricao LIKE :busca)';
            $params['busca'] = '%' . $busca . '%';
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
        $total = (int)$this
            ->query("SELECT COUNT(*) FROM tipos_problema {$whereSql}", $params)
            ->fetchColumn();

        $items = $this
            ->query(
                "SELECT *
                 FROM tipos_problema
                 {$whereSql}
                 ORDER BY ativo DESC, nome ASC
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
    public function listActive(): array
    {
        return $this->findAll(['ativo' => 1], 'nome ASC');
    }

    public function toggleActive(int $id): bool
    {
        $tipo = $this->findById($id);
        if ($tipo === false) {
            return false;
        }

        return $this->update($id, [
            'ativo' => (int)$tipo['ativo'] === 1 ? 0 : 1,
        ]);
    }
}
