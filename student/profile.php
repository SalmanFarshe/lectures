<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Student — My Profile / Activity & Results
|--------------------------------------------------------------------------
| Shows this student's assigned quizzes, their attempt history, scores and
| completion status. Only quizzes assigned to THIS student (directly or via
| their class) are visible, and only their own attempts are shown.
*/

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

// Current account + class.
$stu = $pdo->prepare(
    "SELECT u.id, u.full_name, u.username, u.class_id, u.is_active,
            c.name AS class_name
     FROM users u
     LEFT JOIN classes c ON c.id = u.class_id
     WHERE u.id = ? AND u.role = 'student' LIMIT 1"
);
$stu->execute([$userId]);
$student = $stu->fetch();

if (!$student || (int)$student['is_active'] !== 1) {
    $_SESSION = [];
    session_destroy();
    redirect('/lectures/index.php');
}

$classId = (int)($student['class_id'] ?? 0);
$name = (string)($student['full_name'] ?: 'Student');
$className = (string)($student['class_name'] ?? 'Class not assigned');

// Assigned quizzes with this student's best attempt info.
$q = $pdo->prepare(
    "SELECT DISTINCT q.id, q.title, q.total_marks, q.duration_minutes,
            q.max_attempts, q.available_until, c.name AS class_name,
        (SELECT COUNT(*) FROM quiz_attempts a
          WHERE a.quiz_id = q.id AND a.user_id = ? AND a.status IN ('submitted','graded')) AS used_attempts,
        (SELECT MAX(a.total_score) FROM quiz_attempts a
          WHERE a.quiz_id = q.id AND a.user_id = ? AND a.status IN ('submitted','graded')) AS best_score
     FROM quizzes q
     INNER JOIN classes c ON c.id = q.class_id
     WHERE q.is_published = 1 AND q.class_id = ?
       AND EXISTS (SELECT 1 FROM quiz_assignments qa
                    WHERE qa.quiz_id = q.id AND (qa.user_id = ? OR qa.group_class_id = ?))
     ORDER BY q.id DESC"
);
$q->execute([$userId, $userId, $classId, $userId, $classId]);
$quizzes = $q->fetchAll();

// Full attempt history for this student.
$att = $pdo->prepare(
    "SELECT a.id, a.attempt_number, a.status, a.total_score, a.total_marks,
            a.started_at, a.submitted_at, q.title AS quiz_title, q.total_marks AS quiz_marks
     FROM quiz_attempts a
     INNER JOIN quizzes q ON q.id = a.quiz_id
     WHERE a.user_id = ?
     ORDER BY a.id DESC"
);
$att->execute([$userId]);
$attempts = $att->fetchAll();

// Simple rollups.
$completed = 0;
$pending = 0;
$scoreSum = 0.0;
$scoreCount = 0;
foreach ($quizzes as $qz) {
    if ((int)$qz['used_attempts'] > 0) {
        $completed++;
    } else {
        $pending++;
    }
    if ($qz['best_score'] !== null) {
        $scoreSum += (float)$qz['best_score'];
        $scoreCount++;
    }
}
$avgScore = $scoreCount > 0 ? round($scoreSum / $scoreCount, 2) : 0.0;

$pageTitle = 'My Profile';
$activePage = 'profile';
$showSidebar = false; // Student layout has no sidebar

