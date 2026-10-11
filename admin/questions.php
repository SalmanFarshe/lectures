<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Question Bank';
$activePage = 'questions';
$flash = null;

function q_redirect(string $m, string $t='success'): never {
    $_SESSION['q_flash'] = ['message'=>$m,'type'=>$t];
    redirect('/lectures/admin/questions.php');
}

$classes = $pdo->query('SELECT id,name FROM classes WHERE is_active=1 ORDER BY name')->fetchAll();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify('question')) { http_response_code(403); exit('Invalid token.'); }
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'delete') {
        $qid = (int)($_POST['question_id'] ?? 0);
        $st = $pdo->prepare('DELETE FROM questions WHERE id=?');
        $st->execute([$qid]);
        q_redirect($st->rowCount()>0 ? 'Question deleted.' : 'Question not found.', $st->rowCount()>0?'success':'warning');
    }

    if ($action === 'save') {
        $qid = (int)($_POST['question_id'] ?? 0);
        $classId = (int)($_POST['class_id'] ?? 0);
        $subjectId = (int)($_POST['subject_id'] ?? 0) ?: null;
        $chapterId = (int)($_POST['chapter_id'] ?? 0) ?: null;
        $type = (string)($_POST['question_type'] ?? 'mcq') === 'written' ? 'written' : 'mcq';
        $text = trim((string)($_POST['question_text'] ?? ''));
        $optA = trim((string)($_POST['option_a'] ?? ''));
        $optB = trim((string)($_POST['option_b'] ?? ''));
        $optC = trim((string)($_POST['option_c'] ?? ''));
        $optD = trim((string)($_POST['option_d'] ?? ''));
        $correct = strtoupper(trim((string)($_POST['correct_answer'] ?? '')));
        $marks = (float)($_POST['marks'] ?? 1);
        $explain = trim((string)($_POST['explanation'] ?? ''));

        if ($classId < 1) { q_redirect('Select a class.','danger'); }
        if ($text === '') { q_redirect('Question text is required.','danger'); }
        if ($marks <= 0) { q_redirect('Marks must be greater than 0.','danger'); }
        if ($type === 'mcq') {
            if ($optA==='' || $optB==='') { q_redirect('MCQ needs at least options A and B.','danger'); }
            if (!in_array($correct, ['A','B','C','D'], true)) { q_redirect('Select the correct MCQ option.','danger'); }
            $correct = strtolower($correct);
        }

        if ($qid > 0) {
            $pdo->prepare('UPDATE questions SET class_id=?,subject_id=?,chapter_id=?,question_type=?,question_text=?,option_a=?,option_b=?,option_c=?,option_d=?,correct_answer=?,marks=?,explanation=?,updated_at=NOW() WHERE id=?')
                ->execute([$classId,$subjectId,$chapterId,$type,$text,$optA?:null,$optB?:null,$optC?:null,$optD?:null,$correct?:null,$marks,$explain?:null,$qid]);
            q_redirect('Question updated.');
        } else {
            $pdo->prepare('INSERT INTO questions (class_id,subject_id,chapter_id,question_type,question_text,option_a,option_b,option_c,option_d,correct_answer,marks,explanation,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$classId,$subjectId,$chapterId,$type,$text,$optA?:null,$optB?:null,$optC?:null,$optD?:null,$correct?:null,$marks,$explain?:null,(int)$_SESSION['user_id']]);
            q_redirect('Question added.');
        }
    }
}

$flash = $_SESSION['q_flash'] ?? null;
unset($_SESSION['q_flash']);

$fClass = (int)($_GET['class_id'] ?? 0);
$fSubject = (int)($_GET['subject_id'] ?? 0);
$fType = (string)($_GET['type'] ?? '');
$q = trim((string)($_GET['q'] ?? ''));

$where = ['1=1']; $params = [];
if ($fClass>0) { $where[]='q.class_id=?'; $params[]=$fClass; }
if ($fSubject>0) { $where[]='q.subject_id=?'; $params[]=$fSubject; }
if ($fType==='mcq'||$fType==='written') { $where[]='q.question_type=?'; $params[]=$fType; }
if ($q!=='') { $where[]='q.question_text LIKE ?'; $params[]='%'.$q.'%'; }
$whereSql = implode(' AND ', $where);

