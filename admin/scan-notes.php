<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Notes Scanner';
$activePage = 'scan';

$notesRoot = realpath(__DIR__ . '/../notes');

$files = [];
$errorMessage = '';
$syncSummary = null;

/**
 * Convert PHP filename or folder name into a display name.
 *
 * Example:
 * Chapter_1_Cell_and_Structure.php
 * becomes:
 * Chapter 1 - Cell and Structure
 */
function makeDisplayName(string $value): string
{
    $value = preg_replace('/\.php$/i', '', $value) ?? $value;

    if (preg_match('/^Chapter[_\s-]*(\d+)[_\s-]*(.*)$/i', $value, $matches)) {
        $number = $matches[1];

        $title = str_replace(['_', '-'], ' ', $matches[2]);
        $title = preg_replace('/\s+/', ' ', trim($title)) ?? trim($title);

        return 'Chapter ' . $number
            . ($title !== '' ? ' - ' . ucwords($title) : '');
    }

    $value = str_replace(['_', '-'], ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value) ?? $value;

    return ucwords(trim($value));
}

/**
 * Scan PHP chapter files.
 *
 * Expected structure:
 * notes/class/subject/chapter.php
 */
function scanNotesFiles(string $root): array
{
    $result = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $root,
            FilesystemIterator::SKIP_DOTS
        )
    );

    foreach ($iterator as $file) {
        if (
            !$file->isFile() ||
            strtolower($file->getExtension()) !== 'php'
        ) {
            continue;
        }

        $realPath = $file->getRealPath();

        if ($realPath === false) {
            continue;
        }

        $prefix = $root . DIRECTORY_SEPARATOR;

        if (!str_starts_with($realPath, $prefix)) {
            continue;
        }

        $relativePath = str_replace(
            DIRECTORY_SEPARATOR,
            '/',
            substr($realPath, strlen($prefix))
        );

        $parts = explode('/', $relativePath);

        // Class / Subject / Chapter.php
        if (count($parts) !== 3) {
            continue;
        }

        $classFolder = $parts[0];
        $subjectFolder = $parts[1];
        $filename = $parts[2];

        if (
            !in_array(
                strtolower($classFolder),
                ['hsc', 'ssc', 'jsc'],
                true
            )
        ) {
            continue;
        }

        if (!preg_match('/\.php$/i', $filename)) {
            continue;
        }

        $result[] = [
            'class_folder' => strtolower($classFolder),
            'subject_folder' => $subjectFolder,
            'class_name' => strtoupper($classFolder),
            'subject_name' => makeDisplayName($subjectFolder),
            'title' => makeDisplayName($filename),
            'file_path' => 'notes/' . $relativePath,
        ];
    }

    usort(
        $result,
        static fn(array $a, array $b): int =>
        strnatcasecmp($a['file_path'], $b['file_path'])
    );

    return $result;
}

/*
|--------------------------------------------------------------------------
| Scan Notes
|--------------------------------------------------------------------------
*/

if ($notesRoot === false || !is_dir($notesRoot)) {
    $errorMessage = 'Project-এর notes folder পাওয়া যায়নি।';
} else {
    try {
        $files = scanNotesFiles($notesRoot);
    } catch (Throwable $exception) {
        error_log($exception->getMessage());

        $errorMessage = 'Notes scan করা যায়নি। PHP error log দেখুন।';
    }
}

