<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$code = strtoupper(trim($data['code'] ?? ''));
$subtotal = floatval($data['subtotal'] ?? 0);

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã giảm giá']);
    exit;
}

$db = getDB();

// Find coupon
$stmt = $db->prepare("SELECT * FROM khuyen_mai WHERE code = ? AND active = 1");
$stmt->execute([$code]);
$coupon = $stmt->fetch();

if (!$coupon) {
    echo json_encode(['success' => false, 'message' => 'Mã giảm giá không tồn tại']);
    exit;
}

// Check time
$now = time();
$start = strtotime($coupon['start_at']);
$end = strtotime($coupon['end_at']);

if ($now < $start) {
    echo json_encode(['success' => false, 'message' => 'Mã giảm giá chưa có hiệu lực']);
    exit;
}

if ($now > $end) {
    echo json_encode(['success' => false, 'message' => 'Mã giảm giá đã hết hạn']);
    exit;
}

// Check usage limit
if ($coupon['usage_limit'] !== null && $coupon['used_count'] >= $coupon['usage_limit']) {
    echo json_encode(['success' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng']);
    exit;
}

// Check min order amount
if ($subtotal < $coupon['min_order_amount']) {
    echo json_encode([
        'success' => false, 
        'message' => 'Đơn hàng tối thiểu ' . number_format($coupon['min_order_amount']) . 'đ để sử dụng mã này'
    ]);
    exit;
}

// Calculate discount
$discount = 0;
if ($coupon['type'] === 'percent') {
    $discount = $subtotal * ($coupon['value'] / 100);
    if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
        $discount = $coupon['max_discount'];
    }
} elseif ($coupon['type'] === 'fixed') {
    $discount = $coupon['value'];
} elseif ($coupon['type'] === 'shipping') {
    $discount = 30000; // Shipping fee
}

// Don't let discount exceed subtotal
if ($discount > $subtotal) {
    $discount = $subtotal;
}

echo json_encode([
    'success' => true,
    'message' => 'Áp dụng mã giảm giá thành công!',
    'coupon' => [
        'id' => $coupon['id'],
        'code' => $coupon['code'],
        'title' => $coupon['title'],
        'type' => $coupon['type'],
        'value' => $coupon['value'],
        'discount' => $discount
    ]
]);
