<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Lecturer Dashboard
|--------------------------------------------------------------------------
| Shows lecturer-specific overview: own profile stats, assigned chapters,
| and quick actions. Requires lecturer role.
*/

require_once __DIR__ . '/../includes/auth.php';
requireRole('lecturer');
require_once __DIR__ . '/../config/database.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    redirect('/lectures/logout.php');
}

// Load lecturer profile and user info.
$stmt = $pdo->prepare(
    "SELECT u.id, u.username, u.full_name, u.class_id, u.is_active,
            lp.employee_id, lp.department, lp.qualification, lp.is_approved
     FROM users u
     LEFT JOIN lecturer_profiles lp ON lp.user_id = u.id
     WHERE u.id = ? AND u.role = 'lecturer'
     LIMIT 1"
);
$stmt->execute([$userId]);
$lecturer = $stmt->fetch();

if (!$lecturer || (int)$lecturer['is_active'] !== 1) {
    $_SESSION = [];
    session_destroy();
    redirect('/lectures/index.php');
}

$name = (string)($lecturer['full_name'] ?: 'Lecturer');
$department = (string)($lecturer['department'] ?? 'Not assigned');
$qualification = (string)($lecturer['qualification'] ?? 'Not assigned');
$employeeId = (string)($lecturer['employee_id'] ?? 'Not assigned');
$isApproved = (int)($lecturer['is_approved'] ?? 0);

$totalClasses = (int) $pdo->query("SELECT COUNT(*) FROM classes WHERE is_active = 1")->fetchColumn();
$totalSubjects = (int) $pdo->query("SELECT COUNT(*) FROM subjects WHERE is_active = 1")->fetchColumn();
$totalChapters = (int) $pdo->query("SELECT COUNT(*) FROM chapters WHERE is_active = 1")->fetchColumn();
$totalNotes = (int) $pdo->query("SELECT COUNT(*) FROM notes WHERE is_active = 1")->fetchColumn();

$pageTitle = 'Lecturer Dashboard';
$activePage = 'lecturer-dashboard';

