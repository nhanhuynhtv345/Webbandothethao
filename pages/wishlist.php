<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/pages/login.php');
}

$pageTitle = 'Sản phẩm yêu thích';
$db = getDB();

// Get wishlist items
$stmt = $db->prepare("
    SELECT sp.*, th.ten_thuong_hieu,
           (SELECT url FROM hinh_anh_san_pham WHERE san_pham_id = sp.id AND is_primary = 1 LIMIT 1) as anh_dai_dien,
           yt.created_at as added_at
    FROM yeu_thich yt
    INNER JOIN san_pham sp ON yt.san_pham_id = sp.id
    LEFT JOIN thuong_hieu th ON sp.thuong_hieu_id = th.id
    WHERE yt.nguoi_dung_id = ?
    ORDER BY yt.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$wishlistItems = $stmt->fetchAll();

include __DIR__ . '/../components/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-sm">
            <a href="<?php echo SITE_URL; ?>/index.php" class="text-gray-600 hover:text-primary-600">Trang chủ</a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-900">Yêu thích</span>
        </nav>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Sản phẩm yêu thích</h1>
    
    <?php if (empty($wishlistItems)): ?>
        <div class="bg-white rounded-lg shadow-md p-12 text-center">
            <i class="fas fa-heart text-gray-400 text-6xl mb-4"></i>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Chưa có sản phẩm yêu thích</h3>
            <p class="text-gray-600 mb-6">Khám phá và thêm sản phẩm yêu thích của bạn</p>
            <a href="<?php echo SITE_URL; ?>/pages/products.php" class="inline-flex items-center justify-center bg-primary-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-primary-700 transition-all duration-200">
                <i class="fas fa-shopping-bag mr-2"></i>
                Khám phá ngay
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($wishlistItems as $product): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden group relative">
                <button onclick="removeFromWishlist(<?php echo $product['id']; ?>)" 
                        class="absolute top-2 right-2 z-10 w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-lg text-red-500 hover:bg-red-500 hover:text-white transition-all duration-200">
                    <i class="fas fa-times"></i>
                </button>
                
                <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $product['id']; ?>">
                    <?php if ($product['anh_dai_dien']): ?>
                        <img src="<?php echo UPLOAD_URL . '/' . $product['anh_dai_dien']; ?>" 
                             alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>"
                             class="w-full h-64 object-cover group-hover:scale-110 transition-transform duration-300">
                    <?php else: ?>
                        <div class="w-full h-64 bg-gray-200 flex items-center justify-center">
                            <i class="fas fa-image text-gray-400 text-4xl"></i>
                        </div>
                    <?php endif; ?>
                </a>
                
                <div class="p-4">
                    <?php if ($product['ten_thuong_hieu']): ?>
                        <p class="text-sm text-gray-500 mb-1"><?php echo htmlspecialchars($product['ten_thuong_hieu']); ?></p>
                    <?php endif; ?>
                    
                    <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">
                        <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $product['id']; ?>" 
                           class="hover:text-primary-600">
                            <?php echo htmlspecialchars($product['ten_san_pham']); ?>
                        </a>
                    </h3>
                    
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-2xl font-bold text-primary-600"><?php echo formatCurrency($product['gia_ban']); ?></span>
                        <?php if ($product['gia_goc'] && $product['gia_goc'] > $product['gia_ban']): ?>
                            <span class="text-lg text-gray-500 line-through"><?php echo formatCurrency($product['gia_goc']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <button onclick="addToCart(<?php echo $product['id']; ?>)" 
                            class="w-full bg-primary-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-primary-700 active:bg-primary-800 transition-all duration-200 inline-flex items-center justify-center">
                        <i class="fas fa-shopping-cart mr-2"></i>
                        Thêm vào giỏ
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function removeFromWishlist(productId) {
    if (!confirm('Bạn có chắc muốn xóa sản phẩm này khỏi danh sách yêu thích?')) return;
    
    fetch(`${window.location.origin}/Web/api/wishlist.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'remove',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showNotification('error', data.message || 'Có lỗi xảy ra!');
        }
    });
}
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
