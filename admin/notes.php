<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Admin — Browse Notes: Class -> Subject -> Chapter -> Note
|--------------------------------------------------------------------------
| Admins can browse every active class, subject and chapter and open any
| note directly. This does NOT depend on chapter_permissions. Students
| continue to see only their permitted chapters on their own dashboard.
|
| Navigation is driven by GET parameters:
|   (none)          -> list classes
|   ?class_id=N     -> list subjects within that class
|   ?subject_id=N   -> list chapters within that subject
*/

$classId = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT);
$subjectId = filter_input(INPUT_GET, 'subject_id', FILTER_VALIDATE_INT);

$classId = ($classId !== false && $classId !== null && $classId > 0)
    ? (int)$classId : 0;
$subjectId = ($subjectId !== false && $subjectId !== null && $subjectId > 0)
    ? (int)$subjectId : 0;

// Current context for breadcrumbs / header.
$currentClass = null;
$currentSubject = null;
$classes = [];
$subjects = [];
$chapters = [];

/*
|--------------------------------------------------------------------------
| Level 3: chapters within a subject
|--------------------------------------------------------------------------
*/
if ($subjectId > 0) {
    $subjectStmt = $pdo->prepare(
        "SELECT s.id, s.name, c.id AS class_id, c.name AS class_name
         FROM subjects s
         INNER JOIN classes c ON c.id = s.class_id
         WHERE s.id = ? AND s.is_active = 1 AND c.is_active = 1
         LIMIT 1"
    );
    $subjectStmt->execute([$subjectId]);
    $currentSubject = $subjectStmt->fetch();

    if ($currentSubject) {
        $chapterStmt = $pdo->prepare(
            "SELECT ch.id, ch.title, ch.file_path
             FROM chapters ch
             WHERE ch.subject_id = ? AND ch.is_active = 1
             ORDER BY ch.title ASC"
        );
        $chapterStmt->execute([$subjectId]);
        $chapters = $chapterStmt->fetchAll();
    }
}
/*
|--------------------------------------------------------------------------
| Level 2: subjects within a class
|--------------------------------------------------------------------------
*/
elseif ($classId > 0) {
    $classStmt = $pdo->prepare(
        "SELECT id, name FROM classes
         WHERE id = ? AND is_active = 1
         LIMIT 1"
    );
    $classStmt->execute([$classId]);
    $currentClass = $classStmt->fetch();

    if ($currentClass) {
        $subjectStmt = $pdo->prepare(
            "SELECT s.id, s.name,
                    (SELECT COUNT(*) FROM chapters ch
                      WHERE ch.subject_id = s.id AND ch.is_active = 1) AS chapter_count
             FROM subjects s
             WHERE s.class_id = ? AND s.is_active = 1
             ORDER BY s.name ASC"
        );
        $subjectStmt->execute([$classId]);
        $subjects = $subjectStmt->fetchAll();
    }
}
/*
|--------------------------------------------------------------------------
| Level 1: all classes
|--------------------------------------------------------------------------
*/
else {
    $classStmt = $pdo->query(
        "SELECT c.id, c.name,
                (SELECT COUNT(*) FROM subjects s
                  WHERE s.class_id = c.id AND s.is_active = 1) AS subject_count,
                (SELECT COUNT(*) FROM chapters ch
                   INNER JOIN subjects s2 ON s2.id = ch.subject_id
                  WHERE s2.class_id = c.id AND ch.is_active = 1) AS chapter_count
         FROM classes c
         WHERE c.is_active = 1
         ORDER BY c.name ASC"
    );
    $classes = $classStmt->fetchAll();
}

$pageTitle = 'Browse Notes';
$activePage = 'notes';

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Admin browse-notes components (uses shared design tokens) -->
<style>
    .browse-card {
        display: flex;
        align-items: center;
        gap: 16px;
        height: 100%;
        padding: 20px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        color: var(--text);
        text-decoration: none;
        box-shadow: var(--shadow-sm);
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }

    .browse-card:hover {
        border-color: var(--primary);
        box-shadow: var(--shadow);
        transform: translateY(-2px);
        color: var(--text);
    }

    .browse-icon {
        display: grid;
        place-items: center;
        width: 52px;
        height: 52px;
        flex-shrink: 0;
        border-radius: var(--radius-sm);
        color: var(--primary);
        background: var(--primary-soft);
        font-size: 24px;
    }

    .browse-title {
        font-size: 16px;
        font-weight: 750;
    }

    .browse-desc {
        color: var(--muted);
        font-size: 12px;
        margin-top: 4px;
    }

    .browse-arrow {
        margin-left: auto;
        color: var(--muted);
        font-size: 18px;
    }

    .browse-trail {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-bottom: 18px;
        color: var(--muted);
        font-size: 13px;
    }

    .browse-trail a {
        color: var(--primary);
        font-weight: 650;
    }

    .browse-trail i {
        font-size: 11px;
    }

    .chapter-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 15px 18px;
        border-bottom: 1px solid var(--border);
    }

    .chapter-row:last-child {
        border-bottom: 0;
    }

    .chapter-row:hover {
        background: var(--bg);
    }

    .chapter-info {
        display: flex;
        align-items: center;
        gap: 13px;
        min-width: 0;
    }

    .chapter-badge {
        display: grid;
        place-items: center;
        width: 38px;
        height: 38px;
        flex-shrink: 0;
        color: var(--primary);
        background: var(--primary-soft);
        border-radius: var(--radius-sm);
        font-weight: 800;
    }

    .chapter-name {
        font-weight: 700;
        color: var(--text);
    }
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-text">
        <h1 class="page-title">Browse Notes</h1>
        <p class="page-header-desc">
            Open any lecture note directly. Admin access is not limited by chapter permissions.
        </p>
    </div>
    <div class="page-header-actions">
        <a class="btn btn-outline-secondary" href="/lectures/admin/notes.php">
            <i class="bi bi-house-door me-1"></i> All Classes
        </a>
    </div>
