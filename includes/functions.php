<?php
/**
 * Helper Functions
 */

// Format currency
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' ' . CURRENCY;
}

// Format date
function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

// Format datetime
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    return date($format, strtotime($datetime));
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Generate slug
function generateSlug($string) {
    $string = mb_strtolower($string, 'UTF-8');
    
    $replacements = [
        'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ' => 'a',
        'đ' => 'd',
        'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ' => 'e',
        'í|ì|ỉ|ĩ|ị' => 'i',
        'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ' => 'o',
        'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự' => 'u',
        'ý|ỳ|ỷ|ỹ|ỵ' => 'y'
    ];
    
    foreach ($replacements as $pattern => $replacement) {
        $string = preg_replace('/(' . $pattern . ')/u', $replacement, $string);
    }
    
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    $string = trim($string, '-');
    
    return $string;
}

// Alias for generateSlug
function slugify($string) {
    return generateSlug($string);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM nguoi_dung WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Flash message
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Check if admin is logged in
function isAdmin() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Upload file
function uploadFile($file, $directory = 'products') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return false;
    }
    
    $uploadDir = UPLOAD_PATH . '/' . $directory;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $directory . '/' . $filename;
    }
    
    return false;
}

// Get cart count
function getCartCount() {
    if (!isLoggedIn()) {
        return 0; // Chưa đăng nhập thì không có giỏ hàng
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT COALESCE(SUM(so_luong), 0) as count FROM gio_hang WHERE nguoi_dung_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}

// Get wishlist count
function getWishlistCount() {
    if (!isLoggedIn()) {
        return 0;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM yeu_thich WHERE nguoi_dung_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}

// Calculate discount
function calculateDiscount($originalPrice, $discountPrice) {
    if ($discountPrice >= $originalPrice) {
        return 0;
    }
    return round((($originalPrice - $discountPrice) / $originalPrice) * 100);
}

// Pagination
function paginate($totalItems, $itemsPerPage, $currentPage) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    return [
        'total_items' => $totalItems,
        'items_per_page' => $itemsPerPage,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

// Generate order code
function generateOrderCode() {
    return 'DH' . date('Ymd') . rand(1000, 9999);
}

// Get Google OAuth URL
function getGoogleAuthUrl() {
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online',
        'prompt' => 'select_account'
    ];
    
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}
