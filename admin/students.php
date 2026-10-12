<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function goBack(string $message, string $type = 'success'): never
{
    $_SESSION['student_flash'] = [
        'message' => $message,
        'type' => $type,
    ];

    header('Location: /lectures/admin/students.php');
    exit;
}

$classes = $pdo->query(
    "SELECT id, name FROM classes
     WHERE is_active = 1
     ORDER BY name"
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit('Invalid request. Please reload the page.');
    }

    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'toggle_status') {
            $id = filter_var(
                $_POST['student_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                goBack('Invalid student account.', 'danger');
            }

            $stmt = $pdo->prepare(
                "UPDATE users
                 SET is_active = IF(is_active = 1, 0, 1)
                 WHERE id = :id AND role = 'student'"
            );
            $stmt->execute(['id' => $id]);

            goBack(
                $stmt->rowCount()
                    ? 'Student status updated successfully.'
                    : 'Student account was not found.',
                $stmt->rowCount() ? 'success' : 'warning'
            );
        }

        if ($action === 'save_student') {
            $idValue = $_POST['student_id'] ?? '';
            $id = null;

            if ($idValue !== '') {
                $id = filter_var($idValue, FILTER_VALIDATE_INT);

                if (!$id || $id < 1) {
                    goBack('Invalid student ID.', 'danger');
                }
            }

            $fullName = trim((string) ($_POST['full_name'] ?? ''));
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $classInput = $_POST['class_id'] ?? '';
            $classId = null;

            if ($fullName === '' || strlen($fullName) > 120) {
                goBack('Enter a valid full name.', 'danger');
            }

            if (!preg_match('/^[a-zA-Z0-9_.-]{4,50}$/', $username)) {
                goBack(
                    'Username must be 4-50 characters using letters, numbers, dot, underscore or hyphen.',
                    'danger'
                );
            }

            if ($classInput !== '') {
                $classId = filter_var(
                    $classInput,
                    FILTER_VALIDATE_INT
                );

                if (!$classId || $classId < 1) {
                    goBack('Invalid class selected.', 'danger');
                }

                $checkClass = $pdo->prepare(
                    "SELECT id FROM classes
                     WHERE id = :id AND is_active = 1"
                );
                $checkClass->execute(['id' => $classId]);

                if (!$checkClass->fetch()) {
                    goBack('Selected class is unavailable.', 'danger');
                }
            }

            if ($id === null) {
                if (strlen($password) < 12) {
                    goBack(
                        'New student password must be at least 12 characters.',
                        'danger'
                    );
                }

                $stmt = $pdo->prepare(
                    "INSERT INTO users
                        (username, password_hash, full_name, role, class_id, is_active)
                     VALUES
                        (:username, :password_hash, :full_name, 'student', :class_id, 1)"
                );

                $stmt->execute([
                    'username' => $username,
                    'password_hash' => password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    ),
                    'full_name' => $fullName,
                    'class_id' => $classId,
                ]);

                goBack('Student account created successfully.');
            }

            // Verify that this is a student, never an admin.
            $checkStudent = $pdo->prepare(
                "SELECT id FROM users
                 WHERE id = :id AND role = 'student'"
            );
            $checkStudent->execute(['id' => $id]);

            if (!$checkStudent->fetch()) {
                goBack('Student account not found.', 'danger');
            }

            if ($password !== '' && strlen($password) < 12) {
                goBack(
                    'New password must be at least 12 characters.',
                    'danger'
                );
            }

            if ($password !== '') {
                $stmt = $pdo->prepare(
                    "UPDATE users
                     SET username = :username,
                         full_name = :full_name,
                         class_id = :class_id,
                         password_hash = :password_hash
                     WHERE id = :id AND role = 'student'"
                );

                $stmt->execute([
                    'username' => $username,
                    'full_name' => $fullName,
                    'class_id' => $classId,
                    'password_hash' => password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    ),
                    'id' => $id,
                ]);

                goBack('Student details and password updated.');
            }

            $stmt = $pdo->prepare(
                "UPDATE users
                 SET username = :username,
                     full_name = :full_name,
                     class_id = :class_id
                 WHERE id = :id AND role = 'student'"
            );

            $stmt->execute([
                'username' => $username,
                'full_name' => $fullName,
                'class_id' => $classId,
                'id' => $id,
            ]);

            goBack('Student details updated successfully.');
        }

        /*
        | Account requests (registration + password reset) — admin review.
        */
        if ($action === 'approve_request') {
            $requestId = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);

            if (!$requestId || $requestId < 1) {
                goBack('Invalid request ID.', 'danger');
            }

            $reqStmt = $pdo->prepare('SELECT * FROM account_requests WHERE id = ? AND status = \'pending\' LIMIT 1');
            $reqStmt->execute([$requestId]);
            $req = $reqStmt->fetch();

            if (!$req) {
                goBack('Request not found or already processed.', 'warning');
            }

            if ((string)$req['type'] === 'register') {
                // Create the student account from the approved registration.
                $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
                $check->execute([(string)$req['username']]);
                if ((int)$check->fetchColumn() > 0) {
                    goBack('A user with this username already exists.', 'danger');
                }

                $create = $pdo->prepare(
                    "INSERT INTO users (username, password_hash, full_name, role, class_id, is_active)
                     VALUES (?, ?, ?, 'student', ?, 1)"
                );
                $create->execute([
                    (string)$req['username'],
                    (string)$req['password_hash'],
                    (string)$req['full_name'],
                    $req['class_id'] !== null ? (int)$req['class_id'] : null,
                ]);
            } else {
                // password_reset: apply the requested password hash.
                $apply = $pdo->prepare(
                    "UPDATE users SET password_hash = ?
                     WHERE username = ? AND role = 'student' AND is_active = 1"
                );
                $apply->execute([(string)$req['password_hash'], (string)$req['username']]);

                if ($apply->rowCount() === 0) {
                    goBack('No active account found for this reset request.', 'danger');
                }
            }

            $mark = $pdo->prepare(
                "UPDATE account_requests
                 SET status = 'approved', reviewed_at = NOW(), reviewed_by = ?
                 WHERE id = ?"
            );
            $mark->execute([(int)$_SESSION['user_id'], $requestId]);

            goBack('Request approved successfully.');
        }

        if ($action === 'reject_request') {
            $requestId = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);

            if (!$requestId || $requestId < 1) {
                goBack('Invalid request ID.', 'danger');
            }

            $mark = $pdo->prepare(
                "UPDATE account_requests
                 SET status = 'rejected', reviewed_at = NOW(), reviewed_by = ?
                 WHERE id = ? AND status = 'pending'"
            );
            $mark->execute([(int)$_SESSION['user_id'], $requestId]);

            goBack(
                $mark->rowCount() ? 'Request rejected.' : 'Request not found or already processed.',
                $mark->rowCount() ? 'warning' : 'warning'
            );
        }

        goBack('Unknown action.', 'danger');
    } catch (PDOException $exception) {
        error_log($exception->getMessage());

        if ($exception->getCode() === '23000') {
            goBack(
                'This username already exists. Please choose another.',
                'danger'
            );
        }

        goBack('Database operation failed. Please try again.', 'danger');
    }
}

