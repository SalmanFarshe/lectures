<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Admin — Academic Management
|--------------------------------------------------------------------------
| List, add, edit and activate/deactivate:
|   Classes  (name, folder_key)
|   Subjects (name, folder_key, linked to a class)
|   Chapters (title, linked to a subject; file_path NULL for manual chapters)
*/

$pageTitle = 'Academic Management';
$activePage = 'academic';

function academic_flash(string $message, string $type = 'success'): never
{
    $_SESSION['academic_flash'] = ['message' => $message, 'type' => $type];
    $tab = (string)($_POST['redirect_tab'] ?? '');
    $suffix = in_array($tab, ['classes', 'subjects', 'chapters'], true) ? ('?tab=' . $tab) : '';
    redirect('/lectures/admin/academic.php' . $suffix);
}

function academic_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = (string)preg_replace('/[^a-z0-9]+/', '_', $value);
    return trim($value, '_');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify('academic')) {
        http_response_code(403);
        exit('Invalid security token. Please refresh and try again.');
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'class_save') {
        $id = (int)($_POST['class_id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $folderInput = trim((string)($_POST['folder_key'] ?? ''));
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '' || mb_strlen($name) > 50) {
            academic_flash('Class name is required (max 50 characters).', 'danger');
        }

        $folderKey = $folderInput !== '' ? academic_slug($folderInput) : academic_slug($name);

        if ($folderKey === '' || strlen($folderKey) > 50) {
            academic_flash('Folder key must be valid (letters/numbers, max 50).', 'danger');
        }

        try {
            if ($id > 0) {
                $pdo->prepare('UPDATE classes SET name = ?, folder_key = ?, is_active = ? WHERE id = ?')
                    ->execute([$name, $folderKey, $isActive, $id]);
                academic_flash('Class updated.');
            } else {
                $pdo->prepare('INSERT INTO classes (name, folder_key, is_active) VALUES (?, ?, 1)')
                    ->execute([$name, $folderKey]);
                academic_flash('Class added.');
            }
        } catch (PDOException $e) {
            if ((int)($e->errorInfo[1] ?? 0) === 1062) {
                academic_flash('A class with this name or folder key already exists.', 'danger');
            }
            error_log($e->getMessage());
            academic_flash('Could not save the class.', 'danger');
        }
    }

    if ($action === 'class_toggle') {
        $id = (int)($_POST['class_id'] ?? 0);
        $newStatus = (int)($_POST['new_status'] ?? -1);
        if ($id < 1 || !in_array($newStatus, [0, 1], true)) {
            academic_flash('Invalid class information.', 'danger');
        }
        $pdo->prepare('UPDATE classes SET is_active = ? WHERE id = ?')->execute([$newStatus, $id]);
        academic_flash($newStatus === 1 ? 'Class activated.' : 'Class deactivated.');
    }

    if ($action === 'subject_save') {
        $id = (int)($_POST['subject_id'] ?? 0);
        $classId = (int)($_POST['class_id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $folderInput = trim((string)($_POST['folder_key'] ?? ''));
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($classId < 1) { academic_flash('Select the class this subject belongs to.', 'danger'); }
        if ($name === '' || mb_strlen($name) > 100) { academic_flash('Subject name is required (max 100 characters).', 'danger'); }

        $folderKey = $folderInput !== '' ? academic_slug($folderInput) : academic_slug($name);
        if ($folderKey === '' || strlen($folderKey) > 100) { academic_flash('Folder key must be valid (max 100).', 'danger'); }

        $chk = $pdo->prepare('SELECT id FROM classes WHERE id = ?');
        $chk->execute([$classId]);
        if (!$chk->fetch()) { academic_flash('Selected class does not exist.', 'danger'); }

        try {
            if ($id > 0) {
                $pdo->prepare('UPDATE subjects SET class_id = ?, name = ?, folder_key = ?, is_active = ? WHERE id = ?')
                    ->execute([$classId, $name, $folderKey, $isActive, $id]);
                academic_flash('Subject updated.');
            } else {
                $pdo->prepare('INSERT INTO subjects (class_id, name, folder_key, is_active) VALUES (?, ?, ?, 1)')
                    ->execute([$classId, $name, $folderKey]);
                academic_flash('Subject added.');
            }
        } catch (PDOException $e) {
            if ((int)($e->errorInfo[1] ?? 0) === 1062) {
                academic_flash('A subject with this folder key already exists in this class.', 'danger');
            }
            error_log($e->getMessage());
            academic_flash('Could not save the subject.', 'danger');
        }
    }

    if ($action === 'subject_toggle') {
        $id = (int)($_POST['subject_id'] ?? 0);
        $newStatus = (int)($_POST['new_status'] ?? -1);
        if ($id < 1 || !in_array($newStatus, [0, 1], true)) {
            academic_flash('Invalid subject information.', 'danger');
        }
        $pdo->prepare('UPDATE subjects SET is_active = ? WHERE id = ?')->execute([$newStatus, $id]);
        academic_flash($newStatus === 1 ? 'Subject activated.' : 'Subject deactivated.');
    }

    /* ===== CHAPTER: save ===== */
    if ($action === 'chapter_save') {
        $id = (int)($_POST['chapter_id'] ?? 0);
        $subjectId = (int)($_POST['subject_id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($subjectId < 1) {
            academic_flash('Select the subject this chapter belongs to.', 'danger');
        }

        if ($title === '' || mb_strlen($title) > 200) {
            academic_flash('Chapter title is required (max 200 characters).', 'danger');
        }

        $chk = $pdo->prepare('SELECT id FROM subjects WHERE id = ?');
        $chk->execute([$subjectId]);

        if (!$chk->fetch()) {
            academic_flash('Selected subject does not exist.', 'danger');
        }

        if ($id > 0) {
            $pdo->prepare(
                'UPDATE chapters SET subject_id = ?, title = ?, is_active = ? WHERE id = ?'
            )->execute([$subjectId, $title, $isActive, $id]);
            academic_flash('Chapter updated.');
        } else {
            $pdo->prepare(
                'INSERT INTO chapters (subject_id, title, file_path, is_active) VALUES (?, ?, NULL, 1)'
            )->execute([$subjectId, $title]);
            academic_flash('Chapter added. You can now attach notes to it.');
        }
    }

    /* ===== CHAPTER: toggle active ===== */
    if ($action === 'chapter_toggle') {
        $id = (int)($_POST['chapter_id'] ?? 0);
        $newStatus = (int)($_POST['new_status'] ?? -1);

        if ($id < 1 || !in_array($newStatus, [0, 1], true)) {
            academic_flash('Invalid chapter information.', 'danger');
        }

        $pdo->prepare('UPDATE chapters SET is_active = ? WHERE id = ?')
            ->execute([$newStatus, $id]);

        academic_flash($newStatus === 1 ? 'Chapter activated.' : 'Chapter deactivated.');
    }
}

/* ---------- flash, active tab, filters ---------- */
$flash = $_SESSION['academic_flash'] ?? null;
unset($_SESSION['academic_flash']);

$tab = (string)($_GET['tab'] ?? 'classes');

if (!in_array($tab, ['classes', 'subjects', 'chapters'], true)) {
    $tab = 'classes';
}

$classSearch = trim((string)($_GET['class_search'] ?? ''));
$subjectSearch = trim((string)($_GET['subject_search'] ?? ''));
$subjectClass = (int)($_GET['subject_class'] ?? 0);
$chapterSearch = trim((string)($_GET['chapter_search'] ?? ''));
$chapterClass = (int)($_GET['chapter_class'] ?? 0);
$chapterSubject = (int)($_GET['chapter_subject'] ?? 0);

/* Classes list (with subject + chapter counts). */
$classParams = [];
$classSql = 'SELECT c.id, c.name, c.folder_key, c.is_active,
        (SELECT COUNT(*) FROM subjects s WHERE s.class_id = c.id) AS subject_count,
        (SELECT COUNT(*) FROM chapters ch INNER JOIN subjects s2 ON s2.id = ch.subject_id
          WHERE s2.class_id = c.id) AS chapter_count
     FROM classes c WHERE 1 = 1';

if ($classSearch !== '') {
    $classSql .= ' AND (c.name LIKE ? OR c.folder_key LIKE ?)';
    $classParams[] = '%' . $classSearch . '%';
    $classParams[] = '%' . $classSearch . '%';
}

$classSql .= ' ORDER BY c.name ASC';

$classQuery = $pdo->prepare($classSql);
$classQuery->execute($classParams);
$classes = $classQuery->fetchAll();

/* All classes for dropdowns. */
$classOptions = $pdo->query(
    'SELECT id, name FROM classes ORDER BY name ASC'
)->fetchAll();

/* Subjects filtered by active class and search. */
$subjectParams = [];
$subjectSql = 'SELECT s.id, s.name, s.folder_key, s.class_id, s.is_active,
        c.name AS class_name,
        (SELECT COUNT(*) FROM chapters ch WHERE ch.subject_id = s.id) AS chapter_count
     FROM subjects s
     INNER JOIN classes c ON c.id = s.class_id
     WHERE 1 = 1';

if ($subjectClass > 0) {
    $subjectSql .= ' AND s.class_id = ?';
    $subjectParams[] = $subjectClass;
}

if ($subjectSearch !== '') {
    $subjectSql .= ' AND (s.name LIKE ? OR s.folder_key LIKE ? OR c.name LIKE ?)';
    $subjectParams[] = '%' . $subjectSearch . '%';
    $subjectParams[] = '%' . $subjectSearch . '%';
    $subjectParams[] = '%' . $subjectSearch . '%';
}

$subjectSql .= ' ORDER BY c.name ASC, s.name ASC';

$subjectQuery = $pdo->prepare($subjectSql);
$subjectQuery->execute($subjectParams);
$subjects = $subjectQuery->fetchAll();

/* Chapters filtered by active class, subject and search. */
$chapterParams = [];
$chapterSql = 'SELECT ch.id, ch.title, ch.subject_id, ch.file_path, ch.is_active,
        s.name AS subject_name, s.class_id, c.name AS class_name,
        (SELECT COUNT(*) FROM notes n WHERE n.chapter_id = ch.id) AS note_count,
        (SELECT COUNT(*) FROM chapter_permissions cp WHERE cp.chapter_id = ch.id) AS student_count
     FROM chapters ch
     INNER JOIN subjects s ON s.id = ch.subject_id
     INNER JOIN classes c ON c.id = s.class_id
     WHERE 1 = 1';

if ($chapterClass > 0) {
    $chapterSql .= ' AND c.id = ?';
    $chapterParams[] = $chapterClass;
}

if ($chapterSubject > 0) {
    $chapterSql .= ' AND s.id = ?';
    $chapterParams[] = $chapterSubject;
}

if ($chapterSearch !== '') {
    $chapterSql .= ' AND (ch.title LIKE ? OR s.name LIKE ? OR c.name LIKE ?)';
    $chapterParams[] = '%' . $chapterSearch . '%';
    $chapterParams[] = '%' . $chapterSearch . '%';
    $chapterParams[] = '%' . $chapterSearch . '%';
}

$chapterSql .= ' ORDER BY c.name ASC, s.name ASC, ch.id ASC';

$chapterQuery = $pdo->prepare($chapterSql);
$chapterQuery->execute($chapterParams);
$chapters = $chapterQuery->fetchAll();

/* Subjects valid for the chapter subject filter (restricted to active class). */
$chapterSubjectOptions = [];

if ($chapterClass > 0) {
    $optQuery = $pdo->prepare(
        'SELECT id, name FROM subjects WHERE class_id = ? ORDER BY name ASC'
    );
    $optQuery->execute([$chapterClass]);
    $chapterSubjectOptions = $optQuery->fetchAll();
}

/* Edit targets (prefilled modals). */
$editClass = null;
$editSubject = null;
$editChapter = null;

if ($tab === 'classes' && isset($_GET['edit_class'])) {
    $stmt = $pdo->prepare('SELECT id, name, folder_key, is_active FROM classes WHERE id = ?');
    $stmt->execute([(int)$_GET['edit_class']]);
    $editClass = $stmt->fetch() ?: null;
}

if ($tab === 'subjects' && isset($_GET['edit_subject'])) {
    $stmt = $pdo->prepare(
        'SELECT id, name, folder_key, class_id, is_active FROM subjects WHERE id = ?'
    );
    $stmt->execute([(int)$_GET['edit_subject']]);
    $editSubject = $stmt->fetch() ?: null;
}

if ($tab === 'chapters' && isset($_GET['edit_chapter'])) {
    $stmt = $pdo->prepare(
        'SELECT ch.id, ch.title, ch.subject_id, ch.is_active, s.class_id, s.name AS subject_name
         FROM chapters ch INNER JOIN subjects s ON s.id = ch.subject_id
         WHERE ch.id = ?'
    );
    $stmt->execute([(int)$_GET['edit_chapter']]);
    $editChapter = $stmt->fetch() ?: null;
}

require_once __DIR__ . '/../includes/header.php';
?>
<style>
    .acad-tabs { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:22px; }
    .acad-tab-link { display:inline-flex; align-items:center; gap:8px; padding:11px 18px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sm); color:var(--muted); font-weight:700; font-size:14px; text-decoration:none; }
    .acad-tab-link.active { color:#fff; background:var(--primary); border-color:var(--primary); }
    .acad-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow-sm); }
</style>

<div class="page-header">
    <div class="page-header-text">
        <h1 class="page-title">Academic Management</h1>
        <p class="page-header-desc">Manage classes, subjects and chapters used across notes, questions and quizzes.</p>
    </div>
    <div class="page-header-actions">
        <a class="btn btn-outline-secondary" href="/lectures/admin/note-edit.php">
            <i class="bi bi-plus-square me-1"></i> Add Note
        </a>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= e((string)$flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= e((string)$flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="acad-tabs">
    <a class="acad-tab-link <?= $tab === 'classes' ? 'active' : '' ?>" href="/lectures/admin/academic.php?tab=classes">
        <i class="bi bi-mortarboard-fill"></i> Classes (<?= count($classes) ?>)
    </a>
    <a class="acad-tab-link <?= $tab === 'subjects' ? 'active' : '' ?>" href="/lectures/admin/academic.php?tab=subjects">
        <i class="bi bi-journal-text"></i> Subjects (<?= count($subjects) ?>)
    </a>
    <a class="acad-tab-link <?= $tab === 'chapters' ? 'active' : '' ?>" href="/lectures/admin/academic.php?tab=chapters">
        <i class="bi bi-book"></i> Chapters (<?= count($chapters) ?>)
    </a>
</div>

<?php if ($tab === 'classes'): ?>
    <div class="filter-bar">
        <form method="get" action="/lectures/admin/academic.php" class="d-flex flex-wrap gap-2 w-100 align-items-end">
            <input type="hidden" name="tab" value="classes">
            <div class="flex-grow-1" style="min-width:220px;">
                <label class="form-label">Search classes</label>
                <input class="form-control" name="class_search" value="<?= e($classSearch) ?>" placeholder="Name or folder key…">
            </div>
            <div>
                <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i> Search</button>
                <a class="btn btn-outline-secondary" href="/lectures/admin/academic.php?tab=classes">Reset</a>
                <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#classModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Class
                </button>
            </div>
        </form>
    </div>

    <div class="acad-card" style="padding:0;overflow:hidden;">
        <?php if ($classes): ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Folder Key</th>
                            <th>Subjects</th>
                            <th>Chapters</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $c): ?>
                            <tr>
                                <td class="fw-bold"><?= e($c['name']) ?></td>
                                <td><code><?= e($c['folder_key']) ?></code></td>
                                <td><?= (int)$c['subject_count'] ?></td>
                                <td><?= (int)$c['chapter_count'] ?></td>
                                <td><?= ((int)$c['is_active'] === 1) ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>' ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="/lectures/admin/academic.php?tab=classes&edit_class=<?= (int)$c['id'] ?>"><i class="bi bi-pencil"></i></a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('<?= ((int)$c['is_active'] === 1) ? 'Deactivate' : 'Activate' ?> this class?');">
                                        <?= csrf_field('academic') ?>
                                        <input type="hidden" name="action" value="class_toggle">
                                        <input type="hidden" name="class_id" value="<?= (int)$c['id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= ((int)$c['is_active'] === 1) ? '0' : '1' ?>">
                                        <button class="btn btn-sm <?= ((int)$c['is_active'] === 1) ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit">
                                            <?= ((int)$c['is_active'] === 1) ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state"><i class="bi bi-mortarboard fs-1 d-block mb-2"></i><h5 class="fw-bold">No classes found</h5><p class="mb-0">Adjust the search or add a new class.</p></div>
        <?php endif; ?>
    </div>

<?php elseif ($tab === 'subjects'): ?>
    <div class="filter-bar">
        <form method="get" action="/lectures/admin/academic.php" class="d-flex flex-wrap gap-2 w-100 align-items-end">
            <input type="hidden" name="tab" value="subjects">
            <div style="min-width:200px;">
                <label class="form-label">Class</label>
                <select class="form-select" name="subject_class">
                    <option value="0">All Classes</option>
                    <?php foreach ($classOptions as $co): ?>
                        <option value="<?= (int)$co['id'] ?>" <?= $subjectClass === (int)$co['id'] ? 'selected' : '' ?>><?= e($co['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-grow-1" style="min-width:220px;">
                <label class="form-label">Search subjects</label>
                <input class="form-control" name="subject_search" value="<?= e($subjectSearch) ?>" placeholder="Name, folder key or class…">
            </div>
            <div>
                <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i> Search</button>
                <a class="btn btn-outline-secondary" href="/lectures/admin/academic.php?tab=subjects">Reset</a>
                <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#subjectModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Subject
                </button>
            </div>
        </form>
    </div>

    <div class="acad-card" style="padding:0;overflow:hidden;">
        <?php if ($subjects): ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Folder Key</th>
                            <th>Chapters</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $s): ?>
                            <tr>
                                <td class="fw-bold"><?= e($s['name']) ?></td>
                                <td><?= e($s['class_name']) ?></td>
                                <td><code><?= e($s['folder_key']) ?></code></td>
                                <td><?= (int)$s['chapter_count'] ?></td>
                                <td><?= ((int)$s['is_active'] === 1) ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>' ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="/lectures/admin/academic.php?tab=subjects&edit_subject=<?= (int)$s['id'] ?>"><i class="bi bi-pencil"></i></a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('<?= ((int)$s['is_active'] === 1) ? 'Deactivate' : 'Activate' ?> this subject?');">
                                        <?= csrf_field('academic') ?>
                                        <input type="hidden" name="redirect_tab" value="subjects">
                                        <input type="hidden" name="action" value="subject_toggle">
                                        <input type="hidden" name="subject_id" value="<?= (int)$s['id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= ((int)$s['is_active'] === 1) ? '0' : '1' ?>">
                                        <button class="btn btn-sm <?= ((int)$s['is_active'] === 1) ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit"><?= ((int)$s['is_active'] === 1) ? 'Deactivate' : 'Activate' ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state"><i class="bi bi-journal-text fs-1 d-block mb-2"></i><h5 class="fw-bold">No subjects found</h5><p class="mb-0">Adjust the search or add a new subject.</p></div>
        <?php endif; ?>
    </div>

<?php elseif ($tab === 'chapters'): ?>
    <div class="filter-bar">
        <form method="get" action="/lectures/admin/academic.php" class="d-flex flex-wrap gap-2 w-100 align-items-end">
            <input type="hidden" name="tab" value="chapters">
            <div style="min-width:180px;">
                <label class="form-label">Class</label>
                <select class="form-select" name="chapter_class" id="chapterFilterClass">
                    <option value="0">All Classes</option>
                    <?php foreach ($classOptions as $co): ?>
                        <option value="<?= (int)$co['id'] ?>" <?= $chapterClass === (int)$co['id'] ? 'selected' : '' ?>><?= e($co['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="min-width:200px;">
                <label class="form-label">Subject</label>
                <select class="form-select" name="chapter_subject" id="chapterFilterSubject">
                    <option value="0">All Subjects</option>
                    <?php foreach ($chapterSubjectOptions as $so): ?>
                        <option value="<?= (int)$so['id'] ?>" <?= $chapterSubject === (int)$so['id'] ? 'selected' : '' ?>><?= e($so['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-grow-1" style="min-width:200px;">
                <label class="form-label">Search chapters</label>
                <input class="form-control" name="chapter_search" value="<?= e($chapterSearch) ?>" placeholder="Chapter, subject or class…">
            </div>
            <div>
                <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i> Search</button>
                <a class="btn btn-outline-secondary" href="/lectures/admin/academic.php?tab=chapters">Reset</a>
                <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#chapterModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Chapter
                </button>
            </div>
        </form>
    </div>

    <div class="acad-card" style="padding:0;overflow:hidden;">
        <?php if ($chapters): ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Chapter</th>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Notes</th>
                            <th>Students</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($chapters as $ch): ?>
                            <tr>
                                <td class="fw-bold"><?= e($ch['title']) ?></td>
                                <td><?= e($ch['subject_name']) ?></td>
                                <td><?= e($ch['class_name']) ?></td>
                                <td><?= (int)$ch['note_count'] ?></td>
                                <td><?= (int)$ch['student_count'] ?></td>
                                <td><?= ((int)$ch['is_active'] === 1) ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>' ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="/lectures/admin/academic.php?tab=chapters&edit_chapter=<?= (int)$ch['id'] ?>"><i class="bi bi-pencil"></i></a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('<?= ((int)$ch['is_active'] === 1) ? 'Deactivate' : 'Activate' ?> this chapter?');">
                                        <?= csrf_field('academic') ?>
                                        <input type="hidden" name="redirect_tab" value="chapters">
                                        <input type="hidden" name="action" value="chapter_toggle">
                                        <input type="hidden" name="chapter_id" value="<?= (int)$ch['id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= ((int)$ch['is_active'] === 1) ? '0' : '1' ?>">
                                        <button class="btn btn-sm <?= ((int)$ch['is_active'] === 1) ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit"><?= ((int)$ch['is_active'] === 1) ? 'Deactivate' : 'Activate' ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state"><i class="bi bi-book fs-1 d-block mb-2"></i><h5 class="fw-bold">No chapters found</h5><p class="mb-0">Adjust the search or add a new chapter.</p></div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- ============ MODALS ============ -->

<!-- Class Modal -->
<div class="modal fade" id="classModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <?= csrf_field('academic') ?>
            <input type="hidden" name="redirect_tab" value="classes">
            <input type="hidden" name="action" value="class_save">
            <input type="hidden" name="class_id" value="<?= $editClass ? (int)$editClass['id'] : 0 ?>">
            <div class="modal-header">
                <h5 class="modal-title"><?= $editClass ? 'Edit Class' : 'Add Class' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Class name</label>
                    <input class="form-control" name="name" required maxlength="50" value="<?= $editClass ? e($editClass['name']) : '' ?>" placeholder="e.g. HSC">
                </div>
                <div class="mb-3">
                    <label class="form-label">Folder key <span class="text-muted small">(optional)</span></label>
                    <input class="form-control" name="folder_key" maxlength="50" value="<?= $editClass ? e($editClass['folder_key']) : '' ?>" placeholder="Auto-generated from name if blank">
                    <div class="form-text">Used to match scanned note folders.</div>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" id="classActive" value="1" <?= (!$editClass || (int)$editClass['is_active'] === 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="classActive">Active</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><?= $editClass ? 'Save Changes' : 'Add Class' ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Subject Modal -->
<div class="modal fade" id="subjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <?= csrf_field('academic') ?>
            <input type="hidden" name="redirect_tab" value="subjects">
            <input type="hidden" name="action" value="subject_save">
            <input type="hidden" name="subject_id" value="<?= $editSubject ? (int)$editSubject['id'] : 0 ?>">
            <div class="modal-header">
                <h5 class="modal-title"><?= $editSubject ? 'Edit Subject' : 'Add Subject' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Class</label>
                    <select class="form-select" name="class_id" required>
                        <option value="">Select class…</option>
                        <?php foreach ($classOptions as $co): ?>
                            <option value="<?= (int)$co['id'] ?>" <?= ($editSubject && (int)$editSubject['class_id'] === (int)$co['id']) ? 'selected' : '' ?>><?= e($co['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Subject name</label>
                    <input class="form-control" name="name" required maxlength="100" value="<?= $editSubject ? e($editSubject['name']) : '' ?>" placeholder="e.g. Physics 1st Paper">
                </div>
                <div class="mb-3">
                    <label class="form-label">Folder key <span class="text-muted small">(optional)</span></label>
                    <input class="form-control" name="folder_key" maxlength="100" value="<?= $editSubject ? e($editSubject['folder_key']) : '' ?>" placeholder="Auto-generated from name if blank">
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" id="subjectActive" value="1" <?= (!$editSubject || (int)$editSubject['is_active'] === 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="subjectActive">Active</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><?= $editSubject ? 'Save Changes' : 'Add Subject' ?></button>
            </div>
        </form>
    </div>
</div>


<!-- Chapter Modal -->
<div class="modal fade" id="chapterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <?= csrf_field('academic') ?>
            <input type="hidden" name="redirect_tab" value="chapters">
            <input type="hidden" name="action" value="chapter_save">
            <input type="hidden" name="chapter_id" value="<?= $editChapter ? (int)$editChapter['id'] : 0 ?>">
            <div class="modal-header">
                <h5 class="modal-title"><?= $editChapter ? 'Edit Chapter' : 'Add Chapter' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Class</label>
                    <select class="form-select" id="chapterModalClass" required>
                        <option value="">Select class…</option>
                        <?php foreach ($classOptions as $co): ?>
                            <option value="<?= (int)$co['id'] ?>" <?= ($editChapter && (int)$editChapter['class_id'] === (int)$co['id']) ? 'selected' : '' ?>><?= e($co['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <select class="form-select" name="subject_id" id="chapterModalSubject" required>
                        <option value="">Select class first…</option>
                    </select>
                    <input type="hidden" id="chapterModalPreselectSubject" value="<?= $editChapter ? (int)$editChapter['subject_id'] : 0 ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Chapter title</label>
                    <input class="form-control" name="title" required maxlength="200" value="<?= $editChapter ? e($editChapter['title']) : '' ?>" placeholder="e.g. Chapter 1 - Cell">
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" id="chapterActive" value="1" <?= (!$editChapter || (int)$editChapter['is_active'] === 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="chapterActive">Active</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><?= $editChapter ? 'Save Changes' : 'Add Chapter' ?></button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php if ($editClass): ?>
    bootstrap.Modal.getOrCreateInstance(document.getElementById('classModal')).show();
    <?php elseif ($editSubject): ?>
    bootstrap.Modal.getOrCreateInstance(document.getElementById('subjectModal')).show();
    <?php elseif ($editChapter): ?>
    bootstrap.Modal.getOrCreateInstance(document.getElementById('chapterModal')).show();
    <?php endif; ?>

    function loadSubjects(classId, subjectSel, preselect, placeholder) {
        subjectSel.innerHTML = '<option value="">' + placeholder + '</option>';
        if (!classId) { return; }
        fetch('/lectures/admin/ajax-options.php?field=subject&class_id=' + classId)
            .then(function (r) { return r.json(); })
            .then(function (list) {
                list.forEach(function (o) {
                    var opt = document.createElement('option');
                    opt.value = o.id;
                    opt.textContent = o.name;
                    if (preselect && String(preselect) === String(o.id)) { opt.selected = true; }
                    subjectSel.appendChild(opt);
                });
            })
            .catch(function () {});
    }

    var fClass = document.getElementById('chapterFilterClass');
    var fSubject = document.getElementById('chapterFilterSubject');
    if (fClass && fSubject) {
        fClass.addEventListener('change', function () {
            loadSubjects(this.value, fSubject, 0, 'All Subjects');
        });
    }

    var mClass = document.getElementById('chapterModalClass');
    var mSubject = document.getElementById('chapterModalSubject');
    var mPreselect = document.getElementById('chapterModalPreselectSubject');
    if (mClass && mSubject) {
        var preselectVal = mPreselect ? mPreselect.value : '0';
        if (mClass.value) {
            loadSubjects(mClass.value, mSubject, preselectVal, 'Select subject…');
        }
        mClass.addEventListener('change', function () {
            loadSubjects(this.value, mSubject, 0, 'Select subject…');
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>