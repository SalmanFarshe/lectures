<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Public — Registration Request
|--------------------------------------------------------------------------
| A visitor submits a registration request (name, username, class, password).
| The request is stored as PENDING in account_requests; an admin reviews and
| approves it on the Students page, which then creates the real account.
| No account is created here. CSRF protected.
*/

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

// Already logged in? Send them to their dashboard.
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
$oldFullName = '';
$oldUsername = '';

// Active classes for the dropdown.
$classOptions = $pdo->query(
    'SELECT id, name FROM classes WHERE is_active = 1 ORDER BY name'
)->fetchAll();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // CSRF
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || $submitted === '' || !hash_equals($_SESSION['login_csrf'] ?? '', $submitted)) {
        http_response_code(403);
        $error = 'Invalid request. Page refresh করে আবার চেষ্টা করো।';
    } else {
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $classInput = $_POST['class_id'] ?? '';
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        $oldFullName = $fullName;
        $oldUsername = $username;

        $classId = null;
        if ($classInput !== '') {
            $ci = filter_var($classInput, FILTER_VALIDATE_INT);
            if ($ci && $ci > 0) {
                $classId = $ci;
            } else {
                $error = 'সঠিক Class নির্বাচন করো।';
            }
        }

        if ($error === '' && $fullName === '') {
            $error = 'তোমার পুরো নাম লিখো।';
        } elseif ($error === '' && strlen($fullName) > 120) {
            $error = 'নাম সর্বোচ্চ ১২০ অক্ষরের মধ্যে হতে হবে।';
        }

        if ($error === '' && !preg_match('/^[a-zA-Z0-9_.-]{4,50}$/', $username)) {
            $error = 'Username ৪-৫০ অক্ষর হতে হবে (letters, numbers, dot, underscore, hyphen)।';
        }

        if ($error === '' && $classId === null) {
            $error = 'তোমার Class নির্বাচন করো।';
        }

        if ($error === '' && $password === '') {
            $error = 'Password দিতে হবে।';
        } elseif ($error === '' && $password !== $confirm) {
            $error = 'Password দুটো মিলছে না।';
        }

        // Username must not already exist as a real account.
        if ($error === '') {
            $dupe = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
            $dupe->execute([$username]);
            if ((int)$dupe->fetchColumn() > 0) {
                $error = 'এই Username ইতিমধ্যে ব্যবহৃত হয়েছে। অন্য একটি বেছে নাও।';
            }
        }

        // Username must not already have a pending request.
        if ($error === '') {
            $pend = $pdo->prepare(
                "SELECT COUNT(*) FROM account_requests WHERE username = ? AND status = 'pending'"
            );
            $pend->execute([$username]);
            if ((int)$pend->fetchColumn() > 0) {
                $error = 'এই Username-এ আগে থেকেই একটি Request পাঠানো হয়েছে। Admin-এর অপেক্ষা করো।';
            }
        }

        if ($error === '') {
            $ins = $pdo->prepare(
                "INSERT INTO account_requests
                    (type, full_name, username, class_id, password_hash, status)
                 VALUES
                    ('register', ?, ?, ?, ?, 'pending')"
            );
            $ins->execute([
                $fullName,
                $username,
                $classId,
                password_hash($password, PASSWORD_DEFAULT),
            ]);

            $success = 'তোমার Registration Request পাঠানো হয়েছে! Admin অনুমোদন করলে তোমার Account তৈরি হবে।';
            $oldFullName = '';
            $oldUsername = '';
        }
    }
}

$pageTitle = 'Create Account';
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
        <span class="eyebrow">NEW STUDENT</span>
        <h1>Create your account</h1>
        <p>Registration Request পাঠাও। Admin অনুমোদন করলে Account তৈরি হবে।</p>
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

      <form method="post" action="/lectures/register.php">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['login_csrf']) ?>">

        <div class="form-group">
          <label for="full_name">পুরো নাম</label>
          <input id="full_name" name="full_name" type="text" value="<?= e($oldFullName) ?>" maxlength="120"
            autocomplete="name" placeholder="তোমার পুরো নাম" required>
        </div>

        <div class="form-group">
          <label for="username">Username</label>
          <input id="username" name="username" type="text" value="<?= e($oldUsername) ?>" maxlength="50"
            autocomplete="username" placeholder="letters, numbers, dot, underscore" required>
        </div>

        <div class="form-group">
          <label for="class_id">Class</label>
          <select id="class_id" name="class_id" required>
            <option value="">— Class নির্বাচন করো —</option>
            <?php foreach ($classOptions as $co): ?>
              <option value="<?= (int)$co['id'] ?>"><?= e((string)$co['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" autocomplete="new-password" placeholder="তোমার Password" required>
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password"
            placeholder="আবার Password লিখো" required>
        </div>

        <button type="submit" class="login-button">
          <span>Registration Request পাঠাও</span>
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 12h14" />
            <path d="m12 5 7 7-7 7" />
          </svg>
        </button>
      </form>

      <div class="login-sub">
        Account আছে? <a href="/lectures/index.php">Login করো</a>
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

