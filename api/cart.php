<?php
// Disable error display for clean JSON
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    $db = getDB();
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? '';

    // Get cart count
    if ($action === 'count') {
        $count = getCartCount();
        echo json_encode(['success' => true, 'count' => $count]);
        exit;
    }

    // Add to cart
    if ($action === 'add') {
        // Check if user is logged in
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Bạn phải đăng nhập để thêm vào giỏ hàng!']);
            exit;
        }
        
        $productId = intval($input['product_id'] ?? 0);
        $variantId = !empty($input['variant_id']) ? intval($input['variant_id']) : null;
        $quantity = intval($input['quantity'] ?? 1);
        
        if (!$productId || $quantity < 1) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }
        
        // Check product exists
        $stmt = $db->prepare("SELECT * FROM san_pham WHERE id = ? AND trang_thai = 'active'");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
            exit;
        }
        
        // Check stock
        if ($product['so_luong_ton'] < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không đủ số lượng']);
            exit;
        }
        
        // Add to database (only for logged in users)
        $stmt = $db->prepare("
            INSERT INTO gio_hang (nguoi_dung_id, san_pham_id, bien_the_id, so_luong, gia_tai_thoi_diem)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE so_luong = so_luong + ?
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $productId,
            $variantId,
            $quantity,
            $product['gia_ban'],
            $quantity
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Đã thêm vào giỏ hàng']);
        exit;
    }

    // Update cart quantity
    if ($action === 'update') {
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Bạn phải đăng nhập để cập nhật giỏ hàng!']);
            exit;
        }
        
        $productId = intval($input['product_id'] ?? 0);
        $quantity = intval($input['quantity'] ?? 1);
        
        if (!$productId || $quantity < 1) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE gio_hang SET so_luong = ? WHERE nguoi_dung_id = ? AND san_pham_id = ?");
        $stmt->execute([$quantity, $_SESSION['user_id'], $productId]);
        
        echo json_encode(['success' => true, 'message' => 'Đã cập nhật giỏ hàng']);
        exit;
    }

    // Remove from cart
    if ($action === 'remove') {
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Bạn phải đăng nhập!']);
            exit;
        }
        
        $productId = intval($input['product_id'] ?? 0);
        
        if (!$productId) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }
        
        $stmt = $db->prepare("DELETE FROM gio_hang WHERE nguoi_dung_id = ? AND san_pham_id = ?");
        $stmt->execute([$_SESSION['user_id'], $productId]);
        
        echo json_encode(['success' => true, 'message' => 'Đã xóa khỏi giỏ hàng']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);

} catch (Exception $e) {
    error_log('Cart API Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống']);
}