$search = trim((string) ($_GET['search'] ?? ''));
$status = (string) ($_GET['status'] ?? 'all');

$sql = "
    SELECT u.id, u.username, u.full_name, u.class_id,
           u.is_active, u.created_at, c.name AS class_name
    FROM users u
    LEFT JOIN classes c ON c.id = u.class_id
    WHERE u.role = 'student'
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            u.full_name LIKE :search_name
            OR u.username LIKE :search_username
            OR c.name LIKE :search_class
        )
    ";

    $params['search_name'] = "%{$search}%";
    $params['search_username'] = "%{$search}%";
    $params['search_class'] = "%{$search}%";
}

if ($status === 'active') {
    $sql .= ' AND u.is_active = 1';
} elseif ($status === 'inactive') {
    $sql .= ' AND u.is_active = 0';
}

$sql .= ' ORDER BY u.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$totalStudents = (int) $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'student'"
)->fetchColumn();

$activeStudents = (int) $pdo->query(
    "SELECT COUNT(*) FROM users
     WHERE role = 'student' AND is_active = 1"
)->fetchColumn();

$inactiveStudents = $totalStudents - $activeStudents;

/*
| Pending account requests (registration + password reset) awaiting review.
*/
$pendingRequests = $pdo->query(
    "SELECT ar.id, ar.type, ar.full_name, ar.username, ar.created_at,
            c.name AS class_name
     FROM account_requests ar
     LEFT JOIN classes c ON c.id = ar.class_id
     WHERE ar.status = 'pending'
     ORDER BY ar.created_at ASC"
)->fetchAll();

