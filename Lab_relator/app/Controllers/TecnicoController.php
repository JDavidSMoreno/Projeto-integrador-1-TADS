<?php
declare(strict_types=1);

namespace App\Controllers;

final class TecnicoController extends UsuarioPerfilController
{
    protected function perfil(): string
    {
        return 'tecnico';
    }

    protected function itemKey(): string
    {
        return 'tecnico';
    }

    protected function listKey(): string
    {
        return 'tecnicos';
    }

    protected function viewName(): string
    {
        return 'usuario/tecnicos';
    }

    protected function routeBase(): string
    {
        return '/usuario/tecnico';
    }

    protected function pageTitle(): string
    {
        return 'Tecnicos';
    }

    protected function activeRoute(): string
    {
        return 'tecnico';
    }

    protected function label(): string
    {
        return 'tecnico';
    }
}
