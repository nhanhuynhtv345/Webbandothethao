<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Tin tức thể thao';
$db = getDB();

// Get page and search
$page = max(1, intval($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');

// Build query
$where = "status = 'published'";
$params = [];

if (!empty($search)) {
    $where .= " AND (title LIKE ? OR summary LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm];
}

// Get total count
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM tin_tuc WHERE $where");
$countStmt->execute($params);
$totalNews = $countStmt->fetch()['total'];

// Pagination
$itemsPerPage = 9;
$totalPages = ceil($totalNews / $itemsPerPage);
$offset = ($page - 1) * $itemsPerPage;

// Get news
$stmt = $db->prepare("SELECT * FROM tin_tuc WHERE $where ORDER BY published_at DESC LIMIT $itemsPerPage OFFSET $offset");
$stmt->execute($params);
$newsList = $stmt->fetchAll();

include __DIR__ . '/../components/header.php';
?>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<!-- Hero -->
<section class="bg-gradient-to-r from-primary-600 to-primary-800 text-white py-16">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-5xl font-bold mb-4">Tin tức thể thao</h1>
        <p class="text-xl opacity-90">Cập nhật kiến thức và xu hướng thể thao mới nhất</p>
    </div>
</section>

<!-- Breadcrumb -->
<div class="bg-gray-50 py-4 border-b">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-sm">
            <a href="<?php echo SITE_URL; ?>/index.php" class="text-gray-600 hover:text-primary-600">
                <i class="fas fa-home"></i> Trang chủ
            </a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-900 font-medium">Tin tức</span>
        </nav>
    </div>
</div>

<div class="container mx-auto px-4 py-12">
    <!-- Search -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <form method="GET" class="flex gap-4">
            <input type="text" 
                   name="search" 
                   value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Tìm kiếm tin tức..." 
                   class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            <button type="submit" class="px-8 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium">
                <i class="fas fa-search mr-2"></i>Tìm kiếm
            </button>
            <?php if (!empty($search)): ?>
            <a href="<?php echo SITE_URL; ?>/pages/news.php" class="px-8 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-medium">
                Xóa
            </a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!empty($search)): ?>
    <div class="mb-6">
        <p class="text-gray-600">
            Tìm thấy <strong><?php echo $totalNews; ?></strong> kết quả cho "<strong><?php echo htmlspecialchars($search); ?></strong>"
        </p>
    </div>
    <?php endif; ?>
    
    <?php if (empty($newsList)): ?>
        <div class="bg-white rounded-lg shadow-md p-12 text-center">
            <i class="fas fa-newspaper text-gray-400 text-6xl mb-4"></i>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">
                <?php echo !empty($search) ? 'Không tìm thấy kết quả' : 'Chưa có tin tức'; ?>
            </h3>
            <p class="text-gray-600">
                <?php echo !empty($search) ? 'Thử tìm kiếm với từ khóa khác' : 'Nội dung đang được cập nhật...'; ?>
            </p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-8">
            <?php foreach ($newsList as $news): ?>
            <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 group">
                <a href="<?php echo SITE_URL; ?>/pages/news-detail.php?slug=<?php echo $news['slug']; ?>">
                    <?php if ($news['cover_image']): ?>
                        <div class="overflow-hidden h-48">
                            <img src="<?php echo UPLOAD_URL . '/' . htmlspecialchars($news['cover_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($news['title']); ?>"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                        </div>
                    <?php else: ?>
                        <div class="h-48 bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center">
                            <i class="fas fa-newspaper text-white text-6xl"></i>
                        </div>
                    <?php endif; ?>
                </a>
                
                <div class="p-6">
                    <div class="flex items-center gap-4 text-sm text-gray-500 mb-3">
                        <span><i class="far fa-calendar mr-1"></i><?php echo formatDate($news['published_at']); ?></span>
                        <span><i class="far fa-eye mr-1"></i><?php echo number_format($news['luot_xem']); ?></span>
                    </div>
                    
                    <h3 class="text-xl font-bold mb-3 line-clamp-2">
                        <a href="<?php echo SITE_URL; ?>/pages/news-detail.php?slug=<?php echo $news['slug']; ?>" 
                           class="text-gray-900 hover:text-primary-600 transition-colors">
                            <?php echo htmlspecialchars($news['title']); ?>
                        </a>
                    </h3>
                    
                    <?php if (!empty($news['summary'])): ?>
                        <p class="text-gray-600 mb-4 line-clamp-3"><?php echo htmlspecialchars($news['summary']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($news['tags'])): ?>
                        <div class="flex flex-wrap gap-1 mb-4">
                            <?php foreach (array_slice(explode(',', $news['tags']), 0, 3) as $tag): ?>
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded"><?php echo trim($tag); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <a href="<?php echo SITE_URL; ?>/pages/news-detail.php?slug=<?php echo $news['slug']; ?>" 
                       class="text-primary-600 hover:text-primary-700 font-semibold inline-flex items-center group">
                        Đọc thêm 
                        <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="flex items-center justify-center gap-2 mt-12">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="px-4 py-2 bg-primary-600 text-white rounded-lg font-medium"><?php echo $i; ?></span>
                <?php elseif ($i <= 2 || $i > $totalPages - 2 || abs($i - $page) <= 1): ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                        <?php echo $i; ?>
                    </a>
                <?php elseif ($i === 3 || $i === $totalPages - 2): ?>
                    <span class="px-2 text-gray-500">...</span>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>

