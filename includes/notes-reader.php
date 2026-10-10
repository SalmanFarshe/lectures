<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Unified Notes Reader (shared, reusable)
|--------------------------------------------------------------------------
| Renders any scanned lecture note inside the shared application layout
| (theme #4F46E5 / #F8FAFC) while preserving the note's own HTML, CSS,
| JavaScript and MathJax exactly.
|
| Required variables before including this file:
|   $notePath        string  Validated, absolute path to the note PHP file
|   $readerTitle     string  Page/chapter title for the breadcrumb + <title>
|   $readerBack      string  URL for the Back button
|   $readerBackLabel string  Label for the Back button
|
| Optional:
|   $readerMeta   array    ['subject' => ..., 'class' => ...] breadcrumbs
|
| The caller MUST have already validated $notePath (exists, .php, inside the
| notes directory, readable) before including this file.
*/

$notePath = (string)($notePath ?? '');
$readerTitle = (string)($readerTitle ?? 'Notes');
$readerBack = (string)($readerBack ?? '/lectures/index.php');
$readerBackLabel = (string)($readerBackLabel ?? 'Back');
$readerMeta = is_array($readerMeta ?? null) ? $readerMeta : [];
$activePage = $activePage ?? 'dashboard';

/*
| Optional chapter switcher: callers (e.g. the student viewer) may pass a
| list of the viewer's accessible chapters so the reader can render a
| "jump to chapter" filter directly on the note page.
|   $readerChapters   array   [ ['id'=>int,'title'=>string,'subject'=>string], ... ]
|   $readerCurrentId  int     The chapter currently being viewed
|   $readerJumpBase   string  Base URL for jumping, e.g. /lectures/student/chapter.php
*/
$readerChapters = is_array($readerChapters ?? null) ? $readerChapters : [];
$readerCurrentId = (int)($readerCurrentId ?? 0);
$readerJumpBase = (string)($readerJumpBase ?? '');

if ($notePath === '' || !is_file($notePath) || !is_readable($notePath)) {
    http_response_code(404);

    $pageTitle = 'Note Not Found';
    require __DIR__ . '/header.php';
    echo '<div class="panel"><div class="empty-state">'
        . '<i class="bi bi-journal-x fs-1 d-block mb-2"></i>'
        . '<h5 class="fw-bold">Note Not Found</h5>'
        . '<p class="text-secondary mb-3">The requested note file is not available.</p>'
        . '<a class="btn btn-primary" href="' . e($readerBack) . '">' . e($readerBackLabel) . '</a>'
        . '</div></div>';
    require __DIR__ . '/footer.php';
    return;
}

/*
|--------------------------------------------------------------------------
| Capture the note's own rendered HTML
|--------------------------------------------------------------------------
| The note is a complete standalone HTML document. We buffer its output so
| we can separate its <head> assets from its <body> content and re-host it
| inside the shared application shell.
*/
$noteHtml = (static function (string $path): string {
    ob_start();
    try {
        require $path;
    } catch (Throwable $e) {
        error_log('Notes reader include error: ' . $e->getMessage());
    }
    return (string)ob_get_clean();
})($notePath);

/*
|--------------------------------------------------------------------------
| Split <head> and <body>
|--------------------------------------------------------------------------
*/
$noteHead = '';
$noteBody = $noteHtml;

if (preg_match('#<head[^>]*>(.*?)</head>#is', $noteHtml, $m)) {
    $noteHead = $m[1];
}

if (preg_match('#<body[^>]*>(.*)</body>#is', $noteHtml, $m)) {
    $noteBody = $m[1];
}

/*
|--------------------------------------------------------------------------
| Neutralize the note's global element rules
|--------------------------------------------------------------------------
| Notes commonly reset html / body / * / a. Those would override the shared
| application chrome, so we drop them and rely on the app's own globals.
| All class-based styling (.hero, .layout, .formula, ...) is preserved.
*/
$noteHead = (string)preg_replace(
    '/(^|[}\s])(?:html|body|\*)\s*(?:,[^{}]*)?\{[^{}]*\}/is',
    '$1',
    $noteHead
);
$noteHead = (string)preg_replace(
    '/(^|[}\s])a\s*(?:,[^{}]*)?\{[^{}]*\}/is',
    '$1',
    $noteHead
);

$pageTitle = $readerTitle;

require __DIR__ . '/header.php';
?>

<!-- Note's own styles and scripts (MathJax config + loader), preserved -->
<?= $noteHead ?>

<!-- Unified Notes Reader chrome -->
<style>
    .note-reader {
        position: relative;
        max-width: 1180px;
        margin: 0 auto;
    }

    /*
    | One consistent content width for every note. Notes ship different
    | internal structures (.hero/.hero-inner for some, <header>/.wrap for
    | others) with their own max-widths. We let the note body own a single
    | centered column and neutralize the notes' own max-widths so the hero,
    | topics sidebar and content all line up on the same edges.
    */
    .note-reader .note-body {
        display: block;
    }

    .note-reader .note-body .hero,
    .note-reader .note-body .hero-inner,
    .note-reader .note-body .layout,
    .note-reader .note-body .wrap,
    .note-reader .note-body > header,
    .note-reader .note-body footer {
        max-width: 100%;
        margin-left: auto;
        margin-right: auto;
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

    .note-breadcrumb i {
        color: var(--muted);
        font-size: 11px;
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
        border-color: var(--border);
    }

    .note-switch .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--focus-ring);
    }

    /*
    | Consistent vertical rhythm and sticky topics sidebar.
    | The note ships its own hero + .layout (topics nav + content). We keep
    | its internal styling but fix spacing and make the sticky topics nav sit
    | just below the app's sticky topbar so it never hides underneath it.
    */

    /* Consistent vertical rhythm: breadcrumb -> hero -> layout */
    .note-reader .layout {
        margin-top: 18px;
        margin-bottom: 8px;
        align-items: start;
    }

    /* Topics sidebar: sticky below the app topbar, own scroll, full height */
    .note-reader .layout > nav,
    .note-reader .layout > aside {
        position: -webkit-sticky;
        position: sticky;
        top: 92px;                 /* app topbar (78px) + breathing room */
        max-height: calc(100vh - 108px);
        overflow: auto;
        align-self: start;
    }

    /* Smooth in-page anchors clear the sticky topbar + topics offset */
    .note-reader .section,
    .note-reader [id] {
        scroll-margin-top: 96px;
    }

    /* On very small screens the note collapses to a single column; the
       sticky offset follows the shorter mobile topbar. */
    @media (max-width: 480px) {
        .note-reader .layout > nav,
        .note-reader .layout > aside {
            top: 82px;
            max-height: calc(100vh - 98px);
        }
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
            <span><?= e($readerTitle) ?></span>
        </nav>

        <a class="btn btn-outline-secondary" href="<?= e($readerBack) ?>">
            <i class="bi bi-arrow-left me-1"></i> <?= e($readerBackLabel) ?>
        </a>
    </div>

    <?php if (!empty($readerChapters) && $readerJumpBase !== ''): ?>
        <!-- Jump to another accessible chapter (student note switcher) -->
        <form style="background: var(--surface); padding: 1rem; border-radius: var(--radius-sm);" method="get" action="<?= e($readerJumpBase) ?>"
            class="note-switch mb-3">
            <label class="visually-hidden" for="noteChapterSelect">Choose a chapter</label>
            <i class="bi bi-funnel text-secondary" aria-hidden="true"></i>
            <select class="form-select" id="noteChapterSelect" name="id">
                <option value="">Go to chapter…</option>
                <?php foreach ($readerChapters as $rc): ?>
                    <?php
                    $rcId = (int)($rc['id'] ?? 0);
                    $rcTitle = (string)($rc['title'] ?? '');
                    $rcSubject = (string)($rc['subject'] ?? '');
                    $rcLabel = $rcSubject !== '' ? $rcSubject . ' — ' . $rcTitle : $rcTitle;
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

    <article class="note-body">
        <?= $noteBody ?>
    </article>
</div>

<?php require __DIR__ . '/footer.php'; ?>
