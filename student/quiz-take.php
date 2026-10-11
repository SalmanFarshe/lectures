<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');
require_once __DIR__ . '/../config/database.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$quizId = (int)($_GET['id'] ?? 0);

// Class of student.
$stc = $pdo->prepare('SELECT class_id FROM users WHERE id=? AND role="student" LIMIT 1');
$stc->execute([$userId]);
$classId = (int)($stc->fetchColumn() ?: 0);

// Load quiz with assignment + availability checks.
$qz = $pdo->prepare('SELECT q.* FROM quizzes q WHERE q.id=? AND q.is_published=1 AND q.class_id=?');
$qz->execute([$quizId,$classId]);
$quiz = $qz->fetch();
if (!$quiz) { http_response_code(404); exit('Quiz not found.'); }

$asg = $pdo->prepare('SELECT 1 FROM quiz_assignments WHERE quiz_id=? AND (user_id=? OR group_class_id=?) LIMIT 1');
$asg->execute([$quizId,$userId,$classId]);
if (!$asg->fetch()) { http_response_code(403); exit('This quiz is not assigned to you.'); }

$now = new DateTime('now');
if ($quiz['available_from'] && $now < new DateTime($quiz['available_from'])) { http_response_code(403); exit('This quiz is not open yet.'); }
if ($quiz['available_until'] && $now > new DateTime($quiz['available_until'])) { http_response_code(403); exit('This quiz is closed.'); }

$used = $pdo->prepare('SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id=? AND user_id=? AND status IN ("submitted","graded")');
$used->execute([$quizId,$userId]);
if ((int)$used->fetchColumn() >= (int)$quiz['max_attempts']) { http_response_code(403); exit('You have used all attempts for this quiz.'); }

// Questions.
$qq = $pdo->prepare('SELECT q.* FROM quiz_questions qq INNER JOIN questions q ON q.id=qq.question_id WHERE qq.quiz_id=? AND q.is_active=1 ORDER BY qq.position');
$qq->execute([$quizId]);
$questions = $qq->fetchAll();
if (!$questions) { http_response_code(400); exit('This quiz has no questions yet.'); }

// In-progress attempt (one per quiz until submitted).
$att = $pdo->prepare('SELECT * FROM quiz_attempts WHERE quiz_id=? AND user_id=? AND status="in_progress" ORDER BY id DESC LIMIT 1');
$att->execute([$quizId,$userId]);
$attempt = $att->fetch();

// Handle submit.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify('quizattempt')) { http_response_code(403); exit('Invalid token.'); }
    $attemptId = (int)($_POST['attempt_id'] ?? 0);
    $att2 = $pdo->prepare('SELECT * FROM quiz_attempts WHERE id=? AND quiz_id=? AND user_id=? AND status="in_progress" LIMIT 1');
    $att2->execute([$attemptId,$quizId,$userId]);
    $attempt = $att2->fetch();
    if (!$attempt) { http_response_code(403); exit('Invalid or already submitted attempt.'); }

    // Server-side duration check.
    $allowed = (int)$quiz['duration_minutes'] * 60 + 120;
    if (time() - strtotime((string)$attempt['started_at']) > $allowed) { http_response_code(403); exit('Time limit exceeded. Your attempt was auto-submitted.'); }

    // Server-side attempt-limit re-check.
    $used2 = $pdo->prepare('SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id=? AND user_id=? AND status IN ("submitted","graded")');
    $used2->execute([$quizId,$userId]);
    if ((int)$used2->fetchColumn() >= (int)$quiz['max_attempts']) { http_response_code(403); exit('Attempt limit reached.'); }

    $answers = (array)($_POST['a'] ?? []);
    $score = 0.0;
    $hasWritten = false;
    $ins = $pdo->prepare('INSERT INTO quiz_answers (attempt_id,question_id,answer_text,awarded_marks,is_correct) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE answer_text=VALUES(answer_text),awarded_marks=VALUES(awarded_marks),is_correct=VALUES(is_correct)');
    foreach ($questions as $qinfo) {
        $qid = (int)$qinfo['id'];
        $given = trim((string)($answers[$qid] ?? ''));
        if ($qinfo['question_type'] === 'mcq') {
            $corr = strtolower(trim((string)$qinfo['correct_answer']));
            $ok = (strtolower($given) === $corr && $given !== '') ? 1 : 0;
            $award = $ok ? (float)$qinfo['marks'] : 0.0;
            $score += $award;
            $ins->execute([(int)$attempt['id'],$qid,$given,$award,$ok]);
        } else {
            $hasWritten = true;
            $ins->execute([(int)$attempt['id'],$qid,$given,0.0,null]);
        }
    }
    $status = $hasWritten ? 'submitted' : 'graded';
    $pdo->prepare('UPDATE quiz_attempts SET submitted_at=NOW(), status=?, total_score=?, total_marks=? WHERE id=?')
        ->execute([$status,$score,(float)$quiz['total_marks'],(int)$attempt['id']]);
    redirect('/lectures/student/quizzes.php');
}

