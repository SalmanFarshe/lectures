<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Chapter Management';
$activePage = 'chapters';

$errorMessage = '';
$successMessage = '';

/*
|--------------------------------------------------------------------------
| CSRF Token
|--------------------------------------------------------------------------
*/
if (
    !isset($_SESSION['chapter_csrf']) ||
    !is_string($_SESSION['chapter_csrf']) ||
    $_SESSION['chapter_csrf'] === ''
) {
    $_SESSION['chapter_csrf'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| Active / Inactive Action
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $chapterId = filter_input(INPUT_POST, 'chapter_id', FILTER_VALIDATE_INT);
    $newStatus = filter_input(INPUT_POST, 'new_status', FILTER_VALIDATE_INT);

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['chapter_csrf'], $token)
    ) {
        http_response_code(403);
        exit('Invalid security token. Please refresh the page and try again.');
    }

    if (
        $chapterId === false ||
        $chapterId === null ||
        $chapterId < 1 ||
        !in_array($newStatus, [0, 1], true)
    ) {
        $errorMessage = 'Invalid chapter information.';
    } else {
        try {
            $updateQuery = $pdo->prepare(
                'UPDATE chapters SET is_active = ? WHERE id = ?'
            );
            $updateQuery->execute([$newStatus, $chapterId]);

            if ($updateQuery->rowCount() > 0) {
                $successMessage = $newStatus === 1
                    ? 'Chapter successfully activated.'
                    : 'Chapter successfully deactivated.';
            } else {
                $checkQuery = $pdo->prepare(
                    'SELECT is_active FROM chapters WHERE id = ?'
                );
                $checkQuery->execute([$chapterId]);
                $existingChapter = $checkQuery->fetch();

                if (!$existingChapter) {
                    $errorMessage = 'Chapter not found.';
                } elseif ((int) $existingChapter['is_active'] === $newStatus) {
                    $successMessage = 'Chapter status is already up to date.';
                } else {
                    $errorMessage = 'Chapter status could not be updated.';
                }
            }
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            $errorMessage = 'Chapter status update করা যায়নি।';
        }
    }
}

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/
$selectedClass = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT);
$selectedSubject = filter_input(INPUT_GET, 'subject_id', FILTER_VALIDATE_INT);

$selectedClass = (
    $selectedClass !== false &&
    $selectedClass !== null &&
    $selectedClass > 0
) ? $selectedClass : 0;

$selectedSubject = (
    $selectedSubject !== false &&
    $selectedSubject !== null &&
    $selectedSubject > 0
) ? $selectedSubject : 0;

$search = trim((string) ($_GET['search'] ?? ''));

$classes = [];
$subjects = [];
$chapters = [];

try {
    $classes = $pdo->query(
        'SELECT id, name FROM classes ORDER BY name'
    )->fetchAll();

    $subjectSql = '
        SELECT s.id, s.name, s.folder_key, s.class_id,
               c.name AS class_name
        FROM subjects s
        INNER JOIN classes c ON c.id = s.class_id
        WHERE 1 = 1
    ';
    $subjectParams = [];

    if ($selectedClass > 0) {
        $subjectSql .= ' AND s.class_id = ?';
        $subjectParams[] = $selectedClass;
    }

    $subjectSql .= ' ORDER BY c.name, s.name';

    $subjectQuery = $pdo->prepare($subjectSql);
    $subjectQuery->execute($subjectParams);
    $subjects = $subjectQuery->fetchAll();

    $chapterSql = '
        SELECT ch.id, ch.subject_id, ch.title, ch.file_path,
               ch.is_active, ch.last_scanned_at, ch.created_at,
               s.name AS subject_name, s.class_id,
               c.name AS class_name
        FROM chapters ch
        INNER JOIN subjects s ON s.id = ch.subject_id
        INNER JOIN classes c ON c.id = s.class_id
        WHERE 1 = 1
    ';
    $chapterParams = [];

    if ($selectedClass > 0) {
        $chapterSql .= ' AND c.id = ?';
        $chapterParams[] = $selectedClass;
    }

    if ($selectedSubject > 0) {
        $chapterSql .= ' AND s.id = ?';
        $chapterParams[] = $selectedSubject;
    }

    if ($search !== '') {
        $chapterSql .= '
            AND (
                ch.title LIKE ?
                OR ch.file_path LIKE ?
                OR s.name LIKE ?
            )
        ';
        $like = '%' . $search . '%';
        $chapterParams[] = $like;
        $chapterParams[] = $like;
        $chapterParams[] = $like;
    }

    $chapterSql .= ' ORDER BY c.name, s.name, ch.id';

    $chapterQuery = $pdo->prepare($chapterSql);
    $chapterQuery->execute($chapterParams);
    $chapters = $chapterQuery->fetchAll();
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $errorMessage = 'Chapter data load করা যায়নি। Database পরীক্ষা করুন।';
}

