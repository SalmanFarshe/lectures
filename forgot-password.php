<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Public — Password Reset Request
|--------------------------------------------------------------------------
| A visitor submits a password-reset request for an existing account
| (username + new password + confirm). The request is stored as PENDING in
| account_requests; an admin approves it on the Students page, which then
| applies the new password to the real account. No password is changed here.
| CSRF protected.
*/

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

if (!empty($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        redirect('/lectures/admin/navigator.php');
    }
    redirect('/lectures/student/index.php');
}

// Ensure a login CSRF token exists (shared with the login page).
if (!isset($_SESSION['login_csrf']) || !is_string($_SESSION['login_csrf']) || $_SESSION['login_csrf'] === '') {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';
$oldUsername = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || $submitted === '' || !hash_equals($_SESSION['login_csrf'] ?? '', $submitted)) {
        http_response_code(403);
        $error = 'Invalid request. Page refresh করে আবার চেষ্টা করো।';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        $oldUsername = $username;

        if ($username === '') {
            $error = 'তোমার Username লিখো।';
        }

        if ($error === '' && $password === '') {
            $error = 'নতুন Password দিতে হবে।';
        } elseif ($error === '' && $password !== $confirm) {
            $error = 'Password দুটো মিলছে না।';
        }

        // The account must exist and be active.
        if ($error === '') {
            $chk = $pdo->prepare(
                "SELECT COUNT(*) FROM users WHERE username = ? AND role = 'student' AND is_active = 1"
            );
            $chk->execute([$username]);
            if ((int)$chk->fetchColumn() === 0) {
                $error = 'এই Username দিয়ে কোনো Active Student Account পাওয়া যায়নি।';
            }
        }

        // No duplicate pending reset for the same username.
        if ($error === '') {
            $pend = $pdo->prepare(
                "SELECT COUNT(*) FROM account_requests
                 WHERE username = ? AND type = 'password_reset' AND status = 'pending'"
            );
            $pend->execute([$username]);
            if ((int)$pend->fetchColumn() > 0) {
                $error = 'এই Username-এ আগে থেকেই একটি Reset Request আছে। Admin-এর অপেক্ষা করো।';
            }
        }

        if ($error === '') {
            $ins = $pdo->prepare(
                "INSERT INTO account_requests
                    (type, full_name, username, password_hash, status)
                 VALUES
                    ('password_reset', NULL, ?, ?, 'pending')"
            );
            $ins->execute([
                $username,
                password_hash($password, PASSWORD_DEFAULT),
            ]);

            $success = 'তোমার Password Reset Request পাঠানো হয়েছে! Admin অনুমোদন করলে নতুন Password কার্যকর হবে।';
            $oldUsername = '';
        }
    }
}

$pageTitle = 'Forgot Password';
?>
<!DOCTYPE html>
<html lang="bn">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | Lectures</title>
  <link rel="stylesheet" href="/lectures/assets/css/login.css">
</head>

<body>
  <div class="login-page">
    <div class="login-decoration decoration-one"></div>
    <div class="login-decoration decoration-two"></div>

    <main class="login-card">
      <a href="/lectures/index.php" class="login-brand">
        <span class="login-brand-icon">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z" />
            <path d="M8 7h8" />
            <path d="M8 11h6" />
          </svg>
        </span>
        <span>Lectures<span class="brand-dot">.</span></span>
      </a>

      <div class="login-heading">
        <span class="eyebrow">ACCOUNT RECOVERY</span>
        <h1>Reset your password</h1>
        <p>Username ও নতুন Password দাও। Admin অনুমোদন করলে Password পরিবর্তিত হবে।</p>
      </div>

      <?php if ($error !== ''): ?>
        <div class="login-error" role="alert">
          <span class="error-icon" aria-hidden="true">!</span>
          <span><?= e($error) ?></span>
        </div>
      <?php endif; ?>

      <?php if ($success !== ''): ?>
        <div class="login-success" role="alert">
          <span class="success-icon" aria-hidden="true">&#10003;</span>
          <span><?= e($success) ?></span>
        </div>
      <?php endif; ?>

      <form method="post" action="/lectures/forgot-password.php">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['login_csrf']) ?>">

        <div class="form-group">
          <label for="username">Username</label>
          <input id="username" name="username" type="text" value="<?= e($oldUsername) ?>" maxlength="50"
            autocomplete="username" placeholder="তোমার Username" required>
        </div>

        <div class="form-group">
          <label for="password">নতুন Password</label>
          <input id="password" name="password" type="password" autocomplete="new-password" placeholder="নতুন Password" required>
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password"
            placeholder="আবার নতুন Password লিখো" required>
        </div>

        <button type="submit" class="login-button">
          <span>Reset Request পাঠাও</span>
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 12h14" />
            <path d="m12 5 7 7-7 7" />
          </svg>
        </button>
      </form>

      <div class="login-sub">
        <a href="/lectures/index.php">← Login এ ফিরে যাও</a>
      </div>

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

