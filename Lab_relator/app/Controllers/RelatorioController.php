<?php
declare(strict_types=1);

// Arquivo: app/Controllers/RelatorioController.php
// Lab Relator — Projeto Integrador TADS UniEinstein 2026
// Modificado por: Codex — correção QA

namespace App\Controllers;

use App\Helpers\SessionHelper;
use App\Models\LaboratorioModel;
use App\Models\RelatorioModel;
use App\Models\TipoProblemaModel;
use App\Models\UsuarioModel;
use Throwable;

final class RelatorioController extends BaseController
{
    private RelatorioModel $model;

    public function __construct()
    {
        parent::__construct();

        $this->model = new RelatorioModel();
    }

    public function index(): void
    {
        // ── INÍCIO CORREÇÃO QA ──
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina = 15;
        $filtros = $this->filtros();

        $stats = $this->emptyStats();
        $porTipo = [];
        $porLaboratorio = [];
        $ocorrencias = ['dados' => [], 'total' => 0, 'paginas' => 1];
        $laboratorios = [];
        $tiposProblema = [];
        $tecnicos = [];

        try {
            $porStatus = $this->model->ocorrenciasPorStatus($filtros);
            $tempoMedioHoras = $this->model->tempoMedioResolucao($filtros);

            $stats = $this->statsFromStatus($porStatus, $tempoMedioHoras);
            $porTipo = $this->model->ocorrenciasPorTipo($filtros);
            $porLaboratorio = $this->model->ocorrenciasPorLaboratorio($filtros);
            $ocorrencias = $this->model->listagem($filtros, $pagina, $porPagina);

            $laboratorios = (new LaboratorioModel())->listActive();
            $tiposProblema = (new TipoProblemaModel())->listActive();
            $tecnicos = (new UsuarioModel())->listActiveByPerfil('tecnico');
        } catch (Throwable $exception) {
            error_log('[RelatorioController] Index error: ' . $exception->getMessage());
            SessionHelper::flash('danger', 'Nao foi possivel carregar os relatorios.');
        }

        $this->render('relatorios/index', [
            'stats' => $stats,
            'por_tipo' => $porTipo,
            'por_laboratorio' => $porLaboratorio,
            'ocorrencias' => $ocorrencias['dados'],
            'laboratorios' => $laboratorios,
            'tiposProblema' => $tiposProblema,
            'tecnicos' => $tecnicos,
            'filtros' => $filtros,
            'pagination' => [
                'pagina' => $pagina,
                'total' => $ocorrencias['total'],
                'porPagina' => $porPagina,
            ],
        ], false);
        // ── FIM CORREÇÃO QA ──
    }

    public function exportarCsv(): void
    {
        // ── INÍCIO CORREÇÃO QA ──
        $filtros = $this->filtros();
        $rows = $this->model->listagem($filtros, 1, 5000)['dados'];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="relatorio_' . date('Y-m-d') . '.csv"');

        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'wb');
        if ($out === false) {
            exit;
        }

        fputcsv($out, [
            'ID',
            'Professor',
            'Tecnico',
            'Laboratorio',
            'Tipo',
            'Status',
            'Data Criacao',
            'Data Encerramento',
        ]);

        foreach ($rows as $row) {
            fputcsv($out, [
                (int)$row['id'],
                (string)($row['professor_nome'] ?? ''),
                (string)($row['tecnico_nome'] ?? ''),
                (string)($row['laboratorio_nome'] ?? ''),
                (string)($row['tipo_problema_desc'] ?? ''),
                (string)($row['status'] ?? ''),
                (string)($row['data_criacao'] ?? ''),
                (string)($row['data_encerramento'] ?? ''),
            ]);
        }

