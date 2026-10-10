<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Admin: Open any lecture note
|--------------------------------------------------------------------------
| Admins can view every valid note WITHOUT student-specific chapter
| permissions. We still require the admin role, verify the chapter exists
| and is active, and validate the note file path exactly like the student
| viewer so no arbitrary file can ever be included.
*/

function adminNoteError(int $statusCode, string $title, string $message): never
{
    http_response_code($statusCode);

    $safeTitle = e($title);
    $safeMessage = e($message);

    echo '<!DOCTYPE html>
    <html lang="bn">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>' . $safeTitle . ' | Lectures</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
              rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
              rel="stylesheet">
        <style>
            body {
                background: #F8FAFC;
                color: #0F172A;
                font-family: system-ui, -apple-system, "Segoe UI", "Noto Sans Bengali", Arial, sans-serif;
            }
            .error-card {
                max-width: 560px;
                margin: 12vh auto;
                padding: 32px;
                background: #FFFFFF;
                border: 1px solid #E2E8F0;
                border-radius: 16px;
                box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="error-card text-center">
                <i class="bi bi-shield-lock text-danger" style="font-size:34px;"></i>
                <h1 class="h4 mt-3 mb-3">' . $safeTitle . '</h1>
                <p class="text-secondary">' . $safeMessage . '</p>
                <a href="/lectures/admin/notes.php"
                   class="btn btn-primary mt-2">Back to Browse Notes</a>
            </div>
        </div>
    </body>
    </html>';

    exit;
}

/*
|--------------------------------------------------------------------------
| Validate Chapter ID
|--------------------------------------------------------------------------
*/
$chapterId = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$chapterId) {
    adminNoteError(
        400,
        'Invalid Chapter',
        'A valid chapter id is required.'
    );
}

/*
|--------------------------------------------------------------------------
| Load the chapter (admins: no chapter_permissions filter)
|--------------------------------------------------------------------------
| The chapter must exist, be active, and belong to active subject/class.
*/
$chapterStmt = $pdo->prepare(
    "SELECT
        ch.id,
        ch.title,
        ch.file_path,
        sub.name AS subject_name,
        cl.name AS class_name
     FROM chapters ch
     INNER JOIN subjects sub ON sub.id = ch.subject_id
     INNER JOIN classes cl ON cl.id = sub.class_id
     WHERE ch.id = ?
       AND ch.is_active = 1
       AND sub.is_active = 1
       AND cl.is_active = 1
     LIMIT 1"
);
$chapterStmt->execute([$chapterId]);
$chapter = $chapterStmt->fetch();

if (!$chapter) {
    adminNoteError(
        404,
        'Chapter Not Found',
        'This chapter does not exist or is currently inactive.'
    );
}

/*
|--------------------------------------------------------------------------
| Validate Note File Path (same rules as the student viewer)
|--------------------------------------------------------------------------
*/
$projectRoot = realpath(__DIR__ . '/..');
$notesRoot = $projectRoot !== false
    ? realpath($projectRoot . DIRECTORY_SEPARATOR . 'notes')
    : false;

$relativePath = str_replace(
    '\\',
    '/',
    trim((string)$chapter['file_path'])
);

if (
    $projectRoot === false ||
    $notesRoot === false ||
    $relativePath === '' ||
    !str_starts_with($relativePath, 'notes/') ||
    preg_match('#(?:^|/)\.\.(?:/|$)#', $relativePath) ||
    strtolower(pathinfo($relativePath, PATHINFO_EXTENSION)) !== 'php'
) {
    adminNoteError(
        500,
        'Note Unavailable',
        'The file path for this chapter is not valid.'
    );
}

$notePath = realpath(
    $projectRoot . DIRECTORY_SEPARATOR .
        str_replace('/', DIRECTORY_SEPARATOR, $relativePath)
);

$notesPrefix = rtrim($notesRoot, DIRECTORY_SEPARATOR)
    . DIRECTORY_SEPARATOR;

if (
    $notePath === false ||
    !is_file($notePath) ||
    !str_starts_with($notePath, $notesPrefix) ||
    !is_readable($notePath)
) {
    adminNoteError(
        404,
        'Note File Not Found',
        'The note file for this chapter could not be found on disk.'
    );
}

/*
|--------------------------------------------------------------------------
| Render via the Unified Notes Reader
|--------------------------------------------------------------------------
*/
$readerMeta = [
    'class'   => (string)($chapter['class_name'] ?? ''),
    'subject' => (string)($chapter['subject_name'] ?? ''),
];

$readerTitle = (string)$chapter['title'];
$readerBack = '/lectures/admin/notes.php';
$readerBackLabel = 'Back to Browse Notes';
$activePage = 'notes';

require __DIR__ . '/../includes/notes-reader.php';
exit;
