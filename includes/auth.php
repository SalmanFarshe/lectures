<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Session Security
|--------------------------------------------------------------------------
| Configure session security before starting the session.
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_start();
}

/*
|--------------------------------------------------------------------------
| Shared Functions
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/functions.php';

/*
|--------------------------------------------------------------------------
| Logout CSRF Token
|--------------------------------------------------------------------------
| Generate a token once per session.
| Validate this token in logout.php before destroying the session.
*/

if (
    !isset($_SESSION['logout_csrf']) ||
    !is_string($_SESSION['logout_csrf']) ||
    $_SESSION['logout_csrf'] === ''
) {
    $_SESSION['logout_csrf'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        redirect('/lectures/index.php');
    }
}

/*
|--------------------------------------------------------------------------
| Role-Based Authorization
|--------------------------------------------------------------------------
*/

function requireRole(string $role): void
{
    requireLogin();

    $currentRole = $_SESSION['role'] ?? '';

    if (!is_string($currentRole) || !hash_equals($role, $currentRole)) {
        http_response_code(403);
        exit('403 Forbidden: Access denied.');
    }
}
