<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Shared DB Note Renderer (reusable, isolated)
|--------------------------------------------------------------------------
| Renders a single database-backed note (row from `notes`) inside the shared
| application layout. Supports:
|   - content_type = 'html'  -> sanitized HTML body (MathJax/CSS/JS preserved)
|   - content_type = 'pdf'   -> embedded <iframe> viewer for the stored PDF
|
| Required variables before include:
|   $dbNote        array   The notes row (id,title,content_type,html_content,pdf_path)
|   $readerTitle   string
|   $readerBack    string
|   $readerBackLabel string
| Optional:
|   $readerMeta    array   breadcrumbs ['class'=>..,'subject'=>..]
|   $readerChapters array  optional chapter switcher (same contract as notes-reader)
|   $readerCurrentId int
|   $readerJumpBase string
|   $activePage    string
*/

$dbNote = is_array($dbNote ?? null) ? $dbNote : [];
$readerTitle = (string)($readerTitle ?? ($dbNote['title'] ?? 'Note'));
$readerBack = (string)($readerBack ?? '/lectures/index.php');
$readerBackLabel = (string)($readerBackLabel ?? 'Back');
$readerMeta = is_array($readerMeta ?? null) ? $readerMeta : [];
$readerChapters = is_array($readerChapters ?? null) ? $readerChapters : [];
$readerCurrentId = (int)($readerCurrentId ?? 0);
$readerJumpBase = (string)($readerJumpBase ?? '');
$activePage = $activePage ?? 'dashboard';
$showSidebar = $showSidebar ?? true;

// Normalize the stored PDF path into a browser-reachable web URL.
// Stored paths are relative to the project root (e.g. "uploads/notes/x.pdf"),
// but the viewer runs from a subfolder, so a raw relative src resolves wrongly
// and 404s. Leave absolute (/) and full (scheme://) URLs untouched.
$pdfSrc = (string)($dbNote['pdf_path'] ?? '');
if ($pdfSrc !== '' && $pdfSrc[0] !== '/' && !preg_match('#^[a-z][a-z0-9+.-]*://#i', $pdfSrc)) {
    $pdfSrc = '/lectures/' . ltrim($pdfSrc, '/');
}

$pageTitle = $readerTitle;

require __DIR__ . '/header.php';
?>

<style>
    .note-reader {
        position: relative;
        max-width: 1180px;
        margin: 0 auto;
    }

    .note-breadcrumb-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 18px;
        margin-bottom: 18px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
    }

    .note-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        color: var(--muted);
        font-size: 13px;
    }

    .note-breadcrumb strong {
        color: var(--text);
    }

    .note-switch {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .note-switch .form-select {
        min-width: 220px;
        max-width: 46vw;
        min-height: 38px;
        font-size: 13px;
        border-radius: var(--radius-sm);
    }

    .db-note-body {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
    }

    /* Isolated container for pasted HTML so it does not disturb app chrome. */
    .db-html-note {
        padding: 8px 14px 20px;
    }

    .db-pdf-frame {
        width: 100%;
        height: 80vh;
        min-height: 620px;
        border: 0;
        background: #F8FAFC;
    }

    .db-pdf-fallback {
        padding: 30px;
        text-align: center;
    }
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
            <strong><?= e($readerTitle) ?></strong>
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
                    <option value="<?= $rcId ?>" <?= $rcId === $readerCurrentId ? 'selected' : '' ?>>
                        <?= e($rcLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-box-arrow-in-right me-1"></i> Go
            </button>
        </form>
    <?php endif; ?>

    <div class="db-note-body">
        <?php if (($dbNote['content_type'] ?? '') === 'pdf' && $pdfSrc !== ''): ?>
            <iframe
                class="db-pdf-frame"
                src="<?= e($pdfSrc) ?>"
                title="<?= e($readerTitle) ?>"
                loading="lazy"></iframe>
        <?php elseif (($dbNote['content_type'] ?? '') === 'html'): ?>
            <div class="db-html-note">
                <?= (string)($dbNote['html_content'] ?? '') ?>
            </div>
        <?php else: ?>
            <div class="db-pdf-fallback text-secondary">
                This note has no renderable content yet.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
