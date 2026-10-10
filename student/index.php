<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireRole('student');
require_once __DIR__ . '/../config/database.php';

$userId = filter_var(
    $_SESSION['user_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$userId) {
    redirect('/lectures/logout.php');
}

// Always read the current account and class from the database.
$userStmt = $pdo->prepare(
    "SELECT u.id, u.full_name, u.username, u.class_id, u.is_active,
            c.name AS class_name
     FROM users u
     LEFT JOIN classes c ON c.id = u.class_id
     WHERE u.id = ? AND u.role = 'student'
     LIMIT 1"
);
$userStmt->execute([$userId]);
$student = $userStmt->fetch();

if (!$student || (int)$student['is_active'] !== 1) {
    $_SESSION = [];
    session_destroy();
    redirect('/lectures/index.php');
}

$name = (string)($student['full_name'] ?: 'Student');
$className = (string)($student['class_name'] ?? 'Class not assigned');

// Load only active chapters that this student has permission to access.
$chapterStmt = $pdo->prepare(
    "SELECT
        ch.id,
        ch.title,
        ch.file_path,
        sub.name AS subject_name,
        sub.folder_key AS subject_folder,
        cl.name AS class_name,
        cl.folder_key AS class_folder
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
$chapterStmt->execute([$userId, $student['class_id']]);
$chapters = $chapterStmt->fetchAll();

// Group chapters by subject.
$subjects = [];

foreach ($chapters as $chapter) {
    $subjectKey = (string)$chapter['subject_name'];

    if (!isset($subjects[$subjectKey])) {
        $subjects[$subjectKey] = [];
    }

    $subjects[$subjectKey][] = $chapter;
}

$totalChapters = count($chapters);
$totalSubjects = count($subjects);

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$showSidebar = false; // Student pages use a full-width layout without the sidebar

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Student dashboard components (harmonized with the shared design tokens) -->
<style>
    .welcome {
        position: relative;
        overflow: hidden;
        padding: 30px;
        margin-bottom: 24px;
        color: #ffffff;
        background: linear-gradient(120deg, #4F46E5, #6366F1);
        border-radius: var(--radius-lg);
        box-shadow: 0 12px 30px rgba(79, 70, 229, 0.22);
    }

    .welcome h1 {
        position: relative;
        z-index: 1;
        font-size: clamp(23px, 3vw, 30px);
        font-weight: 800;
        letter-spacing: -0.6px;
        margin: 0 0 8px;
    }

    .welcome p {
        position: relative;
        z-index: 1;
        color: rgba(255, 255, 255, 0.9);
        font-size: 14px;
        margin: 0;
    }

    .welcome .badge {
        position: relative;
        z-index: 1;
        background: rgba(255, 255, 255, 0.18);
        color: #ffffff;
    }

    .welcome::after {
        content: "";
        position: absolute;
        z-index: 0;
        right: -40px;
        bottom: -60px;
        width: 220px;
        height: 220px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 50%;
    }

    .student-stat {
        padding: 22px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
        height: 100%;
    }

    .student-stat .stat-number {
        font-size: 28px;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -0.6px;
        color: var(--primary);
    }

    .student-stat .stat-label {
        color: var(--muted);
        font-size: 12px;
        margin-top: 8px;
    }

    .section-title {
        font-size: 19px;
        font-weight: 800;
        letter-spacing: -0.4px;
        margin: 0 0 4px;
    }

    .subject-section {
        margin-top: 28px;
    }

    .subject-heading {
        font-size: 16px;
        font-weight: 750;
        margin: 0 0 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .chapter-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 16px;
        height: 100%;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }

    .chapter-card:hover {
        border-color: var(--primary);
        box-shadow: var(--shadow);
        transform: translateY(-2px);
    }

    .chapter-number {
        display: grid;
        place-items: center;
        width: 42px;
        height: 42px;
        flex-shrink: 0;
        color: var(--primary);
        background: var(--primary-soft);
        border-radius: var(--radius-sm);
        font-weight: 800;
    }

    .chapter-title {
        font-weight: 750;
        color: var(--text);
    }

    @media (max-width: 576px) {
        .welcome {
            padding: 23px;
        }

        .student-stat {
            padding: 17px;
        }

        .student-stat .stat-number {
            font-size: 24px;
        }
    }
</style>

<!-- Welcome -->
<section class="welcome">
    <h1>স্বাগতম, <?= e($name) ?>! 👋</h1>
    <p>
        তোমার Learning Dashboard-এ স্বাগতম।
        নিচে তোমার অনুমোদিত নোটগুলো দেখতে পারবে।
    </p>
    <div class="mt-3">
        <span class="badge"><i class="bi bi-mortarboard me-1"></i><?= e($className) ?></span>
    </div>
</section>

<!-- Summary Cards -->
<div class="row g-3">
    <div class="col-6 col-md-4">
        <div class="student-stat">
            <div class="stat-number"><?= $totalSubjects ?></div>
            <div class="stat-label">অনুমোদিত Subject</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="student-stat">
            <div class="stat-number"><?= $totalChapters ?></div>
            <div class="stat-label">অনুমোদিত Chapter</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="student-stat">
            <div class="stat-number" style="font-size:22px;"><?= e($className) ?></div>
            <div class="stat-label">তোমার Class</div>
        </div>
    </div>
</div>

<!-- My Study Material -->
<section class="mt-4">
    <h2 class="section-title">তোমার পড়াশোনা</h2>
    <p class="text-secondary mb-0">
        এখানে শুধু তোমাকে অনুমতি দেওয়া Active Chapter-গুলো দেখানো হবে।
    </p>
</section>

<?php if ($totalChapters === 0): ?>
    <div class="empty-state">
        <i class="bi bi-journals fs-1 d-block mb-2"></i>
        <h3 class="h5 fw-bold">এখনো কোনো Chapter দেওয়া হয়নি</h3>
        <p class="text-secondary mb-0">
            Admin তোমাকে Chapter-এর অনুমতি দিলে এখানে দেখতে পাবে।
        </p>
    </div>
<?php else: ?>
    <?php foreach ($subjects as $subjectName => $subjectChapters): ?>
        <section class="subject-section">
            <h3 class="subject-heading">
                <span>📚 <?= e($subjectName) ?></span>
                <span class="badge badge-soft-primary">
                    <?= count($subjectChapters) ?> Chapters
                </span>
            </h3>

            <div class="row g-3">
                <?php foreach ($subjectChapters as $index => $chapter): ?>
                    <div class="col-12 col-md-6">
                        <div class="chapter-card">
                            <div class="d-flex align-items-center gap-3">
                                <span class="chapter-number"><?= $index + 1 ?></span>
                                <div>
                                    <div class="chapter-title">
                                        <?= e($chapter['title']) ?>
                                    </div>
                                    <div class="small text-secondary">
                                        <?= e($subjectName) ?>
                                    </div>
                                </div>
                            </div>
                            <a class="btn btn-primary btn-sm flex-shrink-0"
                                href="/lectures/student/chapter.php?id=<?= (int)$chapter['id'] ?>">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Open
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
