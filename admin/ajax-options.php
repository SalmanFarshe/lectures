<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$field = (string)($_GET['field'] ?? '');

if ($field === 'subject') {
    $classId = (int)($_GET['class_id'] ?? 0);
    if ($classId < 1) { echo '[]'; exit; }
    $st = $pdo->prepare('SELECT id, name FROM subjects WHERE class_id = ? AND is_active = 1 ORDER BY name');
    $st->execute([$classId]);
    echo json_encode($st->fetchAll(), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($field === 'chapter') {
    $subjectId = (int)($_GET['subject_id'] ?? 0);
    if ($subjectId < 1) { echo '[]'; exit; }
    $st = $pdo->prepare('SELECT id, title AS name FROM chapters WHERE subject_id = ? AND is_active = 1 ORDER BY title');
    $st->execute([$subjectId]);
    echo json_encode($st->fetchAll(), JSON_UNESCAPED_UNICODE);
    exit;
}

echo '[]';
