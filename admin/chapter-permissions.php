<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| CSRF Protection
|--------------------------------------------------------------------------
*/
if (
    !isset($_SESSION['permission_csrf']) ||
    !is_string($_SESSION['permission_csrf']) ||
    $_SESSION['permission_csrf'] === ''
) {
    $_SESSION['permission_csrf'] = bin2hex(random_bytes(32));
}

function permissionRedirect(): never
{
    redirect('/lectures/admin/chapter-permissions.php');
}

function permissionFlash(string $message, string $type = 'success'): never
{
    $_SESSION['permission_flash'] = [
        'message' => $message,
        'type' => $type,
    ];

    permissionRedirect();
}

/*
|--------------------------------------------------------------------------
| Handle POST Actions
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrf) ||
        !hash_equals($_SESSION['permission_csrf'], $csrf)
    ) {
        permissionFlash('Security verification failed. Please try again.', 'danger');
    }

    $action = $_POST['action'] ?? '';

    /*
    | Grant chapter access
    */
    if ($action === 'grant') {
        $userId = filter_var(
            $_POST['user_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        $chapterId = filter_var(
            $_POST['chapter_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if (!$userId || !$chapterId) {
            permissionFlash('Please select a valid student and chapter.', 'danger');
        }

        // Only active students can receive permissions.
        $studentStmt = $pdo->prepare(
            "SELECT id, class_id
             FROM users
             WHERE id = ?
               AND role = 'student'
               AND is_active = 1
             LIMIT 1"
        );
        $studentStmt->execute([$userId]);
        $student = $studentStmt->fetch();

        if (!$student) {
            permissionFlash('Student not found or the account is inactive.', 'danger');
        }

        // The chapter must be active and belong to the student's assigned class.
        $chapterStmt = $pdo->prepare(
            "SELECT ch.id, cl.id AS class_id
             FROM chapters ch
             INNER JOIN subjects sub ON sub.id = ch.subject_id
             INNER JOIN classes cl ON cl.id = sub.class_id
             WHERE ch.id = ?
               AND ch.is_active = 1
               AND sub.is_active = 1
               AND cl.is_active = 1
             LIMIT 1"
        );
        $chapterStmt->execute([$chapterId]);
        $chapter = $chapterStmt->fetch();

        if (!$chapter) {
            permissionFlash('Chapter not found or it is inactive.', 'danger');
        }

        if ((int)$student['class_id'] !== (int)$chapter['class_id']) {
            permissionFlash(
                'This chapter does not belong to the selected student’s class.',
                'danger'
            );
        }

        // The composite primary key prevents duplicate permissions.
        $grantStmt = $pdo->prepare(
            "INSERT INTO chapter_permissions (user_id, chapter_id)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)"
        );
        $grantStmt->execute([$userId, $chapterId]);

        permissionFlash('Chapter permission granted successfully.');
    }

    /*
    | Revoke chapter access
    */
    if ($action === 'revoke') {
        $userId = filter_var(
            $_POST['user_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        $chapterId = filter_var(
            $_POST['chapter_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if (!$userId || !$chapterId) {
            permissionFlash('Invalid permission selected.', 'danger');
        }

        $deleteStmt = $pdo->prepare(
            "DELETE FROM chapter_permissions
             WHERE user_id = ? AND chapter_id = ?"
        );
        $deleteStmt->execute([$userId, $chapterId]);

        permissionFlash(
            $deleteStmt->rowCount() > 0
                ? 'Chapter permission revoked successfully.'
                : 'Permission was not found.',
            $deleteStmt->rowCount() > 0 ? 'success' : 'warning'
        );
    }

    permissionFlash('Invalid action.', 'danger');
}

/*
|--------------------------------------------------------------------------
| Flash Message
|--------------------------------------------------------------------------
*/
$flash = $_SESSION['permission_flash'] ?? null;
unset($_SESSION['permission_flash']);

/*
|--------------------------------------------------------------------------
| Active Students
|--------------------------------------------------------------------------
*/
$studentsStmt = $pdo->query(
    "SELECT u.id, u.full_name, u.username, u.class_id, c.name AS class_name
     FROM users u
     LEFT JOIN classes c ON c.id = u.class_id
     WHERE u.role = 'student'
       AND u.is_active = 1
     ORDER BY u.full_name ASC"
);
$students = $studentsStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Active Chapters
|--------------------------------------------------------------------------
*/
$chaptersStmt = $pdo->query(
    "SELECT
        ch.id,
        ch.title,
        sub.name AS subject_name,
        cl.name AS class_name,
        cl.id AS class_id
     FROM chapters ch
     INNER JOIN subjects sub ON sub.id = ch.subject_id
     INNER JOIN classes cl ON cl.id = sub.class_id
     WHERE ch.is_active = 1
       AND sub.is_active = 1
       AND cl.is_active = 1
     ORDER BY cl.name ASC, sub.name ASC, ch.title ASC"
);
$chapters = $chaptersStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Existing Permissions + Search
|--------------------------------------------------------------------------
*/
$search = trim((string)($_GET['search'] ?? ''));
$selectedStudentId = filter_var(
    $_GET['student_id'] ?? '',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$sql = "
    SELECT
        cp.user_id,
        cp.chapter_id,
        cp.granted_at,
        u.full_name,
        u.username,
        c.name AS class_name,
        ch.title AS chapter_title,
        ch.is_active AS chapter_active,
        sub.name AS subject_name
    FROM chapter_permissions cp
    INNER JOIN users u ON u.id = cp.user_id
    INNER JOIN chapters ch ON ch.id = cp.chapter_id
    INNER JOIN subjects sub ON sub.id = ch.subject_id
    INNER JOIN classes c ON c.id = sub.class_id
    WHERE u.role = 'student'
";

$params = [];

if ($selectedStudentId) {
    $sql .= " AND cp.user_id = ?";
    $params[] = $selectedStudentId;
}

if ($search !== '') {
    $sql .= " AND (
        u.full_name LIKE ?
        OR u.username LIKE ?
        OR ch.title LIKE ?
        OR sub.name LIKE ?
        OR c.name LIKE ?
    )";

    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like);
}

$sql .= " ORDER BY u.full_name ASC, c.name ASC, sub.name ASC, ch.title ASC";

$permissionsStmt = $pdo->prepare($sql);
$permissionsStmt->execute($params);
$permissions = $permissionsStmt->fetchAll();

$pageTitle = 'Chapter Permissions';
$totalPermissions = count($permissions);

$activePage = 'permissions';

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-text">
        <h1 class="page-title"><?= e($pageTitle) ?></h1>
        <p class="page-header-desc">
            Grant or revoke each student's access to specific chapters.
        </p>
    </div>
    <div class="page-header-actions">
        <a class="btn btn-outline-secondary" href="/lectures/admin/index.php">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
    </div>
</div>

<?php if (is_array($flash)): ?>
    <div class="alert alert-<?= e((string)($flash['type'] ?? 'info')) ?> alert-dismissible fade show" role="alert">
        <?= e((string)($flash['message'] ?? '')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Grant Access -->
<div class="panel">
    <h2>Grant Chapter Access</h2>

    <?php if (count($students) === 0): ?>
        <div class="empty-state">
            <i class="bi bi-people fs-1 d-block mb-2"></i>
            <p class="mb-0">No active students available. Create a student account first.</p>
        </div>
    <?php elseif (count($chapters) === 0): ?>
        <div class="empty-state">
            <i class="bi bi-journal fs-1 d-block mb-2"></i>
            <p class="mb-0">No active chapters found. Scan your notes first.</p>
        </div>
    <?php else: ?>
        <form method="post" action="/lectures/admin/chapter-permissions.php"
            class="row g-3 align-items-end">
            <input type="hidden" name="csrf_token"
                value="<?= e($_SESSION['permission_csrf']) ?>">
            <input type="hidden" name="action" value="grant">

            <div class="col-12 col-md-4">
                <label class="form-label" for="user_id">Student</label>
                <select class="form-select" id="user_id" name="user_id" required>
                    <option value="">Select student</option>
                    <?php foreach ($students as $student): ?>
                        <option
                            value="<?= (int)$student['id'] ?>"
                            data-class-id="<?= (int)($student['class_id'] ?? 0) ?>">
                            <?= e($student['full_name']) ?>
                            (<?= e($student['username']) ?>)
                            — <?= e($student['class_name'] ?? 'No class') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="chapter_id">Chapter</label>
                <select class="form-select" id="chapter_id" name="chapter_id" required>
                    <option value="">Select chapter</option>
                    <?php foreach ($chapters as $chapter): ?>
                        <option
                            value="<?= (int)$chapter['id'] ?>"
                            data-class-id="<?= (int)$chapter['class_id'] ?>">
                            <?= e($chapter['class_name']) ?>
                            → <?= e($chapter['subject_name']) ?>
                            → <?= e($chapter['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-plus-circle me-1"></i> Grant
                </button>
            </div>

            <div class="col-12">
                <div class="alert alert-warning mb-0 d-none" id="classWarning" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    The selected chapter does not belong to the selected student's class.
                    The request will be rejected.
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<!-- Granted Permissions -->
<div class="panel">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h2 class="mb-1">Granted Permissions</h2>
            <div class="muted">
                <?= $totalPermissions ?> permission(s) in the current result.
            </div>
        </div>
    </div>

    <form method="get" action="/lectures/admin/chapter-permissions.php"
        class="row g-2 mb-4">
        <div class="col-12 col-md-4">
            <input type="search" name="search" class="form-control"
                placeholder="Search student, subject or chapter..."
                value="<?= e($search) ?>">
        </div>
        <div class="col-12 col-md-4">
            <select name="student_id" class="form-select">
                <option value="">All students</option>
                <?php foreach ($students as $student): ?>
                    <option value="<?= (int)$student['id'] ?>"
                        <?= (string)$selectedStudentId === (string)$student['id'] ? 'selected' : '' ?>>
                        <?= e($student['full_name']) ?> (<?= e($student['username']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <button type="submit" class="btn btn-outline-primary w-100">Search</button>
        </div>
        <div class="col-6 col-md-2">
            <a href="/lectures/admin/chapter-permissions.php"
                class="btn btn-outline-secondary w-100">Reset</a>
        </div>
    </form>

    <?php if (count($permissions) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Chapter</th>
                        <th>Granted At</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($permissions as $index => $permission): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <strong><?= e($permission['full_name']) ?></strong>
                                <div class="muted"><?= e($permission['username']) ?></div>
                            </td>
                            <td><?= e($permission['class_name']) ?></td>
                            <td><?= e($permission['subject_name']) ?></td>
                            <td><?= e($permission['chapter_title']) ?></td>
                            <td>
                                <?php
                                $grantedAt = $permission['granted_at'] ?? '';
                                echo e($grantedAt !== '' ? $grantedAt : '—');
                                ?>
                            </td>
                            <td>
                                <?php if ((int)$permission['chapter_active'] === 1): ?>
                                    <span class="badge badge-soft-success">Active</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post"
                                    action="/lectures/admin/chapter-permissions.php"
                                    onsubmit="return confirm('Revoke this student’s access to the chapter?');">
                                    <input type="hidden" name="csrf_token"
                                        value="<?= e($_SESSION['permission_csrf']) ?>">
                                    <input type="hidden" name="action" value="revoke">
                                    <input type="hidden" name="user_id"
                                        value="<?= (int)$permission['user_id'] ?>">
                                    <input type="hidden" name="chapter_id"
                                        value="<?= (int)$permission['chapter_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Revoke
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <h5>No permissions found</h5>
            <p class="mb-0">
                Grant chapter access above, or change your search filters.
            </p>
        </div>
    <?php endif; ?>
</div>

<div class="alert alert-info">
    <strong>Important:</strong>
    This page stores chapter permissions in the database. The student-side chapter page
    verifies this permission before displaying a chapter.
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const studentSelect = document.getElementById('user_id');
        const chapterSelect = document.getElementById('chapter_id');
        const warning = document.getElementById('classWarning');

        if (!studentSelect || !chapterSelect || !warning) {
            return;
        }

        // Remember all chapters so we can restore the full list when needed.
        const allChapterOptions = Array.from(chapterSelect.querySelectorAll('option')).slice(1);

        function setChapterOptions(list, classId) {
            // Keep the leading "Select chapter" placeholder.
            chapterSelect.innerHTML = '<option value="">Select chapter</option>';
            list.forEach(function (opt) {
                const o = document.createElement('option');
                o.value = opt.value;
                o.textContent = opt.textContent;
                o.dataset.classId = classId;
                chapterSelect.appendChild(o);
            });
        }

        function loadChaptersForStudent() {
            const studentOption = studentSelect.options[studentSelect.selectedIndex];
            const classId = studentOption ? studentOption.dataset.classId : '';

            if (!classId || classId === '0') {
                // No class assigned — show every chapter.
                setChapterOptions(allChapterOptions, '');
                checkClassMatch();
                return;
            }

            // Show only chapters that belong to the selected student's class.
            chapterSelect.innerHTML = '<option value="">Loading chapters…</option>';
            fetch('/lectures/admin/ajax-options.php?field=chapter_by_class&class_id=' + encodeURIComponent(classId))
                .then(function (r) { return r.json(); })
                .then(function (list) {
                    chapterSelect.innerHTML = '<option value="">Select chapter</option>';
                    (list || []).forEach(function (item) {
                        const o = document.createElement('option');
                        o.value = item.id;
                        o.textContent = item.name;
                        o.dataset.classId = classId;
                        chapterSelect.appendChild(o);
                    });
                    checkClassMatch();
                })
                .catch(function () {
                    // On failure, fall back to the full list rather than blocking.
                    setChapterOptions(allChapterOptions, '');
                    checkClassMatch();
                });
        }

        function checkClassMatch() {
            const studentOption = studentSelect.options[studentSelect.selectedIndex];
            const chapterOption = chapterSelect.options[chapterSelect.selectedIndex];

            const studentClass = studentOption ? studentOption.dataset.classId : '';
            const chapterClass = chapterOption ? chapterOption.dataset.classId : '';

            const mismatch = studentClass && chapterClass &&
                studentClass !== '0' &&
                studentClass !== chapterClass;

            warning.classList.toggle('d-none', !mismatch);
        }

        studentSelect.addEventListener('change', function () {
            loadChaptersForStudent();
        });
        chapterSelect.addEventListener('change', checkClassMatch);
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
