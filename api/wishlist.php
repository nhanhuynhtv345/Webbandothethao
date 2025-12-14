<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$db = getDB();
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

// Get wishlist count
if ($action === 'count') {
    $count = getWishlistCount();
    echo json_encode(['success' => true, 'count' => $count]);
    exit;
}

// Add to wishlist
if ($action === 'add') {
    $productId = intval($input['product_id'] ?? 0);
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        exit;
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO yeu_thich (nguoi_dung_id, san_pham_id)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE created_at = NOW()
        ");
        $stmt->execute([$_SESSION['user_id'], $productId]);
        
        echo json_encode(['success' => true, 'message' => 'Đã thêm vào yêu thích']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
    }
    exit;
}

// Remove from wishlist
if ($action === 'remove') {
    $productId = intval($input['product_id'] ?? 0);
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        exit;
    }
    
    $stmt = $db->prepare("DELETE FROM yeu_thich WHERE nguoi_dung_id = ? AND san_pham_id = ?");
    $stmt->execute([$_SESSION['user_id'], $productId]);
    
    echo json_encode(['success' => true, 'message' => 'Đã xóa khỏi yêu thích']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
