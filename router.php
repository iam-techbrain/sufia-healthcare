<?php
// PHP Built-in Development Server Router for Sufia Healthcare
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
$isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');

// 1. NEVER redirect POST requests (e.g. form submissions to contact-submit.php, admin login, etc.)
if (!$isPost) {
    // Redirect /index.html or /index.php to root /
    if ($uri === '/index.html' || $uri === '/index.php') {
        header("Location: /" . $queryString, true, 301);
        exit;
    }

    // Redirect .html or .php to clean URL (e.g. /doctors.html -> /doctors, /careers.php -> /careers)
    // Exclude /admin/, API scripts like contact-submit.php, 404, router
    if (preg_match('#^/(?!admin/|contact-submit\.php|404\.php|router\.php)(.+)\.(html|php)$#i', $uri, $matches)) {
        header("Location: /" . $matches[1] . $queryString, true, 301);
        exit;
    }
}

$file = __DIR__ . $uri;

// 2. If physical PHP file exists (e.g. contact-submit.php, admin/login.php, etc.), execute it directly
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        require $file;
        return true;
    }
    // Static assets (CSS, JS, images, fonts, icons) -> let server stream
    return false;
}

// 3. If directory requested, check for index file
if (is_dir($file)) {
    $dir = rtrim($file, '/');
    if (file_exists($dir . '/index.php')) {
        require $dir . '/index.php';
        return true;
    }
    if (file_exists($dir . '/index.html')) {
        require $dir . '/index.html';
        return true;
    }
}

// 4. Clean URLs: Internally route /page to page.html or page.php
if ($uri !== '/') {
    $baseName = ltrim($uri, '/');
    if (file_exists(__DIR__ . '/' . $baseName . '.html')) {
        require __DIR__ . '/' . $baseName . '.html';
        return true;
    }
    if (file_exists(__DIR__ . '/' . $baseName . '.php')) {
        require __DIR__ . '/' . $baseName . '.php';
        return true;
    }
}

// 5. Default home page
if ($uri === '/' || $uri === '') {
    if (file_exists(__DIR__ . '/index.html')) {
        require __DIR__ . '/index.html';
        return true;
    }
    if (file_exists(__DIR__ . '/index.php')) {
        require __DIR__ . '/index.php';
        return true;
    }
}

// 6. Not Found: Render beautiful 404 page
http_response_code(404);
require __DIR__ . '/404.php';
return true;
