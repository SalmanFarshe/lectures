<?php
require_once __DIR__ . '/includes/auth.php';
$_SESSION['user_id'] = (int)($_GET['uid'] ?? '1');
$_SESSION['role'] = $_GET['role'] ?? 'admin';
$_SESSION['full_name'] = 'Tester';
header('Location: ' . ($_GET['to'] ?? '/lectures/admin/quizzes.php'));
exit;
