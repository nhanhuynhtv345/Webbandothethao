<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    redirect(SITE_URL . '/admin/login.php');
}

$db = getDB();
$message = '';
$error = '';

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        // Delete product images first
        $db->prepare("DELETE FROM hinh_anh_san_pham WHERE san_pham_id = ?")->execute([$id]);
        // Delete product variants
        $db->prepare("DELETE FROM bien_the_san_pham WHERE san_pham_id = ?")->execute([$id]);
        // Delete product
        $db->prepare("DELETE FROM san_pham WHERE id = ?")->execute([$id]);
        $message = 'Xóa sản phẩm thành công!';
    } catch (Exception $e) {
        $error = 'Lỗi khi xóa sản phẩm: ' . $e->getMessage();
    }
}

// Handle toggle status (hiển thị/ẩn sản phẩm)
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    try {
        // Đổi trạng thái: active -> inactive, inactive -> active
        $db->prepare("UPDATE san_pham SET trang_thai = IF(trang_thai = 'active', 'inactive', 'active') WHERE id = ?")
           ->execute([$id]);
        $message = 'Cập nhật trạng thái sản phẩm thành công!';
    } catch (Exception $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
}

// Filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;

// Build query
$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(sp.ten_san_pham LIKE ? OR sp.ma_san_pham LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category) {
    $where[] = "sp.danh_muc_id = ?";
    $params[] = $category;
}

if ($status) {
    $where[] = "sp.trang_thai = ?";
    $params[] = $status;
}

$whereClause = implode(' AND ', $where);

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM san_pham sp WHERE $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);
$offset = ($page - 1) * $perPage;

// Get products
$sql = "SELECT sp.*, dm.ten_danh_muc, th.ten_thuong_hieu,
        (SELECT url FROM hinh_anh_san_pham WHERE san_pham_id = sp.id AND is_primary = 1 LIMIT 1) as anh_dai_dien
        FROM san_pham sp
        LEFT JOIN danh_muc dm ON sp.danh_muc_id = dm.id
        LEFT JOIN thuong_hieu th ON sp.thuong_hieu_id = th.id
        WHERE $whereClause
        ORDER BY sp.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$categories = $db->query("SELECT * FROM danh_muc ORDER BY ten_danh_muc")->fetchAll();

$pageTitle = 'Quản lý sản phẩm';
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

<!-- Toolbar -->
<div class="card-hover bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Tìm kiếm sản phẩm..." 
                       class="pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition w-64">
            </div>
            
            <select name="category" class="px-4 py-2.5 border border-slate-200 rounded-xl focus:border-primary-500 outline-none bg-white">
                <option value="">Tất cả danh mục</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['ten_danh_muc']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <select name="status" class="px-4 py-2.5 border border-slate-200 rounded-xl focus:border-primary-500 outline-none bg-white">
                <option value="">Tất cả trạng thái</option>
                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Đang bán</option>
                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Ngừng bán</option>
            </select>
            
            <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-xl font-medium transition">
                <i class="fas fa-filter mr-2"></i>Lọc
            </button>
        </form>
        
        <a href="<?php echo SITE_URL; ?>/admin/product-form.php" 
           class="bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white px-5 py-2.5 rounded-xl font-medium shadow-lg shadow-primary-500/30 transition transform hover:-translate-y-0.5">
            <i class="fas fa-plus mr-2"></i>Thêm sản phẩm
        </a>
    </div>
</div>

