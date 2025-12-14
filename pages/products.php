<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Sản phẩm';
$db = getDB();

// Get filters
$search = $_GET['search'] ?? '';
$category = $_GET['danh_muc'] ?? '';
$brands = $_GET['thuong_hieu'] ?? []; // Changed to array for multiple brands
if (!is_array($brands)) {
    $brands = $brands ? [$brands] : [];
}
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? intval($_GET['min_price']) : 0;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? intval($_GET['max_price']) : 10000000;
$sortBy = $_GET['sort'] ?? 'newest';
$page = max(1, intval($_GET['page'] ?? 1));

// Build query
$where = ["sp.trang_thai = 'active'"];
$params = [];

if ($search) {
    $where[] = "(sp.ten_san_pham LIKE ? OR sp.mo_ta_ngan LIKE ? OR sp.ma_san_pham LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($category) {
    $where[] = "dm.slug = ?";
    $params[] = $category;
}

if (!empty($brands)) {
    $brandPlaceholders = implode(',', array_fill(0, count($brands), '?'));
    $where[] = "th.slug IN ($brandPlaceholders)";
    $params = array_merge($params, $brands);
}

if ($minPrice > 0) {
    $where[] = "sp.gia_ban >= ?";
    $params[] = $minPrice;
}

if ($maxPrice > 0) {
    $where[] = "sp.gia_ban <= ?";
    $params[] = $maxPrice;
}

$whereClause = implode(' AND ', $where);

// Get sort
$orderBy = match($sortBy) {
    'price_asc' => 'sp.gia_ban ASC',
    'price_desc' => 'sp.gia_ban DESC',
    'name_asc' => 'sp.ten_san_pham ASC',
    'name_desc' => 'sp.ten_san_pham DESC',
    'bestseller' => 'sp.luot_ban DESC',
    'rating' => 'sp.diem_trung_binh DESC',
    default => 'sp.created_at DESC'
};

// Count total products
$countSql = "SELECT COUNT(*) as total 
             FROM san_pham sp
             LEFT JOIN danh_muc dm ON sp.danh_muc_id = dm.id
             LEFT JOIN thuong_hieu th ON sp.thuong_hieu_id = th.id
             WHERE $whereClause";
$stmtCount = $db->prepare($countSql);
$stmtCount->execute($params);
$totalProducts = $stmtCount->fetch()['total'];

// Pagination
$pagination = paginate($totalProducts, PRODUCTS_PER_PAGE, $page);

// Get products
$sql = "SELECT sp.*, dm.ten_danh_muc, dm.slug as danh_muc_slug, 
               th.ten_thuong_hieu, th.slug as thuong_hieu_slug,
               (SELECT url FROM hinh_anh_san_pham WHERE san_pham_id = sp.id AND is_primary = 1 LIMIT 1) as anh_dai_dien
        FROM san_pham sp
        LEFT JOIN danh_muc dm ON sp.danh_muc_id = dm.id
        LEFT JOIN thuong_hieu th ON sp.thuong_hieu_id = th.id
        WHERE $whereClause
        ORDER BY $orderBy
        LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get all categories for filter
$categories = $db->query("SELECT * FROM danh_muc WHERE active = 1 ORDER BY ten_danh_muc ASC")->fetchAll();

// Get all brands for filter
$allBrands = $db->query("SELECT * FROM thuong_hieu WHERE active = 1 ORDER BY ten_thuong_hieu ASC")->fetchAll();

