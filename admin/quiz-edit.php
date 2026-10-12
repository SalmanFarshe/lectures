<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Manage Quiz';
$activePage = 'quizzes';
$flash = null;

function qe_redirect(string $m, string $t='success'): never {
    $_SESSION['quiz_flash'] = ['message'=>$m,'type'=>$t];
    redirect('/lectures/admin/quiz-edit.php?id=' . (int)($_GET['id'] ?? 0));
}

$quizId = (int)($_GET['id'] ?? 0);
$quiz = null;
if ($quizId > 0) {
    $st = $pdo->prepare('SELECT q.*, c.name AS class_name FROM quizzes q INNER JOIN classes c ON c.id=q.class_id WHERE q.id=?');
    $st->execute([$quizId]);
    $quiz = $st->fetch();
}
if (!$quiz) { http_response_code(404); exit('Quiz not found.'); }

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify('quiz')) { http_response_code(403); exit('Invalid token.'); }
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'add_questions') {
        $ids = array_map('intval', (array)($_POST['question_ids'] ?? []));
        $ins = $pdo->prepare('INSERT IGNORE INTO quiz_questions (quiz_id, question_id, position) VALUES (?,?,?)');
        $pos = (int)$pdo->query('SELECT COALESCE(MAX(position),0) FROM quiz_questions WHERE quiz_id='.$quizId)->fetchColumn();
        $added = 0;
        foreach ($ids as $qid) { if ($qid>0) { $pos++; $ins->execute([$quizId,$qid,$pos]); $added += $ins->rowCount(); } }
        // Recompute total marks.
        $pdo->prepare('UPDATE quizzes q SET total_marks = (SELECT COALESCE(SUM(qq.marks),0) FROM quiz_questions qq2 INNER JOIN questions qq ON qq.id=qq2.question_id WHERE qq2.quiz_id=q.id) WHERE q.id=?')->execute([$quizId]);
        qe_redirect($added.' question(s) added to the quiz.');
    }

    if ($action === 'remove_question') {
        $qid = (int)($_POST['question_id'] ?? 0);
        $pdo->prepare('DELETE FROM quiz_questions WHERE quiz_id=? AND question_id=?')->execute([$quizId,$qid]);
        $pdo->prepare('UPDATE quizzes q SET total_marks = (SELECT COALESCE(SUM(qq.marks),0) FROM quiz_questions qq2 INNER JOIN questions qq ON qq.id=qq2.question_id WHERE qq2.quiz_id=q.id) WHERE q.id=?')->execute([$quizId]);
        qe_redirect('Question removed.');
    }

    if ($action === 'assign_students') {
        $userIds = array_map('intval', (array)($_POST['user_ids'] ?? []));
        $ins = $pdo->prepare('INSERT IGNORE INTO quiz_assignments (quiz_id,user_id,assigned_by) VALUES (?,?,?)');
        $n=0; foreach ($userIds as $uid) { if ($uid>0) { $ins->execute([$quizId,$uid,(int)$_SESSION['user_id']]); $n+=$ins->rowCount(); } }
        qe_redirect($n.' student(s) assigned.');
    }

    if ($action === 'assign_class') {
        $cid = (int)($_POST['group_class_id'] ?? 0);
        if ($cid<1) { qe_redirect('Select a class.','danger'); }
        $pdo->prepare('INSERT IGNORE INTO quiz_assignments (quiz_id,group_class_id,assigned_by) VALUES (?,?,?)')->execute([$quizId,$cid,(int)$_SESSION['user_id']]);
        qe_redirect('Whole class assigned.');
    }

    if ($action === 'grade') {
        $attemptId = (int)($_POST['attempt_id'] ?? 0);
        $grades = (array)($_POST['award'] ?? []);
        $upd = $pdo->prepare('UPDATE quiz_answers SET awarded_marks=? WHERE id=? AND attempt_id=?');
        foreach ($grades as $ansId=>$marks) { $upd->execute([(float)$marks,(int)$ansId,$attemptId]); }
        // Recompute attempt score.
        $sum = $pdo->prepare('SELECT COALESCE(SUM(awarded_marks),0) FROM quiz_answers WHERE attempt_id=?');
        $sum->execute([$attemptId]);
        $score = (float)$sum->fetchColumn();
        $pdo->prepare('UPDATE quiz_attempts SET total_score=?, status="graded" WHERE id=?')->execute([$score,$attemptId]);
        qe_redirect('Grading saved.');
    }
}

$flash = $_SESSION['quiz_flash'] ?? null;
unset($_SESSION['quiz_flash']);

// Current quiz questions.
$quizQuestions = $pdo->prepare('SELECT q.*, qq.position FROM quiz_questions qq INNER JOIN questions q ON q.id=qq.question_id WHERE qq.quiz_id=? ORDER BY qq.position');
$quizQuestions->execute([$quizId]);
$quizQuestions = $quizQuestions->fetchAll();

