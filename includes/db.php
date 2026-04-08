<?php

require_once __DIR__ . '/../config.php';

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA journal_mode=WAL');
        init_schema($pdo);
    }
    return $pdo;
}

function init_schema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS games (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            igdb_id INTEGER,
            title TEXT NOT NULL,
            cover_url TEXT,
            genres TEXT,
            platforms TEXT,
            release_year INTEGER,
            developer TEXT,
            summary TEXT,
            my_rating REAL,
            playtime_hours REAL,
            completion_percent INTEGER DEFAULT 0,
            status TEXT DEFAULT 'backlog',
            notes TEXT,
            added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT
        );
    ");

    // Migrations — safe to run on existing databases
    try { $pdo->exec("ALTER TABLE games ADD COLUMN platform_played TEXT"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE games ADD COLUMN format TEXT"); } catch (Exception $e) {}
}

function get_setting(string $key): ?string {
    $stmt = get_db()->prepare('SELECT value FROM settings WHERE key = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : null;
}

function set_setting(string $key, string $value): void {
    $stmt = get_db()->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)');
    $stmt->execute([$key, $value]);
}
