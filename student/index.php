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

<?php
/*
| Student dashboard statistics (mirrors the admin dashboard's visual language).
| Quiz rollups are scoped to quizzes assigned to THIS student.
*/
$classId = (int)($student['class_id'] ?? 0);

$stQz = $pdo->prepare(
    "SELECT COUNT(DISTINCT q.id)
     FROM quizzes q
     WHERE q.is_published = 1 AND q.class_id = ?
       AND EXISTS (SELECT 1 FROM quiz_assignments qa
                   WHERE qa.quiz_id = q.id AND (qa.user_id = ? OR qa.group_class_id = ?))"
);
$stQz->execute([$classId, $userId, $classId]);
$totalQuizzes = (int)$stQz->fetchColumn();

$stDone = $pdo->prepare(
    "SELECT COUNT(DISTINCT quiz_id) FROM quiz_attempts
     WHERE user_id = ? AND status IN ('submitted','graded')"
);
$stDone->execute([$userId]);
$completedQuizzes = (int)$stDone->fetchColumn();

$stBest = $pdo->prepare(
    "SELECT COALESCE(MAX(total_score),0) FROM quiz_attempts
     WHERE user_id = ? AND status IN ('submitted','graded')"
);
$stBest->execute([$userId]);
$bestScore = (string)$stBest->fetchColumn();

$pendingQuizzes = max(0, $totalQuizzes - $completedQuizzes);
?>


<!-- Page Header -->
<div class="page-header">
    <div class="page-header-text">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-header-desc">Your learning overview and study materials.</p>
    </div>
    <div class="page-header-actions">
        <a class="btn btn-primary" href="/lectures/student/quizzes.php">
            <i class="bi bi-clipboard-check me-1"></i> My Quizzes
        </a>
    </div>
</div>

<!-- Welcome Banner (same component as the admin dashboard) -->
<section class="welcome-banner mb-4" style="position:relative; overflow:hidden;">
    <div style="position:relative; z-index:1;">
        <span class="welcome-tag">
            <i class="bi bi-mortarboard"></i> <?= e(strtoupper($className)) ?> STUDENT
        </span>
        <h1>স্বাগতম, <?= e($name) ?>! 👋</h1>
        <p>তোমার Learning Dashboard-এ স্বাগতম। নিচে তোমার অনুমোদিত নোট ও কুইজ দেখতে পারবে।</p>
        <p class="mt-3 mb-0" style="color:rgba(255,255,255,0.85); font-size:13px;">
            <i class="bi bi-calendar3 me-2"></i><?= date('l, d F Y') ?>
        </p>
    </div>
    <i class="bi bi-journals dash-banner-icon"></i>
</section>

<!-- Statistic Cards (same component as the admin dashboard) -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="bi bi-journal-text"></i></div>
            <div class="stat-label">অনুমোদিত Subject</div>
            <div class="stat-value"><?= $totalSubjects ?></div>
            <div class="section-subtitle">Subjects you can access</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon success"><i class="bi bi-book"></i></div>
            <div class="stat-label">অনুমোদিত Chapter</div>
            <div class="stat-value"><?= $totalChapters ?></div>
            <div class="section-subtitle">Chapters unlocked for you</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon warning"><i class="bi bi-clipboard-check"></i></div>
            <div class="stat-label">Quizzes</div>
            <div class="stat-value"><?= $totalQuizzes ?></div>
            <div class="section-subtitle"><?= $completedQuizzes ?> completed · <?= $pendingQuizzes ?> pending</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon info"><i class="bi bi-trophy"></i></div>
            <div class="stat-label">Best Score</div>
            <div class="stat-value"><?= e($bestScore) ?></div>
            <div class="section-subtitle">Your highest quiz score</div>
        </div>
    </div>
</div>


<div class="row g-4">
    <!-- My Study Material -->
    <div class="col-12 col-xl-8">
        <section class="section-card">
            <div class="section-header">
                <div>
                    <h2 class="section-title">তোমার পড়াশোনা</h2>
                    <div class="section-subtitle">শুধু তোমাকে অনুমতি দেওয়া Active Chapter-গুলো</div>
                </div>
                <a class="text-link" href="/lectures/student/quizzes.php">
                    Quizzes <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <?php if ($totalChapters === 0): ?>
                <div class="empty-state">
                    <i class="bi bi-journals fs-1 d-block mb-2"></i>
                    <h6 class="fw-bold">এখনো কোনো Chapter দেওয়া হয়নি</h6>
                    <p class="small mb-0 text-secondary">Admin তোমাকে Chapter-এর অনুমতি দিলে এখানে দেখতে পাবে।</p>
                </div>
            <?php else: ?>
                <div class="p-3">
                    <?php foreach ($subjects as $subjectName => $subjectChapters): ?>
                        <div class="mb-4">
                            <h3 class="section-title mb-3">
                                📚 <?= e($subjectName) ?>
                                <span class="badge badge-soft-primary ms-1"><?= count($subjectChapters) ?> Chapters</span>
                            </h3>
                            <div class="row g-3">
                                <?php foreach ($subjectChapters as $index => $chapter): ?>
                                    <div class="col-12 col-md-6">
                                        <div class="quick-action h-100">
                                            <div class="quick-icon primary"><span class="fw-bold"><?= $index + 1 ?></span></div>
                                            <div>
                                                <div class="quick-title"><?= e($chapter['title']) ?></div>
                                                <div class="quick-desc"><?= e($subjectName) ?></div>
                                            </div>
                                            <a class="btn btn-primary btn-sm flex-shrink-0 ms-auto"
                                               href="/lectures/student/chapter.php?id=<?= (int)$chapter['id'] ?>">
                                                <i class="bi bi-box-arrow-up-right"></i> Open
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Quick Links -->
    <div class="col-12 col-xl-4">
        <section class="section-card">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Quick Links</h2>
                    <div class="section-subtitle">Jump to your activities</div>
                </div>
                <i class="bi bi-lightning-charge-fill text-warning fs-5"></i>
            </div>
            <div class="quick-actions">
                <a class="quick-action" href="/lectures/student/quizzes.php">
                    <div class="quick-icon warning"><i class="bi bi-clipboard-check"></i></div>
                    <div>
                        <div class="quick-title">My Quizzes</div>
                        <div class="quick-desc">Attempt assigned quizzes</div>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>
                <a class="quick-action" href="/lectures/student/profile.php">
                    <div class="quick-icon info"><i class="bi bi-person-circle"></i></div>
                    <div>
                        <div class="quick-title">My Profile</div>
                        <div class="quick-desc">Scores &amp; attempt history</div>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>
            </div>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

