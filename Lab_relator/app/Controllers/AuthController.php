<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\SessionHelper;
use App\Models\LoginAttemptModel;
use App\Models\PasswordResetModel;
use App\Models\UsuarioModel;
use Throwable;

final class AuthController extends BaseController
{
    public function login(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }

        $this->renderLogin();
    }

    public function processLogin(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }

        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')), 'UTF-8');
        $senha = (string)($_POST['senha'] ?? '');
        $ipAddress = $this->requestIp();

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->renderLogin('Informe um e-mail valido.', $email);
            return;
        }

        if (strlen($senha) < 8) {
            $this->renderLogin('Informe a senha com pelo menos 8 caracteres.', $email);
            return;
        }

        try {
            $attempts = new LoginAttemptModel();
            if ($this->isLoginBlocked($attempts, $email, $ipAddress)) {
                $minutes = $attempts->remainingMinutes($email, $ipAddress);
                $this->renderLogin(
                    'Muitas tentativas invalidas. Aguarde cerca de ' . $minutes . ' minuto(s) antes de tentar novamente.',
                    $email
                );
                return;
            }

            $user = (new UsuarioModel())->findActiveByEmail($email);
            $validPassword = $user !== false && password_verify($senha, (string)$user['senha']);

            $this->recordLoginAttempt($attempts, $email, $ipAddress, $validPassword);

            if (!$validPassword) {
                $this->renderLogin('E-mail ou senha invalidos.', $email);
                return;
            }

            SessionHelper::login($user);
            Csrf::rotate();
            $this->redirect('/dashboard');
        } catch (Throwable $exception) {
            error_log('[AuthController] Login error: ' . $exception->getMessage());
            $this->renderLogin('Nao foi possivel autenticar agora. Verifique a configuracao do banco de dados.', $email);
        }
    }

    public function logout(): void
    {
        SessionHelper::logout();
        $this->redirect('/auth/login');
    }

    public function recuperar(): void
    {
        $this->renderRecovery();
    }

    public function processRecuperar(): void
    {
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')), 'UTF-8');
        $resetLink = null;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->renderRecovery('Informe um e-mail valido.', null, $email);
            return;
        }

        try {
            $user = (new UsuarioModel())->findActiveByEmail($email);
            if ($user !== false) {
                $token = (new PasswordResetModel())->createForUser(
                    (int)$user['id'],
                    (string)$user['email'],
                    $this->requestIp()
                );
                $resetLink = $this->absoluteUrl('/auth/resetar/' . $token);

                // Fase 2 nao possui MailService; o link fica visivel para teste local.
                error_log('[AuthController] Password reset link for ' . $email . ': ' . $resetLink);
            }

            $this->renderRecovery(
                null,
                'Se o e-mail estiver cadastrado, um link de recuperacao sera gerado.',
                $email,
                $resetLink
            );
        } catch (Throwable $exception) {
            error_log('[AuthController] Recovery error: ' . $exception->getMessage());
            $this->renderRecovery('Nao foi possivel gerar o link agora. Verifique a configuracao do banco de dados.', null, $email);
        }
    }

    /** @param array<string, string> $params */
    public function resetar(array $params): void
    {
        $token = (string)($params['token'] ?? '');
        $this->renderReset($token);
    }

    /** @param array<string, string> $params */
    public function processResetar(array $params): void
    {
        $token = (string)($params['token'] ?? '');
        $senha = (string)($_POST['senha'] ?? '');
        $confirmacao = (string)($_POST['senha_confirmacao'] ?? '');

        if (strlen($senha) < 8) {
            $this->renderReset($token, 'A nova senha deve ter pelo menos 8 caracteres.');
            return;
        }

        if (!hash_equals($senha, $confirmacao)) {
            $this->renderReset($token, 'A confirmacao da senha nao confere.');
            return;
        }

        try {
            $passwordResets = new PasswordResetModel();
            $reset = $passwordResets->findValidByToken($token);

            if ($reset === false) {
                $this->renderReset($token, 'Link de recuperacao invalido ou expirado.');
                return;
            }

            (new UsuarioModel())->updatePassword((int)$reset['usuario_id'], $senha);
            $passwordResets->markUsed((int)$reset['id']);
            Csrf::rotate();

            SessionHelper::flash('success', 'Senha alterada com sucesso. Entre com sua nova senha.');
            $this->redirect('/auth/login');
        } catch (Throwable $exception) {
            error_log('[AuthController] Password reset error: ' . $exception->getMessage());
            $this->renderReset($token, 'Nao foi possivel alterar a senha agora.');
        }
    }

    private function renderLogin(?string $error = null, string $email = ''): void
    {
        $this->render('auth/login', [
            'error' => $error,
            'email' => $email,
            'flash' => SessionHelper::pullFlash(),
        ], false);
    }

    private function renderRecovery(
        ?string $error = null,
        ?string $success = null,
        string $email = '',
        ?string $resetLink = null
    ): void {
        $this->render('auth/recuperar', [
            'error' => $error,
            'success' => $success,
            'email' => $email,
            'resetLink' => $resetLink,
            'flash' => SessionHelper::pullFlash(),
        ], false);
    }

    private function renderReset(string $token, ?string $error = null): void
    {
        $reset = false;

        try {
            if ($token !== '') {
                $reset = (new PasswordResetModel())->findValidByToken($token);
            }
        } catch (Throwable $exception) {
            error_log('[AuthController] Reset lookup error: ' . $exception->getMessage());
        }

        $this->render('auth/resetar', [
            'token' => $token,
            'error' => $error,
            'reset' => $reset,
        ], false);
    }

    private function isLoginBlocked(LoginAttemptModel $attempts, string $email, string $ipAddress): bool
    {
        try {
            return $attempts->isBlocked($email, $ipAddress);
        } catch (Throwable $exception) {
            error_log('[AuthController] Rate limit unavailable: ' . $exception->getMessage());

            return false;
        }
    }

    private function recordLoginAttempt(
        LoginAttemptModel $attempts,
        string $email,
        string $ipAddress,
        bool $success
    ): void {
        try {
            $attempts->record($email, $ipAddress, $success);
        } catch (Throwable $exception) {
            error_log('[AuthController] Login attempt log unavailable: ' . $exception->getMessage());
        }
    }

    private function requestIp(): string
    {
        return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
    }

    private function absoluteUrl(string $path): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? '127.0.0.1');
        $basePath = defined('APP_BASE_PATH') ? (string)APP_BASE_PATH : '';

        return $scheme . '://' . $host . rtrim($basePath, '/') . '/' . ltrim($path, '/');
    }
}
