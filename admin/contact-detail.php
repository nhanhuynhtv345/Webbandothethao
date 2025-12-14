<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . SITE_URL . '/admin/contacts.php');
    exit;
}

$db = getDB();
$successMessage = '';
$errorMessage = '';

// Ensure status column exists with correct type
try {
    $columns = $db->query("SHOW COLUMNS FROM lien_he LIKE 'status'")->fetchAll();
    if (empty($columns)) {
        $db->exec("ALTER TABLE lien_he ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");
    } else {
        // Check if column type needs to be fixed
        $colInfo = $columns[0];
        if (strpos($colInfo['Type'], 'enum') !== false || strpos($colInfo['Type'], 'ENUM') !== false) {
            // Change ENUM to VARCHAR to avoid truncation issues
            $db->exec("ALTER TABLE lien_he MODIFY COLUMN status VARCHAR(20) DEFAULT 'pending'");
        }
    }
} catch (Exception $e) {
    // Table might not exist yet
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $status = $_POST['status'];
    $validStatuses = ['pending', 'processing', 'resolved'];
    
    if (in_array($status, $validStatuses)) {
        try {
            $stmt = $db->prepare("UPDATE lien_he SET status = ? WHERE id = ?");
            $result = $stmt->execute([$status, $id]);
            if ($result) {
                $successMessage = 'Cập nhật trạng thái thành công!';
            } else {
                $errorMessage = 'Không thể cập nhật trạng thái!';
            }
        } catch (Exception $e) {
            $errorMessage = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Get contact (fetch after potential update)
$stmt = $db->prepare("
    SELECT lh.*, nd.ho_ten as user_name, nd.avt as user_avatar, nd.email as user_email, nd.so_dien_thoai as user_phone
    FROM lien_he lh
    LEFT JOIN nguoi_dung nd ON lh.user_id = nd.id
    WHERE lh.id = ?
");
$stmt->execute([$id]);
$contact = $stmt->fetch();

if (!$contact) {
    header('Location: ' . SITE_URL . '/admin/contacts.php');
    exit;
}

$pageTitle = 'Chi tiết liên hệ #' . $id;
$attachments = $contact['attachments'] ? json_decode($contact['attachments'], true) : [];

include __DIR__ . '/includes/header.php';
?>

<!-- Success/Error Messages -->
<?php if ($successMessage): ?>
<div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center gap-3">
    <i class="fas fa-check-circle text-xl"></i>
    <span><?php echo $successMessage; ?></span>
</div>
<?php endif; ?>

<?php if ($errorMessage): ?>
<div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center gap-3">
    <i class="fas fa-exclamation-circle text-xl"></i>
    <span><?php echo $errorMessage; ?></span>
</div>
<?php endif; ?>

<!-- Back Button -->
<div class="mb-6">
    <a href="<?php echo SITE_URL; ?>/admin/contacts.php" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary-600">
        <i class="fas fa-arrow-left"></i>
        <span>Quay lại danh sách</span>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Message Content -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Nội dung tin nhắn</h3>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    <?php 
                    echo match($contact['status']) {
                        'pending' => 'bg-yellow-100 text-yellow-700',
                        'processing' => 'bg-blue-100 text-blue-700',
                        'resolved' => 'bg-green-100 text-green-700',
                        default => 'bg-gray-100 text-gray-700'
                    };
                    ?>">
                    <?php 
                    echo match($contact['status']) {
                        'pending' => 'Chờ xử lý',
                        'processing' => 'Đang xử lý',
                        'resolved' => 'Đã xử lý',
                        default => 'Không xác định'
                    };
                    ?>
                </span>
            </div>
            
            <div class="p-6">
                <!-- Subject -->
                <div class="mb-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-700">
                        <i class="fas fa-tag mr-2"></i>
                        <?php echo htmlspecialchars($contact['subject']); ?>
                    </span>
                </div>
                
                <!-- Message -->
                <div class="prose max-w-none">
                    <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">
                        <?php echo nl2br(htmlspecialchars($contact['message'])); ?>
                    </p>
                </div>
                
                <!-- Attachments -->
                <?php if (!empty($attachments)): ?>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-paperclip mr-2"></i>
                        File đính kèm (<?php echo count($attachments); ?>)
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php foreach ($attachments as $file): 
                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
                        ?>
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <?php if ($isImage): ?>
                            <a href="<?php echo $file; ?>" target="_blank" class="block">
                                <img src="<?php echo $file; ?>" alt="Attachment" class="w-full h-32 object-cover hover:opacity-80 transition">
                            </a>
                            <?php else: ?>
                            <a href="<?php echo $file; ?>" target="_blank" 
                               class="flex items-center gap-3 p-4 hover:bg-gray-50 transition">
                                <i class="fas fa-file-<?php echo $ext === 'pdf' ? 'pdf text-red-500' : 'word text-blue-500'; ?> text-2xl"></i>
                                <span class="text-sm text-gray-600 truncate"><?php echo basename($file); ?></span>
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Customer Info -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Thông tin khách hàng</h3>
            </div>
            <div class="p-6">
                <div class="flex items-center gap-4 mb-6">
                    <?php if ($contact['user_avatar']): ?>
                    <img src="<?php echo htmlspecialchars($contact['user_avatar']); ?>" 
                         class="w-16 h-16 rounded-full object-cover border-2 border-primary-200"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($contact['name']); ?>&background=random&size=64'">
                    <?php else: ?>
                    <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-primary-600 text-2xl"></i>
                    </div>
                    <?php endif; ?>
                    <div>
                        <p class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($contact['name']); ?></p>
                        <?php if ($contact['user_id']): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/user-detail.php?id=<?php echo $contact['user_id']; ?>" 
                           class="text-sm text-primary-600 hover:underline">
                            Xem hồ sơ khách hàng
                        </a>
                        <?php else: ?>
                        <span class="text-sm text-gray-500">Khách vãng lai</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-envelope text-gray-500"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Email</p>
                            <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>" 
                               class="text-sm text-primary-600 hover:underline">
                                <?php echo htmlspecialchars($contact['email']); ?>
                            </a>
                        </div>
                    </div>
                    
                    <?php if ($contact['phone']): ?>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-phone text-gray-500"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Điện thoại</p>
                            <a href="tel:<?php echo htmlspecialchars($contact['phone']); ?>" 
                               class="text-sm text-primary-600 hover:underline">
                                <?php echo htmlspecialchars($contact['phone']); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar text-gray-500"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Ngày gửi</p>
                            <p class="text-sm text-gray-900">
                                <?php echo date('H:i - d/m/Y', strtotime($contact['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Update Status -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Cập nhật trạng thái</h3>
            </div>
            <div class="p-6">
                <form method="POST">
                    <div class="space-y-3">
                        <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-yellow-50 transition
                            <?php echo $contact['status'] === 'pending' ? 'border-yellow-500 bg-yellow-50' : 'border-gray-200'; ?>">
                            <input type="radio" name="status" value="pending" 
                                   <?php echo $contact['status'] === 'pending' ? 'checked' : ''; ?>
                                   class="text-yellow-500 focus:ring-yellow-500">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-clock text-yellow-500"></i>
                                <span class="font-medium">Chờ xử lý</span>
                            </div>
                        </label>
                        
                        <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-blue-50 transition
                            <?php echo $contact['status'] === 'processing' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'; ?>">
                            <input type="radio" name="status" value="processing" 
                                   <?php echo $contact['status'] === 'processing' ? 'checked' : ''; ?>
                                   class="text-blue-500 focus:ring-blue-500">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-spinner text-blue-500"></i>
                                <span class="font-medium">Đang xử lý</span>
                            </div>
                        </label>
                        
                        <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-green-50 transition
                            <?php echo $contact['status'] === 'resolved' ? 'border-green-500 bg-green-50' : 'border-gray-200'; ?>">
                            <input type="radio" name="status" value="resolved" 
                                   <?php echo $contact['status'] === 'resolved' ? 'checked' : ''; ?>
                                   class="text-green-500 focus:ring-green-500">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span class="font-medium">Đã xử lý</span>
                            </div>
                        </label>
                    </div>
                    
                    <button type="submit" class="w-full mt-4 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                        <i class="fas fa-save mr-2"></i>Cập nhật
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Thao tác nhanh</h3>
            </div>
            <div class="p-6 space-y-3">
                <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>?subject=Re: <?php echo urlencode($contact['subject']); ?>" 
                   class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-reply text-primary-600"></i>
                    <span>Trả lời qua Email</span>
                </a>
                
                <?php if ($contact['phone']): ?>
                <a href="tel:<?php echo htmlspecialchars($contact['phone']); ?>" 
                   class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-phone text-green-600"></i>
                    <span>Gọi điện</span>
                </a>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo SITE_URL; ?>/admin/contacts.php" 
                      onsubmit="return confirm('Bạn có chắc muốn xóa liên hệ này?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $contact['id']; ?>">
                    <button type="submit" class="w-full flex items-center gap-3 p-3 border border-red-200 rounded-lg hover:bg-red-50 text-red-600 transition">
                        <i class="fas fa-trash"></i>
                        <span>Xóa liên hệ</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
