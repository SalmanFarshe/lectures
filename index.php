<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bootstrap
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

/*
|--------------------------------------------------------------------------
| Redirect Already Authenticated Users
|--------------------------------------------------------------------------
*/

if (!empty($_SESSION['user_id'])) {
  if (($_SESSION['role'] ?? '') === 'admin') {
    redirect('/lectures/admin/index.php');
  }

  redirect('/lectures/student/index.php');
}

/*
|--------------------------------------------------------------------------
| Login CSRF Token
|--------------------------------------------------------------------------
*/

if (
  !isset($_SESSION['login_csrf']) ||
  !is_string($_SESSION['login_csrf']) ||
  $_SESSION['login_csrf'] === ''
) {
  $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| Login Processing
|--------------------------------------------------------------------------
*/

$error = '';
$username = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  $submittedToken = $_POST['csrf_token'] ?? '';
  $sessionToken = $_SESSION['login_csrf'] ?? '';

  if (
    !is_string($submittedToken) ||
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $submittedToken)
  ) {
    http_response_code(403);
    $error = 'Invalid request. Page refresh করে আবার চেষ্টা করো।';
  } else {
    $usernameInput = $_POST['username'] ?? '';
    $passwordInput = $_POST['password'] ?? '';

    $username = is_string($usernameInput)
      ? trim($usernameInput)
      : '';

    $password = is_string($passwordInput)
      ? $passwordInput
      : '';

    if ($username === '' || $password === '') {
      $error = 'Username ও password দুটোই দিতে হবে।';
    } else {
      $stmt = $pdo->prepare(
        'SELECT id, username, password_hash, full_name,
                        role, class_id, is_active
                 FROM users
                 WHERE username = :username
                 LIMIT 1'
      );

      $stmt->execute([
        'username' => $username,
      ]);

      $user = $stmt->fetch();

      $validUser = is_array($user)
        && (int) $user['is_active'] === 1
        && password_verify(
          $password,
          (string) $user['password_hash']
        );

      if ($validUser) {
        /*
                 * Only allow recognized application roles.
                 */
        $role = (string) $user['role'];

        if (!in_array($role, ['admin', 'student'], true)) {
          $error = 'Account role is not permitted.';
        } else {
          /*
                     * Prevent session fixation.
                     */
          if (!session_regenerate_id(true)) {
            http_response_code(500);
            exit('Unable to establish a secure session.');
          }

          $_SESSION['user_id'] = (int) $user['id'];
          $_SESSION['username'] = (string) $user['username'];
          $_SESSION['full_name'] = (string) $user['full_name'];
          $_SESSION['role'] = $role;
          $_SESSION['class_id'] = $user['class_id'];

          /*
                     * Rotate authentication-related tokens.
                     */
          unset($_SESSION['login_csrf']);

          $_SESSION['logout_csrf'] = bin2hex(
            random_bytes(32)
          );

          if ($role === 'admin') {
            redirect('/lectures/admin/index.php');
          }

          redirect('/lectures/student/index.php');
        }
      } else {
        $error = 'Username অথবা password সঠিক নয়।';
      }
    }
  }
}

/*
|--------------------------------------------------------------------------
| Page Metadata
|--------------------------------------------------------------------------
*/

$pageTitle = 'Login';
?>

<!DOCTYPE html>
<html lang="bn">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title><?= e($pageTitle) ?> | Lectures</title>

  <link
    rel="stylesheet"
    href="/lectures/assets/css/login.css">
</head>

<body>

  <div class="login-page">

    <div class="login-decoration decoration-one"></div>
    <div class="login-decoration decoration-two"></div>

    <main class="login-card">

      <a href="/lectures/index.php" class="login-brand">
        <span class="login-brand-icon">
          <svg
            width="26"
            height="26"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z" />
            <path d="M8 7h8" />
            <path d="M8 11h6" />
          </svg>
        </span>

        <span>Lectures<span class="brand-dot">.</span></span>
      </a>

      <div class="login-heading">
        <span class="eyebrow">WELCOME BACK</span>

        <h1>Login to your account</h1>

        <p>তোমার account দিয়ে login করে পড়াশোনা শুরু করো।</p>
      </div>

      <?php if ($error !== ''): ?>
        <div class="login-error" role="alert">
          <span class="error-icon" aria-hidden="true">!</span>
          <span><?= e($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="post" action="/lectures/index.php">

        <input
          type="hidden"
          name="csrf_token"
          value="<?= e($_SESSION['login_csrf']) ?>">

        <div class="form-group">
          <label for="username">Username</label>

          <input
            id="username"
            name="username"
            type="text"
            value="<?= e($username) ?>"
            maxlength="50"
            autocomplete="username"
            placeholder="Enter your username"
            required
            autofocus>
        </div>

        <div class="form-group">
          <label for="password">Password</label>

          <input
            id="password"
            name="password"
            type="password"
            autocomplete="current-password"
            placeholder="Enter your password"
            required>
        </div>

        <button type="submit" class="login-button">
          <span>Login to Dashboard</span>

          <svg
            width="19"
            height="19"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true">
            <path d="M5 12h14" />
            <path d="m12 5 7 7-7 7" />
          </svg>
        </button>

      </form>

      <div class="login-footer">
        <span class="footer-dot"></span>
        Secure Student Learning Portal
      </div>

    </main>

    <p class="copyright">
      &copy; <?= date('Y') ?> Lectures. Learn. Practice. Succeed.
    </p>

  </div>

</body>

</html>