<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Migration Runner (CLI)
|--------------------------------------------------------------------------
| Usage:  php database/migrate.php
| Applies pending .sql files from database/migrations/ in filename order.
| Records each applied file in schema_migrations (idempotent).
| Never drops or deletes existing data.
*/

require_once __DIR__ . '/../config/database.php';

// Track which migrations have been applied.
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS schema_migrations (
        migration VARCHAR(191) NOT NULL PRIMARY KEY,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$applied = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(
    PDO::FETCH_COLUMN
);
$appliedSet = array_flip($applied);

$dir = __DIR__ . '/migrations';
$files = scandir($dir);
sort($files);

$count = 0;

foreach ($files as $file) {
    if (substr($file, -4) !== '.sql') {
        continue;
    }

    if (isset($appliedSet[$file])) {
        echo "SKIP  (already applied): {$file}\n";
        continue;
    }

    $sql = file_get_contents($dir . '/' . $file);

    if ($sql === false || trim($sql) === '') {
        echo "ERROR could not read: {$file}\n";
        continue;
    }

    try {
        // NOTE: DDL (CREATE TABLE) causes an implicit commit in MySQL, so we
        // do NOT wrap migrations in a transaction. All migrations are written
        // to be idempotent (CREATE TABLE IF NOT EXISTS), so a re-run is safe.
        $pdo->exec($sql);

        $ins = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');
        $ins->execute([$file]);

        echo "APPLIED: {$file}\n";
        $count++;
    } catch (Throwable $e) {
        echo "FAILED:  {$file} -> {$e->getMessage()}\n";
        exit(1);
    }
}

echo "\nDone. {$count} migration(s) applied.\n";
