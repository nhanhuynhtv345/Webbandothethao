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
        $db->prepare("DELETE FROM khuyen_mai WHERE id = ?")->execute([$id]);
        $message = 'Xóa khuyến mãi thành công!';
    } catch (Exception $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
}

// Handle toggle active
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    try {
        $db->prepare("UPDATE khuyen_mai SET active = NOT active WHERE id = ?")->execute([$id]);
        $message = 'Cập nhật trạng thái thành công!';
    } catch (Exception $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
}

// Filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;

// Build query
$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(code LIKE ? OR title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status === 'active') {
    $where[] = "active = 1 AND start_at <= NOW() AND end_at >= NOW()";
} elseif ($status === 'expired') {
    $where[] = "end_at < NOW()";
} elseif ($status === 'upcoming') {
    $where[] = "start_at > NOW()";
} elseif ($status === 'inactive') {
    $where[] = "active = 0";
}

$whereClause = implode(' AND ', $where);

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM khuyen_mai WHERE $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

// Get promotions
$sql = "SELECT * FROM khuyen_mai WHERE $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$promotions = $stmt->fetchAll();

$pageTitle = 'Quản lý khuyến mãi';
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
                       placeholder="Tìm mã, tiêu đề..." 
                       class="pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition w-64">
            </div>
            
            <select name="status" class="px-4 py-2.5 border border-slate-200 rounded-xl focus:border-primary-500 outline-none bg-white">
                <option value="">Tất cả trạng thái</option>
                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Đang hoạt động</option>
                <option value="upcoming" <?php echo $status === 'upcoming' ? 'selected' : ''; ?>>Sắp diễn ra</option>
                <option value="expired" <?php echo $status === 'expired' ? 'selected' : ''; ?>>Đã hết hạn</option>
                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Đã tắt</option>
            </select>
            
            <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-xl font-medium transition">
                <i class="fas fa-filter mr-2"></i>Lọc
            </button>
        </form>
        
        <a href="<?php echo SITE_URL; ?>/admin/promotion-form.php" 
           class="bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white px-5 py-2.5 rounded-xl font-medium shadow-lg shadow-primary-500/30 transition transform hover:-translate-y-0.5">
            <i class="fas fa-plus mr-2"></i>Thêm khuyến mãi
        </a>
    </div>
</div>

<!-- Promotions Table -->
<div class="card-hover bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <table class="w-full">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Mã</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tiêu đề</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Loại</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Giá trị</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Thời gian</th>
                <th class="px-5 py-4 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Đã dùng</th>
                <th class="px-5 py-4 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Trạng thái</th>
                <th class="px-5 py-4 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($promotions)): ?>
            <tr>
                <td colspan="8" class="px-5 py-12 text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-tags text-slate-400 text-2xl"></i>
                    </div>
                    <p class="text-slate-500">Chưa có khuyến mãi nào</p>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($promotions as $promo): 
                $now = time();
                $start = strtotime($promo['start_at']);
                $end = strtotime($promo['end_at']);
                
                if (!$promo['active']) {
                    $statusClass = 'bg-gray-100 text-gray-700';
                    $statusText = 'Đã tắt';
                } elseif ($now < $start) {
                    $statusClass = 'bg-blue-100 text-blue-700';
                    $statusText = 'Sắp diễn ra';
                } elseif ($now > $end) {
                    $statusClass = 'bg-red-100 text-red-700';
                    $statusText = 'Hết hạn';
                } else {
                    $statusClass = 'bg-green-100 text-green-700';
                    $statusText = 'Đang chạy';
                }
                
                $typeLabels = [
                    'percent' => ['Phần trăm', 'bg-purple-100 text-purple-700'],
                    'fixed' => ['Cố định', 'bg-orange-100 text-orange-700'],
                    'shipping' => ['Miễn ship', 'bg-cyan-100 text-cyan-700']
                ];
                $typeInfo = $typeLabels[$promo['type']] ?? ['Khác', 'bg-gray-100 text-gray-700'];
            ?>
            <tr class="hover:bg-slate-50 transition">
                <td class="px-5 py-4">
                    <span class="font-mono font-bold text-primary-600 bg-primary-50 px-2 py-1 rounded"><?php echo htmlspecialchars($promo['code']); ?></span>
                </td>
                <td class="px-5 py-4">
                    <span class="font-semibold text-slate-800"><?php echo htmlspecialchars($promo['title']); ?></span>
                </td>
                <td class="px-5 py-4">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $typeInfo[1]; ?>">
                        <?php echo $typeInfo[0]; ?>
                    </span>
                </td>
                <td class="px-5 py-4 font-bold text-slate-800">
                    <?php 
                    if ($promo['type'] === 'percent') {
                        echo intval($promo['value']) . '%';
                        if ($promo['max_discount']) {
                            echo ' <span class="text-xs text-slate-500">(tối đa ' . formatCurrency($promo['max_discount']) . ')</span>';
                        }
                    } elseif ($promo['type'] === 'shipping') {
                        echo 'Miễn phí';
                    } else {
                        echo formatCurrency($promo['value']);
                    }
                    ?>
                </td>
                <td class="px-5 py-4 text-sm text-slate-600">
                    <div><?php echo date('d/m/Y H:i', $start); ?></div>
                    <div class="text-slate-400">đến <?php echo date('d/m/Y H:i', $end); ?></div>
                </td>
                <td class="px-5 py-4 text-center">
                    <span class="font-semibold"><?php echo $promo['used_count']; ?></span>
                    <?php if ($promo['usage_limit']): ?>
                    <span class="text-slate-400">/ <?php echo $promo['usage_limit']; ?></span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-4 text-center">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $statusClass; ?>">
                        <?php echo $statusText; ?>
                    </span>
                </td>
                <td class="px-5 py-4">
                    <div class="flex items-center justify-center gap-1">
                        <a href="?toggle=<?php echo $promo['id']; ?>&<?php echo http_build_query($_GET); ?>" 
                           class="w-9 h-9 flex items-center justify-center rounded-lg <?php echo $promo['active'] ? 'bg-yellow-50 text-yellow-600 hover:bg-yellow-100' : 'bg-green-50 text-green-600 hover:bg-green-100'; ?> transition" 
                           title="<?php echo $promo['active'] ? 'Tắt' : 'Bật'; ?>">
                            <i class="fas <?php echo $promo['active'] ? 'fa-pause' : 'fa-play'; ?>"></i>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/promotion-form.php?id=<?php echo $promo['id']; ?>" 
                           class="w-9 h-9 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="?delete=<?php echo $promo['id']; ?>&<?php echo http_build_query($_GET); ?>" 
                           onclick="return confirmDelete('Bạn có chắc muốn xóa khuyến mãi này?')"
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

<?php if ($totalPages > 1): ?>
<div class="flex items-center justify-between mt-6 bg-white rounded-xl p-4 border border-slate-200">
    <p class="text-slate-500 text-sm">
        Hiển thị <span class="font-semibold text-slate-700"><?php echo count($promotions); ?></span> / 
        <span class="font-semibold text-slate-700"><?php echo $total; ?></span> khuyến mãi
    </p>
    <div class="flex items-center gap-1">
        <?php if ($page > 1): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
           class="w-9 h-9 flex items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
            <i class="fas fa-chevron-left text-sm"></i>
        </a>
        <?php endif; ?>
        
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
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
