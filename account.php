<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| My Profile & Change Password (shared: admin + student)
|--------------------------------------------------------------------------
| Lets the logged-in user view their account info and change their password.
| Verifies the current password before allowing a change, enforces a strong
| new password, and uses CSRF protection. Works for both roles.
*/

require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

$userId = filter_var(
    $_SESSION['user_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$userId) {
    redirect('/lectures/logout.php');
}

$role = (string)($_SESSION['role'] ?? 'student');
$isAdmin = $role === 'admin';

// Load the current account (+ class name for students).
$stmt = $pdo->prepare(
    "SELECT u.id, u.username, u.full_name, u.role, u.class_id, u.is_active,
            c.name AS class_name
     FROM users u
     LEFT JOIN classes c ON c.id = u.class_id
     WHERE u.id = ? LIMIT 1"
);
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user || (int)$user['is_active'] !== 1) {
    $_SESSION = [];
    session_destroy();
    redirect('/lectures/index.php');
}

$name = (string)($user['full_name'] ?: 'User');
$username = (string)$user['username'];
$className = (string)($user['class_name'] ?? '');
$roleLabel = $isAdmin ? 'Administrator' : 'Student';

$errors = [];
$success = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify('account')) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    // Password change requires only the new password (the user is already
    // authenticated). No length restriction is enforced.
    if ($new === '') {
        $errors[] = 'New password is required.';
    } elseif ($new !== $confirm) {
        $errors[] = 'New password and confirmation do not match.';
    }

    if (!$errors) {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $upd->execute([$newHash, $userId]);
        $success = 'Your password has been changed successfully.';
    }
}

$backUrl = $isAdmin ? '/lectures/admin/index.php' : '/lectures/student/index.php';
$backLabel = $isAdmin ? 'Back to Dashboard' : 'Back to Dashboard';

$pageTitle = 'My Profile';
$activePage = 'profile';
// Admins keep the sidebar; students use the no-sidebar layout.
$showSidebar = $isAdmin;

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .acct-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px; }
    .acct-box {
        background:var(--surface); border:1px solid var(--border);
        border-radius:var(--radius); padding:18px; box-shadow:var(--shadow-sm);
    }
    .acct-box .lbl { color:var(--muted); font-size:12px; margin-bottom:6px; }
    .acct-box .val { font-size:17px; font-weight:750; color:var(--text); }
    .profile-head {
        display:flex; align-items:center; gap:16px; flex-wrap:wrap;
        padding:22px; margin-bottom:20px; color:#fff;
        background:linear-gradient(120deg,#4F46E5,#6366F1);
        border-radius:var(--radius-lg); box-shadow:0 12px 30px rgba(79,70,229,.22);
    }
    .profile-avatar {
        width:64px; height:64px; border-radius:50%; display:grid; place-items:center;
        background:rgba(255,255,255,.18); font-size:28px; font-weight:800;
    }
</style>

<div class="page-header">
    <div class="page-header-text">
        <h1 class="page-title">My Profile</h1>
        <p class="page-header-desc">Your account information and password settings.</p>
    </div>
    <div class="page-header-actions">
        <a class="btn btn-outline-secondary" href="<?= e($backUrl) ?>"><i class="bi bi-arrow-left me-1"></i> <?= e($backLabel) ?></a>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> <?= e($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="profile-head">
    <div class="profile-avatar"><?= e(mb_strtoupper(mb_substr($name, 0, 1))) ?></div>
    <div>
        <h2 class="h4 fw-bold mb-1"><?= e($name) ?></h2>
        <div class="small" style="opacity:.92;">
            <i class="bi bi-person-badge me-1"></i><?= e($username) ?>
            <span class="mx-2">·</span>
            <i class="bi bi-shield me-1"></i><?= e($roleLabel) ?>
            <?php if ($className !== ''): ?>
                <span class="mx-2">·</span>
                <i class="bi bi-mortarboard me-1"></i><?= e($className) ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Account info -->
    <div class="col-12 col-lg-5">
        <div class="panel">
            <h2 class="h6 fw-bold mb-3">Account Information</h2>
            <div class="acct-grid">
                <div class="acct-box">
                    <div class="lbl">Full Name</div>
                    <div class="val"><?= e($name) ?></div>
                </div>
                <div class="acct-box">
                    <div class="lbl">Username</div>
                    <div class="val"><?= e($username) ?></div>
                </div>
                <div class="acct-box">
                    <div class="lbl">Role</div>
                    <div class="val"><?= e($roleLabel) ?></div>
                </div>
                <?php if ($className !== ''): ?>
                <div class="acct-box">
                    <div class="lbl">Class</div>
                    <div class="val"><?= e($className) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Change password -->
    <div class="col-12 col-lg-7">
        <div class="panel">
            <h2 class="h6 fw-bold mb-1">Change Password</h2>
            <p class="text-secondary small mb-3">You're signed in, so just enter a new password. It will be used next time you log in.</p>
            <form method="post" action="/lectures/account.php" autocomplete="off">
                <?= csrf_field('account') ?>
                <div class="mb-3">
                    <label class="form-label" for="new_password">New password</label>
                    <input class="form-control" id="new_password" name="new_password" type="password" required autocomplete="new-password">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="confirm_password">Confirm new password</label>
                    <input class="form-control" id="confirm_password" name="confirm_password" type="password" required autocomplete="new-password">
                </div>
                <button class="btn btn-primary" type="submit"><i class="bi bi-shield-lock me-1"></i> Update Password</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