$pendingRequestCount = count($pendingRequests);

$flash = $_SESSION['student_flash'] ?? null;
unset($_SESSION['student_flash']);

$adminName = (string) ($_SESSION['full_name'] ?? 'Administrator');

$pageTitle = 'Manage Students';
$activePage = 'students';

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page-specific components (harmonized with the shared design tokens) -->
<style>
    /* Compatibility alias used by the original component styles */
    :root {
        --line: var(--border);
    }

    .eyebrow {
        color: var(--primary);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 1.4px;
        text-transform: uppercase;
    }

    .page-heading {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 26px;
    }

    .page-heading h1 {
        font-size: clamp(24px, 3vw, 30px);
        font-weight: 800;
        letter-spacing: -0.8px;
        margin: 8px 0 0;
    }

    .page-description {
        color: var(--muted);
        font-size: 13px;
        margin: 8px 0 0;
    }

    .btn-premium {
        background: var(--primary);
        color: #fff;
        border: 0;
        border-radius: var(--radius-sm);
        padding: 11px 18px;
        font-size: 13px;
        font-weight: 700;
        box-shadow: 0 6px 14px rgba(79, 70, 229, 0.22);
        transition: transform .2s, box-shadow .2s, background .2s;
    }

    .btn-premium:hover {
        color: #fff;
        background: var(--primary-hover);
        transform: translateY(-1px);
        box-shadow: 0 9px 20px rgba(79, 70, 229, 0.32);
    }

    .stat-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 20px;
        height: 100%;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: var(--shadow-sm);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        border-radius: var(--radius-sm);
        font-size: 22px;
    }

    .stat-icon.purple { color: var(--primary); background: var(--primary-soft); }
    .stat-icon.green  { color: var(--success); background: var(--success-soft); }
    .stat-icon.orange { color: var(--warning); background: var(--warning-soft); }

    .stat-label {
        color: var(--muted);
        font-size: 12px;
        margin-bottom: 6px;
    }

    .stat-number {
        font-size: 26px;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -0.7px;
        color: var(--text);
    }

    .stat-note {
        color: var(--muted);
        font-size: 10px;
        margin-top: 7px;
    }

    .table-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        margin-top: 26px;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .table-card-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        padding: 20px 24px;
        border-bottom: 1px solid var(--border);
    }

    .section-title {
        font-size: 16px;
        font-weight: 800;
        margin: 0;
        color: var(--text);
    }

    .section-subtitle {
        color: var(--muted);
        font-size: 11px;
        margin-top: 5px;
    }

    .filter-form {
        display: flex;
        gap: 9px;
        align-items: center;
        flex-wrap: wrap;
    }

    .search-box {
        position: relative;
    }

    .search-box i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--muted);
    }

    .search-box input {
        padding-left: 35px;
        width: 220px;
    }

    .filter-form .form-control,
    .filter-form .form-select {
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        font-size: 12px;
        min-height: 39px;
    }

    .filter-form .form-select {
        width: 130px;
    }

    .btn-filter {
        background: var(--primary-soft);
        color: var(--primary);
        border: 0;
        border-radius: var(--radius-sm);
        padding: 10px 13px;
        font-size: 12px;
        font-weight: 700;
    }

    .btn-filter:hover {
        background: #e0e7ff;
    }

    .table-responsive {
        padding: 0 15px 10px;
    }

    .student-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 760px;
    }

    .student-table thead th {
        color: var(--muted);
        background: var(--bg);
        padding: 15px 12px;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .8px;
        font-weight: 800;
        border-bottom: 1px solid var(--border);
    }

    .student-table tbody td {
        padding: 16px 12px;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
        font-size: 12px;
        color: var(--text);
    }

    .student-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .student-table tbody tr:hover {
        background: var(--bg);
    }

    .student-info {
        display: flex;
        align-items: center;
        gap: 11px;
    }

    .student-avatar {
        width: 39px;
        height: 39px;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        border-radius: var(--radius-sm);
        background: var(--primary-soft);
        color: var(--primary);
        font-size: 14px;
        font-weight: 800;
    }

    .student-name {
        font-weight: 750;
        color: var(--text);
        font-size: 12px;
    }

    .student-id {
        color: var(--muted);
        font-size: 10px;
        margin-top: 4px;
    }

    .username {
        color: var(--text);
        font-weight: 650;
    }

    .class-pill {
        display: inline-block;
        padding: 6px 10px;
        background: var(--primary-soft);
        color: var(--primary);
        border-radius: 7px;
        font-size: 10px;
        font-weight: 750;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 9px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 750;
    }

    .status-pill::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }

    .status-active {
        color: var(--success);
        background: var(--success-soft);
    }

    .status-inactive {
        color: var(--danger);
        background: var(--danger-soft);
    }

    .action-btn {
        width: 33px;
        height: 33px;
        border-radius: var(--radius-sm);
        display: inline-grid;
        place-items: center;
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--muted);
        transition: color .2s, background .2s, border-color .2s;
    }

    .action-btn:hover {
        color: var(--primary);
        background: var(--primary-soft);
        border-color: var(--primary);
    }

    .action-btn.danger:hover {
        color: var(--danger);
        background: var(--danger-soft);
        border-color: var(--danger);
    }

    .empty-state {
        padding: 50px 20px;
        text-align: center;
        color: var(--muted);
    }

    .empty-icon {
        width: 64px;
        height: 64px;
        display: grid;
        place-items: center;
        border-radius: var(--radius);
        background: var(--primary-soft);
        color: var(--primary);
        font-size: 27px;
        margin: 0 auto 15px;
    }

    .table-footer {
        padding: 15px 24px;
        border-top: 1px solid var(--border);
        color: var(--muted);
        font-size: 11px;
    }

    .modal-content {
        border: 0;
        border-radius: var(--radius-lg);
        overflow: hidden;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.22);
    }

    .modal-header-custom {
        padding: 22px 26px;
        color: #fff;
        background: linear-gradient(115deg, #3730A3, #4F46E5);
        border: 0;
    }

    .modal-header-custom .modal-title {
        font-weight: 800;
        font-size: 19px;
    }

    .modal-header-custom p {
        color: #DDD6FE;
        font-size: 12px;
        margin: 5px 0 0;
    }

    .modal-header-custom .btn-close {
        filter: invert(1);
    }

    .modal-body-custom {
        padding: 26px;
    }

    .form-label {
        font-size: 12px;
        color: var(--text);
        font-weight: 750;
        margin-bottom: 8px;
    }

    .modal-body-custom .form-control,
    .modal-body-custom .form-select {
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        min-height: 45px;
        font-size: 13px;
        box-shadow: none !important;
    }

    .modal-body-custom .form-control:focus,
    .modal-body-custom .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--focus-ring) !important;
    }

    .input-icon {
        position: relative;
    }

    .input-icon>i {
        position: absolute;
        left: 13px;
        top: 14px;
        color: var(--muted);
    }

    .input-icon .form-control {
        padding-left: 39px;
    }

    .form-hint {
        color: var(--muted);
        font-size: 10px;
        margin-top: 7px;
    }

    .modal-footer-custom {
        padding: 16px 26px 24px;
        border: 0;
        display: flex;
        justify-content: flex-end;
        gap: 9px;
    }

    .btn-light-custom {
        background: var(--bg);
        color: var(--text);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 11px 17px;
        font-size: 12px;
        font-weight: 750;
    }

    .toast {
        border: 0;
        border-radius: var(--radius-sm);
        box-shadow: 0 12px 35px rgba(15, 23, 42, 0.18);
    }

    .toast-header {
        border-bottom: 0;
        padding: 13px 15px 0;
    }

    .toast-body {
        padding: 10px 15px 15px;
        font-size: 13px;
    }

    @media (max-width: 900px) {
        .search-box input {
            width: 100%;
        }

        .page-heading {
            align-items: flex-start;
            flex-direction: column;
        }

        .page-heading .btn-premium {
            width: 100%;
        }

        .table-card-head {
            padding: 18px 16px;
        }

        .filter-form {
            width: 100%;
        }

        .search-box {
            flex: 1;
        }

        .filter-form .form-select {
            flex: 1;
        }

        .stat-card {
            padding: 16px;
        }

        .stat-number {
            font-size: 23px;
        }

        .modal-body-custom {
            padding: 20px;
        }
    }
