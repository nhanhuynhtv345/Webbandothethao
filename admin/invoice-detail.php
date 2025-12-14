<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    redirect(SITE_URL . '/admin/login.php');
}

$db = getDB();
$id = intval($_GET['id'] ?? 0);

if (!$id) {
    redirect(SITE_URL . '/admin/invoices.php');
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $status = $_POST['status'];
    $validStatuses = ['unpaid', 'paid', 'partially_paid', 'cancelled'];
    
    if (in_array($status, $validStatuses)) {
        $stmt = $db->prepare("UPDATE hoa_don SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }
}

// Get invoice
$stmt = $db->prepare("
    SELECT hd.*, dh.ma_don_hang, dh.ho_ten, dh.so_dien_thoai, dh.email, dh.dia_chi,
           dh.phuong_xa, dh.quan_huyen, dh.tinh_thanh, dh.phuong_thuc_thanh_toan, dh.trang_thai as order_status,
           nd.ho_ten as user_name
    FROM hoa_don hd
    LEFT JOIN don_hang dh ON hd.don_hang_id = dh.id
    LEFT JOIN nguoi_dung nd ON hd.nguoi_dung_id = nd.id
    WHERE hd.id = ?
");
$stmt->execute([$id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    redirect(SITE_URL . '/admin/invoices.php');
}

// Get invoice items
$itemsStmt = $db->prepare("
    SELECT cthd.*, sp.ma_san_pham,
           (SELECT url FROM hinh_anh_san_pham WHERE san_pham_id = sp.id AND is_primary = 1 LIMIT 1) as anh
    FROM chi_tiet_hoa_don cthd
    LEFT JOIN san_pham sp ON cthd.san_pham_id = sp.id
    WHERE cthd.hoa_don_id = ?
");
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();

$statusLabels = [
    'unpaid' => ['label' => 'Chưa thanh toán', 'class' => 'bg-yellow-100 text-yellow-700', 'icon' => 'fa-clock'],
    'paid' => ['label' => 'Đã thanh toán', 'class' => 'bg-green-100 text-green-700', 'icon' => 'fa-check-circle'],
    'cancelled' => ['label' => 'Đã hủy', 'class' => 'bg-red-100 text-red-700', 'icon' => 'fa-times-circle'],
];

$pageTitle = 'Hóa đơn #' . $invoice['ma_hoa_don'];
include __DIR__ . '/includes/header.php';
?>

<div class="mb-4 flex items-center justify-between">
    <a href="<?php echo SITE_URL; ?>/admin/invoices.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách
    </a>
    <div class="flex gap-2">
        <a href="<?php echo SITE_URL; ?>/admin/invoice-print.php?id=<?php echo $invoice['id']; ?>" 
           target="_blank" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            <i class="fas fa-print mr-2"></i>In hóa đơn
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Invoice Header -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900"><?php echo $invoice['ma_hoa_don']; ?></h2>
                    <p class="text-gray-500">Ngày tạo: <?php echo date('d/m/Y H:i', strtotime($invoice['created_at'])); ?></p>
                </div>
                <?php $st = $statusLabels[$invoice['status']] ?? $statusLabels['unpaid']; ?>
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium <?php echo $st['class']; ?>">
                    <i class="fas <?php echo $st['icon']; ?> mr-2"></i>
                    <?php echo $st['label']; ?>
                </span>
            </div>
            
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <h4 class="font-semibold text-gray-700 mb-2">Từ:</h4>
                    <p class="font-bold text-lg"><?php echo SITE_NAME; ?></p>
                    <p class="text-gray-600">123 Trần Hưng Đạo, Q.1, TP.HCM</p>
                    <p class="text-gray-600"><?php echo SITE_PHONE; ?></p>
                    <p class="text-gray-600">NTHsport@gmail.com</p>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-700 mb-2">Đến:</h4>
                    <p class="font-bold text-lg"><?php echo htmlspecialchars($invoice['ho_ten']); ?></p>
                    <p class="text-gray-600"><?php echo htmlspecialchars($invoice['dia_chi']); ?></p>
                    <p class="text-gray-600">
                        <?php echo implode(', ', array_filter([$invoice['phuong_xa'], $invoice['quan_huyen'], $invoice['tinh_thanh']])); ?>
                    </p>
                    <p class="text-gray-600"><?php echo $invoice['so_dien_thoai']; ?></p>
                    <?php if ($invoice['email']): ?>
                    <p class="text-gray-600"><?php echo htmlspecialchars($invoice['email']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Items -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b bg-gray-50">
                <h3 class="font-semibold">Chi tiết hóa đơn</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Sản phẩm</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">SL</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Đơn giá</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <?php if (!empty($item['anh'])): ?>
                                    <img src="<?php echo UPLOAD_URL . '/' . $item['anh']; ?>" class="w-12 h-12 object-cover rounded">
                                    <?php else: ?>
                                    <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="font-medium"><?php echo htmlspecialchars($item['ten_san_pham']); ?></p>
                                        <?php if (!empty($item['thong_tin_bien_the'])): ?>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($item['thong_tin_bien_the']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center"><?php echo $item['so_luong']; ?></td>
                            <td class="px-6 py-4 text-right"><?php echo formatCurrency($item['don_gia']); ?></td>
                            <td class="px-6 py-4 text-right font-semibold"><?php echo formatCurrency($item['thanh_tien']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Summary -->
            <div class="p-6 bg-gray-50 border-t">
                <div class="max-w-xs ml-auto space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tạm tính:</span>
                        <span><?php echo formatCurrency($invoice['tong_tien_san_pham']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Phí vận chuyển:</span>
                        <span><?php echo formatCurrency($invoice['phi_van_chuyen']); ?></span>
                    </div>
                    <?php if ($invoice['giam_gia'] > 0): ?>
                    <div class="flex justify-between text-green-600">
                        <span>Giảm giá:</span>
                        <span>-<?php echo formatCurrency($invoice['giam_gia']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between text-xl font-bold pt-2 border-t">
                        <span>Tổng cộng:</span>
                        <span class="text-primary-600"><?php echo formatCurrency($invoice['tong_thanh_toan']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Status Update -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold mb-4">Cập nhật trạng thái</h3>
            <form method="POST">
                <div class="space-y-3">
                    <?php foreach ($statusLabels as $key => $val): ?>
                    <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition
                        <?php echo $invoice['status'] === $key ? 'border-primary-500 bg-primary-50' : 'border-gray-200'; ?>">
                        <input type="radio" name="status" value="<?php echo $key; ?>" 
                               <?php echo $invoice['status'] === $key ? 'checked' : ''; ?>
                               class="text-primary-600 focus:ring-primary-500">
                        <div class="flex items-center gap-2">
                            <i class="fas <?php echo $val['icon']; ?> <?php echo str_replace(['bg-', '100'], ['text-', '600'], $val['class']); ?>"></i>
                            <span class="font-medium"><?php echo $val['label']; ?></span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="w-full mt-4 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                    <i class="fas fa-save mr-2"></i>Cập nhật
                </button>
            </form>
        </div>
        
        <!-- Order Info -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold mb-4">Thông tin đơn hàng</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Mã đơn hàng:</span>
                    <a href="<?php echo SITE_URL; ?>/admin/order-detail.php?id=<?php echo $invoice['don_hang_id']; ?>" 
                       class="text-primary-600 hover:underline font-medium">
                        <?php echo $invoice['ma_don_hang']; ?>
                    </a>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Phương thức TT:</span>
                    <span><?php echo $invoice['phuong_thuc_thanh_toan'] === 'COD' ? 'COD' : 'Chuyển khoản'; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Trạng thái đơn:</span>
                    <span><?php echo $invoice['order_status']; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold mb-4">Thao tác nhanh</h3>
            <div class="space-y-3">
                <a href="<?php echo SITE_URL; ?>/admin/invoice-print.php?id=<?php echo $invoice['id']; ?>" 
                   target="_blank" class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-print text-green-600"></i>
                    <span>In hóa đơn</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/order-detail.php?id=<?php echo $invoice['don_hang_id']; ?>" 
                   class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-shopping-bag text-blue-600"></i>
                    <span>Xem đơn hàng</span>
                </a>
                <?php if ($invoice['email']): ?>
                <a href="mailto:<?php echo $invoice['email']; ?>?subject=Hóa đơn <?php echo $invoice['ma_hoa_don']; ?>" 
                   class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-envelope text-purple-600"></i>
                    <span>Gửi email</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
