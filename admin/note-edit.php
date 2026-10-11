<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Manage Notes';
$activePage = 'notes';
$flash = null;

/* ---------- helpers ---------- */
function note_redirect(string $m, string $t = 'success'): never {
    $_SESSION['note_flash'] = ['message' => $m, 'type' => $t];
    redirect('/lectures/admin/note-edit.php');
}

$classes = $pdo->query("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY name")->fetchAll();

/* ---------- handle POST (create / update / delete) ---------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify('note')) {
        http_response_code(403);
        exit('Invalid security token.');
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'delete') {
        $noteId = (int)($_POST['note_id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM notes WHERE id = ?');
        $stmt->execute([$noteId]);
        note_redirect($stmt->rowCount() > 0 ? 'Note deleted.' : 'Note not found.', $stmt->rowCount() > 0 ? 'success' : 'warning');
    }

    if ($action === 'save') {
        $noteId = (int)($_POST['note_id'] ?? 0);
        $chapterId = (int)($_POST['chapter_id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $contentType = (string)($_POST['content_type'] ?? 'html');

        if ($chapterId < 1) { note_redirect('Select a chapter first.', 'danger'); }
        if ($title === '' || mb_strlen($title) > 200) { note_redirect('Enter a valid title.', 'danger'); }

        // Verify chapter exists.
        $chk = $pdo->prepare('SELECT id FROM chapters WHERE id = ?');
        $chk->execute([$chapterId]);
        if (!$chk->fetch()) { note_redirect('Chapter not found.', 'danger'); }

        $htmlContent = null;
        $pdfPath = null;

        if ($contentType === 'pdf') {
            // Replace PDF only if a new file was uploaded.
            if (!empty($_FILES['pdf_file']['name'])) {
                $err = validate_pdf_upload($_FILES['pdf_file']);
                if ($err !== null) { note_redirect($err, 'danger'); }
                $safe = bin2hex(random_bytes(12)) . '.pdf';
                $dest = __DIR__ . '/../uploads/notes/' . $safe;
                if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $dest)) {
                    note_redirect('Could not store the uploaded PDF.', 'danger');
                }
                $pdfPath = 'uploads/notes/' . $safe;
            }
            $contentType = 'pdf';
        } else {
            // HTML note: sanitize pasted/typed HTML (never store executable PHP).
            $rawHtml = (string)($_POST['html_content'] ?? '');
            $htmlContent = sanitize_note_html($rawHtml);
            $contentType = 'html';
        }

        if ($noteId > 0) {
            if ($contentType === 'pdf' && $pdfPath === null) {
                // Keep existing PDF path on edit-without-reupload.
                $pdo->prepare('UPDATE notes SET chapter_id=?, title=?, sort_order=?, is_active=?, updated_at=NOW() WHERE id=?')
                    ->execute([$chapterId, $title, $sortOrder, $isActive, $noteId]);
            } else {
                $pdo->prepare('UPDATE notes SET chapter_id=?, title=?, content_type=?, html_content=?, pdf_path=?, sort_order=?, is_active=?, updated_at=NOW() WHERE id=?')
                    ->execute([$chapterId, $title, $contentType, $htmlContent, $pdfPath, $sortOrder, $isActive, $noteId]);
            }
            note_redirect('Note updated.');
        } else {
            $pdo->prepare('INSERT INTO notes (chapter_id,title,content_type,html_content,pdf_path,sort_order,is_active) VALUES (?,?,?,?,?,?,?)')
                ->execute([$chapterId, $title, $contentType, $htmlContent, $pdfPath, $sortOrder, $isActive]);
            note_redirect('Note created.');
        }
    }
}

/* ---------- flash + edit target ---------- */
$flash = $_SESSION['note_flash'] ?? null;
unset($_SESSION['note_flash']);

$editId = (int)($_GET['id'] ?? 0);
$presetChapter = (int)($_GET['chapter_id'] ?? 0);
$note = null;
$noteChapterId = $presetChapter;

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM notes WHERE id = ?');
    $stmt->execute([$editId]);
    $note = $stmt->fetch();
    if ($note) { $noteChapterId = (int)$note['chapter_id']; }
}