/*
|--------------------------------------------------------------------------
| Sync Notes With Database
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['sync_notes']) &&
    $errorMessage === ''
) {
    try {
        if (empty($files)) {
            throw new RuntimeException(
                'Sync করার মতো কোনো valid chapter PHP file পাওয়া যায়নি।'
            );
        }

        $pdo->beginTransaction();

        $classSelect = $pdo->prepare(
            'SELECT id
             FROM classes
             WHERE folder_key = ?
             LIMIT 1'
        );

        $classInsert = $pdo->prepare(
            'INSERT INTO classes (name, folder_key, is_active)
             VALUES (?, ?, 1)'
        );

        $subjectSelect = $pdo->prepare(
            'SELECT id
             FROM subjects
             WHERE class_id = ? AND folder_key = ?
             LIMIT 1'
        );

        $subjectInsert = $pdo->prepare(
            'INSERT INTO subjects
                (class_id, name, folder_key, is_active)
             VALUES (?, ?, ?, 1)'
        );

        $chapterSelect = $pdo->prepare(
            'SELECT id
             FROM chapters
             WHERE file_path = ?
             LIMIT 1'
        );

        $chapterInsert = $pdo->prepare(
            'INSERT INTO chapters
                (subject_id, title, file_path, is_active, last_scanned_at)
             VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP)'
        );

        $chapterUpdate = $pdo->prepare(
            'UPDATE chapters
             SET title = ?, last_scanned_at = CURRENT_TIMESTAMP
             WHERE id = ?'
        );

        $classCache = [];
        $subjectCache = [];

        $newClasses = 0;
        $newSubjects = 0;
        $newChapters = 0;
        $updatedChapters = 0;
        $existingChapters = 0;

        foreach ($files as $item) {
            /*
             * Find or create class.
             */
            $classKey = $item['class_folder'];

            if (!isset($classCache[$classKey])) {
                $classSelect->execute([$classKey]);

                $classId = $classSelect->fetchColumn();

                if ($classId === false) {
                    $classInsert->execute([
                        $item['class_name'],
                        $classKey,
                    ]);

                    $classId = (int) $pdo->lastInsertId();
                    $newClasses++;
                } else {
                    $classId = (int) $classId;
                }

                $classCache[$classKey] = $classId;
            }

            $classId = $classCache[$classKey];

            /*
             * Find or create subject.
             */
            $subjectKey = $classId . ':' . $item['subject_folder'];

            if (!isset($subjectCache[$subjectKey])) {
                $subjectSelect->execute([
                    $classId,
                    $item['subject_folder'],
                ]);

                $subjectId = $subjectSelect->fetchColumn();

                if ($subjectId === false) {
                    $subjectInsert->execute([
                        $classId,
                        $item['subject_name'],
                        $item['subject_folder'],
                    ]);

                    $subjectId = (int) $pdo->lastInsertId();
                    $newSubjects++;
                } else {
                    $subjectId = (int) $subjectId;
                }

                $subjectCache[$subjectKey] = $subjectId;
            }

            $subjectId = $subjectCache[$subjectKey];

            /*
             * Find or create chapter.
             */
            $chapterSelect->execute([$item['file_path']]);

            $chapterId = $chapterSelect->fetchColumn();

            if ($chapterId === false) {
                $chapterInsert->execute([
                    $subjectId,
                    $item['title'],
                    $item['file_path'],
                ]);

                $newChapters++;
            } else {
                $chapterUpdate->execute([
                    $item['title'],
                    (int) $chapterId,
                ]);

                $updatedChapters++;
            }
        }

        $pdo->commit();

        $syncSummary = [
            'classes' => $newClasses,
            'subjects' => $newSubjects,
            'chapters' => $newChapters,
            'updated' => $updatedChapters,
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log($exception->getMessage());

        $errorMessage = $exception instanceof RuntimeException
            ? $exception->getMessage()
            : 'Sync ব্যর্থ হয়েছে। Database structure ও PHP error log পরীক্ষা করুন।';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">

    <!-- Page heading -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1">Notes Scanner &amp; Sync</h2>

            <p class="text-muted mb-0">
                Class → Subject → Chapter files scan করে database-এ register করুন।
            </p>
        </div>
    </div>

    <!-- Error message -->
    <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?= e($errorMessage) ?>
        </div>
    <?php endif; ?>

    <!-- Sync summary -->
    <?php if ($syncSummary !== null): ?>
        <div class="alert alert-success" role="status">
            <h5 class="alert-heading">
                <i class="bi bi-check-circle me-2"></i>
                Sync সম্পন্ন হয়েছে!
            </h5>

            <div>
                নতুন Class:
                <strong><?= (int) $syncSummary['classes'] ?></strong>
            </div>

            <div>
                নতুন Subject:
                <strong><?= (int) $syncSummary['subjects'] ?></strong>
            </div>

            <div>
                নতুন Chapter:
                <strong><?= (int) $syncSummary['chapters'] ?></strong>
            </div>

            <div>
                আগে থেকে থাকা Chapter update:
                <strong><?= (int) $syncSummary['updated'] ?></strong>
            </div>
        </div>
    <?php endif; ?>

    <!-- Scanner summary and sync button -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">

            <div>
                <h5 class="fw-bold mb-1">Detected PHP Files</h5>

                <p class="text-muted mb-0">
                    মোট <?= count($files) ?>টি valid chapter file পাওয়া গেছে।
                </p>
            </div>

            <form
                method="post"
                onsubmit="return confirm('Scan করা files database-এ sync করবেন?');">
                <button
                    type="submit"
                    name="sync_notes"
                    value="1"
                    class="btn btn-primary"
                    <?= empty($files) ? 'disabled' : '' ?>>
                    <i class="bi bi-arrow-repeat me-2"></i>
                    Scan &amp; Sync
                </button>
            </form>

        </div>
    </div>

    <!-- File listing -->
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">
                <i class="bi bi-folder2-open me-2"></i>
                Chapter File List
            </h5>
        </div>

        <div class="card-body p-0">

            <?php if (empty($files)): ?>

                <div class="text-center py-5">
                    <i class="bi bi-folder2-open display-4 text-muted"></i>

                    <p class="mt-3 mb-1">
                        কোনো valid PHP chapter file পাওয়া যায়নি।
                    </p>

                    <small class="text-muted">
                        Expected structure: notes/class/subject/chapter.php
                    </small>
                </div>

            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">
                            <tr>
                                <th class="px-3">#</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Chapter Title</th>
                                <th>File Path</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($files as $index => $item): ?>

                                <tr>
                                    <td class="px-3">
                                        <?= $index + 1 ?>
                                    </td>

                                    <td>
                                        <span class="badge text-bg-primary">
                                            <?= e($item['class_name']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= e($item['subject_name']) ?>
                                    </td>

                                    <td class="fw-semibold">
                                        <?= e($item['title']) ?>
                                    </td>

                                    <td>
                                        <code><?= e($item['file_path']) ?></code>
                                    </td>
                                </tr>

                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <!-- Information -->
    <div class="alert alert-info mt-4 mb-0">
        <i class="bi bi-info-circle me-2"></i>

        একই file path আবার scan করলে duplicate chapter তৈরি হয় না।
        Existing users এবং chapter permissions মুছে ফেলা হয় না।
        Filename থেকে তৈরি chapter title পুনরায় Sync করলে update হয়।
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>