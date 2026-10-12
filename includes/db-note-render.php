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

    /* Isolated, themed container for pasted HTML notes. */
    .db-html-note {
        padding: 10px 26px 32px;
        color: var(--text);
        font-family: 'Hind Siliguri', 'Noto Sans Bengali', 'Segoe UI', system-ui, sans-serif;
        font-size: 16.5px;
        line-height: 1.95;
        word-wrap: break-word;
        overflow-wrap: anywhere;
    }

    /* Inner readable column so long lines don't stretch too wide. */
    .db-html-note > * {
        max-width: 860px;
        margin-left: auto;
        margin-right: auto;
    }

    /* ---- Headings: handwritten Bengali accent font ---- */
    .db-html-note h1,
    .db-html-note h2,
    .db-html-note h3,
    .db-html-note h4,
    .db-html-note h5,
    .db-html-note h6 {
        font-family: 'Galada', 'Hind Siliguri', 'Noto Sans Bengali', cursive;
        color: var(--text);
        line-height: 1.6;
        letter-spacing: 0.2px;
        margin: 34px 0 12px;
    }

    .db-html-note h1 {
        font-size: 30px;
        padding-bottom: 12px;
        border-bottom: 3px solid var(--primary-soft);
        margin-top: 8px;
    }

    .db-html-note h2 {
        font-size: 25px;
        padding-left: 14px;
        border-left: 5px solid var(--primary);
    }

    .db-html-note h3 { font-size: 21px; }
    .db-html-note h4 { font-size: 18px; color: var(--primary-hover); }
    .db-html-note h5 { font-size: 16.5px; color: var(--primary-hover); }
    .db-html-note h6 { font-size: 15px; color: var(--muted); }

    /* ---- Paragraphs & inline text ---- */
    .db-html-note p {
        margin: 0 0 16px;
        color: var(--text);
    }

    .db-html-note strong,
    .db-html-note b {
        font-weight: 700;
        color: var(--text);
    }

    .db-html-note em,
    .db-html-note i {
        font-style: italic;
        color: var(--primary-hover);
    }

    .db-html-note u { text-decoration-color: var(--primary); }

    .db-html-note small { color: var(--muted); font-size: 13px; }

    .db-html-note mark {
        background: var(--warning-soft);
        color: #7c4a03;
        padding: 1px 6px;
        border-radius: 6px;
    }

    .db-html-note a {
        color: var(--primary);
        font-weight: 600;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .db-html-note a:hover { color: var(--primary-hover); }

    /* ---- Blockquote ---- */
    .db-html-note blockquote {
        margin: 18px 0;
        padding: 12px 20px;
        color: var(--muted);
        background: var(--bg);
        border-left: 4px solid var(--primary);
        border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
    }

    .db-html-note blockquote p:last-child { margin-bottom: 0; }

    /* ---- Lists ---- */
    .db-html-note ul,
    .db-html-note ol {
        margin: 0 0 16px;
        padding-left: 26px;
    }

    .db-html-note li { margin: 6px 0; }
    .db-html-note li::marker { color: var(--primary); font-weight: 700; }

    /* ---- Inline code & pre ---- */
    .db-html-note code {
        font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 0.9em;
        color: #b5306b;
        background: var(--primary-soft);
        padding: 2px 7px;
        border-radius: 6px;
    }

    .db-html-note pre {
        margin: 16px 0;
        padding: 16px 18px;
        background: #0F172A;
        color: #E2E8F0;
        border-radius: var(--radius-sm);
        overflow-x: auto;
        line-height: 1.6;
    }

    .db-html-note pre code {
        background: transparent;
        color: inherit;
        padding: 0;
    }

    /* ---- Tables ---- */
    .db-html-note table {
        width: 100%;
        margin: 18px 0;
        border-collapse: collapse;
        font-size: 15px;
    }

    .db-html-note th,
    .db-html-note td {
        padding: 10px 14px;
        border: 1px solid var(--border);
        text-align: left;
    }

    .db-html-note thead th {
        background: var(--primary-soft);
        color: var(--primary-hover);
        font-weight: 700;
    }

    .db-html-note tbody tr:nth-child(even) { background: var(--bg); }

    /* ---- Images ---- */
    .db-html-note img {
        max-width: 100%;
        height: auto;
        border-radius: var(--radius-sm);
        margin: 12px 0;
    }

    /* ---- Horizontal rule ---- */
    .db-html-note hr {
        margin: 28px 0;
        border: 0;
        border-top: 1px solid var(--border);
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

<?php
/*
|--------------------------------------------------------------------------
| Auto-load MathJax for HTML notes
|--------------------------------------------------------------------------
| Equations only render when MathJax is configured and loaded. Rather than
| making authors paste the MathJax setup into every note, inject it here so
| all HTML notes render formulas automatically. We skip injection when the
| pasted HTML already references MathJax, to avoid loading it twice.
*/
$htmlContent = (string)($dbNote['html_content'] ?? '');
$needsMathjax = (($dbNote['content_type'] ?? '') === 'html')
    && $htmlContent !== ''
    && stripos($htmlContent, 'mathjax') === false;
?>
<?php if ($needsMathjax): ?>
<script>
window.MathJax = {
  tex: {
    inlineMath: [['\\(', '\\)'], ['$', '$']],
    displayMath: [['\\[', '\\]'], ['$$', '$$']]
  },
  options: {
    skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code']
  },
  startup: {
    typeset: true
  }
};
</script>
<script defer src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
<?php endif; ?>

