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
    
    // Get form data
    $hoTen = trim($_POST['ho_ten'] ?? '');
    $soDienThoai = trim($_POST['so_dien_thoai'] ?? '');
    $diaChi = trim($_POST['dia_chi'] ?? '');
    
    // Validate
    if (empty($hoTen)) {
        echo json_encode(['success' => false, 'message' => 'Họ tên không được để trống']);
        exit;
    }
    
    // Handle avatar upload
    $avatarPath = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
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
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $avatarPath = SITE_URL . '/assets/uploads/avatars/' . $filename;
            
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
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể upload ảnh. Vui lòng thử lại']);
            exit;
        }
    }
    
    // Update user info
    if ($avatarPath) {
        $stmt = $db->prepare("
            UPDATE nguoi_dung 
            SET ho_ten = ?, so_dien_thoai = ?, dia_chi = ?, avt = ?
            WHERE id = ?
        ");
        $stmt->execute([$hoTen, $soDienThoai, $diaChi, $avatarPath, $userId]);
    } else {
        $stmt = $db->prepare("
            UPDATE nguoi_dung 
            SET ho_ten = ?, so_dien_thoai = ?, dia_chi = ?
            WHERE id = ?
        ");
        $stmt->execute([$hoTen, $soDienThoai, $diaChi, $userId]);
    }
    
    // Update session
    $_SESSION['user_name'] = $hoTen;
    
    // Get updated user info
    $stmtUser = $db->prepare("SELECT * FROM nguoi_dung WHERE id = ?");
    $stmtUser->execute([$userId]);
    $updatedUser = $stmtUser->fetch();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Cập nhật thông tin thành công',
        'user' => [
            'ho_ten' => $updatedUser['ho_ten'],
            'so_dien_thoai' => $updatedUser['so_dien_thoai'],
            'dia_chi' => $updatedUser['dia_chi'],
            'avt' => $updatedUser['avt']
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi: ' . $e->getMessage()]);
}
