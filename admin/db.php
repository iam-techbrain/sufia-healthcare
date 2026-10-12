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

    // Create tables: career_applications, users, contact_inquiries
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

        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            name TEXT NOT NULL,
            phone TEXT,
            email TEXT,
            age INTEGER,
            gender TEXT,
            sidebar_color TEXT DEFAULT 'dark',
            header_color TEXT DEFAULT 'white',
            footer_color TEXT DEFAULT 'white',
            role TEXT DEFAULT 'Admin',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS contact_inquiries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT NOT NULL,
            email TEXT,
            department TEXT,
            message TEXT NOT NULL,
            status TEXT DEFAULT 'New',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Ensure users table has all required columns
    $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll();
    $col_names = array_column($cols, 'name');
    $needed = [
        'name'          => "TEXT DEFAULT 'Admin User'",
        'phone'         => "TEXT DEFAULT ''",
        'email'         => "TEXT DEFAULT ''",
        'age'           => "INTEGER DEFAULT 30",
        'gender'        => "TEXT DEFAULT 'Male'",
        'sidebar_color' => "TEXT DEFAULT 'dark'",
        'header_color'  => "TEXT DEFAULT 'white'",
        'footer_color'  => "TEXT DEFAULT 'white'",
        'role'          => "TEXT DEFAULT 'Admin'"
    ];
    foreach ($needed as $col => $def) {
        if (!in_array($col, $col_names)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN $col $def");
        }
    }

    // Seed default admin accounts if empty
    $check = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($check == 0) {
        $ins = $pdo->prepare("INSERT INTO users (username, password, name, phone, email, age, gender, sidebar_color, header_color, footer_color, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute(['admin', 'admin123', 'Super Admin', '+91 9709876543', 'admin@sufiahealthcare.com', 35, 'Male', 'dark', 'white', 'white', 'Hospital Administrator']);
        $ins->execute(['sufia', 'sufia@2026', 'Dr. Sufia Parveen', '+91 9431081234', 'director@sufiahealthcare.com', 32, 'Female', 'green', 'white', 'white', 'Medical Director']);
    }

    return $pdo;
}

/**
 * Returns current logged-in user record and theme configuration
 */
function getCurrentAdminUser($pdo) {
    if (!isset($_SESSION['sufia_admin_user'])) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_SESSION['sufia_admin_user']]);
    $user = $stmt->fetch();
    if ($user) {
        // Keep session colors synced
        $_SESSION['sufia_sidebar_color'] = $user['sidebar_color'] ?: 'dark';
        $_SESSION['sufia_header_color']  = $user['header_color'] ?: 'white';
        $_SESSION['sufia_footer_color']  = $user['footer_color'] ?: 'white';
        $_SESSION['sufia_user_name']     = $user['name'];
        $_SESSION['sufia_user_role']     = $user['role'];
    }
    return $user;
}

/**
 * Color map and style generator for Sidebar, Header, and Footer
 */
function renderThemeStyles($sidebar_color = 'dark', $header_color = 'white', $footer_color = 'white') {
    $colors = [
        'dark'   => ['bg' => '#0b1a2d', 'text' => '#ffffff', 'nav' => '#ffffff', 'active' => '#1572e8', 'border' => '#182b42'],
        'white'  => ['bg' => '#ffffff', 'text' => '#1f2937', 'nav' => '#4b5563', 'active' => '#0eaba3', 'border' => '#e5e7eb'],
        'green'  => ['bg' => '#0b5e43', 'text' => '#ffffff', 'nav' => '#ffffff', 'active' => '#10b981', 'border' => '#084833'],
        'teal'   => ['bg' => '#0eaba3', 'text' => '#ffffff', 'nav' => '#ffffff', 'active' => '#065f5b', 'border' => '#0b8f88'],
        'blue'   => ['bg' => '#1572e8', 'text' => '#ffffff', 'nav' => '#ffffff', 'active' => '#0c52ad', 'border' => '#105ec2'],
        'purple' => ['bg' => '#5867dd', 'text' => '#ffffff', 'nav' => '#ffffff', 'active' => '#3b4bbf', 'border' => '#4554bf'],
        'dark2'  => ['bg' => '#182030', 'text' => '#ffffff', 'nav' => '#ffffff', 'active' => '#1572e8', 'border' => '#242f44']
    ];

    $sb = $colors[$sidebar_color] ?? $colors['dark'];
    $hd = $colors[$header_color] ?? $colors['white'];
    $ft = $colors[$footer_color] ?? $colors['white'];

    $is_sb_white = ($sidebar_color === 'white');
    $is_hd_white = ($header_color === 'white');
    $is_ft_white = ($footer_color === 'white');

    ob_start();
    ?>
    <style id="sufia-custom-theme-style">
      /* User Custom Sidebar Color: <?= htmlspecialchars($sidebar_color) ?> */
      .sidebar[data-background-color="dark"],
      .sidebar {
        background: <?= $sb['bg'] ?> !important;
        border-right: 1px solid <?= $sb['border'] ?> !important;
      }
      .sidebar .logo-header {
        background: <?= $sb['bg'] ?> !important;
        border-bottom: 1px solid <?= $sb['border'] ?> !important;
      }
      .sidebar .nav.nav-secondary > .nav-item a {
        color: <?= $sb['nav'] ?> !important;
      }
      .sidebar .nav.nav-secondary > .nav-item.active a {
        background: <?= $sb['active'] ?> !important;
        color: #ffffff !important;
      }
      .sidebar .nav-section .text-section {
        color: <?= $is_sb_white ? '#64748b' : 'rgba(255,255,255,0.65)' ?> !important;
      }
      .sidebar .btn-toggle {
        color: <?= $sb['text'] ?> !important;
      }

      /* User Custom Header Color: <?= htmlspecialchars($header_color) ?> */
      .main-header,
      .main-header .navbar-header {
        background: <?= $hd['bg'] ?> !important;
        border-bottom: 1px solid <?= $hd['border'] ?> !important;
      }
      .main-header .navbar-nav .profile-username {
        color: <?= $hd['text'] ?> !important;
      }
      .main-header .btn-search,
      .main-header .search-icon {
        color: <?= $is_hd_white ? '#64748b' : '#ffffff' ?> !important;
      }
      .main-header .form-control {
        background: <?= $is_hd_white ? '#f8fafc' : 'rgba(255,255,255,0.15)' ?> !important;
        color: <?= $hd['text'] ?> !important;
        border-color: <?= $hd['border'] ?> !important;
      }

      /* User Custom Footer Color: <?= htmlspecialchars($footer_color) ?> */
      .footer {
        background: <?= $ft['bg'] ?> !important;
        border-top: 1px solid <?= $ft['border'] ?> !important;
        color: <?= $ft['text'] ?> !important;
      }
      .footer .nav-link,
      .footer .copyright {
        color: <?= $ft['text'] ?> !important;
      }
    </style>
    <?php
    return ob_get_clean();
}
