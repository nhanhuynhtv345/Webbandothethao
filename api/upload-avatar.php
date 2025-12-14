<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $db = getDB();
    $userId = $_SESSION['user_id'];
    
    // Check if file was uploaded
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Không có file được upload']);
        exit;
    }
    
    $file = $_FILES['avatar'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file JPG, PNG hoặc GIF']);
        exit;
    }
    
    // Validate file size (2MB max)
    if ($file['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Kích thước file không được vượt quá 2MB']);
        exit;
    }
    
    // Create upload directory if not exists
    $uploadDir = __DIR__ . '/../assets/uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode(['success' => false, 'message' => 'Không thể upload ảnh. Vui lòng thử lại']);
        exit;
    }
    
    $avatarUrl = SITE_URL . '/assets/uploads/avatars/' . $filename;
    
    // Delete old avatar if exists
    $oldUser = $db->prepare("SELECT avt FROM nguoi_dung WHERE id = ?");
    $oldUser->execute([$userId]);
    $oldAvatar = $oldUser->fetch();
    
    if ($oldAvatar && !empty($oldAvatar['avt']) && strpos($oldAvatar['avt'], 'googleusercontent.com') === false) {
        $oldFilePath = str_replace(SITE_URL, __DIR__ . '/..', $oldAvatar['avt']);
        if (file_exists($oldFilePath)) {
            unlink($oldFilePath);
        }
    }
    
    // Update avatar in database
    $stmt = $db->prepare("UPDATE nguoi_dung SET avt = ? WHERE id = ?");
    $stmt->execute([$avatarUrl, $userId]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Upload ảnh đại diện thành công',
        'avatar_url' => $avatarUrl
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi: ' . $e->getMessage()]);
}