<!-- Products Table -->
<div class="card-hover bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <table class="w-full">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Sản phẩm</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Mã SP</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Danh mục</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Giá bán</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tồn kho</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Trạng thái</th>
                <th class="px-5 py-4 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($products)): ?>
            <tr>
                <td colspan="7" class="px-5 py-12 text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-box-open text-slate-400 text-2xl"></i>
                    </div>
                    <p class="text-slate-500">Không có sản phẩm nào</p>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($products as $product): ?>
            <tr class="hover:bg-slate-50 transition">
                <td class="px-5 py-4">
                    <div class="flex items-center gap-4">
                        <?php if ($product['anh_dai_dien']): ?>
                        <img src="<?php echo UPLOAD_URL . '/' . $product['anh_dai_dien']; ?>" 
                             class="w-14 h-14 object-cover rounded-xl shadow-sm">
                        <?php else: ?>
                        <div class="w-14 h-14 bg-slate-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-image text-slate-300 text-xl"></i>
                        </div>
                        <?php endif; ?>
                        <div>
                            <p class="font-semibold text-slate-800 line-clamp-1"><?php echo htmlspecialchars($product['ten_san_pham']); ?></p>
                            <p class="text-xs text-slate-400 mt-1"><?php echo htmlspecialchars($product['ten_thuong_hieu'] ?? ''); ?></p>
                        </div>
                    </div>
                </td>
                <td class="px-5 py-4">
                    <span class="text-sm font-mono bg-slate-100 px-2 py-1 rounded text-slate-600"><?php echo $product['ma_san_pham']; ?></span>
                </td>
                <td class="px-5 py-4">
                    <span class="text-sm text-slate-600"><?php echo htmlspecialchars($product['ten_danh_muc'] ?? '-'); ?></span>
                </td>
                <td class="px-5 py-4">
                    <span class="font-bold text-slate-800"><?php echo formatCurrency($product['gia_ban']); ?></span>
                </td>
                <td class="px-5 py-4">
                    <?php if ($product['so_luong_ton'] <= 10): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-100 text-red-700 rounded-full text-sm font-semibold">
                        <i class="fas fa-exclamation-circle text-xs"></i>
                        <?php echo $product['so_luong_ton']; ?>
                    </span>
                    <?php else: ?>
                    <span class="text-sm text-slate-600"><?php echo $product['so_luong_ton']; ?></span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-4">
                    <?php if ($product['trang_thai'] === 'active'): ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-semibold">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                        Đang bán
                    </span>
                    <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-semibold">
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                        Ngừng bán
                    </span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-4">
                    <div class="flex items-center justify-center gap-1">
                        <!-- Nút toggle hiển thị/ẩn -->
                        <a href="?toggle=<?php echo $product['id']; ?>&<?php echo http_build_query($_GET); ?>" 
                           class="w-9 h-9 flex items-center justify-center rounded-lg <?php echo $product['trang_thai'] === 'active' ? 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100' : 'bg-slate-50 text-slate-400 hover:bg-slate-100'; ?> transition" 
                           title="<?php echo $product['trang_thai'] === 'active' ? 'Ẩn sản phẩm' : 'Hiển thị sản phẩm'; ?>">
                            <i class="fas <?php echo $product['trang_thai'] === 'active' ? 'fa-eye' : 'fa-eye-slash'; ?>"></i>
                        </a>
                        <!-- Nút sửa -->
                        <a href="<?php echo SITE_URL; ?>/admin/product-form.php?id=<?php echo $product['id']; ?>" 
                           class="w-9 h-9 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </a>
                        <!-- Nút xóa -->
                        <a href="?delete=<?php echo $product['id']; ?>" 
                           onclick="return confirmDelete('Bạn có chắc muốn xóa sản phẩm này?')"
                           class="w-9 h-9 flex items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="flex items-center justify-between mt-6 bg-white rounded-xl p-4 border border-slate-200">
    <p class="text-slate-500 text-sm">
        Hiển thị <span class="font-semibold text-slate-700"><?php echo count($products); ?></span> / 
        <span class="font-semibold text-slate-700"><?php echo $total; ?></span> sản phẩm
    </p>
    <div class="flex items-center gap-1">
        <?php if ($page > 1): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
           class="w-9 h-9 flex items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
            <i class="fas fa-chevron-left text-sm"></i>
        </a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
           class="w-9 h-9 flex items-center justify-center rounded-lg font-medium transition <?php echo $i === $page ? 'bg-primary-500 text-white shadow-lg shadow-primary-500/30' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
           class="w-9 h-9 flex items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
            <i class="fas fa-chevron-right text-sm"></i>
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
