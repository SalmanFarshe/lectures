<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bootstrap & Authorization
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

require_once __DIR__ . '/../config/database.php';

$name = (string) ($_SESSION['full_name'] ?? 'Admin');

$totalStudents = (int) $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'student'"
)->fetchColumn();

$activeStudents = (int) $pdo->query(
    "SELECT COUNT(*) FROM users
     WHERE role = 'student' AND is_active = 1"
)->fetchColumn();

$totalClasses = (int) $pdo->query(
    "SELECT COUNT(*) FROM classes WHERE is_active = 1"
)->fetchColumn();

$totalChapters = (int) $pdo->query(
    "SELECT COUNT(*) FROM chapters WHERE is_active = 1"
)->fetchColumn();

$stmt = $pdo->query(
    "SELECT u.id, u.full_name, u.username, u.is_active,
            c.name AS class_name
     FROM users u
     LEFT JOIN classes c ON c.id = u.class_id
     WHERE u.role = 'student'
     ORDER BY u.id DESC
     LIMIT 6"
);

$recentStudents = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Layout Contract
|--------------------------------------------------------------------------
*/

$pageTitle = 'Dashboard';
$activePage = 'dashboard';

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Dashboard-specific components (uses shared design tokens) -->
<style>
    .dash-banner-icon {
        position: absolute;
        z-index: 1;
        right: 24px;
        bottom: 12px;
        font-size: 92px;
        line-height: 1;
        color: rgba(255, 255, 255, 0.18);
    }

    .welcome-tag {
        position: relative;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 12px;
        margin-bottom: 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.16);
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .section-card {
        height: 100%;
        overflow: hidden;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
    }

    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 18px 22px;
        border-bottom: 1px solid var(--border);
    }

    .section-title {
        margin: 0;
        font-size: 16px;
        font-weight: 750;
    }

    .section-subtitle {
        color: var(--muted);
        font-size: 12px;
        margin-top: 4px;
    }

    .text-link {
        color: var(--primary);
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        white-space: nowrap;
    }

    .text-link:hover {
        color: var(--primary-hover);
    }

    .quick-actions {
        padding: 18px;
    }

    .quick-action {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 15px;
        margin-bottom: 11px;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        color: var(--text);
        text-decoration: none;
        transition: border-color .2s ease, background .2s ease, transform .2s ease;
    }

    .quick-action:last-child {
        margin-bottom: 0;
    }

    .quick-action:hover {
        border-color: var(--primary);
        background: var(--primary-soft);
        transform: translateX(2px);
        color: var(--text);
    }

    .quick-icon {
        display: grid;
        place-items: center;
        width: 42px;
        height: 42px;
        flex-shrink: 0;
        border-radius: var(--radius-sm);
        font-size: 18px;
    }

    .quick-icon.primary { color: var(--primary); background: var(--primary-soft); }
    .quick-icon.success { color: var(--success); background: var(--success-soft); }
    .quick-icon.warning { color: var(--warning); background: var(--warning-soft); }
    .quick-icon.info    { color: var(--info);    background: var(--info-soft); }

    .quick-title {
        font-size: 13px;
        font-weight: 750;
    }

    .quick-desc {
        color: var(--muted);
        font-size: 12px;
        margin-top: 4px;
    }

    .quick-arrow {
        margin-left: auto;
        color: var(--muted);
    }

    .student-info {
        display: flex;
        align-items: center;
        gap: 11px;
    }

    .student-avatar {
        display: grid;
        place-items: center;
        width: 36px;
        height: 36px;
        flex-shrink: 0;
        border-radius: var(--radius-sm);
        background: var(--primary-soft);
        color: var(--primary);
        font-weight: 800;
    }

    .student-name {
        font-weight: 700;
        color: var(--text);
    }

    .student-username {
        color: var(--muted);
        font-size: 12px;
        margin-top: 2px;
    }

    .status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
    }

    .status::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }

    .status-active {
        color: var(--success);
        background: var(--success-soft);
    }

    .status-inactive {
        color: var(--danger);
        background: var(--danger-soft);
    }
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-text">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-header-desc">Overview of your education platform.</p>
    </div>
    <div class="page-header-actions">
        <a class="btn btn-primary" href="/lectures/admin/students.php">
            <i class="bi bi-people-fill me-1"></i> Manage Students
        </a>
    </div>
</div>