$list = $pdo->prepare("SELECT q.*, c.name AS class_name, s.name AS subject_name
    FROM questions q
    INNER JOIN classes c ON c.id=q.class_id
    LEFT JOIN subjects s ON s.id=q.subject_id
    WHERE $whereSql ORDER BY q.id DESC LIMIT 200");
$list->execute($params);
$questions = $list->fetchAll();

$subjects = [];
if ($fClass>0) { $st=$pdo->prepare('SELECT id,name FROM subjects WHERE class_id=? ORDER BY name'); $st->execute([$fClass]); $subjects=$st->fetchAll(); }

$editId=(int)($_GET['id'] ?? 0);
$edit=null;
$editSubjectName='';
if ($editId>0) {
    $st=$pdo->prepare('SELECT q.*, s.name AS subject_name FROM questions q LEFT JOIN subjects s ON s.id=q.subject_id WHERE q.id=?');
    $st->execute([$editId]); $edit=$st->fetch();
    if ($edit) { $editSubjectName=(string)($edit['subject_name'] ?? ''); }
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.q-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow-sm); padding:22px; }
.q-row { padding:16px 18px; border-bottom:1px solid var(--border); }
.q-row:last-child { border-bottom:0; }
.q-text { font-weight:700; color:var(--text); margin-bottom:6px; }
.q-opt { font-size:13px; color:var(--muted); }
.q-opt b { color:var(--text); }
</style>

