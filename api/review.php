<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để đánh giá']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$productId = intval($data['product_id'] ?? 0);
$rating = intval($data['rating'] ?? 0);
$title = trim($data['title'] ?? '');
$content = trim($data['content'] ?? '');
$userId = $_SESSION['user_id'];

// Validate
if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng chọn số sao đánh giá (1-5)']);
    exit;
}

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập nội dung đánh giá']);
    exit;
}

$db = getDB();

// Get user info
$user = getCurrentUser();

// Check if product exists
$stmt = $db->prepare("SELECT id FROM san_pham WHERE id = ?");
$stmt->execute([$productId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
    exit;
}

// Check if user already reviewed this product
$stmt = $db->prepare("SELECT id FROM danh_gia_san_pham WHERE san_pham_id = ? AND nguoi_dung_id = ?");
$stmt->execute([$productId, $userId]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Bạn đã đánh giá sản phẩm này rồi']);
    exit;
}

// Check if user has purchased this product and get order id
// Cho phép đánh giá khi đơn hàng đã giao (delivered) hoặc hoàn thành (completed)
$stmt = $db->prepare("
    SELECT dh.id as don_hang_id FROM don_hang dh
    INNER JOIN chi_tiet_don_hang ct ON dh.id = ct.don_hang_id
    WHERE dh.nguoi_dung_id = ? AND ct.san_pham_id = ? AND dh.trang_thai IN ('delivered', 'completed')
    LIMIT 1
");
$stmt->execute([$userId, $productId]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần mua sản phẩm này trước khi đánh giá']);
    exit;
}

try {
    $db->beginTransaction();
    
    // Insert review - phù hợp với cấu trúc bảng của bạn
    $stmt = $db->prepare("
        INSERT INTO danh_gia_san_pham (san_pham_id, nguoi_dung_id, don_hang_id, ho_ten, email, diem_danh_gia, tieu_de, noi_dung, trang_thai, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved', NOW())
    ");
    $stmt->execute([
        $productId, 
        $userId, 
        $order['don_hang_id'],
        $user['ho_ten'],
        $user['email'],
        $rating, 
        $title ?: null, 
        $content
    ]);
    
    // Update product rating
    $stmt = $db->prepare("
        UPDATE san_pham SET 
            so_luot_danh_gia = (SELECT COUNT(*) FROM danh_gia_san_pham WHERE san_pham_id = ? AND trang_thai = 'approved'),
            diem_trung_binh = (SELECT AVG(diem_danh_gia) FROM danh_gia_san_pham WHERE san_pham_id = ? AND trang_thai = 'approved')
        WHERE id = ?
    ");
    $stmt->execute([$productId, $productId, $productId]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Cảm ơn bạn đã đánh giá sản phẩm!'
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
