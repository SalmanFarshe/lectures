<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| One-time migration: static notes/ files -> database `notes` table
|--------------------------------------------------------------------------
| Reads every chapter that still has a `file_path` pointing into the legacy
| `notes/` folder, extracts the inner <body> content (which preserves the
| note's own <style>, interactive quiz <script>, markup and MathJax), and
| inserts it as a database-backed HTML note. Chapters that already have a
| DB note are skipped, so nothing is duplicated or overwritten.
|
| Run once from the CLI:  php database/migrate-static-notes.php
| After verifying, the legacy `notes/` folder can be deleted.
*/

require_once __DIR__ . '/../config/database.php';

$projectRoot = realpath(__DIR__ . '/..');
$notesRoot = $projectRoot !== false ? realpath($projectRoot . '/notes') : false;

if ($notesRoot === false || !is_dir($notesRoot)) {
    fwrite(STDERR, "Legacy notes/ folder not found — nothing to migrate.\n");
    exit(0);
}

/**
 * Extract the inner HTML of the <body>...</body> block.
 * Falls back to the whole file if no body tag is present.
 */
function extractBody(string $html): string
{
    if (preg_match('#<body[^>]*>(.*)</body>#is', $html, $m)) {
        return trim($m[1]);
    }

    return trim($html);
}

$chapters = $pdo->query(
    "SELECT id, title, file_path FROM chapters
     WHERE file_path IS NOT NULL AND file_path <> ''
     ORDER BY id ASC"
)->fetchAll();

$hasNoteStmt = $pdo->prepare('SELECT COUNT(*) FROM notes WHERE chapter_id = ?');
$insertStmt = $pdo->prepare(
    'INSERT INTO notes (chapter_id, title, content_type, html_content, source_file, sort_order, is_active)
     VALUES (?, ?, \'html\', ?, ?, 0, 1)'
);

$migrated = 0;
$skipped = 0;
$missing = 0;

foreach ($chapters as $chapter) {
    $chapterId = (int)$chapter['id'];
    $relative = str_replace('\\', '/', trim((string)$chapter['file_path']));

    // Already has a DB note -> skip (never duplicate).
    $hasNoteStmt->execute([$chapterId]);
    if ((int)$hasNoteStmt->fetchColumn() > 0) {
        $skipped++;
        echo "SKIP  chapter #{$chapterId} '{$chapter['title']}' (already has a DB note)\n";
        continue;
    }

    // Resolve + security-check the file path (must stay inside notes/).
    if (!str_starts_with($relative, 'notes/') || preg_match('#(?:^|/)\.\.(?:/|$)#', $relative)) {
        echo "SKIP  chapter #{$chapterId} — unsafe path '{$relative}'\n";
        continue;
    }

    $absPath = realpath($projectRoot . '/' . $relative);

    if (
        $absPath === false || !is_file($absPath) || !is_readable($absPath) ||
        !str_starts_with($absPath, $notesRoot . DIRECTORY_SEPARATOR)
    ) {
        $missing++;
        echo "MISS  chapter #{$chapterId} '{$chapter['title']}' — file not found: {$relative}\n";
        continue;
    }

    $raw = (string)file_get_contents($absPath);
    $body = extractBody($raw);

    if ($body === '') {
        echo "SKIP  chapter #{$chapterId} — empty body\n";
        continue;
    }

    $insertStmt->execute([$chapterId, (string)$chapter['title'], $body, $relative]);
    $migrated++;
    echo "OK    chapter #{$chapterId} '{$chapter['title']}' -> notes row id " . $pdo->lastInsertId() . "\n";
}

echo "\nDone. Migrated: {$migrated} · Skipped (had note): {$skipped} · Missing files: {$missing}\n";
echo "Verify the notes render, then you may delete the legacy notes/ folder.\n";
