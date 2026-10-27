<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Public Notes — Public notes listing with class + price filter
|--------------------------------------------------------------------------
| No login required. Lists all active public notes. Filter by class and
| price type (free/paid) via query params: ?class_id=1&price_type=free
| Requires $pdo.
*/

$pageTitle = 'পাবলিক নোট';
$activePage = 'public-notes';

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

/*
|--------------------------------------------------------------------------
| Filter options
|--------------------------------------------------------------------------
*/
$classFilter = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
$priceFilter = isset($_GET['price_type']) && in_array($_GET['price_type'], ['free', 'paid'], true)
    ? $_GET['price_type']
    : '';

/*
|--------------------------------------------------------------------------
| Classes for filter dropdown
|--------------------------------------------------------------------------
*/
$stmtClasses = $pdo->query('SELECT id, name FROM classes WHERE is_active = 1 ORDER BY name');
$classes = $stmtClasses->fetchAll();

/*
|--------------------------------------------------------------------------
| Query public notes with filters
|--------------------------------------------------------------------------
*/
$sql = '
    SELECT n.id, n.title, n.price_type, n.created_at,
           c.name AS class_name, s.name AS subject_name, ch.title AS chapter_title
    FROM notes n
    JOIN chapters ch ON ch.id = n.chapter_id
    JOIN subjects s ON s.id = ch.subject_id
    JOIN classes c ON c.id = s.class_id
    WHERE n.is_active = 1 AND n.is_public = 1';
$params = [];

if ($classFilter > 0) {
    $sql .= ' AND c.id = :class_id';
    $params[':class_id'] = $classFilter;
}
if ($priceFilter !== '') {
    $sql .= ' AND n.price_type = :price_type';
    $params[':price_type'] = $priceFilter;
}
$sql .= ' ORDER BY n.created_at DESC';

$stmtNotes = $pdo->prepare($sql);
$stmtNotes->execute($params);
$notes = $stmtNotes->fetchAll();

/*
|--------------------------------------------------------------------------
| Summary counts for filter chips
|--------------------------------------------------------------------------
*/
$stmtFree = $pdo->query("SELECT COUNT(*) FROM notes WHERE is_active = 1 AND is_public = 1 AND price_type = 'free'");
$totalFree = (int) $stmtFree->fetchColumn();

$stmtPaid = $pdo->query("SELECT COUNT(*) FROM notes WHERE is_active = 1 AND is_public = 1 AND price_type = 'paid'");
$totalPaid = (int) $stmtPaid->fetchColumn();

?>
            <!-- Page Header -->
            <section class="page-header">
                <div class="page-header-text">
                    <h1 class="page-heading">পাবলিক নোট</h1>
                    <p class="page-header-desc">প্রতি ক্লাসে ফ্রি ও পেইড নোট — ফিল্টার করুন এবং পড়ুন</p>
                </div>
                <div class="page-header-actions">
                    <a href="/lectures/public-notes.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </section>

            <!-- Filter Bar -->
            <section class="filter-section">
                <div class="filter-bar row g-3 align-items-center">
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-medium">ক্লাস বাছাই করুন</label>
                        <select class="form-select" id="classFilter" onchange="applyClassFilter()">
                            <option value="0">সব ক্লাস</option>
                            <?php foreach ($classes as $cls): ?>
                                <option value="<?= (int) $cls['id'] ?>" <?= $classFilter === (int) $cls['id'] ? 'selected' : '' ?>>
                                    <?= e($cls['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-medium">দামের ধরন</label>
                        <select class="form-select" id="priceFilter" onchange="applyPriceFilter()">
                            <option value="" <?= $priceFilter === '' ? 'selected' : '' ?>>সব দামের ধরন</option>
                            <option value="free" <?= $priceFilter === 'free' ? 'selected' : '' ?>>ফ্রি</option>
                            <option value="paid" <?= $priceFilter === 'paid' ? 'selected' : '' ?>>পেইড</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 d-flex align-items-end">
                        <a href="/lectures/public-notes.php" class="btn btn-outline-secondary w-100">Reset</a>
                    </div>
                </div>

                <!-- Summary chips -->
                <div class="summary-chips mt-3">
                    <span class="chip chip-all"><?= count($notes) ?> মোট নোট</span>
                    <a href="/lectures/public-notes.php?price_type=free<?= $classFilter > 0 ? '&class_id=' . $classFilter : '' ?>" class="chip chip-free">ফ্রি: <?= $totalFree ?></a>
                    <a href="/lectures/public-notes.php?price_type=paid<?= $classFilter > 0 ? '&class_id=' . $classFilter : '' ?>" class="chip chip-paid">পেইড: <?= $totalPaid ?></a>
                </div>
            </section>

            <!-- Notes List -->
            <section class="section-card" aria-label="পাবলিক নোট তালিকা">
                <div class="section-header">
                    <h2 class="section-title"><?= e((string) count($notes)) ?>টি নোট পাওয়া গেছে</h2>
                </div>

                <?php if ($notes): ?>
                    <div class="notes-grid row g-3">
                        <?php foreach ($notes as $note): ?>
                            <div class="col-12 col-sm-6 col-lg-4">
                                <article class="note-card h-100">
                                    <div class="note-card-body">
                                        <span class="note-price-badge <?= e($note['price_type']) ?>"><?= e($note['price_type']) === 'paid' ? 'পেইড' : 'ফ্রি' ?></span>
                                        <h3 class="note-title"><?= e($note['title']) ?></h3>
                                        <div class="note-meta">
                                            <span><i class="bi bi-building me-1"></i><?= e($note['class_name']) ?></span>
                                            <span><i class="bi bi-book me-1"></i><?= e($note['subject_name']) ?></span>
                                            <span><i class="bi bi-file-earmark me-1"></i><?= e($note['chapter_title']) ?></span>
                                        </div>
                                        <div class="note-date small text-muted"><?= date('d M Y', strtotime($note['created_at'])) ?></div>
                                        <a href="/lectures/student/chapter.php?id=<?= (int) $note['id'] ?>" class="btn btn-primary w-100">নোট পড়ুন</a>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state text-center py-5">
                        <i class="bi bi-search"></i>
                        <h3>কোনো নোট পাওয়া যায়নি</h3>
                        <p>অন্য ফিল্টার চেষ্টা করুন অথবা পরে দেখুন।</p>
                        <a href="/lectures/public-notes.php" class="btn btn-primary">Reset Filter</a>
                    </div>
                <?php endif; ?>
            </section>

<script>
function applyClassFilter() {
    const classId = document.getElementById('classFilter').value;
    const url = new URL(window.location.href);
    if (classId > 0) {
        url.searchParams.set('class_id', classId);
    } else {
        url.searchParams.delete('class_id');
    }
    window.location.href = url.toString();
}

function applyPriceFilter() {
    const priceType = document.getElementById('priceFilter').value;
    const url = new URL(window.location.href);
    if (priceType !== '') {
        url.searchParams.set('price_type', priceType);
    } else {
        url.searchParams.delete('price_type');
    }
    window.location.href = url.toString();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>