require_once __DIR__ . '/../includes/header.php';
?>
<style>
    .stat-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px; }
    .stat-box {
        background:var(--surface); border:1px solid var(--border);
        border-radius:var(--radius); padding:18px; box-shadow:var(--shadow-sm);
    }
    .stat-box .num { font-size:26px; font-weight:800; color:var(--text); line-height:1.1; }
    .stat-box .lbl { color:var(--muted); font-size:12px; margin-top:6px; }
    .profile-head {
        display:flex; align-items:center; gap:16px; flex-wrap:wrap;
        padding:22px; margin-bottom:20px; color:#fff;
        background:linear-gradient(120deg,#4F46E5,#6366F1);
        border-radius:var(--radius-lg); box-shadow:0 12px 30px rgba(79,70,229,.22);
    }
    .profile-avatar {
        width:60px; height:60px; border-radius:50%; display:grid; place-items:center;
        background:rgba(255,255,255,.18); font-size:26px; font-weight:800;
    }
</style>

<div class="profile-head">
    <div class="profile-avatar"><?= e(mb_strtoupper(mb_substr($name, 0, 1))) ?></div>
    <div>
        <h1 class="h4 fw-bold mb-1"><?= e($name) ?></h1>
        <div class="small" style="opacity:.9;">
            <i class="bi bi-person-badge me-1"></i><?= e((string)$student['username']) ?>
            <span class="mx-2">·</span>
            <i class="bi bi-mortarboard me-1"></i><?= e($className) ?>
        </div>
    </div>
    <a class="btn btn-light btn-sm ms-auto" href="/lectures/student/index.php">
        <i class="bi bi-speedometer2 me-1"></i> Dashboard
    </a>
</div>

<div class="stat-grid mb-4">
    <div class="stat-box"><div class="num"><?= count($quizzes) ?></div><div class="lbl">Assigned Quizzes</div></div>
    <div class="stat-box"><div class="num"><?= $completed ?></div><div class="lbl">Completed</div></div>
    <div class="stat-box"><div class="num"><?= $pending ?></div><div class="lbl">Pending</div></div>
    <div class="stat-box"><div class="num"><?= e((string)$avgScore) ?></div><div class="lbl">Average Score</div></div>
</div>

<div class="panel mb-4" style="padding:0;">
    <div style="padding:16px 18px;border-bottom:1px solid var(--border);font-weight:750;">My Quizzes</div>
    <?php if ($quizzes): ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Quiz</th><th>Class</th><th>Attempts</th><th>Best Score</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                <?php foreach ($quizzes as $qz): ?>
                    <?php
                    $used = (int)$qz['used_attempts'];
                    $canAttempt = $used < (int)$qz['max_attempts'];
                    $now = new DateTime('now');
                    $open = true;
                    if ($qz['available_until'] && $now > new DateTime((string)$qz['available_until'])) { $open = false; }
                    ?>
                    <tr>
                        <td class="fw-bold"><?= e((string)$qz['title']) ?></td>
                        <td><?= e((string)$qz['class_name']) ?></td>
                        <td><?= $used ?> / <?= (int)$qz['max_attempts'] ?></td>
                        <td><?= $qz['best_score'] !== null ? e((string)$qz['best_score']) . ' / ' . e((string)$qz['total_marks']) : '—' ?></td>
                        <td>
                            <?php if ($used > 0): ?>
                                <span class="badge badge-soft-success">Completed</span>
                            <?php elseif (!$open): ?>
                                <span class="badge badge-soft-warning">Closed</span>
                            <?php else: ?>
                                <span class="badge badge-soft-info">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($open && $canAttempt): ?>
                                <a class="btn btn-sm btn-primary" href="/lectures/student/quiz-take.php?id=<?= (int)$qz['id'] ?>"><i class="bi bi-play me-1"></i> <?= $used > 0 ? 'Retry' : 'Start' ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state"><p class="mb-0 text-secondary">No quizzes assigned yet.</p></div>
    <?php endif; ?>
</div>

<div class="panel" style="padding:0;">
    <div style="padding:16px 18px;border-bottom:1px solid var(--border);font-weight:750;">Attempt History (<?= count($attempts) ?>)</div>
    <?php if ($attempts): ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Quiz</th><th>#</th><th>Status</th><th>Score</th><th>Submitted</th></tr></thead>
                <tbody>
                <?php foreach ($attempts as $a): ?>
                    <tr>
                        <td class="fw-bold"><?= e((string)$a['quiz_title']) ?></td>
                        <td><?= (int)$a['attempt_number'] ?></td>
                        <td>
                            <?php
                            $st = (string)$a['status'];
                            $cls = $st === 'graded' ? 'badge-soft-success' : ($st === 'submitted' ? 'badge-soft-info' : 'badge-soft-warning');
                            ?>
                            <span class="badge <?= $cls ?>"><?= e($st) ?></span>
                        </td>
                        <td><?= e((string)$a['total_score']) ?> / <?= e((string)$a['quiz_marks']) ?></td>
                        <td><?= $a['submitted_at'] ? e((string)$a['submitted_at']) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state"><p class="mb-0 text-secondary">No attempts yet.</p></div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

