<?php
declare(strict_types=1);

function getDb(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dataDir = dirname(__DIR__) . '/data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    $dbPath = $dataDir . '/studentcollab.db';
    $isNew = !file_exists($dbPath);

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    if ($isNew) {
        initializeDatabase($pdo);
    } else {
        ensureSeeded($pdo);
    }

    require_once dirname(__DIR__) . '/database/migrate.php';
    migrateDatabase($pdo);

    return $pdo;
}

function initializeDatabase(PDO $pdo): void
{
    $schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
    if ($schema) {
        $pdo->exec($schema);
    }
    runSeed($pdo);
}

function ensureSeeded(PDO $pdo): void
{
    $tables = (int)$pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='users'")->fetchColumn();
    if ($tables === 0) {
        $schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
        if ($schema) {
            $pdo->exec($schema);
        }
    }
    runSeed($pdo);
}

function runSeed(PDO $pdo): void
{
    require_once dirname(__DIR__) . '/database/seed.php';
    seedDatabase($pdo);
}