include __DIR__ . '/../components/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-sm">
            <a href="<?php echo SITE_URL; ?>/index.php" class="text-gray-600 hover:text-primary-600">Trang chủ</a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-900">Sản phẩm</span>
        </nav>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        
        <!-- Sidebar Filters -->
        <aside class="lg:w-64 flex-shrink-0">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                <h3 class="text-lg font-bold mb-4">Bộ lọc</h3>
                
                <form action="" method="GET" id="filterForm">
                    <!-- Search -->
                    <?php if ($search): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <?php endif; ?>
                    
                    <!-- Category Filter -->
                    <div class="mb-6">
                        <h4 class="font-semibold mb-3">Danh mục</h4>
                        <div class="space-y-2">
                            <?php foreach ($categories as $cat): ?>
                            <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded transition">
                                <input type="radio" name="danh_muc" value="<?php echo $cat['slug']; ?>"
                                       <?php echo $category === $cat['slug'] ? 'checked' : ''; ?>
                                       onchange="applyFilters()"
                                       class="text-primary-600 focus:ring-primary-500">
                                <span class="text-sm"><?php echo htmlspecialchars($cat['ten_danh_muc']); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Brand Filter -->
                    <div class="mb-6">
                        <h4 class="font-semibold mb-3">Thương hiệu</h4>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            <?php foreach ($allBrands as $b): ?>
                            <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded transition">
                                <input type="checkbox" name="thuong_hieu[]" value="<?php echo $b['slug']; ?>"
                                       <?php echo in_array($b['slug'], $brands) ? 'checked' : ''; ?>
                                       onchange="applyFilters()"
                                       class="text-primary-600 focus:ring-primary-500 rounded">
                                <span class="text-sm"><?php echo htmlspecialchars($b['ten_thuong_hieu']); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Price Range -->
                    <div class="mb-6">
                        <h4 class="font-semibold mb-4 flex items-center gap-2">
                            <i class="fas fa-dollar-sign text-primary-600"></i>
                            Khoảng giá
                        </h4>
                        <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase mb-2 block">Giá tối thiểu</label>
                                <div class="relative">
                                    <input type="number" name="min_price" value="<?php echo $minPrice > 0 ? $minPrice : ''; ?>" 
                                           class="w-full pl-3 pr-12 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition" 
                                           placeholder="0"
                                           min="0">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">đ</span>
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase mb-2 block">Giá tối đa</label>
                                <div class="relative">
                                    <input type="number" name="max_price" value="<?php echo $maxPrice < 10000000 ? $maxPrice : ''; ?>" 
                                           class="w-full pl-3 pr-12 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition" 
                                           placeholder="10000000"
                                           min="0">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">đ</span>
                                </div>
                            </div>
                            <!-- Quick price options -->
                            <div class="pt-2">
                                <p class="text-xs font-medium text-gray-500 uppercase mb-2">Gợi ý giá</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" onclick="setPrice(0, 500000)" 
                                            class="text-xs py-2 px-3 bg-white border border-gray-200 rounded-lg hover:border-primary-500 hover:text-primary-600 transition">
                                        Dưới 500K
                                    </button>
                                    <button type="button" onclick="setPrice(500000, 1000000)" 
                                            class="text-xs py-2 px-3 bg-white border border-gray-200 rounded-lg hover:border-primary-500 hover:text-primary-600 transition">
                                        500K - 1M
                                    </button>
                                    <button type="button" onclick="setPrice(1000000, 2000000)" 
                                            class="text-xs py-2 px-3 bg-white border border-gray-200 rounded-lg hover:border-primary-500 hover:text-primary-600 transition">
                                        1M - 2M
                                    </button>
                                    <button type="button" onclick="setPrice(2000000, 10000000)" 
                                            class="text-xs py-2 px-3 bg-white border border-gray-200 rounded-lg hover:border-primary-500 hover:text-primary-600 transition">
                                        Trên 2M
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-gradient-to-r from-primary-600 to-primary-700 text-white px-4 py-3 rounded-lg font-semibold hover:from-primary-700 hover:to-primary-800 active:scale-95 transition-all duration-200 inline-flex items-center justify-center shadow-lg hover:shadow-xl">
                        <i class="fas fa-filter mr-2"></i>
                        Áp dụng bộ lọc
                    </button>
                    
                    <a href="<?php echo SITE_URL; ?>/pages/products.php" class="block text-center mt-3 text-sm text-gray-600 hover:text-primary-600 font-medium transition">
                        <i class="fas fa-redo mr-1"></i> Xóa tất cả bộ lọc
                    </a>
                </form>
            </div>
        </aside>
        
        <!-- Products Grid -->
        <div class="flex-1">
            <!-- Toolbar -->
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <p class="text-gray-600">
                            Hiển thị <span class="font-semibold"><?php echo count($products); ?></span> 
                            trong tổng số <span class="font-semibold"><?php echo $totalProducts; ?></span> sản phẩm
                        </p>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <label class="text-sm text-gray-600">Sắp xếp:</label>
                        <select name="sort" onchange="changeSort(this.value)" 
                                class="input text-sm">
                            <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                            <option value="price_asc" <?php echo $sortBy === 'price_asc' ? 'selected' : ''; ?>>Giá tăng dần</option>
                            <option value="price_desc" <?php echo $sortBy === 'price_desc' ? 'selected' : ''; ?>>Giá giảm dần</option>
                            <option value="name_asc" <?php echo $sortBy === 'name_asc' ? 'selected' : ''; ?>>Tên A-Z</option>
                            <option value="name_desc" <?php echo $sortBy === 'name_desc' ? 'selected' : ''; ?>>Tên Z-A</option>
                            <option value="bestseller" <?php echo $sortBy === 'bestseller' ? 'selected' : ''; ?>>Bán chạy</option>
                            <option value="rating" <?php echo $sortBy === 'rating' ? 'selected' : ''; ?>>Đánh giá cao</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Products -->
            <?php if (empty($products)): ?>
                <div class="bg-white rounded-lg shadow-md p-12 text-center">
                    <i class="fas fa-box-open text-gray-400 text-6xl mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Không tìm thấy sản phẩm</h3>
                    <p class="text-gray-600 mb-4">Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm</p>
                    <a href="<?php echo SITE_URL; ?>/pages/products.php" class="inline-flex items-center justify-center bg-primary-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-primary-700 transition-all duration-200">
                        Xem tất cả sản phẩm
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <?php foreach ($products as $product): ?>
                    <?php include __DIR__ . '/../components/product-card.php'; ?>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                <div class="flex items-center justify-center gap-2">
                    <?php if ($pagination['has_prev']): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="px-4 py-2 bg-primary-600 text-white rounded-lg"><?php echo $i; ?></span>
                        <?php elseif ($i <= 3 || $i > $pagination['total_pages'] - 3 || abs($i - $page) <= 2): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">
                                <?php echo $i; ?>
                            </a>
                        <?php elseif ($i === 4 || $i === $pagination['total_pages'] - 3): ?>
                            <span class="px-2">...</span>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($pagination['has_next']): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function changeSort(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', value);
    url.searchParams.set('page', '1'); // Reset to page 1 when sorting
    window.location.href = url.toString();
}

function setPrice(min, max) {
    const minInput = document.querySelector('input[name="min_price"]');
    const maxInput = document.querySelector('input[name="max_price"]');
    
    minInput.value = min;
    maxInput.value = max;
    
    // Auto apply when using quick price buttons
    applyFilters();
}

function applyFilters() {
    document.getElementById('filterForm').submit();
}

// Auto apply when price inputs lose focus
document.addEventListener('DOMContentLoaded', function() {
    const priceInputs = document.querySelectorAll('input[name="min_price"], input[name="max_price"]');
    
    priceInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value !== '') {
                applyFilters();
            }
        });
        
        // Also submit on Enter key
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyFilters();
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
