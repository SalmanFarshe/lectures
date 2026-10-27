<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Homepage — Public landing page
|--------------------------------------------------------------------------
| No login required. Shows welcome info, quick stats and featured public notes.
| Requires $pdo (already available via config include chain).
*/

$pageTitle = 'Home';
$activePage = 'home';

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

/*
|--------------------------------------------------------------------------
| Quick statistics
|--------------------------------------------------------------------------
*/

$stmtClasses = $pdo->query('SELECT COUNT(*) AS c FROM classes WHERE is_active = 1');
$totalClasses = (int) $stmtClasses->fetchColumn();

$stmtSubjects = $pdo->query('SELECT COUNT(*) AS c FROM subjects WHERE is_active = 1');
$totalSubjects = (int) $stmtSubjects->fetchColumn();

$stmtChapters = $pdo->query('SELECT COUNT(*) AS c FROM chapters WHERE is_active = 1');
$totalChapters = (int) $stmtChapters->fetchColumn();

$stmtNotes = $pdo->query('SELECT COUNT(*) AS c FROM notes WHERE is_active = 1 AND is_public = 1');
$totalPublicNotes = (int) $stmtNotes->fetchColumn();

/*
|--------------------------------------------------------------------------
| Featured public notes (latest 6)
|--------------------------------------------------------------------------
*/
$stmtFeatured = $pdo->prepare(
    'SELECT n.id, n.title, n.price_type, c.name AS class_name, s.name AS subject_name, ch.title AS chapter_title
     FROM notes n
     JOIN chapters ch ON ch.id = n.chapter_id
     JOIN subjects s ON s.id = ch.subject_id
     JOIN classes c ON c.id = s.class_id
     WHERE n.is_active = 1 AND n.is_public = 1
     ORDER BY n.created_at DESC
     LIMIT 6'
);
$stmtFeatured->execute();
$featuredNotes = $stmtFeatured->fetchAll();

?>
            <!-- Welcome Banner -->
            <section class="welcome-banner" aria-label="Welcome">
                <div class="welcome-tag">প্রারম্ভ করুন আজই</div>
                <h1>আপনার শিক্ষা প্ল্যাটফর্ম, লেকচারস</h1>
                <p>ক্লাস, সাবজেক্ট, চ্যাপ্টার এবং নোট — সব এক জায়গায়। এখনই শুরু করুন এবং অগ্রগতি করুন।</p>
                <div class="welcome-actions">
                    <a href="/lectures/public-notes.php" class="btn btn-primary btn-lg">পাবলিক নোট দেখুন</a>
                    <?php if (empty($_SESSION['user_id'] ?? '')): ?>
                        <a href="/lectures/index.php" class="btn btn-outline-secondary btn-lg">লগইন করুন</a>
                    <?php else: ?>
                        <a href="/lectures/student/index.php" class="btn btn-outline-secondary btn-lg">ড্যাশবোর্ডে যান</a>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Quick Stats -->
            <section class="stat-section" aria-label="প্ল্যাটফর্ম পরিসংখ্যান">
                <div class="row g-3">
                    <div class="col-6 col-sm-3">
                        <div class="stat-card text-center">
                            <div class="stat-icon"><i class="bi bi-building"></i></div>
                            <div class="stat-value"><?= $totalClasses ?></div>
                            <div class="stat-label">ক্লাস</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="stat-card text-center">
                            <div class="stat-icon"><i class="bi bi-book"></i></div>
                            <div class="stat-value"><?= $totalSubjects ?></div>
                            <div class="stat-label">সাবজেক্ট</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="stat-card text-center">
                            <div class="stat-icon"><i class="bi bi-file-earmark-richtext"></i></div>
                            <div class="stat-value"><?= $totalChapters ?></div>
                            <div class="stat-label">চ্যাপ্টার</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="stat-card text-center">
                            <div class="stat-icon"><i class="bi bi-play-circle"></i></div>
                            <div class="stat-value"><?= $totalPublicNotes ?></div>
                            <div class="stat-label">পাবলিক নোট</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Featured Public Notes -->
            <section class="section-card" aria-label="প্রদর্শিত পাবলিক নোট">
                <div class="section-header">
                    <h2 class="section-title">প্রদর্শিত পাবলিক নোট</h2>
                    <p class="section-subtitle">এক ক্লিকে পড়ুন — কোনো লগইন প্রয়োজন নেই</p>
                    <a href="/lectures/public-notes.php" class="text-link">সব দেখুন →</a>
                </div>

                <?php if ($featuredNotes): ?>
                    <div class="note-grid row g-3">
                        <?php foreach ($featuredNotes as $note): ?>
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
                                        <a href="/lectures/student/chapter.php?id=<?= (int) $note['id'] ?>" class="btn btn-sm btn-outline-primary w-100">উদ್ಘাটন করুন</a>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state text-center py-5">
                        <i class="bi bi-collection"></i>
                        <h3>এখনো কোনো পাবলিক নোট নেই</h3>
                        <p>অ্যাডমিন পাবলিক নোট যোগ করলে এখানে এগুলো প্রদর্শিত হবে।</p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Quick Access -->
            <section class="quick-actions" aria-label="দ্রুত অ্যাক্সেস">
                <div class="section-header">
                    <h2 class="section-title">দ্রুত অ্যাক্সেস</h2>
                </div>
                <div class="quick-grid row g-3">
                    <div class="col-6 col-sm-4 col-lg-2">
                        <a href="/lectures/public-notes.php" class="quick-card text-center">
                            <i class="bi bi-collection quick-icon"></i>
                            <div class="quick-label">পাবলিক নোট</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-lg-2">
                        <a href="/lectures/index.php" class="quick-card text-center">
                            <i class="bi bi-box-arrow-in-right quick-icon"></i>
                            <div class="quick-label">লগইন</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-lg-2">
                        <a href="/lectures/register.php" class="quick-card text-center">
                            <i class="bi bi-person-plus quick-icon"></i>
                            <div class="quick-label">অ্যাকাউন্ট তৈরি করুন</div>
                        </a>
                    </div>
                    <?php if (!empty($_SESSION['user_id'] ?? '')): ?>
                        <div class="col-6 col-sm-4 col-lg-2">
                            <a href="/lectures/account.php" class="quick-card text-center">
                                <i class="bi bi-person-gear quick-icon"></i>
                                <div class="quick-label">প্রোফাইল</div>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>