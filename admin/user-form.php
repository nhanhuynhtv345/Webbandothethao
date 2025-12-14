<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    redirect(SITE_URL . '/admin/login.php');
}

$db = getDB();
$message = '';
$error = '';
$user = null;

// Edit mode
$editId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM nguoi_dung WHERE id = ?");
    $stmt->execute([$editId]);
    $user = $stmt->fetch();
    if (!$user) {
        redirect(SITE_URL . '/admin/users.php');
    }
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $so_dien_thoai = trim($_POST['so_dien_thoai'] ?? '');
    $dia_chi = trim($_POST['dia_chi'] ?? '');
    $mat_khau = $_POST['mat_khau'] ?? '';
    
    // Validate
    if (empty($ho_ten)) {
        $error = 'Vui lòng nhập họ tên';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
    } elseif (!$editId && empty($mat_khau)) {
        $error = 'Vui lòng nhập mật khẩu';
    } else {
        // Check email exists
        $checkStmt = $db->prepare("SELECT id FROM nguoi_dung WHERE email = ? AND id != ?");
        $checkStmt->execute([$email, $editId]);
        if ($checkStmt->fetch()) {
            $error = 'Email đã được sử dụng';
        } elseif (!empty($so_dien_thoai)) {
            // Check phone exists
            $checkStmt = $db->prepare("SELECT id FROM nguoi_dung WHERE so_dien_thoai = ? AND id != ?");
            $checkStmt->execute([$so_dien_thoai, $editId]);
            if ($checkStmt->fetch()) {
                $error = 'Số điện thoại đã được sử dụng';
            }
        }
        
        if (empty($error)) {
            // Handle avatar upload
            $avt = $user['avt'] ?? null;
            if (isset($_FILES['avt']) && $_FILES['avt']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = UPLOAD_PATH . '/avatars/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $ext = pathinfo($_FILES['avt']['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                
                if (move_uploaded_file($_FILES['avt']['tmp_name'], $uploadDir . $filename)) {
                    $avt = 'avatars/' . $filename;
                }
            }
            
            try {
                if ($editId) {
                    // Update
                    if (!empty($mat_khau)) {
                        $stmt = $db->prepare("UPDATE nguoi_dung SET ho_ten = ?, email = ?, mat_khau = ?, so_dien_thoai = ?, dia_chi = ?, avt = ? WHERE id = ?");
                        $stmt->execute([$ho_ten, $email, password_hash($mat_khau, PASSWORD_DEFAULT), $so_dien_thoai, $dia_chi, $avt, $editId]);
                    } else {
                        $stmt = $db->prepare("UPDATE nguoi_dung SET ho_ten = ?, email = ?, so_dien_thoai = ?, dia_chi = ?, avt = ? WHERE id = ?");
                        $stmt->execute([$ho_ten, $email, $so_dien_thoai, $dia_chi, $avt, $editId]);
                    }
                    $message = 'Cập nhật khách hàng thành công!';
                    
                    // Reload
                    $stmt = $db->prepare("SELECT * FROM nguoi_dung WHERE id = ?");
                    $stmt->execute([$editId]);
                    $user = $stmt->fetch();
                } else {
                    // Insert
                    $stmt = $db->prepare("INSERT INTO nguoi_dung (ho_ten, email, mat_khau, so_dien_thoai, dia_chi, avt, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$ho_ten, $email, password_hash($mat_khau, PASSWORD_DEFAULT), $so_dien_thoai, $dia_chi, $avt]);
                    $message = 'Thêm khách hàng thành công!';
                    redirect(SITE_URL . '/admin/users.php');
                }
            } catch (Exception $e) {
                $error = 'Lỗi: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = $editId ? 'Sửa khách hàng' : 'Thêm khách hàng';
include __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?>
<div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3">
    <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0">
        <i class="fas fa-check text-emerald-500"></i>
    </div>
    <p><?php echo $message; ?></p>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3">
    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
        <i class="fas fa-exclamation-circle text-red-500"></i>
    </div>
    <p><?php echo $error; ?></p>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <div class="card-hover bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Thông tin khách hàng</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Họ tên <span class="text-red-500">*</span></label>
                        <input type="text" name="ho_ten" value="<?php echo htmlspecialchars($user['ho_ten'] ?? ''); ?>" 
                               class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition"
                               placeholder="Nhập họ tên" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" 
                               class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition"
                               placeholder="email@example.com" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Số điện thoại</label>
                        <input type="text" name="so_dien_thoai" value="<?php echo htmlspecialchars($user['so_dien_thoai'] ?? ''); ?>" 
                               class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition"
                               placeholder="0123 456 789">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Địa chỉ</label>
                        <textarea name="dia_chi" rows="3" 
                                  class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition resize-none"
                                  placeholder="Nhập địa chỉ"><?php echo htmlspecialchars($user['dia_chi'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Mật khẩu <?php echo $editId ? '(để trống nếu không đổi)' : '<span class="text-red-500">*</span>'; ?>
                        </label>
                        <input type="password" name="mat_khau" 
                               class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition"
                               placeholder="<?php echo $editId ? '••••••••' : 'Nhập mật khẩu'; ?>" <?php echo $editId ? '' : 'required'; ?>>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Avatar -->
            <div class="card-hover bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Ảnh đại diện</h3>
                
                <div class="text-center">
                    <?php if (!empty($user['avt'])): ?>
                    <?php 
                    $avtUrl = (strpos($user['avt'], 'http') === 0) ? $user['avt'] : UPLOAD_URL . '/' . $user['avt'];
                    ?>
                    <img src="<?php echo $avtUrl; ?>" class="w-32 h-32 rounded-full object-cover mx-auto mb-4" id="preview-img">
                    <?php else: ?>
                    <div class="w-32 h-32 bg-gradient-to-br from-primary-400 to-primary-600 rounded-full flex items-center justify-center mx-auto mb-4 text-white text-4xl font-bold" id="preview-placeholder">
                        <?php echo strtoupper(mb_substr($user['ho_ten'] ?? 'U', 0, 1)); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <label class="block">
                    <div class="border-2 border-dashed border-slate-200 rounded-xl p-4 text-center hover:border-primary-400 transition cursor-pointer">
                        <i class="fas fa-camera text-2xl text-slate-400 mb-2"></i>
                        <p class="text-sm text-slate-500">Chọn ảnh</p>
                    </div>
                    <input type="file" name="avt" accept="image/*" class="hidden" id="avt-input">
                </label>
            </div>
            
            <!-- Actions -->
            <div class="flex gap-3">
                <a href="<?php echo SITE_URL; ?>/admin/users.php" 
                   class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-3 rounded-xl font-medium text-center transition">
                    <i class="fas fa-arrow-left mr-2"></i>Quay lại
                </a>
                <button type="submit" 
                        class="flex-1 bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white px-5 py-3 rounded-xl font-medium shadow-lg shadow-primary-500/30 transition">
                    <i class="fas fa-save mr-2"></i><?php echo $editId ? 'Cập nhật' : 'Thêm mới'; ?>
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.getElementById('avt-input').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            let preview = document.getElementById('preview-img');
            const placeholder = document.getElementById('preview-placeholder');
            
            if (placeholder) {
                placeholder.remove();
            }
            
            if (!preview) {
                preview = document.createElement('img');
                preview.id = 'preview-img';
                preview.className = 'w-32 h-32 rounded-full object-cover mx-auto mb-4';
                document.querySelector('.card-hover .text-center').prepend(preview);
            }
            preview.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