// Resolve the current chapter's subject/class for the form + Go workflow.
$noteChapter = null;
if ($noteChapterId > 0) {
    $stmt = $pdo->prepare("SELECT ch.id, ch.title, ch.subject_id, sub.class_id, sub.name AS subject_name, c.name AS class_name
        FROM chapters ch INNER JOIN subjects sub ON sub.id = ch.subject_id
        INNER JOIN classes c ON c.id = sub.class_id WHERE ch.id = ? LIMIT 1");
    $stmt->execute([$noteChapterId]);
    $noteChapter = $stmt->fetch();
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .step-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow-sm); padding:20px; }
    .step-label { display:inline-flex; align-items:center; gap:8px; font-weight:800; font-size:13px; color:var(--primary); text-transform:uppercase; letter-spacing:.5px; margin-bottom:12px; }
    .note-list-row { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 16px; border-bottom:1px solid var(--border); }
    .note-list-row:last-child { border-bottom:0; }
</style>

<div class="page-header">
    <div class="page-header-text">
        <h1 class="page-title"><?= $note ? 'Edit Note' : 'Add Note' ?></h1>
        <p class="page-header-desc">Select Class → Subject → Chapter, then paste HTML or upload a PDF.</p>
    </div>
    <div class="page-header-actions">
        <a class="btn btn-outline-secondary" href="/lectures/admin/navigator.php">
            <i class="bi bi-journal-richtext me-1"></i> Note Navigator
        </a>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= e((string)$flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= e((string)$flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- STEP 1: Class → Subject → Chapter → Go -->
<div class="step-card mb-4">
    <div class="step-label"><i class="bi bi-signpost-split"></i> Step 1 — Choose a chapter</div>
    <form method="get" action="/lectures/admin/note-edit.php" class="row g-3 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label" for="f_class">Class</label>
            <select class="form-select" id="f_class" name="class_id" required data-workflow="1">
                <option value="">Select class</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= ($noteChapter && (int)$noteChapter['class_id'] === (int)$c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label" for="f_subject">Subject</label>
            <select class="form-select" id="f_subject" name="subject_id" required data-workflow="1">
                <option value="">Select subject</option>
                <?php if ($noteChapter): ?>
                    <option value="<?= (int)$noteChapter['subject_id'] ?>" selected><?= e($noteChapter['subject_name']) ?></option>
                <?php endif; ?>
            </select>
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label" for="f_chapter">Chapter</label>
            <select class="form-select" id="f_chapter" name="chapter_id" required>
                <option value="">Select chapter</option>
                <?php if ($noteChapter): ?>
                    <option value="<?= (int)$noteChapter['id'] ?>" selected><?= e($noteChapter['title']) ?></option>
                <?php endif; ?>
            </select>
        </div>
        <div class="col-12 col-md-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-arrow-right-circle me-1"></i> Go
            </button>
        </div>
    </form>
</div>

<?php if ($noteChapter): ?>
    <!-- STEP 2: Add/Edit form -->
    <div class="step-card mb-4">
        <div class="step-label"><i class="bi bi-pencil-square"></i> Step 2 — Note content</div>
        <div class="mb-3 text-secondary small">
            Editing notes for <strong><?= e($noteChapter['class_name']) ?> → <?= e($noteChapter['subject_name']) ?> → <?= e($noteChapter['title']) ?></strong>
        </div>

        <form method="post" action="/lectures/admin/note-edit.php" enctype="multipart/form-data">
            <?= csrf_field('note') ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="note_id" value="<?= (int)($note['id'] ?? 0) ?>">
            <input type="hidden" name="chapter_id" value="<?= (int)$noteChapter['id'] ?>">

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-8">
                    <label class="form-label" for="title">Title *</label>
                    <input class="form-control" id="title" name="title" maxlength="200" required value="<?= e((string)($note['title'] ?? '')) ?>" placeholder="e.g. Chapter 1 — Introduction">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="sort_order">Order</label>
                    <input class="form-control" id="sort_order" name="sort_order" type="number" min="0" value="<?= (int)($note['sort_order'] ?? 0) ?>">
                </div>
                <div class="col-6 col-md-2 d-flex align-items-end">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= ((int)($note['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label d-block">Content type</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="content_type" id="ct_html" value="html" <?= (($note['content_type'] ?? 'html') === 'html') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ct_html">HTML (paste ChatGPT output)</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="content_type" id="ct_pdf" value="pdf" <?= (($note['content_type'] ?? '') === 'pdf') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ct_pdf">PDF upload</label>
                </div>
            </div>

            <div id="htmlBlock" class="mb-3">
                <label class="form-label" for="html_content">HTML content</label>
                <textarea class="form-control" id="html_content" name="html_content" rows="10" placeholder="Paste full HTML here (MathJax, CSS, JS supported). PHP is stripped automatically."><?= e((string)($note['html_content'] ?? '')) ?></textarea>
                <div class="form-text text-muted">Pasted PHP code is removed automatically. MathJax/CSS/JS are preserved.</div>
            </div>

            <div id="pdfBlock" class="mb-3">
                <label class="form-label" for="pdf_file">PDF file (max 20 MB)</label>
                <input class="form-control" type="file" id="pdf_file" name="pdf_file" accept="application/pdf">
                <?php if (($note['content_type'] ?? '') === 'pdf' && !empty($note['pdf_path'])): ?>
                    <div class="form-text text-muted">Current PDF: <code><?= e((string)$note['pdf_path']) ?></code>. Upload a new file to replace it.</div>
                <?php endif; ?>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle me-1"></i> <?= $note ? 'Update Note' : 'Create Note' ?>
                </button>
                <a class="btn btn-outline-secondary" href="/lectures/admin/note-edit.php">Cancel</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php if ($noteChapter): ?>
    <!-- Existing notes for this chapter -->
    <?php
    $lst = $pdo->prepare('SELECT id, title, content_type, is_active FROM notes WHERE chapter_id = ? ORDER BY sort_order, id');
    $lst->execute([(int)$noteChapter['id']]);
    $chapterNotes = $lst->fetchAll();
    ?>
    <?php if ($chapterNotes): ?>
        <div class="panel" style="padding:0;">
            <div style="padding:16px 18px; border-bottom:1px solid var(--border); font-weight:750;">Existing notes in this chapter</div>
            <?php foreach ($chapterNotes as $cn): ?>
                <div class="note-list-row">
                    <div>
                        <strong><?= e($cn['title']) ?></strong>
                        <span class="badge badge-soft-primary ms-2 text-uppercase"><?= e($cn['content_type']) ?></span>
                        <?php if ((int)$cn['is_active'] === 0): ?><span class="badge text-bg-secondary ms-1">Hidden</span><?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-secondary" href="/lectures/admin/note-edit.php?id=<?= (int)$cn['id'] ?>"><i class="bi bi-pencil"></i> Edit</a>
                        <form method="post" action="/lectures/admin/note-edit.php" class="d-inline" onsubmit="return confirm('Delete this note permanently?');">
                            <?= csrf_field('note') ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="note_id" value="<?= (int)$cn['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Dynamic Class -> Subject -> Chapter cascade via JSON endpoint.
    const classSel = document.getElementById('f_class');
    const subjectSel = document.getElementById('f_subject');
    const chapterSel = document.getElementById('f_chapter');

    const load = (url, sel, placeholder) => fetch(url).then(r => r.json()).then(list => {
        sel.innerHTML = '<option value="">' + placeholder + '</option>';
        list.forEach(item => {
            const o = document.createElement('option');
            o.value = item.id; o.textContent = item.name;
            sel.appendChild(o);
        });
    });

    if (classSel && subjectSel) {
        classSel.addEventListener('change', function () {
            if (!this.value) { subjectSel.innerHTML = '<option value="">Select subject</option>'; chapterSel.innerHTML = '<option value="">Select chapter</option>'; return; }
            load('/lectures/admin/ajax-options.php?field=subject&class_id=' + this.value, subjectSel, 'Select subject');
            chapterSel.innerHTML = '<option value="">Select chapter</option>';
        });
    }
    if (subjectSel && chapterSel) {
        subjectSel.addEventListener('change', function () {
            if (!this.value) { chapterSel.innerHTML = '<option value="">Select chapter</option>'; return; }
            load('/lectures/admin/ajax-options.php?field=chapter&subject_id=' + this.value, chapterSel, 'Select chapter');
        });
    }

    // Toggle HTML vs PDF blocks.
    const ctHtml = document.getElementById('ct_html');
    const ctPdf = document.getElementById('ct_pdf');
    const htmlBlock = document.getElementById('htmlBlock');
    const pdfBlock = document.getElementById('pdfBlock');
    function toggleBlocks() {
        const isPdf = ctPdf && ctPdf.checked;
        if (htmlBlock) htmlBlock.style.display = isPdf ? 'none' : '';
        if (pdfBlock) pdfBlock.style.display = isPdf ? '' : 'none';
    }
    if (ctHtml) ctHtml.addEventListener('change', toggleBlocks);
    if (ctPdf) ctPdf.addEventListener('change', toggleBlocks);
    toggleBlocks();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