// Available questions (same class, not already added).
$avail = $pdo->prepare('SELECT q.* FROM questions q WHERE q.class_id=? AND q.id NOT IN (SELECT question_id FROM quiz_questions WHERE quiz_id=?) AND q.is_active=1 ORDER BY q.id DESC LIMIT 300');
$avail->execute([(int)$quiz['class_id'],$quizId]);
$available = $avail->fetchAll();

// Students in the quiz's class + already assigned ids.
$students = $pdo->prepare('SELECT id,full_name,username FROM users WHERE role="student" AND class_id=? AND is_active=1 ORDER BY full_name');
$students->execute([(int)$quiz['class_id']]);
$students = $students->fetchAll();
$assignedUserIds = [];
$ast = $pdo->prepare('SELECT user_id FROM quiz_assignments WHERE quiz_id=? AND user_id IS NOT NULL');
$ast->execute([$quizId]);
$assignedUserIds = array_map('intval', $ast->fetchAll(PDO::FETCH_COLUMN));

// Attempts.
$att = $pdo->prepare('SELECT a.*, u.full_name, u.username FROM quiz_attempts a INNER JOIN users u ON u.id=a.user_id WHERE a.quiz_id=? ORDER BY a.id DESC');
$att->execute([$quizId]);
$attempts = $att->fetchAll();

// Grading: fetch answers for a selected attempt (if any).
$gradeAttemptId = (int)($_GET['attempt'] ?? 0);
$gradeAnswers = [];
if ($gradeAttemptId>0) {
    $ga=$pdo->prepare('SELECT ans.*, qq.question_text, qq.question_type, qq.marks AS full_marks, qq.option_a, qq.option_b, qq.option_c, qq.option_d, qq.correct_answer FROM quiz_answers ans JOIN quiz_attempts att ON att.id=ans.attempt_id JOIN quiz_questions qq ON qq.question_id=ans.question_id AND qq.quiz_id=att.quiz_id WHERE ans.attempt_id=? ORDER BY qq.position');
    $ga->execute([$gradeAttemptId]); $gradeAnswers=$ga->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-text">
        <h1 class="page-title"><?= e($quiz['title']) ?></h1>
        <p class="page-header-desc"><?= e($quiz['class_name']) ?> · <?= count($quizQuestions) ?> question(s) · Total <?= e((string)$quiz['total_marks']) ?> marks · <?= (int)$quiz['duration_minutes'] ?> min · <?= (int)$quiz['max_attempts'] ?> attempt(s)</p>
    </div>
    <div class="page-header-actions">
        <a class="btn btn-outline-secondary" href="/lectures/admin/quizzes.php"><i class="bi bi-arrow-left me-1"></i> All Quizzes</a>
        <a class="btn btn-outline-primary" href="/lectures/student/quiz-take.php?id=<?= $quizId ?>&preview=1"><i class="bi bi-eye me-1"></i> Preview as Student</a>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= e((string)$flash['type']) ?> alert-dismissible fade show" role="alert"><?= e((string)$flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-lg-6">
        <div class="panel">
            <h2 class="h6 fw-bold mb-3">Quiz questions (<?= count($quizQuestions) ?>)</h2>
            <?php if ($quizQuestions): ?>
                <?php foreach ($quizQuestions as $qq): ?>
                    <div class="d-flex justify-content-between align-items-start gap-2 p-2" style="border-bottom:1px solid var(--border);">
                        <div style="min-width:0;">
                            <div class="fw-bold"><?= e($qq['question_text']) ?></div>
                            <span class="badge badge-soft-primary"><?= strtoupper(e($qq['question_type'])) ?></span>
                            <span class="badge text-bg-secondary"><?= e((string)$qq['marks']) ?></span>
                        </div>
                        <form method="post" class="flex-shrink-0" onsubmit="return confirm('Remove this question?');">
                            <?= csrf_field('quiz') ?>
                            <input type="hidden" name="action" value="remove_question">
                            <input type="hidden" name="question_id" value="<?= (int)$qq['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-x"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-secondary small mb-0">No questions yet. Add from the right panel.</p>
            <?php endif; ?>
        </div>

        <div class="panel mt-4">
            <h2 class="h6 fw-bold mb-3">Assign students</h2>
            <form method="post" class="mb-3">
                <?= csrf_field('quiz') ?>
                <input type="hidden" name="action" value="assign_students">
                <div style="max-height:240px;overflow:auto;border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px;">
                    <?php foreach ($students as $stu): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="user_ids[]" value="<?= (int)$stu['id'] ?>" id="stu<?= (int)$stu['id'] ?>" <?= in_array((int)$stu['id'],$assignedUserIds,true)?'checked':'' ?>>
                            <label class="form-check-label" for="stu<?= (int)$stu['id'] ?>"><?= e($stu['full_name']) ?> (<?= e($stu['username']) ?>)</label>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$students): ?><span class="text-secondary small">No active students in this class.</span><?php endif; ?>
                </div>
                <button class="btn btn-primary btn-sm mt-2" type="submit"><i class="bi bi-person-check me-1"></i> Assign selected</button>
            </form>
            <form method="post" class="d-flex gap-2">
                <?= csrf_field('quiz') ?>
                <input type="hidden" name="action" value="assign_class">
                <select class="form-select form-select-sm" name="group_class_id"><option value="<?= (int)$quiz['class_id'] ?>"><?= e($quiz['class_name']) ?> (whole class)</option></select>
                <button class="btn btn-outline-primary btn-sm" type="submit"><i class="bi bi-people me-1"></i> Assign class</button>
            </form>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="panel">
            <h2 class="h6 fw-bold mb-3">Add from Question Bank</h2>
            <?php if ($available): ?>
                <form method="post">
                    <?= csrf_field('quiz') ?>
                    <input type="hidden" name="action" value="add_questions">
                    <div style="max-height:320px;overflow:auto;border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px;">
                        <?php foreach ($available as $aq): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="question_ids[]" value="<?= (int)$aq['id'] ?>" id="aq<?= (int)$aq['id'] ?>">
                                <label class="form-check-label" for="aq<?= (int)$aq['id'] ?>"><?= e($aq['question_text']) ?> <span class="badge badge-soft-primary"><?= strtoupper(e($aq['question_type'])) ?></span></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="btn btn-primary btn-sm mt-2" type="submit"><i class="bi bi-plus-lg me-1"></i> Add selected</button>
                </form>
            <?php else: ?>
                <p class="text-secondary small mb-0">No unused questions in this class. Create more in the Question Bank.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Attempts / Results -->
