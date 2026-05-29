<?php
declare(strict_types=1);

namespace App\Helpers;

final class SessionHelper
{
    public static function isAuthenticated(): bool
    {
        return !empty($_SESSION['usuario']['id']);
    }

    /** @param array<string, mixed> $user */
    public static function login(array $user): void
    {
        session_regenerate_id(true);

        $id = (int)$user['id'];
        $nome = (string)$user['nome'];
        $email = (string)$user['email'];
        $perfil = (string)($user['perfil'] ?? $user['tipo'] ?? '');

        $_SESSION['usuario'] = [
            'id' => $id,
            'nome' => $nome,
            'email' => $email,
            'perfil' => $perfil,
        ];

        // Chaves legadas mantidas para as views ja existentes.
        $_SESSION['id_usuario'] = $id;
        $_SESSION['nome_usuario'] = $nome;
        $_SESSION['email_usuario'] = $email;
        $_SESSION['tipo_usuario'] = $perfil;
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'] ?? '/',
                $params['domain'] ?? '',
                (bool)($params['secure'] ?? false),
                (bool)($params['httponly'] ?? true)
            );
        }

        session_destroy();
    }

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        return isset($_SESSION['usuario']) && is_array($_SESSION['usuario'])
            ? $_SESSION['usuario']
            : null;
    }

    public static function id(): ?int
    {
        return self::isAuthenticated() ? (int)$_SESSION['usuario']['id'] : null;
    }

    public static function role(): ?string
    {
        return self::isAuthenticated() ? (string)$_SESSION['usuario']['perfil'] : null;
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash_type'] = $type;
        $_SESSION['flash_message'] = $message;
    }

    /** @return array{type: string, message: string}|null */
    public static function pullFlash(): ?array
    {
        if (empty($_SESSION['flash_message'])) {
            return null;
        }

        $flash = [
            'type' => (string)($_SESSION['flash_type'] ?? 'info'),
            'message' => (string)$_SESSION['flash_message'],
        ];

        unset($_SESSION['flash_type'], $_SESSION['flash_message']);

        return $flash;
    }
}
