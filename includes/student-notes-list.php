<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Student Notes List — all notes inside one permitted chapter
|--------------------------------------------------------------------------
| Included by student/chapter.php after the per-chapter permission check.
| Every active note inside a permitted chapter is viewable (permission is
| granted per chapter). Reuses the same visual language as the unified DB
| note renderer (breadcrumb bar + chapter switcher + note rows).
|
| Expects (already set by chapter.php):
|   $chapter, $dbNotes, $readerChapters, $readerMeta, $readerBack,
|   $readerBackLabel, $readerCurrentId, $readerJumpBase, $activePage,
|   $showSidebar
*/

/*
|--------------------------------------------------------------------------
| Defensive variable guards
|--------------------------------------------------------------------------
| This file is included by student/chapter.php after the permission check.
| Intelephense flags the caller-provided variables as "undefined" because
| they are set by the parent page. These guards make the contract explicit
| and keep the partial safe to include even if a variable is missing.
*/
$chapter = is_array($chapter ?? null) ? $chapter : [];
$dbNotes = is_array($dbNotes ?? null) ? $dbNotes : [];
$readerMeta = is_array($readerMeta ?? null) ? $readerMeta : [];
$readerChapters = is_array($readerChapters ?? null) ? $readerChapters : [];
$readerBack = (string)($readerBack ?? '/lectures/student/index.php');
$readerBackLabel = (string)($readerBackLabel ?? 'Back to Dashboard');
$readerCurrentId = (int)($readerCurrentId ?? 0);
$readerJumpBase = (string)($readerJumpBase ?? '');
$activePage = $activePage ?? 'dashboard';
$showSidebar = $showSidebar ?? true;

$pageTitle = (string)($chapter['title'] ?? 'Chapter');
$snoteChapterId = (int)($chapter['id'] ?? 0);

require __DIR__ . '/header.php';
?>

<style>
    .note-reader { position: relative; max-width: 1180px; margin: 0 auto; }
    .note-breadcrumb-bar {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
        gap: 12px; padding: 14px 18px; margin-bottom: 18px;
        background: var(--surface); border: 1px solid var(--border);
        border-radius: var(--radius); box-shadow: var(--shadow-sm);
    }
    .note-breadcrumb {
        display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
        color: var(--muted); font-size: 13px;
    }
    .note-breadcrumb strong { color: var(--text); }
    .note-switch { display: flex; align-items: center; gap: 8px; }
    .note-switch .form-select {
        min-width: 220px; max-width: 46vw; min-height: 38px;
        font-size: 13px; border-radius: var(--radius-sm);
    }
    .snote-list {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-sm);
    }
    .snote-row {
        display: flex; align-items: center; justify-content: space-between;
        gap: 14px; padding: 15px 18px; border-bottom: 1px solid var(--border);
    }
    .snote-row:last-child { border-bottom: 0; }
    .snote-row:hover { background: var(--bg); }
    .snote-info { display: flex; align-items: center; gap: 13px; min-width: 0; }
    .snote-badge {
        display: grid; place-items: center; width: 42px; height: 42px; flex-shrink: 0;
        color: var(--primary); background: var(--primary-soft);
        border-radius: var(--radius-sm); font-size: 18px;
    }
    .snote-name { font-weight: 700; color: var(--text); }
    .snote-meta { color: var(--muted); font-size: 12px; margin-top: 2px; }
</style>

<div class="note-reader">
    <div class="note-breadcrumb-bar">
        <nav class="note-breadcrumb" aria-label="Breadcrumb">
            <?php if (!empty($readerMeta['class'])): ?>
                <span><?= e((string)$readerMeta['class']) ?></span>
                <i class="bi bi-chevron-right"></i>
            <?php endif; ?>
            <?php if (!empty($readerMeta['subject'])): ?>
                <span><?= e((string)$readerMeta['subject']) ?></span>
                <i class="bi bi-chevron-right"></i>
            <?php endif; ?>
            <strong><?= e((string)$chapter['title']) ?></strong>
        </nav>

        <a class="btn btn-outline-secondary" href="<?= e($readerBack) ?>">
            <i class="bi bi-arrow-left me-1"></i> <?= e($readerBackLabel) ?>
        </a>
    </div>

    <?php if (!empty($readerChapters) && $readerJumpBase !== ''): ?>
        <form method="get" action="<?= e($readerJumpBase) ?>" class="note-switch mb-3">
            <label class="visually-hidden" for="noteChapterSelect">Choose a chapter</label>
            <i class="bi bi-funnel text-secondary" aria-hidden="true"></i>
            <select class="form-select" id="noteChapterSelect" name="id">
                <option value="">Go to chapter…</option>
                <?php foreach ($readerChapters as $rc): ?>
                    <?php
                    $rcId = (int)($rc['id'] ?? 0);
                    $rcLabel = (string)($rc['label'] ?? ($rc['title'] ?? ''));
                    ?>
                    <option value="<?= $rcId ?>" <?= $rcId === (int)$readerCurrentId ? 'selected' : '' ?>>
                        <?= e($rcLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-box-arrow-in-right me-1"></i> Go
            </button>
        </form>
    <?php endif; ?>

    <?php if (!empty($dbNotes)): ?>
        <div class="snote-list">
            <?php foreach ($dbNotes as $note): ?>
                <?php
                $noteType = (string)($note['content_type'] ?? '');
                $isPdf = $noteType === 'pdf';
                $hidden = (int)($note['is_active'] ?? 1) === 0;
                ?>
                <div class="snote-row">
                    <div class="snote-info">
                        <span class="snote-badge">
                            <i class="bi <?= $isPdf ? 'bi-file-earmark-pdf' : 'bi-journal-text' ?>"></i>
                        </span>
                        <div>
                            <div class="snote-name"><?= e((string)$note['title']) ?></div>
                            <div class="snote-meta"><?= e(strtoupper($noteType)) ?><?= $hidden ? ' · Hidden' : '' ?></div>
                        </div>
                    </div>
                    <a class="btn btn-primary btn-sm"
                       href="/lectures/student/chapter.php?id=<?= $snoteChapterId ?>&note_id=<?= (int)$note['id'] ?>">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Open
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="snote-list"><div class="snote-row">
            <div class="text-secondary">এই Chapter-এ এখনো কোনো Note নেই।</div>
        </div></div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/footer.php'; ?>

