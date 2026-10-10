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

                    <i
                        class="bi bi-person-circle"
                        aria-hidden="true"></i>

                    <span><?= e($userName) ?></span>

                    <?php if (!$showSidebar): ?>
                        <!-- Logout (shown when the sidebar is hidden) -->
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

                </div>

            </header>

            <!-- Individual Page Content Starts Here -->
            <div class="page-content"><?php /* Page content begins here. */ ?>