// Start/reuse attempt on GET.
if (!$attempt) {
    $num = $pdo->prepare('SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id=? AND user_id=?');
    $num->execute([$quizId,$userId]);
    $n = (int)$num->fetchColumn() + 1;
    $pdo->prepare('INSERT INTO quiz_attempts (quiz_id,user_id,attempt_number,status,started_at,total_marks) VALUES (?,?,?,"in_progress",NOW(),?)')
        ->execute([$quizId,$userId,$n,(float)$quiz['total_marks']]);
    $attemptId = (int)$pdo->lastInsertId();
    $attempt = ['id'=>$attemptId];
}

$pageTitle = 'Quiz: ' . $quiz['title'];
$activePage = 'quizzes';
$showSidebar = false;
require_once __DIR__ . '/../includes/header.php';
?>

<style>
.qbox{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:22px;}
.qitem{padding:16px 0;border-bottom:1px solid var(--border);}
.qitem:last-child{border-bottom:0;}
</style>

<div class="page-header">
    <div class="page-header-text">
        <h1 class="page-title"><?= e($quiz['title']) ?></h1>
        <p class="page-header-desc"><?= count($questions) ?> question(s) · <?= (int)$quiz['duration_minutes'] ?> min · <?= e((string)$quiz['total_marks']) ?> marks</p>
    </div>
    <div class="page-header-actions">
        <a class="btn btn-outline-secondary" href="/lectures/student/quizzes.php"><i class="bi bi-arrow-left me-1"></i> My Quizzes</a>
    </div>
</div>

<?php if (!empty($quiz['instructions'])): ?><div class="alert alert-info"><?= nl2br(e($quiz['instructions'])) ?></div><?php endif; ?>

<div class="qbox">
    <form method="post" action="/lectures/student/quiz-take.php?id=<?= $quizId ?>">
        <?= csrf_field('quizattempt') ?>
        <input type="hidden" name="attempt_id" value="<?= (int)$attempt['id'] ?>">
        <?php foreach ($questions as $i=>$qinfo): ?>
            <div class="qitem">
                <div class="fw-bold mb-2"><?= ($i+1) ?>. <?= e($qinfo['question_text']) ?>
                    <span class="badge text-bg-secondary ms-1"><?= e((string)$qinfo['marks']) ?> marks</span>
                </div>
                <?php if ($qinfo['question_type']==='mcq'): ?>
                    <?php foreach (['a'=>'option_a','b'=>'option_b','c'=>'option_c','d'=>'option_d'] as $k=>$col): ?>
                        <?php if ($qinfo[$col]!==null&&$qinfo[$col]!==''): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="a[<?= (int)$qinfo['id'] ?>]" value="<?= $k ?>" id="q<?= (int)$qinfo['id'] ?>_<?= $k ?>" required>
                                <label class="form-check-label" for="q<?= (int)$qinfo['id'] ?>_<?= $k ?>"><b><?= strtoupper($k) ?>.</b> <?= e((string)$qinfo[$col]) ?></label>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <textarea class="form-control" name="a[<?= (int)$qinfo['id'] ?>]" rows="3" placeholder="Write your answer…"></textarea>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div class="mt-3">
            <button class="btn btn-primary" type="submit" onclick="return confirm('Submit this quiz? You cannot change answers after submitting.');">
                <i class="bi bi-check2-circle me-1"></i> Submit Quiz
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>