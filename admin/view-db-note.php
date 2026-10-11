<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/database.php';

$noteId = (int)($_GET['id'] ?? 0);
if ($noteId < 1) { http_response_code(400); exit('Invalid note id.'); }

$stmt = $pdo->prepare(
    "SELECT n.*, ch.title AS chapter_title,
            sub.name AS subject_name, c.name AS class_name,
            ch.id AS chapter_id, sub.id AS subject_id, c.id AS class_id
     FROM notes n
     INNER JOIN chapters ch ON ch.id = n.chapter_id
     INNER JOIN subjects sub ON sub.id = ch.subject_id
     INNER JOIN classes c ON c.id = sub.class_id
     WHERE n.id = ? LIMIT 1"
);
$stmt->execute([$noteId]);
$dbNote = $stmt->fetch();

if (!$dbNote) { http_response_code(404); exit('Note not found.'); }

$readerMeta = ['class' => (string)$dbNote['class_name'], 'subject' => (string)$dbNote['subject_name']];
$readerTitle = (string)$dbNote['title'];
$readerBack = '/lectures/admin/navigator.php?chapter_id=' . (int)$dbNote['chapter_id'];
$readerBackLabel = 'Back to Chapter';
$activePage = 'navigator';

require __DIR__ . '/../includes/db-note-render.php';
exit;