        fclose($out);
        exit;
        // ── FIM CORREÇÃO QA ──
    }

    public function exportarPdf(): void
    {
        // ── INÍCIO CORREÇÃO QA ──
        $filtros = $this->filtros();
        $rows = $this->model->listagem($filtros, 1, 5000)['dados'];
        $h = static fn (mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');

        header('Content-Type: text/html; charset=utf-8');
        ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Relatorio de Ocorrencias</title>
  <style>
    body { font-family: Arial, sans-serif; color: #1f2933; margin: 24px; }
    h1 { font-size: 22px; margin: 0 0 6px; }
    p { margin: 0 0 18px; color: #52606d; }
    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    th, td { border: 1px solid #d9e2ec; padding: 7px 8px; text-align: left; vertical-align: top; }
    th { background: #f0f4f8; }
    tr:nth-child(even) td { background: #fbfcfe; }
    @media print {
      body { margin: 12mm; }
      .no-print { display: none; }
      table { page-break-inside: auto; }
      tr { page-break-inside: avoid; page-break-after: auto; }
    }
  </style>
  <script>
    window.onload = function () { window.print(); };
  </script>
</head>
<body>
  <button class="no-print" onclick="window.print()">Imprimir</button>
  <h1>Relatorio de Ocorrencias</h1>
  <p>Gerado em <?= $h(date('d/m/Y H:i')) ?></p>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Professor</th>
        <th>Tecnico</th>
        <th>Laboratorio</th>
        <th>Tipo</th>
        <th>Status</th>
        <th>Data Criacao</th>
        <th>Data Encerramento</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($rows === []): ?>
        <tr><td colspan="8">Nenhuma ocorrencia encontrada.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><?= (int)$row['id'] ?></td>
            <td><?= $h($row['professor_nome'] ?? '') ?></td>
            <td><?= $h($row['tecnico_nome'] ?? '') ?></td>
            <td><?= $h($row['laboratorio_nome'] ?? '') ?></td>
            <td><?= $h($row['tipo_problema_desc'] ?? '') ?></td>
            <td><?= $h($row['status'] ?? '') ?></td>
            <td><?= $h($row['data_criacao'] ?? '') ?></td>
            <td><?= $h($row['data_encerramento'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
        <?php
        exit;
        // ── FIM CORREÇÃO QA ──
    }

    /** @return array<string, string> */
    private function filtros(): array
    {
        return [
            'data_inicio' => trim((string)($_GET['data_inicio'] ?? '')),
            'data_fim' => trim((string)($_GET['data_fim'] ?? '')),
            'status' => trim((string)($_GET['status'] ?? '')),
            'id_laboratorio' => trim((string)($_GET['id_laboratorio'] ?? '')),
            'id_tipo_problema' => trim((string)($_GET['id_tipo_problema'] ?? '')),
            'id_tecnico' => trim((string)($_GET['id_tecnico'] ?? '')),
        ];
    }

    /** @return array{total: int, encerrada: int, nao_atendida: int, em_edicao: int, em_atendimento: int, tempo_medio_dias: float} */
    private function emptyStats(): array
    {
        return [
            'total' => 0,
            'encerrada' => 0,
            'nao_atendida' => 0,
            'em_edicao' => 0,
            'em_atendimento' => 0,
            'tempo_medio_dias' => 0.0,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $porStatus
     * @return array{total: int, encerrada: int, nao_atendida: int, em_edicao: int, em_atendimento: int, tempo_medio_dias: float}
     */
    private function statsFromStatus(array $porStatus, float $tempoMedioHoras): array
    {
        $stats = $this->emptyStats();

        foreach ($porStatus as $row) {
            $status = (string)($row['status'] ?? '');
            $total = (int)($row['total'] ?? 0);
            $stats['total'] += $total;

            if ($status === 'Nao Atendida') {
                $stats['nao_atendida'] = $total;
            } elseif ($status === 'Em Edicao') {
                $stats['em_edicao'] = $total;
            } elseif ($status === 'Em Atendimento') {
                $stats['em_atendimento'] = $total;
            } elseif ($status === 'Encerrada') {
                $stats['encerrada'] = $total;
            }
        }

        $stats['tempo_medio_dias'] = $tempoMedioHoras > 0 ? round($tempoMedioHoras / 24, 1) : 0.0;

        return $stats;
    }
}
