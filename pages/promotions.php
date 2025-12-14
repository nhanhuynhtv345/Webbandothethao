<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Khuyến mãi';
$metaDescription = 'Các chương trình khuyến mãi và ưu đãi đặc biệt';

$db = getDB();

// Get active promotions (coupon codes)
$promoStmt = $db->query("
    SELECT * FROM khuyen_mai 
    WHERE active = 1 AND start_at <= NOW() AND end_at >= NOW() 
    AND (usage_limit IS NULL OR used_count < usage_limit)
    ORDER BY value DESC
");
$promotions = $promoStmt->fetchAll();

// Pagination for products
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Get promotional products
$stmt = $db->prepare("
    SELECT sp.*, dm.ten_danh_muc, th.ten_thuong_hieu,
           (SELECT url FROM hinh_anh_san_pham WHERE san_pham_id = sp.id AND is_primary = 1 LIMIT 1) as anh_dai_dien
    FROM san_pham sp
    LEFT JOIN danh_muc dm ON sp.danh_muc_id = dm.id
    LEFT JOIN thuong_hieu th ON sp.thuong_hieu_id = th.id
    WHERE sp.gia_goc IS NOT NULL AND sp.gia_goc > 0 AND sp.gia_goc > sp.gia_ban AND sp.trang_thai = 'active'
    ORDER BY (sp.gia_goc - sp.gia_ban) DESC, sp.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Get total count
$stmtCount = $db->query("
    SELECT COUNT(*) as total 
    FROM san_pham 
    WHERE gia_goc IS NOT NULL AND gia_goc > 0 AND gia_goc > gia_ban AND trang_thai = 'active'
");
$totalProducts = $stmtCount->fetch()['total'];
$totalPages = ceil($totalProducts / $limit);

include __DIR__ . '/../components/header.php';
?>

<!-- Page Header -->
<div class="bg-gradient-to-r from-red-600 to-orange-600 text-white py-12">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl font-bold mb-2">
            <i class="fas fa-tags mr-3"></i>Khuyến mãi đặc biệt
        </h1>
        <p class="text-lg">Giảm giá lên đến 50% cho các sản phẩm chọn lọc</p>
    </div>
</div>

<!-- Coupon Codes Section -->
<?php if (!empty($promotions)): ?>
<section class="py-8 bg-gradient-to-b from-orange-50 to-white">
    <div class="container mx-auto px-4">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
            <i class="fas fa-ticket-alt text-orange-500"></i>
            Mã giảm giá đang có
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($promotions as $promo): 
                $daysLeft = ceil((strtotime($promo['end_at']) - time()) / 86400);
            ?>
            <div class="bg-white rounded-xl border-2 border-dashed border-orange-300 p-4 hover:border-orange-500 hover:shadow-lg transition relative overflow-hidden">
                <!-- Ribbon -->
                <?php if ($promo['type'] === 'percent' && $promo['value'] >= 30): ?>
                <div class="absolute -right-8 top-3 bg-red-500 text-white text-xs font-bold px-8 py-1 rotate-45">HOT</div>
                <?php endif; ?>
                
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-sm font-bold">
                                <?php 
                                if ($promo['type'] === 'percent') {
                                    echo 'Giảm ' . intval($promo['value']) . '%';
                                } elseif ($promo['type'] === 'shipping') {
                                    echo 'Free Ship';
                                } else {
                                    echo 'Giảm ' . formatCurrency($promo['value']);
                                }
                                ?>
                            </span>
                            <?php if ($promo['max_discount']): ?>
                            <span class="text-xs text-gray-500">Tối đa <?php echo formatCurrency($promo['max_discount']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <h3 class="font-semibold text-gray-900 mb-1"><?php echo htmlspecialchars($promo['title']); ?></h3>
                        
                        <?php if ($promo['min_order_amount'] > 0): ?>
                        <p class="text-sm text-gray-500">Đơn tối thiểu <?php echo formatCurrency($promo['min_order_amount']); ?></p>
                        <?php endif; ?>
                        
                        <p class="text-xs text-gray-400 mt-2">
                            <i class="fas fa-clock mr-1"></i>
                            <?php if ($daysLeft <= 3): ?>
                            <span class="text-red-500 font-semibold">Còn <?php echo $daysLeft; ?> ngày</span>
                            <?php else: ?>
                            HSD: <?php echo date('d/m/Y', strtotime($promo['end_at'])); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="text-center">
                        <div class="bg-gray-100 border-2 border-dashed border-gray-300 rounded-lg px-3 py-2 mb-2">
                            <span class="font-mono font-bold text-primary-600 text-lg"><?php echo htmlspecialchars($promo['code']); ?></span>
                        </div>
                        <button onclick="copyCode('<?php echo htmlspecialchars($promo['code']); ?>')" 
                                class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                            <i class="fas fa-copy mr-1"></i>Sao chép
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Promotions Section -->
<section class="py-12">
    <div class="container mx-auto px-4">
        
        <?php if (empty($products)): ?>
            <!-- Empty State -->
            <div class="text-center py-16">
                <i class="fas fa-tags text-gray-300 text-6xl mb-4"></i>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Chưa có chương trình khuyến mãi</h3>
                <p class="text-gray-600 mb-6">Hãy quay lại sau để không bỏ lỡ các ưu đãi hấp dẫn</p>
                <a href="<?php echo SITE_URL; ?>/pages/products.php" 
                   class="inline-block bg-primary-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-primary-700">
                    Xem tất cả sản phẩm
                </a>
            </div>
        <?php else: ?>
            <!-- Products Count -->
            <div class="flex items-center justify-between mb-6">
                <p class="text-gray-600">
                    Tìm thấy <span class="font-bold text-gray-900"><?php echo $totalProducts; ?></span> sản phẩm đang khuyến mãi
                </p>
            </div>

            <!-- Products Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <?php foreach ($products as $product): ?>
                <?php include __DIR__ . '/../components/product-card.php'; ?>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="flex justify-center items-center gap-2">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" 
                   class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="px-4 py-2 bg-primary-600 text-white rounded-lg font-medium"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>" 
                   class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<script>
function copyCode(code) {
    navigator.clipboard.writeText(code).then(function() {
        alert('Đã sao chép mã: ' + code);
    });
}
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
