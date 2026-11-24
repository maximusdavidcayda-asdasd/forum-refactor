<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Models\UserModel;
use Mustache_Engine;

final class AuthController
{
    public function __construct(
        private readonly UserModel $users,
        private readonly Mustache_Engine $mustache
    ) {
    }

    public function showAuthForm(?string $error = null, ?string $notice = null): void
    {
        $this->renderPage('auth', [
            'page_title'   => 'Account — Nested Forum Lab',
            'current_user' => $_SESSION['user'] ?? null,
            'csrf_token'   => Csrf::token(),
            'error'        => $error,
            'notice'       => $notice,
        ]);
    }

    public function register(): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            $this->showAuthForm('Invalid CSRF token.');
            return;
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
            http_response_code(422);
            $this->showAuthForm('Username must be 3–30 alphanumeric/underscore characters.');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
            http_response_code(422);
            $this->showAuthForm('Please enter a valid email address.');
            return;
        }

        if (strlen($password) < 8) {
            http_response_code(422);
            $this->showAuthForm('Password must be at least 8 characters.');
            return;
        }

        if ($this->users->existsByUsernameOrEmail($username, $email)) {
            http_response_code(409);
            $this->showAuthForm('An account with that username or email already exists.');
            return;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $userId = $this->users->create($username, $email, $hash);

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'       => $userId,
            'username' => $username,
        ];

        header('Location: /discussions/1', true, 302);
        exit;
    }

    public function login(): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            $this->showAuthForm('Invalid CSRF token.');
            return;
        }

        $identifier = trim((string) ($_POST['identifier'] ?? ''));
        $password   = (string) ($_POST['password'] ?? '');

        $user = $identifier !== '' ? $this->users->findByUsernameOrEmail($identifier) : null;

        if (
            $user === null ||
            !is_string($user['password_hash']) ||
            $user['password_hash'] === '' ||
            !password_verify($password, $user['password_hash'])
        ) {
            http_response_code(401);
            $this->showAuthForm('Invalid credentials.');
            return;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'       => (int) $user['id'],
            'username' => (string) $user['username'],
        ];

        header('Location: /discussions/1', true, 302);
        exit;
    }

    public function logout(): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            $this->renderPage('error', [
                'page_title'   => '403 Forbidden — Nested Forum Lab',
                'current_user' => $_SESSION['user'] ?? null,
                'csrf_token'   => Csrf::token(),
                'code'         => 403,
                'title'        => 'Forbidden',
                'message'      => 'Invalid CSRF token.',
            ]);
            return;
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires'  => time() - 42000,
                    'path'     => $params['path'] ?: '/',
                    'domain'   => $params['domain'] ?: '',
                    'secure'   => (bool) $params['secure'],
                    'httponly' => true,
                    'samesite' => $params['samesite'] ?: 'Lax',
                ]
            );
        }

        session_destroy();
        header('Location: /discussions/1', true, 302);
        exit;
    }

    private function renderPage(string $view, array $data): void
    {
        $data['body_html'] = $this->mustache->render($view, $data);
        echo $this->mustache->render('layout', $data);
    }
}