$totalChapters = count($chapters);
$activeChapters = count(array_filter(
    $chapters,
    static fn(array $chapter): bool => (int) $chapter['is_active'] === 1
));
$inactiveChapters = $totalChapters - $activeChapters;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-1">Chapter Management</h2>
            <p class="text-muted mb-0">
                Manage chapters scanned from your PHP notes files.
            </p>
        </div>

        <a href="/lectures/admin/scan-notes.php" class="btn btn-primary">
            <i class="bi bi-arrow-repeat me-1"></i>
            Scan & Sync Notes
        </a>
    </div>

    <?php if ($successMessage !== ''): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= e($successMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"
                aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger" role="alert">
            <?= e($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Total Chapters</div>
                    <div class="fs-3 fw-bold"><?= $totalChapters ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Active Chapters</div>
                    <div class="fs-3 fw-bold text-success"><?= $activeChapters ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Inactive Chapters</div>
                    <div class="fs-3 fw-bold text-secondary"><?= $inactiveChapters ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="/lectures/admin/chapters.php">
                <div class="row g-3 align-items-end">

                    <div class="col-md-3">
                        <label for="class_id" class="form-label">Class</label>
                        <select name="class_id" id="class_id" class="form-select">
                            <option value="0">All Classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option
                                    value="<?= (int) $class['id'] ?>"
                                    <?= $selectedClass === (int) $class['id'] ? 'selected' : '' ?>>
                                    <?= e($class['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="subject_id" class="form-label">Subject</label>
                        <select name="subject_id" id="subject_id" class="form-select">
                            <option value="0">All Subjects</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option
                                    value="<?= (int) $subject['id'] ?>"
                                    <?= $selectedSubject === (int) $subject['id'] ? 'selected' : '' ?>>
                                    <?= e($subject['class_name'] . ' - ' . $subject['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input
                            type="search"
                            name="search"
                            id="search"
                            class="form-control"
                            placeholder="Chapter title or file path..."
                            value="<?= e($search) ?>">
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="/lectures/admin/chapters.php"
                            class="btn btn-outline-secondary">
                            Reset
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">Chapter List</h5>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Chapter</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>File Path</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($chapters === []): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="bi bi-journal-x fs-1 text-muted"></i>
                                    <p class="mt-2 mb-1">No chapters found.</p>
                                    <small class="text-muted">
                                        Try another filter or scan your notes folder.
                                    </small>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($chapters as $index => $chapter): ?>
                                <?php $isActive = (int) $chapter['is_active'] === 1; ?>
                                <tr>
                                    <td class="ps-3"><?= $index + 1 ?></td>

                                    <td>
                                        <div class="fw-semibold">
                                            <?= e($chapter['title']) ?>
                                        </div>
                                    </td>

                                    <td><?= e($chapter['class_name']) ?></td>

                                    <td><?= e($chapter['subject_name']) ?></td>

                                    <td>
                                        <?php if ($isActive): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <code class="small">
                                            <?= e($chapter['file_path']) ?>
                                        </code>
                                    </td>

                                    <td class="text-end pe-3">
                                        <form
                                            method="POST"
                                            action="/lectures/admin/chapters.php"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to <?= $isActive ? 'deactivate' : 'activate' ?> this chapter?');">
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($_SESSION['chapter_csrf']) ?>">

                                            <input
                                                type="hidden"
                                                name="chapter_id"
                                                value="<?= (int) $chapter['id'] ?>">

                                            <input
                                                type="hidden"
                                                name="new_status"
                                                value="<?= $isActive ? '0' : '1' ?>">

                                            <?php if ($isActive): ?>
                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-toggle-off me-1"></i>
                                                    Deactivate
                                                </button>
                                            <?php else: ?>
                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-toggle-on me-1"></i>
                                                    Activate
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>