require_once __DIR__ . '/../includes/header.php';
?>
            <!-- Page Header -->
            <section class="page-header">
                <div class="page-header-text">
                    <h1 class="page-heading">Lecturer Dashboard</h1>
                    <p class="page-header-desc">আপনার প্রোফাইল ও প্ল্যাটফর্ম পরিসংখ্যান</p>
                </div>
            </section>

            <!-- Approval Status Alert -->
            <?php if (!$isApproved): ?>
                <div class="alert alert-warning" role="alert">
                    আপনার অ্যাকাউন্ট এখনো অনুমোদিত হয়নি। অ্যাডমিন অনুমোদন দেওয়ার পর আপনি সম্পূর্ণ অ্যাক্সেস পাবেন।
                </div>
            <?php endif; ?>

            <!-- Welcome Banner -->
            <section class="welcome-banner mb-4" style="position:relative; overflow:hidden;">
                <div style="position:relative; z-index:1;">
                    <span class="welcome-tag">
                        <i class="bi bi-person-badge"></i> <?= e(strtoupper($department)) ?>
                    </span>
                    <h1>স্বাগতম, <?= e($name) ?>! 👋</h1>
                    <p>লেকচারার ড্যাশবোর্ডে আপনাকে স্বাগতম। আপনার প্রোফাইল এবং প্ল্যাটফর্মের পরিসংখ্যান নিচে দেওয়া হলো।</p>
                    <p class="mt-3 mb-0" style="color:rgba(255,255,255,0.85); font-size:13px;">
                        <i class="bi bi-calendar3 me-2"></i><?= date('l, d F Y') ?>
                    </p>
                </div>
                <i class="bi bi-person-chalkboard dash-banner-icon"></i>
            </section>

            <!-- Statistic Cards -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon primary"><i class="bi bi-person-badge"></i></div>
                        <div class="stat-label">লেকচারার আইডি</div>
                        <div class="stat-value"><?= e($employeeId) ?></div>
                        <div class="section-subtitle">আপনার employee ID</div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon success"><i class="bi bi-building"></i></div>
                        <div class="stat-label">মোট ক্লাস</div>
                        <div class="stat-value"><?= $totalClasses ?></div>
                        <div class="section-subtitle">প্ল্যাটফর্মে সক্রিয় ক্লাস</div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon info"><i class="bi bi-book"></i></div>
                        <div class="stat-label">মোট সাবজেক্ট</div>
                        <div class="stat-value"><?= $totalSubjects ?></div>
                        <div class="section-subtitle">প্ল্যাটফর্মে সক্রিয় সাবজেক্ট</div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon warning"><i class="bi bi-file-earmark-richtext"></i></div>
                        <div class="stat-label">মোট চ্যাপ্টার</div>
                        <div class="stat-value"><?= $totalChapters ?></div>
                        <div class="section-subtitle">প্ল্যাটফর্মে সক্রিয় চ্যাপ্টার</div>
                    </div>
                </div>
            </div>

            <!-- Profile Info Card -->
            <section class="section-card">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">আপনার প্রোফাইল</h2>
                        <p class="section-subtitle">আপনার অ্যাকাউন্টের বিবরণ</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="profile-field">
                            <label class="form-label">পুরো নাম</label>
                            <p class="form-control-static"><?= e($name) ?></p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="profile-field">
                            <label class="form-label">ইউজারনেম</label>
                            <p class="form-control-static"><?= e($lecturer['username']) ?></p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="profile-field">
                            <label class="form-label">ডিপার্টমেন্ট</label>
                            <p class="form-control-static"><?= e($department) ?></p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="profile-field">
                            <label class="form-label">কোয়ালিফিকেশন</label>
                            <p class="form-control-static"><?= e($qualification) ?></p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="profile-field">
                            <label class="form-label">এমপ্লয়ি আইডি</label>
                            <p class="form-control-static"><?= e($employeeId) ?></p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="profile-field">
                            <label class="form-label">অনুমোদন অবস্থা</label>
                            <p class="form-control-static">
                                <span class="badge <?= $isApproved ? 'bg-success' : 'bg-warning' ?>">
                                    <?= $isApproved ? 'অনুমোদিত' : 'অনুমোদিত নয়' ?>
                                </span>

            <!-- Quick Actions -->
            <section class="quick-actions">
                <div class="section-header">
                    <h2 class="section-title">দ্রুত অ্যাক্সেস</h2>
                </div>
                <div class="quick-grid row g-3">
                    <div class="col-6 col-sm-4 col-lg-2">
                        <a href="/lectures/admin/navigator.php" class="quick-card text-center">
                            <i class="bi bi-journal-richtext quick-icon primary"></i>
                            <div class="quick-label">নোট নেভিগেটর</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-lg-2">
                        <a href="/lectures/admin/academic.php" class="quick-card text-center">
                            <i class="bi bi-diagram-3 quick-icon info"></i>
                            <div class="quick-label">অ্যাকাডেমিক</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-lg-2">
                        <a href="/lectures/admin/questions.php" class="quick-card text-center">
                            <i class="bi bi-question-diamond quick-icon warning"></i>
                            <div class="quick-label">কwestion ব্যাংক</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-lg-2">
                        <a href="/lectures/admin/quizzes.php" class="quick-card text-center">
                            <i class="bi bi-clipboard-check quick-icon info"></i>
                            <div class="quick-label">কুইজ জেনারেটর</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-lg-2">
                        <a href="/lectures/account.php" class="quick-card text-center">
                            <i class="bi bi-person-gear quick-icon success"></i>
                            <div class="quick-label">প্রোফাইল ও পাসওয়ার্ড</div>
                        </a>
                    </div>
                </div>
            </section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </section>