<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Sidebar User Information
|--------------------------------------------------------------------------
*/

$userRole = (string)($_SESSION['role'] ?? '');
$userName = trim((string)($_SESSION['full_name'] ?? 'User'));

$isAdmin = $userRole === 'admin';

$basePath = $isAdmin
    ? '/lectures/admin/'
    : '/lectures/student/';

$currentPage = (string)($activePage ?? 'dashboard');

/*
|--------------------------------------------------------------------------
| Navigation Menu
|--------------------------------------------------------------------------
*/

$adminMenuItems = [
    [
        'key' => 'dashboard',
        'label' => 'Dashboard',
        'icon' => 'bi-grid-1x2',
        'url' => 'index.php',
    ],
    [
        'key' => 'students',
        'label' => 'Students',
        'icon' => 'bi-people',
        'url' => 'students.php',
    ],
    [
        'key' => 'academic',
        'label' => 'Academic',
        'icon' => 'bi-diagram-3',
        'url' => 'academic.php',
    ],
    [
        'key' => 'navigator',
        'label' => 'Notes',
        'icon' => 'bi-journal-richtext',
        'url' => 'navigator.php',
    ],
    [
        'key' => 'permissions',
        'label' => 'Chapter Permissions',
        'icon' => 'bi-shield-lock',
        'url' => 'chapter-permissions.php',
    ],
    [
        'key' => 'questions',
        'label' => 'Question Bank',
        'icon' => 'bi-question-diamond',
        'url' => 'questions.php',
    ],
    [
        'key' => 'quizzes',
        'label' => 'Quiz Generator',
        'icon' => 'bi-clipboard-check',
        'url' => 'quizzes.php',
    ],
    [
        'key' => 'lecturers',
        'label' => 'Lecturer Requests',
        'icon' => 'bi-person-plus',
        'url' => 'lecturers.php',
    ],
];

$studentMenuItems = [
    [
        'key' => 'dashboard',
        'label' => 'Dashboard',
        'icon' => 'bi-grid-1x2',
        'url' => 'index.php',
    ],
    [
        'key' => 'quizzes',
        'label' => 'My Quizzes',
        'icon' => 'bi-clipboard-check',
        'url' => 'quizzes.php',
    ],
    [
        'key' => 'profile',
        'label' => 'My Profile',
        'icon' => 'bi-person-circle',
        'url' => 'profile.php',
    ],
];

$lecturerMenuItems = [
    [
        'key' => 'lecturer-dashboard',
        'label' => 'Lecturer Dashboard',
        'icon' => 'bi-person-chalkboard',
        'url' => 'lecturer-dashboard.php',
    ],
    [
        'key' => 'navigator',
        'label' => 'Notes',
        'icon' => 'bi-journal-richtext',
        'url' => 'navigator.php',
    ],
    [
        'key' => 'profile',
        'label' => 'My Profile',
        'icon' => 'bi-person-circle',
        'url' => '../account.php',
    ],
];

$menuItems = $isAdmin
    ? $adminMenuItems
    : ($userRole === 'lecturer' ? $lecturerMenuItems : $studentMenuItems);

/*
|--------------------------------------------------------------------------
| Avatar Initial
|--------------------------------------------------------------------------
*/

$avatarInitial = 'U';

if ($userName !== '') {
    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
        $avatarInitial = mb_strtoupper(
            mb_substr($userName, 0, 1, 'UTF-8'),
            'UTF-8'
        );
    } else {
        $avatarInitial = strtoupper(substr($userName, 0, 1));
    }
}

/*
|--------------------------------------------------------------------------
| Validate Active Page Key
|--------------------------------------------------------------------------
*/

$validKeys = array_column($menuItems, 'key');

if (!in_array($currentPage, $validKeys, true)) {
    $currentPage = '';
}
?>

<aside
    class="app-sidebar"
    id="appSidebar"
    aria-label="Sidebar navigation">

    <!-- Brand -->
    <a
        href="<?= e($basePath . 'index.php') ?>"
        class="brand"
        aria-label="Lectures home">

        <span class="brand-icon">
            <i class="bi bi-journal-bookmark-fill" aria-hidden="true"></i>
        </span>

        <span class="brand-name">
            Lectures<span class="brand-dot">.</span>
        </span>
    </a>

    <!-- Main Navigation -->
    <div class="sidebar-label">MAIN MENU</div>

    <nav class="sidebar-nav" aria-label="Main navigation">

        <?php foreach ($menuItems as $item): ?>
            <?php $isActive = $currentPage === $item['key']; ?>

            <a
                href="<?= e($basePath . $item['url']) ?>"
                class="sidebar-link<?= $isActive ? ' active' : '' ?>"
                <?= $isActive ? 'aria-current="page"' : '' ?>>

                <i
                    class="bi <?= e($item['icon']) ?>"
                    aria-hidden="true"></i>

                <span><?= e($item['label']) ?></span>

                <?php if ($isActive): ?>
                    <span class="sidebar-active-indicator"
                        aria-hidden="true"></span>
                <?php endif; ?>

            </a>
        <?php endforeach; ?>

    </nav>

    <!-- User Section -->
    <div class="sidebar-bottom">

        <div class="user-mini">

            <div class="user-avatar" aria-hidden="true">
                <?= e($avatarInitial) ?>
            </div>

            <div class="user-mini-info">
                <strong title="<?= e($userName) ?>">
                    <?= e($userName) ?>
                </strong>

                <small>
                    <?= $isAdmin ? 'Administrator' : 'Student' ?>
                </small>
            </div>

        </div>

        <!-- CSRF-Protected Logout -->
        <form method="post" action="/lectures/logout.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e((string)($_SESSION['logout_csrf'] ?? '')) ?>">

            <button type="submit" class="sidebar-logout">

                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>

                <span>Logout</span>

            </button>

        </form>

    </div>

</aside>