<div class="page-header">
    <div class="page-header-text">
        <h1 class="page-title">Question Bank</h1>
        <p class="page-header-desc">Create, edit, search and filter MCQ / written questions.</p>
    </div>
    <div class="page-header-actions">
        <a class="btn btn-outline-secondary" href="#add"><i class="bi bi-plus-lg me-1"></i> New Question</a>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= e((string)$flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= e((string)$flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="filter-bar">
    <form method="get" action="/lectures/admin/questions.php" class="row g-2 w-100 align-items-end">
        <div class="col-12 col-md-3"><label class="form-label">Search</label><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Question text…"></div>
        <div class="col-6 col-md-2"><label class="form-label">Class</label><select class="form-select" name="class_id"><option value="">All</option><?php foreach ($classes as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $fClass===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-6 col-md-2"><label class="form-label">Subject</label><select class="form-select" name="subject_id"><option value="">All</option><?php foreach ($subjects as $s): ?><option value="<?= (int)$s['id'] ?>" <?= $fSubject===(int)$s['id']?'selected':'' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-6 col-md-2"><label class="form-label">Type</label><select class="form-select" name="type"><option value="">All</option><option value="mcq" <?= $fType==='mcq'?'selected':'' ?>>MCQ</option><option value="written" <?= $fType==='written'?'selected':'' ?>>Written</option></select></div>
        <div class="col-6 col-md-3 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i> Filter</button><a class="btn btn-outline-secondary" href="/lectures/admin/questions.php">Reset</a></div>
    </form>
</div>

<div class="q-card mb-4" id="add">
    <h2 class="h5 fw-bold mb-3"><?= $edit ? 'Edit Question' : 'Add Question' ?></h2>
    <form method="post" action="/lectures/admin/questions.php">
        <?= csrf_field('question') ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="question_id" value="<?= (int)($edit['id'] ?? 0) ?>">

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4"><label class="form-label">Class *</label><select class="form-select" name="class_id" id="qf_class" required><option value="">Select class</option><?php foreach ($classes as $c): ?><option value="<?= (int)$c['id'] ?>" <?= ($edit && (int)$edit['class_id']===(int)$c['id'])?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-12 col-md-4"><label class="form-label">Subject</label><select class="form-select" name="subject_id" id="qf_subject"><option value="">Optional</option><?php if ($edit && $edit['subject_id']): ?><option value="<?= (int)$edit['subject_id'] ?>" selected><?= e($editSubjectName) ?></option><?php endif; ?></select></div>
            <div class="col-12 col-md-4"><label class="form-label">Chapter</label><select class="form-select" name="chapter_id" id="qf_chapter"><option value="">Optional</option><?php if ($edit && $edit['chapter_id']): ?><option value="<?= (int)$edit['chapter_id'] ?>" selected>Current</option><?php endif; ?></select></div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-8"><label class="form-label">Question type</label><select class="form-select" name="question_type" id="qf_type"><option value="mcq" <?= (!$edit || $edit['question_type']==='mcq')?'selected':'' ?>>MCQ</option><option value="written" <?= ($edit && $edit['question_type']==='written')?'selected':'' ?>>Written</option></select></div>
            <div class="col-12 col-md-4"><label class="form-label">Marks *</label><input class="form-control" type="number" step="0.25" min="0.25" name="marks" value="<?= e((string)($edit['marks'] ?? '1')) ?>" required></div>
        </div>

        <div class="mb-3"><label class="form-label">Question text *</label><textarea class="form-control" name="question_text" rows="3" required><?= e((string)($edit['question_text'] ?? '')) ?></textarea></div>

        <div id="mcq_opts" class="row g-3 mb-3">
            <div class="col-12 col-md-6"><label class="form-label">Option A</label><input class="form-control" name="option_a" value="<?= e((string)($edit['option_a'] ?? '')) ?>"></div>
            <div class="col-12 col-md-6"><label class="form-label">Option B</label><input class="form-control" name="option_b" value="<?= e((string)($edit['option_b'] ?? '')) ?>"></div>
            <div class="col-12 col-md-6"><label class="form-label">Option C</label><input class="form-control" name="option_c" value="<?= e((string)($edit['option_c'] ?? '')) ?>"></div>
            <div class="col-12 col-md-6"><label class="form-label">Option D</label><input class="form-control" name="option_d" value="<?= e((string)($edit['option_d'] ?? '')) ?>"></div>
            <div class="col-12 col-md-4"><label class="form-label">Correct option</label><select class="form-select" name="correct_answer"><?php foreach (['A','B','C','D'] as $L): ?><option value="<?= $L ?>" <?= ($edit && strtoupper((string)$edit['correct_answer'])===$L)?'selected':'' ?>><?= $L ?></option><?php endforeach; ?></select></div>
        </div>

        <div class="mb-3"><label class="form-label">Explanation (optional)</label><textarea class="form-control" name="explanation" rows="2"><?= e((string)($edit['explanation'] ?? '')) ?></textarea></div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle me-1"></i> <?= $edit?'Update':'Add' ?> Question</button>
            <?php if ($edit): ?><a class="btn btn-outline-secondary" href="/lectures/admin/questions.php">Cancel</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="panel" style="padding:0;">
    <div style="padding:16px 18px;border-bottom:1px solid var(--border);font-weight:750;" class="d-flex justify-content-between align-items-center"><span><?= count($questions) ?> question(s)</span></div>
    <?php if ($questions): ?>
        <?php foreach ($questions as $qq): ?>
            <div class="q-row">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div style="min-width:0;">
                        <div class="q-text"><?= e($qq['question_text']) ?></div>
                        <div class="small text-secondary mb-1">
                            <span class="badge badge-soft-primary"><?= e($qq['class_name']) ?></span>
                            <?php if ($qq['subject_name']): ?><span class="badge badge-soft-info"><?= e($qq['subject_name']) ?></span><?php endif; ?>
                            <span class="badge <?= $qq['question_type']==='mcq'?'badge-soft-success':'badge-soft-warning' ?>"><?= strtoupper(e($qq['question_type'])) ?></span>
                            <span class="badge text-bg-secondary"><?= e((string)$qq['marks']) ?> marks</span>
                        </div>
                        <?php if ($qq['question_type']==='mcq'): ?>
                            <div class="q-opt">
                                <?php
                                $opts = ['a'=>$qq['option_a'],'b'=>$qq['option_b'],'c'=>$qq['option_c'],'d'=>$qq['option_d']];
                                foreach ($opts as $k=>$v) { if ($v!==null&&$v!=='') { echo '<div><b>'.strtoupper($k).'.</b> '.e((string)$v).((strtoupper((string)$qq['correct_answer'])===strtoupper($k))?' ✓':'').'</div>'; } }
                                ?>
                            </div>
                        <?php else: ?>
                            <div class="q-opt"><?= $qq['correct_answer']!==null ? '<b>Model:</b> '.e((string)$qq['correct_answer']) : '<em>Manual grading</em>' ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <a class="btn btn-sm btn-outline-secondary" href="/lectures/admin/questions.php?id=<?= (int)$qq['id'] ?>#add"><i class="bi bi-pencil"></i></a>
                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this question?');">
                            <?= csrf_field('question') ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="question_id" value="<?= (int)$qq['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state"><i class="bi bi-question-diamond fs-1 d-block mb-2"></i><h5 class="fw-bold">No questions yet</h5><p class="mb-0">Add your first question above.</p></div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const load=(u,sel,ph)=>fetch(u).then(r=>r.json()).then(l=>{sel.innerHTML='<option value="">'+ph+'</option>';l.forEach(i=>{const o=document.createElement('option');o.value=i.id;o.textContent=i.name;sel.appendChild(o);});});
    const ffc=document.getElementById('qf_class'), ffs=document.getElementById('qf_subject'), ffc2=document.getElementById('qf_chapter');
    if(ffc&&ffs){ffc.addEventListener('change',function(){if(!this.value){ffs.innerHTML='<option value="">Optional</option>';return;}load('/lectures/admin/ajax-options.php?field=subject&class_id='+this.value,ffs,'Optional');});}
    if(ffs&&ffc2){ffs.addEventListener('change',function(){if(!this.value){ffc2.innerHTML='<option value="">Optional</option>';return;}load('/lectures/admin/ajax-options.php?field=chapter&subject_id='+this.value,ffc2,'Optional');});}
    const qt=document.getElementById('qf_type'), opts=document.getElementById('mcq_opts');
    function tg(){if(opts)opts.style.display=(qt.value==='written')?'none':'';}
    if(qt){qt.addEventListener('change',tg);tg();}
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>