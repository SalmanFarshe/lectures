<?php
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin'); // Admin-only page হলে

$pageTitle = 'Dashboard';
$activePage = 'dashboard';

require_once __DIR__ . '/../includes/header.php';
?>

<!-- এখানে page-এর মূল content থাকবে -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>