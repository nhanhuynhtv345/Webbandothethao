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
        $db->prepare("DELETE FROM danh_muc WHERE id = ?")->execute([$id]);
        $message = 'Xóa danh mục thành công!';
    } catch (Exception $e) {
        $error = 'Không thể xóa danh mục này vì có sản phẩm liên quan.';
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $ten = trim($_POST['ten_danh_muc']);
    $slug = generateSlug($ten);
    $moTa = trim($_POST['mo_ta'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;
    
    try {
        if ($id > 0) {
            $db->prepare("UPDATE danh_muc SET ten_danh_muc = ?, slug = ?, mo_ta = ?, active = ? WHERE id = ?")
               ->execute([$ten, $slug, $moTa, $active, $id]);
            $message = 'Cập nhật danh mục thành công!';
        } else {
            $db->prepare("INSERT INTO danh_muc (ten_danh_muc, slug, mo_ta, active) VALUES (?, ?, ?, ?)")
               ->execute([$ten, $slug, $moTa, $active]);
            $message = 'Thêm danh mục thành công!';
        }
    } catch (Exception $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
}

// Get all categories
$categories = $db->query("
    SELECT dm.*, (SELECT COUNT(*) FROM san_pham WHERE danh_muc_id = dm.id) as product_count 
    FROM danh_muc dm ORDER BY dm.ten_danh_muc
")->fetchAll();

// Get category for edit
$editCategory = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM danh_muc WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $editCategory = $stmt->fetch();
}

$pageTitle = 'Quản lý danh mục';
include __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?>
<div class="bg-green-100 text-green-700 p-4 rounded mb-6"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="bg-red-100 text-red-700 p-4 rounded mb-6"><?php echo $error; ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Form -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4"><?php echo $editCategory ? 'Sửa danh mục' : 'Thêm danh mục'; ?></h3>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="id" value="<?php echo $editCategory['id'] ?? ''; ?>">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tên danh mục *</label>
                <input type="text" name="ten_danh_muc" required
                       value="<?php echo htmlspecialchars($editCategory['ten_danh_muc'] ?? ''); ?>"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                <textarea name="mo_ta" rows="3" class="w-full px-4 py-2 border rounded-lg"><?php echo htmlspecialchars($editCategory['mo_ta'] ?? ''); ?></textarea>
            </div>
            
            <div class="flex items-center gap-2">
                <input type="checkbox" name="active" id="active" value="1"
                       <?php echo ($editCategory['active'] ?? 1) ? 'checked' : ''; ?>
                       class="w-4 h-4 text-blue-600 rounded">
                <label for="active" class="text-sm text-gray-700">Kích hoạt</label>
            </div>
            
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-1"></i><?php echo $editCategory ? 'Cập nhật' : 'Thêm mới'; ?>
                </button>
                <?php if ($editCategory): ?>
                <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Hủy</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- List -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Tên danh mục</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Slug</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600">Sản phẩm</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600">Trạng thái</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">Chưa có danh mục nào</td>
                </tr>
                <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($cat['ten_danh_muc']); ?></td>
                    <td class="px-4 py-3 text-sm text-gray-500"><?php echo $cat['slug']; ?></td>
                    <td class="px-4 py-3 text-center"><?php echo $cat['product_count']; ?></td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 text-xs rounded <?php echo $cat['active'] ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600'; ?>">
                            <?php echo $cat['active'] ? 'Hoạt động' : 'Ẩn'; ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="?edit=<?php echo $cat['id']; ?>" class="text-blue-600 hover:text-blue-800 mx-1">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="?delete=<?php echo $cat['id']; ?>" onclick="return confirmDelete()" class="text-red-600 hover:text-red-800 mx-1">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
