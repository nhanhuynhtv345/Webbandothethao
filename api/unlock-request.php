<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$email = trim($data['email'] ?? '');
$hoTen = trim($data['ho_ten'] ?? '');
$dienThoai = trim($data['dien_thoai'] ?? '');
$lyDo = trim($data['ly_do'] ?? '');

// Validate
if (empty($email) || empty($hoTen) || empty($dienThoai) || empty($lyDo)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
    exit;
}

$db = getDB();

// Kiểm tra email có tồn tại và bị khóa không
$stmt = $db->prepare("SELECT id, ho_ten, is_locked FROM nguoi_dung WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Email không tồn tại trong hệ thống']);
    exit;
}

if ($user['is_locked'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Tài khoản này không bị khóa']);
    exit;
}

// Kiểm tra đã gửi yêu cầu chưa (trong 24h) - kiểm tra trong bảng lien_he
$stmt = $db->prepare("SELECT id FROM lien_he WHERE email = ? AND subject = 'Yêu cầu mở khóa tài khoản' AND status = 'pending' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Bạn đã gửi yêu cầu rồi. Vui lòng chờ Admin xử lý.']);
    exit;
}

try {
    // Tạo nội dung tin nhắn
    $message = "YÊU CẦU MỞ KHÓA TÀI KHOẢN\n\n";
    $message .= "Email tài khoản bị khóa: $email\n";
    $message .= "Họ tên: $hoTen\n";
    $message .= "Số điện thoại: $dienThoai\n\n";
    $message .= "Lý do yêu cầu mở khóa:\n$lyDo\n\n";
    $message .= "---\n";
    $message .= "User ID: " . $user['id'];
    
    // Lưu vào bảng lien_he
    $stmt = $db->prepare("INSERT INTO lien_he (user_id, name, email, phone, subject, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->execute([
        $user['id'],
        $hoTen,
        $email,
        $dienThoai,
        'Yêu cầu mở khóa tài khoản',
        $message
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Yêu cầu đã được gửi thành công! Admin sẽ xem xét và liên hệ với bạn.']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
