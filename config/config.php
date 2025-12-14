<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/database.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Site configuration
define('SITE_URL', env('SITE_URL', 'http://localhost/Web'));
define('SITE_NAME', env('SITE_NAME', 'NTH SPORT'));
define('SITE_EMAIL', 'NTHsport@gmail.com');
define('SITE_PHONE', '0888 899 107');
define('SITE_ADDRESS', '123 Trần Hưng Đạo, Quận 1, TPHCM');

// Path configuration
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/' . env('UPLOAD_PATH', 'assets/uploads'));
define('UPLOAD_URL', SITE_URL . '/' . env('UPLOAD_PATH', 'assets/uploads'));

// Upload settings
define('MAX_UPLOAD_SIZE', env('MAX_UPLOAD_SIZE', 5242880)); // 5MB

// Pagination
define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 10);

// Currency
define('CURRENCY', 'VNĐ');

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', env('GOOGLE_CLIENT_ID'));
define('GOOGLE_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET'));
define('GOOGLE_REDIRECT_URI', env('GOOGLE_REDIRECT_URI'));

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Error reporting (disable display_errors for API calls)
if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
