<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');
require_once __DIR__ . '/../config/database.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

// Class id of this student (limits what is visible).
$stc = $pdo->prepare('SELECT class_id FROM users WHERE id=? AND role="student" LIMIT 1');
$stc->execute([$userId]);
$classId = (int)($stc->fetchColumn() ?: 0);

// All quizzes assigned to this student: direct assignment OR whole-class assignment.
$list = $pdo->prepare(
    "SELECT DISTINCT q.*, c.name AS class_name,
        (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id=q.id) AS question_count,
        (SELECT COUNT(*) FROM quiz_attempts a WHERE a.quiz_id=q.id AND a.user_id=? AND a.status IN ('submitted','graded')) AS used_attempts
     FROM quizzes q
     INNER JOIN classes c ON c.id=q.class_id
     LEFT JOIN quiz_assignments qa ON qa.quiz_id=q.id AND (qa.user_id = ? OR qa.group_class_id = ?)
     WHERE q.is_published=1 AND q.class_id=?
       AND EXISTS (SELECT 1 FROM quiz_assignments qa2 WHERE qa2.quiz_id=q.id AND (qa2.user_id=? OR qa2.group_class_id=?))
     ORDER BY q.id DESC"
);
$list->execute([$userId,$userId,$classId,$classId,$userId,$classId]);
$quizzes = $list->fetchAll();

$pageTitle = 'My Quizzes';
$activePage = 'quizzes';
$showSidebar = false;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-text">
        <h1 class="page-title">My Quizzes</h1>
        <p class="page-header-desc">Assigned quizzes. Attempt within the time limit and availability window.</p>
    </div>
    <div class="page-header-actions">
        <a class="btn btn-outline-secondary" href="/lectures/student/profile.php"><i class="bi bi-person-circle me-1"></i> My Results</a>
    </div>
</div>

<div class="row g-3">
<?php foreach ($quizzes as $qz): ?>
    <?php
    $canAttempt = (int)$qz['used_attempts'] < (int)$qz['max_attempts'];
    $now = new DateTime('now');
    $open = true;
    if ($qz['available_from'] && $now < new DateTime($qz['available_from'])) { $open = false; }
    if ($qz['available_until'] && $now > new DateTime($qz['available_until'])) { $open = false; }
    ?>
    <div class="col-12 col-md-6">
        <div class="panel h-100">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <h2 class="h6 fw-bold mb-1"><?= e($qz['title']) ?></h2>
                <?php if (!$open): ?><span class="badge badge-soft-warning">Closed</span><?php elseif (!$canAttempt): ?><span class="badge badge-soft-warning">Attempts used</span><?php else: ?><span class="badge badge-soft-success">Open</span><?php endif; ?>
            </div>
            <div class="small text-secondary mb-2">
                <?= (int)$qz['question_count'] ?> question(s) · <?= (int)$qz['duration_minutes'] ?> min · <?= e((string)$qz['total_marks']) ?> marks · Attempts <?= (int)$qz['used_attempts'] ?>/<?= (int)$qz['max_attempts'] ?>
            </div>
            <?php if (!empty($qz['instructions'])): ?><p class="small text-secondary"><?= nl2br(e($qz['instructions'])) ?></p><?php endif; ?>
            <div class="d-flex gap-2 mt-2">
                <?php if ($open && $canAttempt): ?>
                    <a class="btn btn-primary btn-sm" href="/lectures/student/quiz-take.php?id=<?= (int)$qz['id'] ?>"><i class="bi bi-play me-1"></i> Start Quiz</a>
                <?php endif; ?>
                <a class="btn btn-outline-secondary btn-sm" href="/lectures/student/profile.php#quiz-<?= (int)$qz['id'] ?>">View Results</a>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php if (!$quizzes): ?>
    <div class="panel"><div class="empty-state">
        <i class="bi bi-clipboard-check fs-1 d-block mb-2"></i>
        <h5 class="fw-bold">No quizzes assigned yet</h5>
        <p class="text-secondary mb-0">Your teacher will assign quizzes here.</p>
    </div></div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>