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
require_once __DIR__ . '/../config/database.php';

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

/*
|--------------------------------------------------------------------------
| Remember Me Token Helpers
|--------------------------------------------------------------------------
| Persistent login tokens let users stay signed in across browser restarts.
| Tokens are stored hashed in `remember_tokens` and validated via
| password_verify() so a DB leak never exposes raw tokens.
*/

function set_remember_token(int $userId, int $days = 30): void
{
    global $pdo;

    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = password_hash($rawToken, PASSWORD_DEFAULT);
    $expiresAt = date('Y-m-d H:i:s', time() + ($days * 86400));

    // Revoke any existing active token for this user (one device at a time).
    $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = :user_id')
        ->execute(['user_id' => $userId]);

    $pdo->prepare(
        'INSERT INTO remember_tokens (user_id, token_hash, expires_at)
         VALUES (:user_id, :token_hash, :expires_at)'
    )->execute([
        'user_id' => $userId,
        'token_hash' => $tokenHash,
        'expires_at' => $expiresAt,
    ]);

    $cookieParams = session_get_cookie_params();
    setcookie(
        'remember_token',
        $rawToken,
        [
            'expires' => time() + ($days * 86400),
            'path' => $cookieParams['path'] ?? '/',
            'domain' => $cookieParams['domain'] ?? '',
            'secure' => $cookieParams['secure'] ?? false,
            'httponly' => true,
            'samesite' => $cookieParams['samesite'] ?? 'Lax',
        ]
    );
}

function clear_remember_token(): void
{
    global $pdo;

    $rawToken = $_COOKIE['remember_token'] ?? '';
    if ($rawToken !== '') {
        // Find and delete the matching token row by verifying hashes.
        $stmt = $pdo->prepare('SELECT id, user_id, token_hash FROM remember_tokens');
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            if (password_verify($rawToken, (string) $row['token_hash'])) {
                $pdo->prepare('DELETE FROM remember_tokens WHERE id = :id')
                    ->execute(['id' => (int) $row['id']]);
                break;
            }
        }
    }

    $cookieParams = session_get_cookie_params();
    setcookie(
        'remember_token',
        '',
        [
            'expires' => time() - 42000,
            'path' => $cookieParams['path'] ?? '/',
            'domain' => $cookieParams['domain'] ?? '',
            'secure' => $cookieParams['secure'] ?? false,
            'httponly' => true,
            'samesite' => $cookieParams['samesite'] ?? 'Lax',
        ]
    );
}

function validate_remember_token(): ?array
{
    global $pdo;

    $rawToken = $_COOKIE['remember_token'] ?? '';
    if ($rawToken === '') {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT rt.id, rt.user_id, rt.token_hash, rt.expires_at, u.username, u.full_name, u.role, u.class_id, u.is_active
         FROM remember_tokens rt
         JOIN users u ON u.id = rt.user_id'
    );
    $stmt->execute();
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        if (!password_verify($rawToken, (string) $row['token_hash'])) {
            continue;
        }

        // Expired?
        $expiresAt = strtotime((string) $row['expires_at']);
        if ($expiresAt === false || time() > $expiresAt) {
            $pdo->prepare('DELETE FROM remember_tokens WHERE id = :id')
                ->execute(['id' => (int) $row['id']]);
            continue;
        }

        // Inactive user?
        if ((int) $row['is_active'] !== 1) {
            $pdo->prepare('DELETE FROM remember_tokens WHERE id = :id')
                ->execute(['id' => (int) $row['id']]);
            continue;
        }

        // Touch last_used_at.
        $pdo->prepare(
            'UPDATE remember_tokens SET last_used_at = CURRENT_TIMESTAMP WHERE id = :id'
        )->execute(['id' => (int) $row['id']]);

        return [
            'id' => (int) $row['user_id'],
            'username' => (string) $row['username'],
            'full_name' => (string) $row['full_name'],
            'role' => (string) $row['role'],
            'class_id' => $row['class_id'] ? (int) $row['class_id'] : null,
        ];
    }

    // Token not found or invalid — clear the dead cookie.
    clear_remember_token();
    return null;
}
