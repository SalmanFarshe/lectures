<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Quiz Generator';
$activePage = 'quizzes';
$flash = null;

function quiz_redirect(string $m, string $t='success'): never {
    $_SESSION['quiz_flash'] = ['message'=>$m,'type'=>$t];
    redirect('/lectures/admin/quizzes.php');
}

$classes = $pdo->query('SELECT id,name FROM classes WHERE is_active=1 ORDER BY name')->fetchAll();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify('quiz')) { http_response_code(403); exit('Invalid token.'); }
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'delete') {
        $qid = (int)($_POST['quiz_id'] ?? 0);
        $st = $pdo->prepare('DELETE FROM quizzes WHERE id=?');
        $st->execute([$qid]);
        quiz_redirect($st->rowCount()>0?'Quiz deleted.':'Not found.', $st->rowCount()>0?'success':'warning');
    }

    if ($action === 'create') {
        $title = trim((string)($_POST['title'] ?? ''));
        $classId = (int)($_POST['class_id'] ?? 0);
        $subjectId = (int)($_POST['subject_id'] ?? 0) ?: null;
        $instructions = trim((string)($_POST['instructions'] ?? ''));
        $duration = max(1, (int)($_POST['duration_minutes'] ?? 30));
        $maxAttempts = max(1, (int)($_POST['max_attempts'] ?? 1));
        $from = (string)($_POST['available_from'] ?? '');
        $until = (string)($_POST['available_until'] ?? '');
        $showResult = isset($_POST['show_result']) ? 1 : 0;
        $isPublished = isset($_POST['is_published']) ? 1 : 0;

        if ($title === '' || $classId < 1) { quiz_redirect('Title and class are required.','danger'); }

        $pdo->prepare('INSERT INTO quizzes (title,class_id,subject_id,instructions,duration_minutes,max_attempts,available_from,available_until,show_result,is_published,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([$title,$classId,$subjectId,$instructions?:null,$duration,$maxAttempts,$from?:null,$until?:null,$showResult,$isPublished,(int)$_SESSION['user_id']]);
        quiz_redirect('Quiz created. Now add questions and assign students.');
    }
}

$flash = $_SESSION['quiz_flash'] ?? null;
unset($_SESSION['quiz_flash']);

$quizzes = $pdo->query('SELECT q.*, c.name AS class_name,
    (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id=q.id) AS question_count,
    (SELECT COUNT(*) FROM quiz_assignments qa WHERE qa.quiz_id=q.id) AS assign_count,
    (SELECT COUNT(*) FROM quiz_attempts qa WHERE qa.quiz_id=q.id AND qa.status IN ("submitted","graded")) AS attempt_count
    FROM quizzes q INNER JOIN classes c ON c.id=q.class_id ORDER BY q.id DESC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-text">
        <h1 class="page-title">Quiz Generator</h1>
        <p class="page-header-desc">Create quizzes, add Question Bank questions, and assign to students or groups.</p>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= e((string)$flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= e((string)$flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Create quiz -->
<div class="panel mb-4">
    <h2 class="h5 fw-bold mb-3">Create a quiz</h2>
    <form method="post" action="/lectures/admin/quizzes.php">
        <?= csrf_field('quiz') ?>
        <input type="hidden" name="action" value="create">
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-5"><label class="form-label">Title *</label><input class="form-control" name="title" required maxlength="200" placeholder="e.g. HSC Physics — Quiz 1"></div>
            <div class="col-12 col-md-3"><label class="form-label">Class *</label><select class="form-select" name="class_id" required><option value="">Select</option><?php foreach ($classes as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label">Duration (min)</label><input class="form-control" type="number" min="1" name="duration_minutes" value="30"></div>
            <div class="col-6 col-md-2"><label class="form-label">Max attempts</label><input class="form-control" type="number" min="1" name="max_attempts" value="1"></div>
        </div>
        <div class="mb-3"><label class="form-label">Instructions</label><textarea class="form-control" name="instructions" rows="2"></textarea></div>
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3"><label class="form-label">Available from</label><input class="form-control" type="datetime-local" name="available_from"></div>
            <div class="col-6 col-md-3"><label class="form-label">Available until</label><input class="form-control" type="datetime-local" name="available_until"></div>
            <div class="col-12 col-md-6 d-flex align-items-center gap-4 pt-4">
                <div class="form-check"><input class="form-check-input" type="checkbox" name="show_result" id="show_result" checked><label class="form-check-label" for="show_result">Show result after submit</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="is_published" id="is_published" checked><label class="form-check-label" for="is_published">Published</label></div>
            </div>
        </div>
        <button class="btn btn-primary" type="submit"><i class="bi bi-plus-lg me-1"></i> Create Quiz</button>
    </form>
</div>

<!-- Quiz list -->
<div class="panel" style="padding:0;">
    <div style="padding:16px 18px;border-bottom:1px solid var(--border);font-weight:750;"><?= count($quizzes) ?> quiz(zes)</div>
    <?php if ($quizzes): ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Title</th><th>Class</th><th>Questions</th><th>Assigned</th><th>Attempts</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($quizzes as $zz): ?>
                    <tr>
                        <td class="fw-bold"><?= e($zz['title']) ?></td>
                        <td><?= e($zz['class_name']) ?></td>
                        <td><?= (int)$zz['question_count'] ?></td>
                        <td><?= (int)$zz['assign_count'] ?></td>
                        <td><?= (int)$zz['attempt_count'] ?></td>
                        <td><?= ((int)$zz['is_published']===1) ? '<span class="badge badge-soft-success">Published</span>' : '<span class="badge badge-soft-warning">Draft</span>' ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-primary" href="/lectures/admin/quiz-edit.php?id=<?= (int)$zz['id'] ?>"><i class="bi bi-pencil-square me-1"></i> Manage</a>
                            <form method="post" class="d-inline" onsubmit="return confirm('Delete this quiz?');">
                                <?= csrf_field('quiz') ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="quiz_id" value="<?= (int)$zz['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state"><i class="bi bi-clipboard-check fs-1 d-block mb-2"></i><h5 class="fw-bold">No quizzes yet</h5><p class="mb-0">Create your first quiz above.</p></div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>