</style>

<!-- Page Heading -->
<div class="page-heading">
    <div>
        <div class="eyebrow">People &amp; Accounts</div>
        <h1 class="page-title" style="margin:8px 0 0;">Student Management</h1>
        <p class="page-description">
            Manage student accounts, classes and login credentials.
        </p>
    </div>

    <button type="button" class="btn-premium" onclick="openStudentModal()">
        <i class="bi bi-plus-lg me-2"></i> Add New Student
    </button>
</div>

<!-- Pending Account Requests (registration + password reset) -->
<?php if ($pendingRequestCount > 0): ?>
<section class="table-card" style="border-left:4px solid var(--warning); margin-bottom:20px;">
    <div class="table-card-head">
        <div>
            <h2 class="section-title">
                <i class="bi bi-inbox me-1"></i> Pending Requests (<?= $pendingRequestCount ?>)
            </h2>
            <div class="section-subtitle">
                Registration and password-reset requests awaiting your review
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Class</th>
                    <th>Submitted</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingRequests as $req): ?>
                    <tr>
                        <td>
                            <?php if ((string)$req['type'] === 'register'): ?>
                                <span class="badge badge-soft-primary">Registration</span>
                            <?php else: ?>
                                <span class="badge badge-soft-warning">Password Reset</span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-bold"><?= e((string)($req['full_name'] ?? '—')) ?></td>
                        <td><?= e((string)$req['username']) ?></td>
                        <td><?= e((string)($req['class_name'] ?? '—')) ?></td>
                        <td class="small text-secondary"><?= e((string)$req['created_at']) ?></td>
                        <td class="text-end">
                            <form method="post" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="approve_request">
                                <input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-success"
                                    onclick="return confirm('Approve this request?');">
                                    <i class="bi bi-check-lg me-1"></i> Approve
                                </button>
                            </form>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="reject_request">
                                <input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Reject this request?');">
                                    <i class="bi bi-x-lg me-1"></i> Reject
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row g-3">
    <div class="col-12 col-md-4">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="stat-label">Total Students</div>
                <div class="stat-number"><?= $totalStudents ?></div>
                <div class="stat-note">All registered accounts</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-person-check-fill"></i></div>
            <div>
                <div class="stat-label">Active Students</div>
                <div class="stat-number"><?= $activeStudents ?></div>
                <div class="stat-note">Accounts enabled for login</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="bi bi-person-lock"></i></div>
            <div>
                <div class="stat-label">Inactive Students</div>
                <div class="stat-number"><?= $inactiveStudents ?></div>
                <div class="stat-note">Accounts currently disabled</div>
            </div>
        </div>
    </div>
