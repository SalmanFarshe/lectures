<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireRole('student');
require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Helper: Show an error page
|--------------------------------------------------------------------------
*/
function showChapterError(int $statusCode, string $title, string $message): never
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
        <style>
            body {
                background: #F8FAFC;
                color: #0F172A;
                font-family: system-ui, -apple-system, "Segoe UI", "Noto Sans Bengali", Arial, sans-serif;
            }
            .error-card {
                max-width: 550px;
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
                <h1 class="h4 mb-3">' . $safeTitle . '</h1>
                <p class="text-secondary">' . $safeMessage . '</p>
                <a href="/lectures/student/index.php"
                   class="btn btn-primary mt-2">Back to Dashboard</a>
            </div>
        </div>
    </body>
    </html>';

    exit;
}

/*
|--------------------------------------------------------------------------
| Validate Logged-in Student
|--------------------------------------------------------------------------
*/
$userId = filter_var(
    $_SESSION['user_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$userId) {
    redirect('/lectures/index.php');
}

$studentStmt = $pdo->prepare(
    "SELECT id, class_id, is_active
     FROM users
     WHERE id = ?
       AND role = 'student'
     LIMIT 1"
);
$studentStmt->execute([$userId]);
$student = $studentStmt->fetch();

if (!$student || (int)$student['is_active'] !== 1) {
    $_SESSION = [];
    session_destroy();
    redirect('/lectures/index.php');
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
    showChapterError(
        400,
        'Invalid Chapter',
        'সঠিক Chapter নির্বাচন করে আবার চেষ্টা করো।'
    );
}

/*
|--------------------------------------------------------------------------
| Permission Check
|--------------------------------------------------------------------------
| The chapter must:
| 1. Exist in the database.
| 2. Be active.
| 3. Belong to the student's assigned class.
| 4. Have an explicit permission for this student.
| 5. Belong to active subject and class records.
*/
$chapterStmt = $pdo->prepare(
    "SELECT
        ch.id,
        ch.title,
        ch.file_path,
        ch.is_active,
        sub.name AS subject_name,
        cl.name AS class_name
     FROM chapter_permissions cp
     INNER JOIN chapters ch ON ch.id = cp.chapter_id
     INNER JOIN subjects sub ON sub.id = ch.subject_id
     INNER JOIN classes cl ON cl.id = sub.class_id
     WHERE cp.user_id = ?
       AND ch.id = ?
       AND ch.is_active = 1
       AND sub.is_active = 1
       AND cl.is_active = 1
       AND cl.id = ?
     LIMIT 1"
);

$chapterStmt->execute([
    $userId,
    $chapterId,
    $student['class_id']
]);

$chapter = $chapterStmt->fetch();

if (!$chapter) {
    showChapterError(
        403,
        'Access Denied',
        'এই Chapter দেখার অনুমতি তোমার নেই, অথবা Chapter-টি বর্তমানে Active নয়।'
    );
}

/*
|--------------------------------------------------------------------------
| Validate Note File Path
|--------------------------------------------------------------------------
| Expected example:
| notes/hsc/physics_1st_paper/chapter_1_chaptername_ekhane.php
|
| Only PHP files inside the project's notes directory are allowed.
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
    showChapterError(
        500,
        'Note Unavailable',
        'এই Chapter-এর File Path সঠিক নয়। Admin-এর সঙ্গে যোগাযোগ করো।'
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
    showChapterError(
        404,
        'Note Not Found',
        'Chapter-এর নোট ফাইলটি পাওয়া যায়নি। Admin-কে File Path পরীক্ষা করতে বলো।'
    );
}

/*
|--------------------------------------------------------------------------
| Load ALL chapters this student may access (for the on-page switcher)
|--------------------------------------------------------------------------
| Same permission rules as the dashboard: active chapter + active subject +
| active class that belongs to the student's class, with an explicit
| permission row for this student.
*/
$accessibleStmt = $pdo->prepare(
    "SELECT
        ch.id,
        ch.title,
        sub.name AS subject_name
     FROM chapter_permissions cp
     INNER JOIN chapters ch ON ch.id = cp.chapter_id
     INNER JOIN subjects sub ON sub.id = ch.subject_id
     INNER JOIN classes cl ON cl.id = sub.class_id
     WHERE cp.user_id = ?
       AND ch.is_active = 1
       AND sub.is_active = 1
       AND cl.is_active = 1
       AND cl.id = ?
     ORDER BY sub.name ASC, ch.title ASC"
);
$accessibleStmt->execute([$userId, $student['class_id']]);
$accessibleChapters = $accessibleStmt->fetchAll();

$readerChapters = [];
foreach ($accessibleChapters as $ac) {
    $readerChapters[] = [
        'id'      => (int)$ac['id'],
        'title'   => (string)$ac['title'],
        'subject' => (string)$ac['subject_name'],
    ];
}

/*
|--------------------------------------------------------------------------
| Render the Authorized Note via the Unified Reader
|--------------------------------------------------------------------------
| The note file is included only after all checks pass. It is rendered inside
| the shared application layout by the unified notes reader, which preserves
| the note's HTML, CSS, JavaScript and MathJax.
*/

$readerMeta = [
    'class'   => (string)($chapter['class_name'] ?? ''),
    'subject' => (string)($chapter['subject_name'] ?? ''),
];

$readerTitle = (string)$chapter['title'];
$readerBack = '/lectures/student/index.php';
$readerBackLabel = 'Back to Dashboard';
$activePage = 'dashboard';
$showSidebar = false; // Student note view uses a full-width layout without the sidebar
$readerCurrentId = (int)$chapter['id'];
$readerJumpBase = '/lectures/student/chapter.php';

require __DIR__ . '/../includes/notes-reader.php';
exit;
