<?php
/**
 * Admin Setup Script
 * Chạy file này một lần để tạo tài khoản admin mặc định
 * Sau khi chạy xong, hãy xóa file này để bảo mật
 */

require_once __DIR__ . '/../config/config.php';

$db = getDB();
$messages = [];

try {
    // Kiểm tra xem đã có admin chưa
    $stmt = $db->query("SELECT COUNT(*) FROM admin WHERE username = 'admin'");
    $exists = $stmt->fetchColumn() > 0;
    
    if (!$exists) {
        // Tạo tài khoản admin mặc định
        // Username: admin
        // Password: admin123
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $db->prepare("
            INSERT INTO admin (username, password, full_name) 
            VALUES (?, ?, ?)
        ")->execute(['admin', $password, 'Administrator']);
        
        $messages[] = '✓ Tạo tài khoản admin mặc định thành công';
        $messages[] = '';
        $messages[] = '📋 Thông tin đăng nhập:';
        $messages[] = '   Username: admin';
        $messages[] = '   Password: admin123';
        $messages[] = '';
        $messages[] = '⚠️ Hãy đổi mật khẩu ngay sau khi đăng nhập!';
    } else {
        $messages[] = '⚠️ Tài khoản admin đã tồn tại';
    }
    
    $messages[] = '';
    $messages[] = '🎉 Setup hoàn tất!';
    $messages[] = '🔗 Truy cập: ' . SITE_URL . '/admin/login.php';
    $messages[] = '';
    $messages[] = '⚠️ QUAN TRỌNG: Hãy xóa file setup.php này sau khi hoàn tất!';
    
} catch (Exception $e) {
    $messages[] = '❌ Lỗi: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-2xl w-full">
        <h1 class="text-2xl font-bold mb-6 text-center">🛠️ Admin Setup</h1>
        
        <div class="bg-gray-50 rounded-lg p-4 font-mono text-sm">
            <?php foreach ($messages as $msg): ?>
            <div class="py-1 <?php echo strpos($msg, '❌') !== false ? 'text-red-600' : (strpos($msg, '⚠️') !== false ? 'text-yellow-600' : 'text-gray-700'); ?>">
                <?php echo $msg ?: '&nbsp;'; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-6 text-center">
            <a href="<?php echo SITE_URL; ?>/admin/login.php" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700">
                Đi đến trang đăng nhập Admin
            </a>
        </div>
    </div>
</body>
</html>
