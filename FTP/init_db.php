<?php
/**
 * データベース初期化処理
 */
require_once __DIR__ . '/config.php';

function initDatabase() {
    try {
        $pdo = getDbConnection();
        $query = "
            CREATE TABLE IF NOT EXISTS shared_files (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                file_id TEXT NOT NULL UNIQUE,
                original_name TEXT NOT NULL,
                stored_path TEXT NOT NULL,
                password_hash TEXT NOT NULL,
                download_count INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $pdo->exec($query);
        return true;
    } catch (PDOException $e) {
        error_log("Database Initialization Error: " . $e->getMessage());
        return false;
    }
}

// 直接実行された場合
if (php_sapi_name() === 'cli' || basename($_SERVER['PHP_SELF']) === 'init_db.php') {
    if (initDatabase()) {
        echo "Database initialized successfully.\n";
    } else {
        echo "Failed to initialize database.\n";
    }
}
