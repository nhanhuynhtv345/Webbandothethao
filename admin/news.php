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
        $db->prepare("DELETE FROM tin_tuc WHERE id = ?")->execute([$id]);
        $message = 'Xóa tin tức thành công!';
    } catch (Exception $e) {
        $error = 'Lỗi khi xóa tin tức: ' . $e->getMessage();
    }
}

// Handle toggle status
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    try {
        $stmt = $db->prepare("SELECT status FROM tin_tuc WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetchColumn();
        
        $newStatus = $current === 'published' ? 'draft' : 'published';
        $publishedAt = $newStatus === 'published' ? date('Y-m-d H:i:s') : null;
        
        $db->prepare("UPDATE tin_tuc SET status = ?, published_at = ? WHERE id = ?")
           ->execute([$newStatus, $publishedAt, $id]);
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
    $where[] = "title LIKE ?";
    $params[] = "%$search%";
}

if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
}

$whereClause = implode(' AND ', $where);

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM tin_tuc WHERE $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);
$offset = ($page - 1) * $perPage;

// Get news
$sql = "SELECT * FROM tin_tuc WHERE $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$newsList = $stmt->fetchAll();

$pageTitle = 'Quản lý tin tức';
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
                       placeholder="Tìm kiếm tin tức..." 
                       class="pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition w-64">
            </div>
            
            <select name="status" class="px-4 py-2.5 border border-slate-200 rounded-xl focus:border-primary-500 outline-none bg-white">
                <option value="">Tất cả trạng thái</option>
                <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Đã xuất bản</option>
                <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Bản nháp</option>
                <option value="archived" <?php echo $status === 'archived' ? 'selected' : ''; ?>>Lưu trữ</option>
            </select>
            
            <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-xl font-medium transition">
                <i class="fas fa-filter mr-2"></i>Lọc
            </button>
        </form>
        
        <a href="<?php echo SITE_URL; ?>/admin/news-form.php" 
           class="bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white px-5 py-2.5 rounded-xl font-medium shadow-lg shadow-primary-500/30 transition transform hover:-translate-y-0.5">
            <i class="fas fa-plus mr-2"></i>Thêm tin tức
        </a>
    </div>
</div>

<!-- News Table -->
<div class="card-hover bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <table class="w-full">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tin tức</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tags</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Lượt xem</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Trạng thái</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Ngày tạo</th>
                <th class="px-5 py-4 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($newsList)): ?>
            <tr>
                <td colspan="6" class="px-5 py-12 text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-newspaper text-slate-400 text-2xl"></i>
                    </div>
                    <p class="text-slate-500">Chưa có tin tức nào</p>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($newsList as $news): ?>
            <tr class="hover:bg-slate-50 transition">
                <td class="px-5 py-4">
                    <div class="flex items-center gap-4">
                        <?php if ($news['cover_image']): ?>
                        <img src="<?php echo UPLOAD_URL . '/' . $news['cover_image']; ?>" 
                             class="w-16 h-12 object-cover rounded-lg shadow-sm">
                        <?php else: ?>
                        <div class="w-16 h-12 bg-slate-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-image text-slate-300"></i>
                        </div>
                        <?php endif; ?>
                        <div>
                            <p class="font-semibold text-slate-800 line-clamp-1"><?php echo htmlspecialchars($news['title']); ?></p>
                            <p class="text-xs text-slate-400 mt-1 line-clamp-1"><?php echo htmlspecialchars($news['summary'] ?? ''); ?></p>
                        </div>
                    </div>
                </td>
                <td class="px-5 py-4">
                    <?php if ($news['tags']): ?>
                    <div class="flex flex-wrap gap-1">
                        <?php foreach (array_slice(explode(',', $news['tags']), 0, 2) as $tag): ?>
                        <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded"><?php echo trim($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <span class="text-slate-400">-</span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-4">
                    <span class="text-sm text-slate-600"><i class="fas fa-eye mr-1"></i><?php echo number_format($news['luot_xem']); ?></span>
                </td>
                <td class="px-5 py-4">
                    <?php if ($news['status'] === 'published'): ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-semibold">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                        Đã xuất bản
                    </span>
                    <?php elseif ($news['status'] === 'archived'): ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-semibold">
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                        Lưu trữ
                    </span>
                    <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-semibold">
                        <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span>
                        Bản nháp
                    </span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-4">
                    <span class="text-sm text-slate-600"><?php echo date('d/m/Y H:i', strtotime($news['created_at'])); ?></span>
                </td>
                <td class="px-5 py-4">
                    <div class="flex items-center justify-center gap-1">
                        <a href="?toggle=<?php echo $news['id']; ?>&<?php echo http_build_query($_GET); ?>" 
                           class="w-9 h-9 flex items-center justify-center rounded-lg <?php echo $news['status'] === 'published' ? 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100' : 'bg-amber-50 text-amber-600 hover:bg-amber-100'; ?> transition" 
                           title="<?php echo $news['status'] === 'published' ? 'Chuyển sang nháp' : 'Xuất bản'; ?>">
                            <i class="fas <?php echo $news['status'] === 'published' ? 'fa-eye' : 'fa-eye-slash'; ?>"></i>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/news-form.php?id=<?php echo $news['id']; ?>" 
                           class="w-9 h-9 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="?delete=<?php echo $news['id']; ?>" 
                           onclick="return confirmDelete('Bạn có chắc muốn xóa tin tức này?')"
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
        Hiển thị <span class="font-semibold text-slate-700"><?php echo count($newsList); ?></span> / 
        <span class="font-semibold text-slate-700"><?php echo $total; ?></span> tin tức
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
