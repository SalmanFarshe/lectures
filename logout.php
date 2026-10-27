<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Authentication Bootstrap
|--------------------------------------------------------------------------
| Starts the session and loads shared authentication helpers.
*/

require_once __DIR__ . '/includes/auth.php';

/*
|--------------------------------------------------------------------------
| Request Method Validation
|--------------------------------------------------------------------------
*/

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method Not Allowed');
}

/*
|--------------------------------------------------------------------------
| CSRF Validation
|--------------------------------------------------------------------------
*/

$submittedToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['logout_csrf'] ?? '';

if (
    !is_string($submittedToken) ||
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $submittedToken)
) {
    http_response_code(403);
    exit('Invalid logout request.');
}

/*
|--------------------------------------------------------------------------
| Clear Session Data
|--------------------------------------------------------------------------
*/

$_SESSION = [];

/*
|--------------------------------------------------------------------------
| Remove Session Cookie
|--------------------------------------------------------------------------
*/

if (ini_get('session.use_cookies')) {
    $cookieParams = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 42000,
            'path' => $cookieParams['path'],
            'domain' => $cookieParams['domain'],
            'secure' => $cookieParams['secure'],
            'httponly' => $cookieParams['httponly'],
            'samesite' => $cookieParams['samesite'] ?? 'Lax',
        ]
    );
}

/*
|--------------------------------------------------------------------------
| Revoke Remember Me Token
|--------------------------------------------------------------------------
| Delete the persistent remember token (if any) so the user is fully
| signed out across devices/browser restarts.
*/

clear_remember_token();

/*
|--------------------------------------------------------------------------
| Destroy Session
|--------------------------------------------------------------------------
*/

session_destroy();

/*
|--------------------------------------------------------------------------
| Redirect to Login
|--------------------------------------------------------------------------
*/

redirect('/lectures/index.php');