</div>

<!-- Breadcrumb trail -->
<div class="browse-trail">
    <a href="/lectures/admin/notes.php">Classes</a>
    <?php if ($currentClass): ?>
        <i class="bi bi-chevron-right"></i>
        <strong><?= e((string)$currentClass['name']) ?></strong>
    <?php elseif ($currentSubject): ?>
        <i class="bi bi-chevron-right"></i>
        <a href="/lectures/admin/notes.php?class_id=<?= (int)$currentSubject['class_id'] ?>">
            <?= e((string)$currentSubject['class_name']) ?>
        </a>
        <i class="bi bi-chevron-right"></i>
        <strong><?= e((string)$currentSubject['name']) ?></strong>
    <?php endif; ?>
</div>

<?php if ($subjectId > 0 && !$currentSubject): ?>
    <div class="panel"><div class="empty-state">
        <i class="bi bi-exclamation-circle fs-1 d-block mb-2"></i>
        <h5 class="fw-bold">Subject not found</h5>
        <p class="mb-3">This subject does not exist or is inactive.</p>
        <a class="btn btn-primary" href="/lectures/admin/notes.php">Back to Classes</a>
    </div></div>

<?php elseif ($classId > 0 && !$currentClass): ?>
    <div class="panel"><div class="empty-state">
        <i class="bi bi-exclamation-circle fs-1 d-block mb-2"></i>
        <h5 class="fw-bold">Class not found</h5>
        <p class="mb-3">This class does not exist or is inactive.</p>
        <a class="btn btn-primary" href="/lectures/admin/notes.php">Back to Classes</a>
    </div></div>

<?php elseif ($subjectId > 0): ?>
    <!-- LEVEL 3: Chapters -->
    <?php if ($chapters): ?>
        <div class="panel" style="padding:0;">
            <?php foreach ($chapters as $index => $chapter): ?>
                <div class="chapter-row">
                    <div class="chapter-info">
                        <span class="chapter-badge"><?= $index + 1 ?></span>
                        <div class="chapter-name"><?= e((string)$chapter['title']) ?></div>
                    </div>
                    <a class="btn btn-primary btn-sm flex-shrink-0"
                        href="/lectures/admin/view-note.php?id=<?= (int)$chapter['id'] ?>">
                        <i class="bi bi-book me-1"></i> Open Note
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="panel"><div class="empty-state">
            <i class="bi bi-journal fs-1 d-block mb-2"></i>
            <h5 class="fw-bold">No chapters yet</h5>
            <p class="mb-0">No active chapters in this subject. Run Scan Notes to register notes.</p>
        </div></div>
    <?php endif; ?>

<?php elseif ($classId > 0): ?>
    <!-- LEVEL 2: Subjects -->
    <?php if ($subjects): ?>
        <div class="row g-3">
            <?php foreach ($subjects as $subject): ?>
                <div class="col-12 col-md-6">
                    <a class="browse-card"
                        href="/lectures/admin/notes.php?subject_id=<?= (int)$subject['id'] ?>">
                        <span class="browse-icon"><i class="bi bi-journal-text"></i></span>
                        <div>
                            <div class="browse-title"><?= e((string)$subject['name']) ?></div>
                            <div class="browse-desc">
                                <?= (int)$subject['chapter_count'] ?> chapter(s)
                            </div>
                        </div>
                        <i class="bi bi-chevron-right browse-arrow"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="panel"><div class="empty-state">
            <i class="bi bi-journals fs-1 d-block mb-2"></i>
            <h5 class="fw-bold">No subjects yet</h5>
            <p class="mb-0">No active subjects in this class. Run Scan Notes to register notes.</p>
        </div></div>
    <?php endif; ?>

<?php else: ?>
    <!-- LEVEL 1: Classes -->
    <?php if ($classes): ?>
        <div class="row g-3">
            <?php foreach ($classes as $class): ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <a class="browse-card"
                        href="/lectures/admin/notes.php?class_id=<?= (int)$class['id'] ?>">
                        <span class="browse-icon"><i class="bi bi-mortarboard-fill"></i></span>
                        <div>
                            <div class="browse-title"><?= e((string)$class['name']) ?></div>
                            <div class="browse-desc">
                                <?= (int)$class['subject_count'] ?> subject(s) ·
                                <?= (int)$class['chapter_count'] ?> chapter(s)
                            </div>
                        </div>
                        <i class="bi bi-chevron-right browse-arrow"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="panel"><div class="empty-state">
            <i class="bi bi-collection fs-1 d-block mb-2"></i>
            <h5 class="fw-bold">No classes yet</h5>
            <p class="mb-3">Register your notes with Scan Notes to populate classes.</p>
            <a class="btn btn-primary" href="/lectures/admin/scan-notes.php">
                <i class="bi bi-arrow-repeat me-1"></i> Go to Scan Notes
            </a>
        </div></div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
