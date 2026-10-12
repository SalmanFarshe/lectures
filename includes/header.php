<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Page Configuration
|--------------------------------------------------------------------------
| Set $pageTitle and $activePage before including this file.
*/

$pageTitle = $pageTitle ?? 'Dashboard';
$activePage = $activePage ?? 'dashboard';

/*
|--------------------------------------------------------------------------
| Sidebar visibility
|--------------------------------------------------------------------------
| Pages may set $showSidebar = false before including this file to render a
| full-width layout without the shared sidebar (used by student pages).
*/
$showSidebar = $showSidebar ?? true;
$noSidebarClass = $showSidebar ? '' : ' no-sidebar';

$userName = (string) ($_SESSION['full_name'] ?? 'User');
$userRole = (string) ($_SESSION['role'] ?? 'student');
$logoutToken = (string) ($_SESSION['logout_csrf'] ?? '');

/*
| Resolve the user's class name for the profile dropdown. Only a lightweight
| lookup, and only when a class id is present in the session.
*/
$userClass = '';
$sessionClassId = $_SESSION['class_id'] ?? null;
if (!empty($sessionClassId) && isset($pdo)) {
    try {
        $clsStmt = $pdo->prepare('SELECT name FROM classes WHERE id = ? LIMIT 1');
        $clsStmt->execute([(int)$sessionClassId]);
        $userClass = (string)($clsStmt->fetchColumn() ?: '');
    } catch (Throwable $e) {
        $userClass = '';
    }
}

?>
<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="color-scheme" content="light">

    <title><?= e($pageTitle) ?> | Lectures</title>

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet">

    <!-- Bengali fonts: Hind Siliguri (readable) + Galada (handwritten accent) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Galada&display=swap"
        rel="stylesheet">

    <!-- Shared Application CSS -->
    <link
        href="/lectures/assets/css/style.css"
        rel="stylesheet">

</head>

<body>

    <div class="app-layout">

        <?php if ($showSidebar): ?>
            <!-- Shared Sidebar -->
            <?php require __DIR__ . '/sidebar.php'; ?>
        <?php endif; ?>

        <main class="app-main<?= $noSidebarClass ?>">

            <!-- Top Navigation -->
            <header class="topbar">

                <?php if ($showSidebar): ?>
                    <button
                        type="button"
                        class="mobile-menu-btn"
                        id="sidebarToggle"
                        aria-label="Toggle navigation menu"
                        aria-controls="appSidebar"
                        aria-expanded="false">
                        <i class="bi bi-list" aria-hidden="true"></i>
                    </button>
                <?php endif; ?>

                <div class="topbar-title">

                    <span class="topbar-eyebrow">
                        LEARNING MANAGEMENT
                    </span>

                    <strong><?= e($pageTitle) ?></strong>

                </div>

                <div class="topbar-profile">

                    <?php if (!$showSidebar): ?>
                        <!-- Logout (shown when the sidebar is hidden, i.e. students) -->
                        <form method="post" action="/lectures/logout.php" class="topbar-logout">
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e($logoutToken) ?>">
                            <button type="submit" class="topbar-logout-btn"
                                title="Logout" aria-label="Logout">
                                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- Profile dropdown: user info + account & logout links -->
                    <div class="dropdown">
                        <button
                            class="topbar-profile-btn"
                            type="button"
                            id="profileMenuBtn"
                            data-bs-toggle="dropdown"
                            data-bs-display="static"
                            aria-expanded="false"
                            aria-label="Account menu"
                            title="Account">
                            <i class="bi bi-person-circle" aria-hidden="true"></i>
                            <span class="topbar-profile-name"><?= e($userName) ?></span>
                            <i class="bi bi-chevron-down topbar-caret" aria-hidden="true"></i>
                        </button>

                        <div class="dropdown-menu dropdown-menu-end profile-menu" aria-labelledby="profileMenuBtn">
                            <div class="profile-menu-head">
                                <div class="profile-menu-name"><?= e($userName) ?></div>
                                <div class="profile-menu-meta">
                                    <i class="bi bi-shield me-1"></i><?= e($userRole === 'admin' ? 'Administrator' : 'Student') ?>
                                    <?php if (!empty($userClass)): ?>
                                        <span class="mx-1">·</span><i class="bi bi-mortarboard me-1"></i><?= e($userClass) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item profile-menu-item" href="/lectures/account.php">
                                <i class="bi bi-person-gear me-2"></i> My Profile &amp; Password
                            </a>
                            <form method="post" action="/lectures/logout.php">
                                <input type="hidden" name="csrf_token" value="<?= e($logoutToken) ?>">
                                <button type="submit" class="dropdown-item profile-menu-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>

                </div>

            </header>

            <!-- Individual Page Content Starts Here -->
            <div class="page-content"><?php /* Page content begins here. */ ?>