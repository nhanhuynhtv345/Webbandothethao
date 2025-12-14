<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Trang chủ';
$metaDescription = 'NTH SPORT - Đồ thể thao chính hãng, giá tốt nhất';

// Get database connection
$db = getDB();

// Get featured products - sản phẩm có điểm đánh giá cao nhất (dùng trường có sẵn trong bảng san_pham)
$stmtFeatured = $db->query("
    SELECT sp.*, dm.ten_danh_muc, th.ten_thuong_hieu,
           (SELECT url FROM hinh_anh_san_pham WHERE san_pham_id = sp.id AND is_primary = 1 LIMIT 1) as anh_dai_dien
    FROM san_pham sp
    LEFT JOIN danh_muc dm ON sp.danh_muc_id = dm.id
    LEFT JOIN thuong_hieu th ON sp.thuong_hieu_id = th.id
    WHERE sp.trang_thai = 'active' AND sp.so_luot_danh_gia > 0
    ORDER BY sp.diem_trung_binh DESC, sp.so_luot_danh_gia DESC
    LIMIT 8
");
$featuredProducts = $stmtFeatured->fetchAll();

// Get categories
$stmtCategories = $db->query("
    SELECT * FROM danh_muc 
    WHERE parent_id IS NULL AND active = 1 
    ORDER BY sort_order ASC 
    LIMIT 6
");
$categories = $stmtCategories->fetchAll();

// Get brands
$stmtBrands = $db->query("
    SELECT * FROM thuong_hieu 
    WHERE active = 1 
    ORDER BY ten_thuong_hieu ASC 
    LIMIT 8
");
$brands = $stmtBrands->fetchAll();

// Get active banners
$stmtBanners = $db->query("
    SELECT * FROM banner 
    WHERE active = 1 
    AND vi_tri = 'home_slider'
    AND (start_at IS NULL OR start_at <= NOW())
    AND (end_at IS NULL OR end_at >= NOW())
    ORDER BY sort_order ASC
    LIMIT 5
");
$banners = $stmtBanners->fetchAll();

include __DIR__ . '/components/header.php';
?>

<?php if (isset($_GET['logout']) && $_GET['logout'] === 'success'): ?>
<div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 mb-4 container mx-auto mt-4 rounded" role="alert">
    <div class="flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <span>Đăng xuất thành công! Hẹn gặp lại bạn.</span>
    </div>
</div>
<?php endif; ?>

<!-- Hero Slider -->
<section class="relative">
    <div class="swiper heroSwiper">
        <div class="swiper-wrapper">
            <?php if (empty($banners)): ?>
                <!-- Default Banner -->
                <div class="swiper-slide">
                    <div class="relative h-[500px] bg-gradient-to-r from-primary-600 to-primary-800">
                        <div class="container mx-auto px-4 h-full flex items-center justify-between">
                            <div class="text-white max-w-2xl">
                                <h1 class="text-5xl font-bold mb-4">Chào mừng đến với <?php echo SITE_NAME; ?></h1>
                                <p class="text-xl mb-8">Đồ thể thao chính hãng - Giá tốt nhất thị trường</p>
                                <a href="<?php echo SITE_URL; ?>/pages/products.php" class="inline-flex items-center justify-center bg-white text-primary-600 px-8 py-3 rounded-lg font-bold text-lg hover:bg-gray-100 transition-all duration-200 shadow-lg">
                                    Mua sắm ngay <i class="fas fa-arrow-right ml-2"></i>
                                </a>
                            </div>
                            <div class="hidden lg:block">
                                <img src="img/anhshop.jpg" 
                                     alt="Thể thao" 
                                     class="w-110 h-96 object-cover rounded-2xl shadow-2xl transform hover:scale-105 transition-transform duration-300">
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($banners as $banner): ?>
                <div class="swiper-slide">
                    <div class="relative h-[500px]">
                        <img src="<?php echo UPLOAD_URL . '/' . $banner['image_url']; ?>" 
                             alt="<?php echo htmlspecialchars($banner['title']); ?>"
                             class="w-full h-full object-cover">
                        <?php if ($banner['link_url']): ?>
                        <a href="<?php echo $banner['link_url']; ?>" class="absolute inset-0"></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-pagination"></div>
    </div>
</section>

<!-- Features -->
<section class="py-10 bg-white border-b">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="flex items-center gap-4 p-4 rounded-xl hover:bg-primary-50 transition-all duration-300 group">
                <div class="w-16 h-16 bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <i class="fas fa-shipping-fast text-white text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900">Miễn phí vận chuyển</h3>
                    <p class="text-sm text-gray-600">Đơn hàng từ 500K</p>
                </div>
            </div>
            <div class="flex items-center gap-4 p-4 rounded-xl hover:bg-green-50 transition-all duration-300 group">
                <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <i class="fas fa-undo text-white text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900">Đổi trả dễ dàng</h3>
                    <p class="text-sm text-gray-600">Trong vòng 7 ngày</p>
                </div>
            </div>
            <div class="flex items-center gap-4 p-4 rounded-xl hover:bg-orange-50 transition-all duration-300 group">
                <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <i class="fas fa-shield-alt text-white text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900">Hàng chính hãng</h3>
                    <p class="text-sm text-gray-600">100% authentic</p>
                </div>
            </div>
            <div class="flex items-center gap-4 p-4 rounded-xl hover:bg-purple-50 transition-all duration-300 group">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <i class="fas fa-headset text-white text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900">Hỗ trợ 24/7</h3>
                    <p class="text-sm text-gray-600">Tư vấn nhiệt tình</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories -->
<section class="py-16 bg-gradient-to-b from-gray-50 to-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-900 mb-3">Danh mục sản phẩm</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Khám phá đa dạng các sản phẩm thể thao chất lượng cao</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
            <?php foreach ($categories as $index => $category): ?>
            <a href="<?php echo SITE_URL; ?>/pages/products.php?danh_muc=<?php echo $category['slug']; ?>" 
               class="category-card bg-white rounded-2xl p-6 text-center shadow-sm hover:shadow-xl border border-gray-100"
               style="animation-delay: <?php echo $index * 0.1; ?>s">
                <div class="category-icon w-20 h-20 bg-gradient-to-br from-primary-100 to-primary-200 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="<?php echo $category['icon'] ?? 'fas fa-box'; ?> text-primary-600 text-3xl"></i>
                </div>
                <h3 class="font-bold text-gray-900"><?php echo htmlspecialchars($category['ten_danh_muc']); ?></h3>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products -->
<?php if (!empty($featuredProducts)): ?>
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between mb-10">
            <div>
                <h2 class="text-4xl font-bold text-gray-900 mb-2">Sản phẩm nổi bật</h2>
                <p class="text-gray-600">Những sản phẩm được yêu thích nhất</p>
            </div>
            <a href="<?php echo SITE_URL; ?>/pages/products.php" 
               class="btn-primary inline-flex items-center gap-2 bg-primary-600 text-white px-6 py-3 rounded-full font-semibold hover:bg-primary-700 shadow-lg hover:shadow-xl">
                Xem tất cả <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php foreach ($featuredProducts as $product): ?>
            <?php include __DIR__ . '/components/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
// Initialize Swiper
const heroSwiper = new Swiper('.heroSwiper', {
    loop: true,
    autoplay: {
        delay: 5000,
        disableOnInteraction: false,
    },
    pagination: {
        el: '.swiper-pagination',
        clickable: true,
    },
    navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
    },
});
</script>

<!-- Brands -->
<section class="py-16 bg-gradient-to-b from-white to-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-900 mb-3">Thương hiệu nổi tiếng</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Sản phẩm chính hãng từ các thương hiệu hàng đầu thế giới</p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-6">
            <!-- Nike -->
            <a href="<?php echo SITE_URL; ?>/pages/products.php?thuong_hieu=nike" 
               class="bg-white rounded-lg p-6 flex items-center justify-center hover:shadow-xl transition-all duration-300 border border-gray-200 group">
                <img src="https://upload.wikimedia.org/wikipedia/commons/a/a6/Logo_NIKE.svg" 
                     alt="Nike" 
                     class="h-12 w-auto filter grayscale group-hover:grayscale-0 transition">
            </a>
            
            <!-- Adidas -->
            <a href="<?php echo SITE_URL; ?>/pages/products.php?thuong_hieu=adidas" 
               class="bg-white rounded-lg p-6 flex items-center justify-center hover:shadow-xl transition-all duration-300 border border-gray-200 group">
                <img src="https://upload.wikimedia.org/wikipedia/commons/2/20/Adidas_Logo.svg" 
                     alt="Adidas" 
                     class="h-12 w-auto filter grayscale group-hover:grayscale-0 transition">
            </a>
            
            <!-- Puma -->
            <a href="<?php echo SITE_URL; ?>/pages/products.php?thuong_hieu=puma" 
               class="bg-white rounded-lg p-6 flex items-center justify-center hover:shadow-xl transition-all duration-300 border border-gray-200 group">
                <img src="https://upload.wikimedia.org/wikipedia/en/d/da/Puma_complete_logo.svg" 
                     alt="Puma" 
                     class="h-12 w-auto filter grayscale group-hover:grayscale-0 transition">
            </a>
            
            <!-- Under Armour -->
            <a href="<?php echo SITE_URL; ?>/pages/products.php?thuong_hieu=under-armour" 
               class="bg-white rounded-lg p-6 flex items-center justify-center hover:shadow-xl transition-all duration-300 border border-gray-200 group">
                <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Under_armour_logo.svg" 
                     alt="Under Armour" 
                     class="h-12 w-auto filter grayscale group-hover:grayscale-0 transition">
            </a>
            
            <!-- New Balance -->
            <a href="<?php echo SITE_URL; ?>/pages/products.php?thuong_hieu=new-balance" 
               class="bg-white rounded-lg p-6 flex items-center justify-center hover:shadow-xl transition-all duration-300 border border-gray-200 group">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/ea/New_Balance_logo.svg/2560px-New_Balance_logo.svg.png" 
                     alt="New Balance" 
                     class="h-20 w-auto object-contain filter grayscale group-hover:grayscale-0 transition">
            </a>
            
            <!-- Reebok -->
            <a href="<?php echo SITE_URL; ?>/pages/products.php?thuong_hieu=reebok" 
               class="bg-white rounded-lg p-6 flex items-center justify-center hover:shadow-xl transition-all duration-300 border border-gray-200 group">
                <img src="https://logos-world.net/wp-content/uploads/2020/04/Reebok-Logo.png" 
                     alt="Reebok" 
                     class="h-16 w-auto object-contain filter grayscale group-hover:grayscale-0 transition">
            </a>
            
            <!-- Converse -->
            <a href="<?php echo SITE_URL; ?>/pages/products.php?thuong_hieu=converse" 
               class="bg-white rounded-lg p-6 flex items-center justify-center hover:shadow-xl transition-all duration-300 border border-gray-200 group">
                <img src="https://upload.wikimedia.org/wikipedia/commons/3/30/Converse_logo.svg" 
                     alt="Converse" 
                     class="h-12 w-auto filter grayscale group-hover:grayscale-0 transition">
            </a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/components/footer.php'; ?>
