<?php
declare(strict_types=1);

namespace App\Controllers;

final class PageController extends BaseController
{
    public function laboratorio(): void
    {
        $this->render('laboratorio/index', [], false);
    }

    public function equipamento(): void
    {
        $this->render('equipamento/index', [], false);
    }

    public function professores(): void
    {
        $this->render('usuario/professores', [], false);
    }

    public function tecnicos(): void
    {
        $this->render('usuario/tecnicos', [], false);
    }

    public function ocorrencias(): void
    {
        $this->render('ocorrencias/list', [], false);
    }

    public function criarOcorrencia(): void
    {
        $this->render('ocorrencias/create', [], false);
    }

    public function monitor(): void
    {
        $this->render('monitor/index', [], false);
    }

    public function relatorios(): void
    {
        $this->render('relatorios/index', [], false);
    }

    public function tiposProblema(): void
    {
        $this->render('tipo-problema/index', [], false);
    }
}
