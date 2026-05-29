<?php
declare(strict_types=1);

namespace App\Controllers;

final class ProfessorController extends UsuarioPerfilController
{
    protected function perfil(): string
    {
        return 'professor';
    }

    protected function itemKey(): string
    {
        return 'professor';
    }

    protected function listKey(): string
    {
        return 'professores';
    }

    protected function viewName(): string
    {
        return 'usuario/professores';
    }

    protected function routeBase(): string
    {
        return '/usuario/professor';
    }

    protected function pageTitle(): string
    {
        return 'Professores';
    }

    protected function activeRoute(): string
    {
        return 'professor';
    }

    protected function label(): string
    {
        return 'professor';
    }
}
