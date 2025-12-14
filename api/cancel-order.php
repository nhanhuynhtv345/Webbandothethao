<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = intval($input['order_id'] ?? 0);

    if (!$orderId) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin đơn hàng']);
        exit;
    }

    $db = getDB();

    // Check if order belongs to user and is pending
    $stmt = $db->prepare("SELECT * FROM don_hang WHERE id = ? AND nguoi_dung_id = ?");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
        exit;
    }

    if ($order['trang_thai'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Chỉ có thể hủy đơn hàng đang chờ xác nhận']);
        exit;
    }

    // Start transaction
    $db->beginTransaction();

    try {
        // Update order status
        $db->prepare("UPDATE don_hang SET trang_thai = 'cancelled' WHERE id = ?")
           ->execute([$orderId]);

        // Restore stock
        $itemsStmt = $db->prepare("SELECT san_pham_id, so_luong FROM chi_tiet_don_hang WHERE don_hang_id = ?");
        $itemsStmt->execute([$orderId]);
        $items = $itemsStmt->fetchAll();

        $restoreStmt = $db->prepare("UPDATE san_pham SET so_luong_ton = so_luong_ton + ? WHERE id = ?");
        foreach ($items as $item) {
            $restoreStmt->execute([$item['so_luong'], $item['san_pham_id']]);
        }

        $db->commit();

        echo json_encode(['success' => true, 'message' => 'Đã hủy đơn hàng thành công']);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