<div class="panel mt-4" style="padding:0;">
    <div style="padding:16px 18px;border-bottom:1px solid var(--border);font-weight:750;">Attempts &amp; Results (<?= count($attempts) ?>)</div>
    <?php if ($attempts): ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Student</th><th>#</th><th>Status</th><th>Score</th><th>Submitted</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                <?php foreach ($attempts as $at): ?>
                    <tr>
                        <td class="fw-bold"><?= e($at['full_name']) ?></td>
                        <td><?= (int)$at['attempt_number'] ?></td>
                        <td><span class="badge <?= $at['status']==='graded'?'badge-soft-success':($at['status']==='submitted'?'badge-soft-info':'badge-soft-warning') ?>"><?= e($at['status']) ?></span></td>
                        <td><?= e((string)$at['total_score']) ?> / <?= e((string)$at['total_marks']) ?></td>
                        <td><?= $at['submitted_at'] ? e((string)$at['submitted_at']) : '—' ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/lectures/admin/quiz-edit.php?id=<?= $quizId ?>&attempt=<?= (int)$at['id'] ?>"><i class="bi bi-clipboard-check me-1"></i> Grade / View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state"><p class="mb-0">No attempts yet.</p></div>
    <?php endif; ?>
</div>

<?php if ($gradeAnswers): ?>
    <div class="panel mt-4">
        <h2 class="h6 fw-bold mb-3">Grade attempt #<?= $gradeAttemptId ?></h2>
        <form method="post">
            <?= csrf_field('quiz') ?>
            <input type="hidden" name="action" value="grade">
            <input type="hidden" name="attempt_id" value="<?= $gradeAttemptId ?>">
            <?php foreach ($gradeAnswers as $i=>$ans): ?>
                <div class="p-3 mb-3" style="border:1px solid var(--border);border-radius:var(--radius-sm);">
                    <div class="fw-bold mb-1"><?= ($i+1) ?>. <?= e($ans['question_text']) ?> <span class="badge badge-soft-primary"><?= strtoupper(e($ans['question_type'])) ?></span></div>
                    <?php if ($ans['question_type']==='mcq'): ?>
                        <div class="small mb-1">Student answer: <strong><?= e((string)strtoupper((string)$ans['answer_text'])) ?></strong> · Correct: <strong><?= e(strtoupper((string)$ans['correct_answer'])) ?></strong></div>
                    <?php else: ?>
                        <div class="small mb-1"><em>Student answer:</em><br><?= nl2br(e((string)($ans['answer_text'] ?? ''))) ?></div>
                    <?php endif; ?>
                    <label class="form-label small mb-0">Awarded marks (max <?= e((string)$ans['full_marks']) ?>)</label>
                    <input class="form-control form-control-sm" style="max-width:140px;" type="number" step="0.25" min="0" max="<?= e((string)$ans['full_marks']) ?>" name="award[<?= (int)$ans['id'] ?>]" value="<?= e((string)$ans['awarded_marks']) ?>">
                </div>
            <?php endforeach; ?>
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle me-1"></i> Save grading</button>
        </form>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>