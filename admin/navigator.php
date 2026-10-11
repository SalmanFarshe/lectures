<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Admin — Note Navigator (landing page)
|--------------------------------------------------------------------------
| Class -> Subject -> Chapter -> Note View, with breadcrumbs and Back buttons.
| Notes come from the `notes` table (DB-backed) with a fallback to legacy
| file-based chapters. Admin access is not limited by chapter_permissions.
*/

$classId   = (int)($_GET['class_id']   ?? 0);
$subjectId = (int)($_GET['subject_id'] ?? 0);
$chapterId = (int)($_GET['chapter_id'] ?? 0);

$classes = $pdo->query(
    "SELECT c.id, c.name,
            (SELECT COUNT(*) FROM subjects s WHERE s.class_id = c.id AND s.is_active = 1) AS subject_count
     FROM classes c WHERE c.is_active = 1 ORDER BY c.name ASC"
)->fetchAll();

$currentClass = null;
$currentSubject = null;
$currentChapter = null;
$subjects = [];
$chapters = [];
$notes = [];

if ($chapterId > 0) {
    // Chapter level -> list notes for this chapter.
    $st = $pdo->prepare(
        "SELECT ch.id, ch.title, sub.id AS subject_id, sub.name AS subject_name,
                c.id AS class_id, c.name AS class_name
         FROM chapters ch
         INNER JOIN subjects sub ON sub.id = ch.subject_id
         INNER JOIN classes c ON c.id = sub.class_id
         WHERE ch.id = ? LIMIT 1"
    );
    $st->execute([$chapterId]);
    $currentChapter = $st->fetch();

    if ($currentChapter) {
        $subjectId = (int)$currentChapter['subject_id'];
        $classId = (int)$currentChapter['class_id'];
        $nst = $pdo->prepare(
            "SELECT id, title, content_type, pdf_path, sort_order, is_active
             FROM notes WHERE chapter_id = ? ORDER BY sort_order ASC, id ASC"
        );
        $nst->execute([$chapterId]);
        $notes = $nst->fetchAll();
    }
} elseif ($subjectId > 0) {
    // Subject level -> list chapters (with note counts).
    $st = $pdo->prepare(
        "SELECT s.id, s.name, c.id AS class_id, c.name AS class_name
         FROM subjects s INNER JOIN classes c ON c.id = s.class_id
         WHERE s.id = ? LIMIT 1"
    );
    $st->execute([$subjectId]);
    $currentSubject = $st->fetch();

    if ($currentSubject) {
        $classId = (int)$currentSubject['class_id'];
        $chst = $pdo->prepare(
            "SELECT ch.id, ch.title, ch.is_active,
                    (SELECT COUNT(*) FROM notes n WHERE n.chapter_id = ch.id) AS note_count
             FROM chapters ch WHERE ch.subject_id = ? ORDER BY ch.title ASC"
        );
        $chst->execute([$subjectId]);
        $chapters = $chst->fetchAll();
    }
} elseif ($classId > 0) {
    // Class level -> list subjects.
    $st = $pdo->prepare("SELECT id, name FROM classes WHERE id = ? LIMIT 1");
    $st->execute([$classId]);
    $currentClass = $st->fetch();

    if ($currentClass) {
        $sst = $pdo->prepare(
            "SELECT s.id, s.name,
                    (SELECT COUNT(*) FROM chapters ch WHERE ch.subject_id = s.id) AS chapter_count
             FROM subjects s WHERE s.class_id = ? AND s.is_active = 1 ORDER BY s.name ASC"
        );
        $sst->execute([$classId]);
        $subjects = $sst->fetchAll();
    }
}

