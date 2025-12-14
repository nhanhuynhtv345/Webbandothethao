<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    redirect(SITE_URL . '/admin/login.php');
}

$db = getDB();
$message = '';
$error = '';
$promo = null;

// Edit mode
$editId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM khuyen_mai WHERE id = ?");
    $stmt->execute([$editId]);
    $promo = $stmt->fetch();
    if (!$promo) {
        redirect(SITE_URL . '/admin/promotions.php');
    }
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = $_POST['type'] ?? 'percent';
    $value = floatval($_POST['value'] ?? 0);
    $max_discount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : null;
    $min_order_amount = floatval($_POST['min_order_amount'] ?? 0);
    $start_at = $_POST['start_at'] ?? '';
    $end_at = $_POST['end_at'] ?? '';
    $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Validate
    if (empty($code)) {
        $error = 'Vui lòng nhập mã khuyến mãi';
    } elseif (empty($title)) {
        $error = 'Vui lòng nhập tiêu đề';
    } elseif ($value <= 0) {
        $error = 'Giá trị giảm phải lớn hơn 0';
    } elseif (empty($start_at) || empty($end_at)) {
        $error = 'Vui lòng chọn thời gian';
    } elseif (strtotime($end_at) <= strtotime($start_at)) {
        $error = 'Thời gian kết thúc phải sau thời gian bắt đầu';
    } else {
        // Check code exists
        $checkStmt = $db->prepare("SELECT id FROM khuyen_mai WHERE code = ? AND id != ?");
        $checkStmt->execute([$code, $editId]);
        if ($checkStmt->fetch()) {
            $error = 'Mã khuyến mãi đã tồn tại';
        }
    }
    
    if (empty($error)) {
        try {
            if ($editId) {
                $stmt = $db->prepare("UPDATE khuyen_mai SET code = ?, title = ?, description = ?, type = ?, value = ?, max_discount = ?, min_order_amount = ?, start_at = ?, end_at = ?, usage_limit = ?, active = ? WHERE id = ?");
                $stmt->execute([$code, $title, $description, $type, $value, $max_discount, $min_order_amount, $start_at, $end_at, $usage_limit, $active, $editId]);
                $message = 'Cập nhật khuyến mãi thành công!';
                
                // Reload
                $stmt = $db->prepare("SELECT * FROM khuyen_mai WHERE id = ?");
                $stmt->execute([$editId]);
                $promo = $stmt->fetch();
            } else {
                $stmt = $db->prepare("INSERT INTO khuyen_mai (code, title, description, type, value, max_discount, min_order_amount, start_at, end_at, usage_limit, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$code, $title, $description, $type, $value, $max_discount, $min_order_amount, $start_at, $end_at, $usage_limit, $active]);
                $message = 'Thêm khuyến mãi thành công!';
                redirect(SITE_URL . '/admin/promotions.php');
            }
        } catch (Exception $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

$pageTitle = $editId ? 'Sửa khuyến mãi' : 'Thêm khuyến mãi';
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

<form method="POST" class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <div class="card-hover bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Thông tin khuyến mãi</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Mã khuyến mãi <span class="text-red-500">*</span></label>
                        <input type="text" name="code" value="<?php echo htmlspecialchars($promo['code'] ?? ''); ?>" 
                               class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition uppercase"
                               placeholder="VD: SALE50" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Tiêu đề <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($promo['title'] ?? ''); ?>" 
                               class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition"
                               placeholder="VD: Giảm 50% đơn hàng" required>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Mô tả</label>
                        <textarea name="description" rows="3" 
                                  class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition resize-none"
                                  placeholder="Mô tả chi tiết về khuyến mãi"><?php echo htmlspecialchars($promo['description'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="card-hover bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Giá trị giảm</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Loại giảm giá <span class="text-red-500">*</span></label>
                        <select name="type" id="promo-type" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-primary-500 outline-none bg-white">
                            <option value="percent" <?php echo ($promo['type'] ?? '') === 'percent' ? 'selected' : ''; ?>>Phần trăm (%)</option>
                            <option value="fixed" <?php echo ($promo['type'] ?? '') === 'fixed' ? 'selected' : ''; ?>>Số tiền cố định (VNĐ)</option>
                            <option value="shipping" <?php echo ($promo['type'] ?? '') === 'shipping' ? 'selected' : ''; ?>>Miễn phí vận chuyển</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Giá trị <span class="text-red-500">*</span></label>
                        <input type="number" name="value" value="<?php echo $promo['value'] ?? ''; ?>" step="0.01" min="0"
                               class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition"
                               placeholder="VD: 50" required>
                    </div>
                    
                    <div id="max-discount-field">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Giảm tối đa (VNĐ)</label>
                        <input type="number" name="max_discount" value="<?php echo $promo['max_discount'] ?? ''; ?>" step="1000" min="0"
                               class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition"
                               placeholder="VD: 100000">
                        <p class="text-xs text-slate-500 mt-1">Chỉ áp dụng cho loại phần trăm</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Đơn hàng tối thiểu (VNĐ)</label>
                        <input type="number" name="min_order_amount" value="<?php echo $promo['min_order_amount'] ?? 0; ?>" step="1000" min="0"
                               class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition"
                               placeholder="VD: 500000">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="card-hover bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Thời gian</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Bắt đầu <span class="text-red-500">*</span></label>
                        <input type="datetime-local" name="start_at" value="<?php echo $promo ? date('Y-m-d\TH:i', strtotime($promo['start_at'])) : ''; ?>" 
                               class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Kết thúc <span class="text-red-500">*</span></label>
                        <input type="datetime-local" name="end_at" value="<?php echo $promo ? date('Y-m-d\TH:i', strtotime($promo['end_at'])) : ''; ?>" 
                               class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Giới hạn sử dụng</label>
                        <input type="number" name="usage_limit" value="<?php echo $promo['usage_limit'] ?? ''; ?>" min="1"
                               class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition"
                               placeholder="Không giới hạn">
                    </div>
                    
                    <div class="flex items-center gap-3 pt-2">
                        <input type="checkbox" name="active" id="active" value="1" 
                               <?php echo ($promo['active'] ?? 1) ? 'checked' : ''; ?>
                               class="w-5 h-5 text-primary-600 border-slate-300 rounded focus:ring-primary-500">
                        <label for="active" class="text-sm font-medium text-slate-700">Kích hoạt ngay</label>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex gap-3">
                <a href="<?php echo SITE_URL; ?>/admin/promotions.php" 
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
document.getElementById('promo-type').addEventListener('change', function() {
    const maxDiscountField = document.getElementById('max-discount-field');
    if (this.value === 'percent') {
        maxDiscountField.style.display = 'block';
    } else {
        maxDiscountField.style.display = 'none';
    }
});
// Trigger on load
document.getElementById('promo-type').dispatchEvent(new Event('change'));
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