<!-- Welcome Banner -->
<section class="welcome-banner" style="position:relative; overflow:hidden;">
    <div style="position:relative; z-index:1;">
        <span class="welcome-tag">
            <i class="bi bi-stars"></i> MASTER ADMINISTRATOR
        </span>
        <h2>Welcome back, <?= e($name) ?>! 👋</h2>
        <p>
            Manage student accounts, organize classes and control access to your lecture notes.
        </p>
        <p class="mt-3 mb-0" style="color:rgba(255,255,255,0.85); font-size:13px;">
            <i class="bi bi-calendar3 me-2"></i><?= date('l, d F Y') ?>
        </p>
    </div>
    <i class="bi bi-mortarboard-fill dash-banner-icon"></i>
</section>

<!-- Statistic Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="stat-label">Total Students</div>
                <div class="stat-value"><?= $totalStudents ?></div>
                <div class="section-subtitle">Registered accounts</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon success"><i class="bi bi-person-check-fill"></i></div>
            <div>
                <div class="stat-label">Active Students</div>
                <div class="stat-value"><?= $activeStudents ?></div>
                <div class="section-subtitle">Enabled accounts</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon warning"><i class="bi bi-mortarboard-fill"></i></div>
            <div>
                <div class="stat-label">Active Classes</div>
                <div class="stat-value"><?= $totalClasses ?></div>
                <div class="section-subtitle">Available classes</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon info"><i class="bi bi-journal-richtext"></i></div>
            <div>
                <div class="stat-label">Active Chapters</div>
                <div class="stat-value"><?= $totalChapters ?></div>
                <div class="section-subtitle">Registered lecture notes</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Students -->
    <div class="col-12 col-xl-7">
        <section class="section-card">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Recent Students</h2>
                    <div class="section-subtitle">Latest registered student accounts</div>
                </div>
                <a class="text-link" href="/lectures/admin/students.php">
                    View all <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <?php if ($recentStudents): ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentStudents as $student): ?>
                                <tr>
                                    <td>
                                        <div class="student-info">
                                            <div class="student-avatar">
                                                <?= e(strtoupper(substr($student['full_name'], 0, 1))) ?>
                                            </div>
                                            <div>
                                                <div class="student-name">
                                                    <?= e($student['full_name']) ?>
                                                </div>
                                                <div class="student-username">
                                                    <?= e($student['username']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= e($student['class_name'] ?? 'Not assigned') ?></td>
                                    <td>
                                        <span class="status <?= (int) $student['is_active'] === 1
                                                        ? 'status-active'
                                                        : 'status-inactive' ?>">
                                            <?= (int) $student['is_active'] === 1
                                                ? 'Active'
                                                : 'Inactive' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-people fs-1 d-block mb-2"></i>
                    <h6 class="fw-bold">No students yet</h6>
                    <p class="small mb-3">
                        Create your first student account to get started.
                    </p>
                    <a class="btn btn-primary" href="/lectures/admin/students.php">
                        <i class="bi bi-plus-lg me-1"></i> Add Student
                    </a>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Quick Actions -->
    <div class="col-12 col-xl-5">
        <section class="section-card">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Quick Actions</h2>
                    <div class="section-subtitle">Common administration tasks</div>
                </div>
                <i class="bi bi-lightning-charge-fill text-warning fs-5"></i>
            </div>

            <div class="quick-actions">
                <a class="quick-action" href="/lectures/admin/students.php">
                    <div class="quick-icon primary"><i class="bi bi-person-plus-fill"></i></div>
                    <div>
                        <div class="quick-title">Student Management</div>
                        <div class="quick-desc">Create, edit and manage accounts</div>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>

                <a class="quick-action" href="/lectures/admin/notes.php">
                    <div class="quick-icon success"><i class="bi bi-journal-richtext"></i></div>
                    <div>
                        <div class="quick-title">Browse Notes</div>
                        <div class="quick-desc">Open any lecture note by class, subject and chapter</div>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>

                <a class="quick-action" href="/lectures/admin/chapters.php">
                    <div class="quick-icon warning"><i class="bi bi-journal-bookmark-fill"></i></div>
                    <div>
                        <div class="quick-title">Chapter Management</div>
                        <div class="quick-desc">Scan and manage lecture notes</div>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>

                <a class="quick-action" href="/lectures/admin/chapter-permissions.php">
                    <div class="quick-icon info"><i class="bi bi-shield-lock-fill"></i></div>
                    <div>
                        <div class="quick-title">Chapter Permissions</div>
                        <div class="quick-desc">Control each student's chapter access</div>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>
            </div>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