$pageTitle = 'Note Navigator';
$activePage = 'navigator';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .nav-card { display:flex; align-items:center; gap:16px; height:100%; padding:20px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); color:var(--text); text-decoration:none; box-shadow:var(--shadow-sm); transition:border-color .2s, box-shadow .2s, transform .2s; }
    .nav-card:hover { border-color:var(--primary); box-shadow:var(--shadow); transform:translateY(-2px); color:var(--text); }
    .nav-icon { display:grid; place-items:center; width:52px; height:52px; flex-shrink:0; border-radius:var(--radius-sm); color:var(--primary); background:var(--primary-soft); font-size:24px; }
    .nav-title { font-size:16px; font-weight:750; }
    .nav-desc { color:var(--muted); font-size:12px; margin-top:4px; }
    .nav-arrow { margin-left:auto; color:var(--muted); }
    .nav-trail { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-bottom:18px; color:var(--muted); font-size:13px; }
    .nav-trail a { color:var(--primary); font-weight:650; }
    .nav-trail i { font-size:11px; }
    .note-row { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:15px 18px; border-bottom:1px solid var(--border); }
    .note-row:last-child { border-bottom:0; }
    .note-row:hover { background:var(--bg); }
    .note-info { display:flex; align-items:center; gap:13px; min-width:0; }
    .note-badge { display:grid; place-items:center; width:38px; height:38px; flex-shrink:0; color:var(--primary); background:var(--primary-soft); border-radius:var(--radius-sm); }
    .note-name { font-weight:700; color:var(--text); }
</style>

<div class="page-header">
    <div class="page-header-text">
        <h1 class="page-title">Note Navigator</h1>
        <p class="page-header-desc">Browse Class → Subject → Chapter → Notes.</p>
    </div>
    <div class="page-header-actions">
        <a class="btn btn-outline-secondary" href="/lectures/admin/navigator.php">
            <i class="bi bi-house-door me-1"></i> All Classes
        </a>
        <a class="btn btn-primary" href="/lectures/admin/note-edit.php">
            <i class="bi bi-plus-lg me-1"></i> Add Note
        </a>
    </div>
</div>

<div class="nav-trail">
    <a href="/lectures/admin/navigator.php">Classes</a>
    <?php if ($currentClass): ?>
        <i class="bi bi-chevron-right"></i>
        <strong><?= e($currentClass['name']) ?></strong>
    <?php elseif ($currentSubject): ?>
        <i class="bi bi-chevron-right"></i>
        <a href="/lectures/admin/navigator.php?class_id=<?= (int)$currentSubject['class_id'] ?>"><?= e($currentSubject['class_name']) ?></a>
        <i class="bi bi-chevron-right"></i>
        <strong><?= e($currentSubject['name']) ?></strong>
    <?php elseif ($currentChapter): ?>
        <i class="bi bi-chevron-right"></i>
        <a href="/lectures/admin/navigator.php?class_id=<?= (int)$currentChapter['class_id'] ?>"><?= e($currentChapter['class_name']) ?></a>
        <i class="bi bi-chevron-right"></i>
        <a href="/lectures/admin/navigator.php?subject_id=<?= (int)$currentChapter['subject_id'] ?>"><?= e($currentChapter['subject_name']) ?></a>
        <i class="bi bi-chevron-right"></i>
        <strong><?= e($currentChapter['title']) ?></strong>
    <?php endif; ?>
</div>

<?php if ($chapterId > 0 && !$currentChapter): ?>
    <div class="panel"><div class="empty-state">
        <i class="bi bi-exclamation-circle fs-1 d-block mb-2"></i>
        <h5 class="fw-bold">Chapter not found</h5>
        <a class="btn btn-primary mt-2" href="/lectures/admin/navigator.php">Back to Classes</a>
    </div></div>

<?php elseif ($subjectId > 0 && !$currentSubject): ?>
    <div class="panel"><div class="empty-state">
        <i class="bi bi-exclamation-circle fs-1 d-block mb-2"></i>
        <h5 class="fw-bold">Subject not found</h5>
        <a class="btn btn-primary mt-2" href="/lectures/admin/navigator.php">Back to Classes</a>
    </div></div>

<?php elseif ($classId > 0 && !$currentClass): ?>
    <div class="panel"><div class="empty-state">
        <i class="bi bi-exclamation-circle fs-1 d-block mb-2"></i>
        <h5 class="fw-bold">Class not found</h5>
        <a class="btn btn-primary mt-2" href="/lectures/admin/navigator.php">Back to Classes</a>
    </div></div>

