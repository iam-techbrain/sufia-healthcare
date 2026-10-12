<?php
/**
 * SQLite Database Connection & Schema Setup
 * Stored at: admin/database.sqlite
 */
function getDbConnection() {
    $db_file = __DIR__ . '/database.sqlite';
    
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Create career_applications table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS career_applications (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT NOT NULL,
            position TEXT NOT NULL,
            experience TEXT,
            qualification TEXT,
            city TEXT,
            message TEXT,
            resume_file TEXT,
            resume_path TEXT,
            status TEXT DEFAULT 'New',
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Seed default admin accounts if empty
    $check = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
    if ($check == 0) {
        $ins = $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
        $ins->execute(['admin', 'admin123']);
        $ins->execute(['sufia', 'sufia@2026']);
    }

    return $pdo;
}