</div>

<!-- Students Table -->
<section class="table-card">
    <div class="table-card-head">
        <div>
            <h2 class="section-title">All Students</h2>
            <div class="section-subtitle">
                Search and manage registered student accounts
            </div>
        </div>

        <form method="get" class="filter-form">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input class="form-control" name="search"
                    placeholder="Search students..."
                    value="<?= e($search) ?>">
            </div>

            <select class="form-select" name="status">
                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>
                    All Status
                </option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>
                    Active
                </option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>
                    Inactive
                </option>
            </select>

            <button class="btn-filter" type="submit">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
        </form>
    </div>

    <?php if ($students): ?>
        <div class="table-responsive">
            <table class="student-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Username</th>
                        <th>Class</th>
                        <th>Status</th>
                        <th>Account ID</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td>
                                <div class="student-info">
                                    <div class="student-avatar">
                                        <?= e(strtoupper(substr($s['full_name'], 0, 1))) ?>
                                    </div>
                                    <div>
                                        <div class="student-name">
                                            <?= e($s['full_name']) ?>
                                        </div>
                                        <div class="student-id">
                                            <?= (int) $s['is_active'] === 1
                                                ? 'Account enabled'
                                                : 'Account disabled' ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="username"><?= e($s['username']) ?></td>

                            <td>
                                <?php if (!empty($s['class_name'])): ?>
                                    <span class="class-pill"><?= e($s['class_name']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="status-pill <?= (int) $s['is_active'] === 1
                                                                ? 'status-active'
                                                                : 'status-inactive' ?>">
                                    <?= (int) $s['is_active'] === 1
                                        ? 'Active'
                                        : 'Inactive' ?>
                                </span>
                            </td>

                            <td class="text-muted">#<?= (int) $s['id'] ?></td>

                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <button type="button" class="action-btn"
                                        title="Edit student"
                                        aria-label="Edit student"
                                        data-id="<?= (int) $s['id'] ?>"
                                        data-name="<?= e($s['full_name']) ?>"
                                        data-username="<?= e($s['username']) ?>"
                                        data-class="<?= e((string) ($s['class_id'] ?? '')) ?>"
                                        onclick="editStudent(this)">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <form method="post" class="d-inline"
                                        onsubmit="return confirm('Change this student account status?')">
                                        <input type="hidden" name="csrf_token"
                                            value="<?= e($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="action"
                                            value="toggle_status">
                                        <input type="hidden" name="student_id"
                                            value="<?= (int) $s['id'] ?>">

                                        <button type="submit"
                                            class="action-btn <?= (int) $s['is_active'] === 1 ? 'danger' : '' ?>"
                                            title="<?= (int) $s['is_active'] === 1
                                                                ? 'Deactivate student'
                                                                : 'Activate student' ?>">
                                            <i class="bi <?= (int) $s['is_active'] === 1
                                                                ? 'bi-person-dash'
                                                                : 'bi-person-check' ?>"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="bi bi-people"></i></div>
            <h5 class="fw-bold">No students found</h5>
            <p class="small mb-3">
                Try a different search or create your first student account.
            </p>
            <button type="button" class="btn-premium" onclick="openStudentModal()">
                <i class="bi bi-plus-lg me-1"></i> Add Student
            </button>
        </div>
    <?php endif; ?>

    <div class="table-footer d-flex justify-content-between flex-wrap gap-2">
        <span>Showing <?= count($students) ?> matching student(s)</span>
        <span><i class="bi bi-shield-check me-1"></i> Secure account management</span>
    </div>
</section>

<!-- Add / Edit Student Modal -->
<div class="modal fade" id="studentModal" tabindex="-1"
    aria-labelledby="studentModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" id="studentForm">
                <input type="hidden" name="csrf_token"
                    value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="save_student">
                <input type="hidden" name="student_id" id="student_id">

                <div class="modal-header modal-header-custom">
                    <div>
                        <h2 class="modal-title" id="studentModalTitle">
                            Add New Student
                        </h2>
                        <p id="studentModalSubtitle">
                            Create a new student account.
                        </p>
                    </div>
                    <button type="button" class="btn-close"
                        data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body modal-body-custom">
                    <div class="mb-3">
                        <label class="form-label" for="full_name">Full Name</label>
                        <div class="input-icon">
                            <i class="bi bi-person"></i>
                            <input class="form-control" id="full_name"
                                name="full_name" type="text"
                                maxlength="120" placeholder="Enter student's full name"
                                required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="username">Username</label>
                        <div class="input-icon">
                            <i class="bi bi-at"></i>
                            <input class="form-control" id="username"
                                name="username" type="text"
                                minlength="4" maxlength="50"
                                pattern="[a-zA-Z0-9_.-]+"
                                placeholder="e.g. student_2026" required>
                        </div>
                        <div class="form-hint">
                            4–50 characters. Letters, numbers, dot, underscore or hyphen.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="class_id">Assign Class</label>
                        <div class="input-icon">
                            <i class="bi bi-mortarboard"></i>
                            <select class="form-select" id="class_id" name="class_id">
                                <option value="">Not assigned yet</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= (int) $class['id'] ?>">
                                        <?= e($class['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (!$classes): ?>
                            <div class="form-hint text-warning">
                                No classes yet. You can assign one later from Class Management.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-2">
                        <label class="form-label" for="password" id="passwordLabel">
                            Initial Password
                        </label>
                        <div class="input-icon">
                            <i class="bi bi-shield-lock"></i>
                            <input class="form-control" id="password"
                                name="password" type="password"
                                minlength="12" autocomplete="new-password"
                                placeholder="Enter a strong password">
                        </div>
                        <div class="form-hint" id="passwordHint">
                            At least 12 characters. The password is stored securely as a hash.
                        </div>
                    </div>
                </div>

                <div class="modal-footer-custom">
                    <button type="button" class="btn-light-custom"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-premium" id="saveButton">
                        <i class="bi bi-check2-circle me-1"></i> Create Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2000">
    <div id="appToast" class="toast" role="status" aria-live="polite"
        aria-atomic="true">
        <div class="toast-header">
            <i id="toastIcon" class="bi bi-check-circle-fill text-success me-2"></i>
            <strong class="me-auto" id="toastTitle">Notification</strong>
            <button type="button" class="btn-close"
                data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastMessage"></div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        "use strict";

        const studentModalElement = document.getElementById('studentModal');
        const studentModal = new bootstrap.Modal(studentModalElement);
        const studentForm = document.getElementById('studentForm');

        // Expose for inline onclick handlers on the page.
        window.openStudentModal = function () {
            studentForm.reset();

            document.getElementById('student_id').value = '';
            document.getElementById('studentModalTitle').textContent = 'Add New Student';
            document.getElementById('studentModalSubtitle').textContent =
                'Create a new student account.';
            document.getElementById('passwordLabel').textContent = 'Initial Password';
            document.getElementById('passwordHint').textContent =
                'At least 12 characters. The password is stored securely as a hash.';
            document.getElementById('password').required = true;
            document.getElementById('saveButton').innerHTML =
                '<i class="bi bi-check2-circle me-1"></i> Create Student';

            studentModal.show();
        };

        window.editStudent = function (button) {
            studentForm.reset();

            document.getElementById('student_id').value = button.dataset.id;
            document.getElementById('full_name').value = button.dataset.name;
            document.getElementById('username').value = button.dataset.username;
            document.getElementById('class_id').value = button.dataset.class || '';

            document.getElementById('studentModalTitle').textContent = 'Edit Student';
            document.getElementById('studentModalSubtitle').textContent =
                'Update account details or set a new password.';
            document.getElementById('passwordLabel').textContent =
                'New Password (Optional)';
            document.getElementById('passwordHint').textContent =
                'Leave blank to keep the current password. If changing it, use at least 12 characters.';
            document.getElementById('password').required = false;
            document.getElementById('saveButton').innerHTML =
                '<i class="bi bi-save me-1"></i> Save Changes';

            studentModal.show();
        };

        <?php if ($flash): ?>
            const type = <?= json_encode(
                                $flash['type'],
                                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
                            ) ?>;
            const message = <?= json_encode(
                                $flash['message'],
                                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
                            ) ?>;

            const icon = document.getElementById('toastIcon');
            icon.className = type === 'success' ?
                'bi bi-check-circle-fill text-success me-2' :
                'bi bi-exclamation-triangle-fill text-warning me-2';

            document.getElementById('toastTitle').textContent =
                type === 'success' ? 'Success' : 'Notice';

            document.getElementById('toastMessage').textContent = message;

            bootstrap.Toast.getOrCreateInstance(
                document.getElementById('appToast'), {
                    delay: 4000
                }
            ).show();
        <?php endif; ?>
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