<?php elseif ($chapterId > 0): ?>
    <!-- Notes for this chapter -->
    <?php if ($notes): ?>
        <div class="panel" style="padding:0;">
            <?php foreach ($notes as $note): ?>
                <div class="note-row">
                    <div class="note-info">
                        <span class="note-badge"><i class="bi <?= ($note['content_type'] ?? '') === 'pdf' ? 'bi-file-earmark-pdf' : 'bi-journal-text' ?>"></i></span>
                        <div>
                            <div class="note-name"><?= e($note['title']) ?></div>
                            <div class="small text-secondary text-uppercase"><?= e($note['content_type'] ?? '') ?><?= ((int)$note['is_active'] === 0) ? ' · Hidden' : '' ?></div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-primary btn-sm" href="/lectures/admin/view-db-note.php?id=<?= (int)$note['id'] ?>">
                            <i class="bi bi-eye me-1"></i> View
                        </a>
                        <a class="btn btn-outline-secondary btn-sm" href="/lectures/admin/note-edit.php?id=<?= (int)$note['id'] ?>">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="panel"><div class="empty-state">
            <i class="bi bi-journal fs-1 d-block mb-2"></i>
            <h5 class="fw-bold">No notes yet</h5>
            <p class="mb-3">Add HTML or a PDF for this chapter.</p>
            <a class="btn btn-primary" href="/lectures/admin/note-edit.php?chapter_id=<?= (int)$chapterId ?>">
                <i class="bi bi-plus-lg me-1"></i> Add Note
            </a>
        </div></div>
    <?php endif; ?>

<?php elseif ($subjectId > 0): ?>
    <?php if ($chapters): ?>
        <div class="panel" style="padding:0;">
            <?php foreach ($chapters as $ch): ?>
                <div class="note-row">
                    <div class="note-info">
                        <span class="note-badge"><i class="bi bi-book"></i></span>
                        <div>
                            <div class="note-name"><?= e($ch['title']) ?></div>
                            <div class="small text-secondary"><?= (int)$ch['note_count'] ?> note(s)<?= ((int)$ch['is_active'] === 0) ? ' · Inactive' : '' ?></div>
                        </div>
                    </div>
                    <a class="btn btn-primary btn-sm" href="/lectures/admin/navigator.php?chapter_id=<?= (int)$ch['id'] ?>">
                        Open <i class="bi bi-chevron-right ms-1"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="panel"><div class="empty-state">
            <i class="bi bi-journal fs-1 d-block mb-2"></i>
            <h5 class="fw-bold">No chapters yet</h5>
        </div></div>
    <?php endif; ?>

<?php elseif ($classId > 0): ?>
    <?php if ($subjects): ?>
        <div class="row g-3">
            <?php foreach ($subjects as $subject): ?>
                <div class="col-12 col-md-6">
                    <a class="nav-card" href="/lectures/admin/navigator.php?subject_id=<?= (int)$subject['id'] ?>">
                        <span class="nav-icon"><i class="bi bi-journal-text"></i></span>
                        <div>
                            <div class="nav-title"><?= e($subject['name']) ?></div>
                            <div class="nav-desc"><?= (int)$subject['chapter_count'] ?> chapter(s)</div>
                        </div>
                        <i class="bi bi-chevron-right nav-arrow"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="panel"><div class="empty-state">
            <i class="bi bi-journals fs-1 d-block mb-2"></i>
            <h5 class="fw-bold">No subjects yet</h5>
        </div></div>
    <?php endif; ?>

<?php else: ?>
    <?php if ($classes): ?>
        <div class="row g-3">
            <?php foreach ($classes as $class): ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <a class="nav-card" href="/lectures/admin/navigator.php?class_id=<?= (int)$class['id'] ?>">
                        <span class="nav-icon"><i class="bi bi-mortarboard-fill"></i></span>
                        <div>
                            <div class="nav-title"><?= e($class['name']) ?></div>
                            <div class="nav-desc"><?= (int)$class['subject_count'] ?> subject(s)</div>
                        </div>
                        <i class="bi bi-chevron-right nav-arrow"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="panel"><div class="empty-state">
            <i class="bi bi-collection fs-1 d-block mb-2"></i>
            <h5 class="fw-bold">No classes yet</h5>
        </div></div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
