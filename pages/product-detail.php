<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$productId = intval($_GET['id'] ?? 0);

if (!$productId) {
    redirect(SITE_URL . '/pages/products.php');
}

// Get product details
$stmt = $db->prepare("
    SELECT sp.*, dm.ten_danh_muc, dm.slug as danh_muc_slug,
           th.ten_thuong_hieu, th.slug as thuong_hieu_slug
    FROM san_pham sp
    LEFT JOIN danh_muc dm ON sp.danh_muc_id = dm.id
    LEFT JOIN thuong_hieu th ON sp.thuong_hieu_id = th.id
    WHERE sp.id = ? AND sp.trang_thai = 'active'
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    redirect(SITE_URL . '/pages/products.php');
}

// Update view count
$db->prepare("UPDATE san_pham SET luot_xem = luot_xem + 1 WHERE id = ?")->execute([$productId]);

// Get product images
$stmtImages = $db->prepare("SELECT * FROM hinh_anh_san_pham WHERE san_pham_id = ? ORDER BY is_primary DESC, sort_order ASC");
$stmtImages->execute([$productId]);
$images = $stmtImages->fetchAll();

// Get product variants
$stmtVariants = $db->prepare("SELECT * FROM bien_the_san_pham WHERE san_pham_id = ? AND active = 1 ORDER BY size ASC");
$stmtVariants->execute([$productId]);
$variants = $stmtVariants->fetchAll();

// Get product reviews
$stmtReviews = $db->prepare("
    SELECT dg.*, nd.avt
    FROM danh_gia_san_pham dg
    LEFT JOIN nguoi_dung nd ON dg.nguoi_dung_id = nd.id
    WHERE dg.san_pham_id = ? AND dg.trang_thai = 'approved'
    ORDER BY dg.created_at DESC
    LIMIT 10
");
$stmtReviews->execute([$productId]);
$reviews = $stmtReviews->fetchAll();

// Get related products
$stmtRelated = $db->prepare("
    SELECT sp.*, 
           (SELECT url FROM hinh_anh_san_pham WHERE san_pham_id = sp.id AND is_primary = 1 LIMIT 1) as anh_dai_dien
    FROM san_pham sp
    WHERE sp.danh_muc_id = ? AND sp.id != ? AND sp.trang_thai = 'active'
    ORDER BY RAND()
    LIMIT 4
");
$stmtRelated->execute([$product['danh_muc_id'], $productId]);
$relatedProducts = $stmtRelated->fetchAll();

$pageTitle = $product['ten_san_pham'];
$metaDescription = $product['mo_ta_ngan'] ?? '';

include __DIR__ . '/../components/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-sm">
            <a href="<?php echo SITE_URL; ?>/index.php" class="text-gray-600 hover:text-primary-600">Trang chủ</a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <a href="<?php echo SITE_URL; ?>/pages/products.php" class="text-gray-600 hover:text-primary-600">Sản phẩm</a>
            <?php if ($product['ten_danh_muc']): ?>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <a href="<?php echo SITE_URL; ?>/pages/products.php?danh_muc=<?php echo $product['danh_muc_slug']; ?>" 
               class="text-gray-600 hover:text-primary-600">
                <?php echo htmlspecialchars($product['ten_danh_muc']); ?>
            </a>
            <?php endif; ?>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-900"><?php echo htmlspecialchars($product['ten_san_pham']); ?></span>
        </nav>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md p-8 mb-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Product Images -->
            <div>
                <div class="mb-4">
                    <?php if (!empty($images)): ?>
                        <img id="mainImage" src="<?php echo UPLOAD_URL . '/' . $images[0]['url']; ?>" 
                             alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>"
                             class="w-full rounded-lg">
                    <?php else: ?>
                        <div class="w-full aspect-square bg-gray-200 rounded-lg flex items-center justify-center">
                            <i class="fas fa-image text-gray-400 text-6xl"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (count($images) > 1): ?>
                <div class="grid grid-cols-5 gap-2">
                    <?php foreach ($images as $img): ?>
                    <img src="<?php echo UPLOAD_URL . '/' . $img['url']; ?>" 
                         alt="<?php echo $img['alt_text']; ?>"
                         onclick="document.getElementById('mainImage').src = this.src"
                         class="w-full aspect-square object-cover rounded cursor-pointer border-2 border-transparent hover:border-primary-600">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Info -->
            <div>
                <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($product['ten_san_pham']); ?></h1>
                
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex items-center gap-2">
                        <div class="star-rating">
                            <?php 
                            $rating = $product['diem_trung_binh'];
                            for ($i = 1; $i <= 5; $i++): 
                                if ($i <= $rating): ?>
                                    <i class="fas fa-star"></i>
                                <?php elseif ($i - 0.5 <= $rating): ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif;
                            endfor; ?>
                        </div>
                        <span class="text-sm text-gray-600">(<?php echo $product['so_luot_danh_gia']; ?> đánh giá)</span>
                    </div>
                    <span class="text-sm text-gray-600">|</span>
                    <span class="text-sm text-gray-600">Đã bán: <?php echo $product['luot_ban']; ?></span>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <div class="flex items-center gap-4">
                        <span class="text-4xl font-bold text-primary-600"><?php echo formatCurrency($product['gia_ban']); ?></span>
                        <?php if ($product['gia_goc'] && $product['gia_goc'] > $product['gia_ban']): ?>
                            <span class="text-2xl text-gray-500 line-through"><?php echo formatCurrency($product['gia_goc']); ?></span>
                            <span class="badge badge-danger text-base">
                                -<?php echo calculateDiscount($product['gia_goc'], $product['gia_ban']); ?>%
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($product['mo_ta_ngan']): ?>
                <div class="mb-6">
                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($product['mo_ta_ngan'])); ?></p>
                </div>
                <?php endif; ?>
                
                <!-- Product Variants -->
                <?php if (!empty($variants)): ?>
                <div class="mb-6">
                    <h3 class="font-semibold mb-3">Chọn phân loại:</h3>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($variants as $variant): ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="variant" value="<?php echo $variant['id']; ?>" class="hidden peer">
                            <div class="px-4 py-2 border-2 border-gray-300 rounded-lg peer-checked:border-primary-600 peer-checked:bg-primary-50 hover:border-primary-400">
                                <?php echo htmlspecialchars($variant['ten_bien_the']); ?>
                                <?php if ($variant['gia_ban']): ?>
                                <span class="text-sm text-gray-600">(+<?php echo formatCurrency($variant['gia_ban']); ?>)</span>
                                <?php endif; ?>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Quantity -->
                <div class="mb-6">
                    <h3 class="font-semibold mb-3">Số lượng:</h3>
                    <div class="flex items-center gap-3">
                        <button onclick="decreaseQty()" class="w-10 h-10 border border-gray-300 rounded-lg hover:bg-gray-100">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" id="quantity" value="1" min="1" max="<?php echo $product['so_luong_ton']; ?>" 
                               class="w-20 text-center border border-gray-300 rounded-lg py-2">
                        <button onclick="increaseQty()" class="w-10 h-10 border border-gray-300 rounded-lg hover:bg-gray-100">
                            <i class="fas fa-plus"></i>
                        </button>
                        <span class="text-sm text-gray-600">
                            (Còn <?php echo $product['so_luong_ton']; ?> sản phẩm)
                        </span>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="flex gap-4 mb-6">
                    <button onclick="addToCartFromDetail()" class="flex-1 bg-primary-600 text-white px-4 py-3 rounded-lg font-medium hover:bg-primary-700 active:bg-primary-800 transition-all duration-200 inline-flex items-center justify-center text-lg">
                        <i class="fas fa-shopping-cart mr-2"></i>
                        Thêm vào giỏ hàng
                    </button>
                    <button onclick="buyNow()" class="flex-1 bg-red-600 text-white px-4 py-3 rounded-lg font-medium hover:bg-red-700 active:bg-red-800 transition-all duration-200 inline-flex items-center justify-center text-lg">
                        Mua ngay
                    </button>
                </div>
                
                <!-- Additional Info -->
                <div class="border-t pt-6 space-y-3">
                    <?php if ($product['ten_thuong_hieu']): ?>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-tag text-gray-400"></i>
                        <span class="text-gray-600">Thương hiệu:</span>
                        <a href="<?php echo SITE_URL; ?>/pages/products.php?thuong_hieu=<?php echo $product['thuong_hieu_slug']; ?>" 
                           class="text-primary-600 font-semibold hover:underline">
                            <?php echo htmlspecialchars($product['ten_thuong_hieu']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-barcode text-gray-400"></i>
                        <span class="text-gray-600">Mã sản phẩm:</span>
                        <span class="font-semibold"><?php echo htmlspecialchars($product['ma_san_pham']); ?></span>
                    </div>
                    <?php if ($product['bao_hanh']): ?>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-shield-alt text-gray-400"></i>
                        <span class="text-gray-600">Bảo hành:</span>
                        <span class="font-semibold"><?php echo htmlspecialchars($product['bao_hanh']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Product Details Tabs -->
    <div class="bg-white rounded-lg shadow-md p-8 mb-8">
        <div class="border-b mb-6">
            <nav class="flex gap-8">
                <button onclick="showTab('description')" id="tab-description" 
                        class="pb-4 border-b-2 border-primary-600 text-primary-600 font-semibold">
                    Mô tả sản phẩm
                </button>
                <button onclick="showTab('specs')" id="tab-specs" 
                        class="pb-4 border-b-2 border-transparent text-gray-600 font-semibold hover:text-primary-600">
                    Thông số kỹ thuật
                </button>
                <button onclick="showTab('reviews')" id="tab-reviews" 
                        class="pb-4 border-b-2 border-transparent text-gray-600 font-semibold hover:text-primary-600">
                    Đánh giá (<?php echo $product['so_luot_danh_gia']; ?>)
                </button>
            </nav>
        </div>
        
        <div id="content-description" class="tab-content">
            <div class="prose max-w-none">
                <?php echo nl2br(htmlspecialchars($product['mo_ta_chi_tiet'] ?? $product['mo_ta_ngan'] ?? 'Đang cập nhật...')); ?>
            </div>
        </div>
        
        <div id="content-specs" class="tab-content hidden">
            <table class="w-full">
                <?php if ($product['chat_lieu']): ?>
                <tr class="border-b">
                    <td class="py-3 text-gray-600 font-medium w-1/3">Chất liệu</td>
                    <td class="py-3"><?php echo htmlspecialchars($product['chat_lieu']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($product['mau_sac']): ?>
                <tr class="border-b">
                    <td class="py-3 text-gray-600 font-medium">Màu sắc</td>
                    <td class="py-3"><?php echo htmlspecialchars($product['mau_sac']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($product['kich_thuoc']): ?>
                <tr class="border-b">
                    <td class="py-3 text-gray-600 font-medium">Kích thước</td>
                    <td class="py-3"><?php echo htmlspecialchars($product['kich_thuoc']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($product['trong_luong']): ?>
                <tr class="border-b">
                    <td class="py-3 text-gray-600 font-medium">Trọng lượng</td>
                    <td class="py-3"><?php echo $product['trong_luong']; ?> g</td>
                </tr>
                <?php endif; ?>
                <?php if ($product['xuat_xu']): ?>
                <tr class="border-b">
                    <td class="py-3 text-gray-600 font-medium">Xuất xứ</td>
                    <td class="py-3"><?php echo htmlspecialchars($product['xuat_xu']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <div id="content-reviews" class="tab-content hidden">
            <!-- Review Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8 pb-8 border-b">
                <div class="text-center">
                    <div class="text-5xl font-bold text-primary-600 mb-2"><?php echo number_format($product['diem_trung_binh'], 1); ?></div>
                    <div class="star-rating text-xl mb-2">
                        <?php 
                        $avgRating = $product['diem_trung_binh'];
                        for ($i = 1; $i <= 5; $i++): 
                            if ($i <= $avgRating): ?>
                                <i class="fas fa-star"></i>
                            <?php elseif ($i - 0.5 <= $avgRating): ?>
                                <i class="fas fa-star-half-alt"></i>
                            <?php else: ?>
                                <i class="far fa-star"></i>
                            <?php endif;
                        endfor; ?>
                    </div>
                    <p class="text-gray-600"><?php echo $product['so_luot_danh_gia']; ?> đánh giá</p>
                </div>
                
                <div class="md:col-span-2">
                    <?php if (isLoggedIn()): ?>
                        <?php
                        // Check if user already reviewed
                        $stmtCheck = $db->prepare("SELECT id FROM danh_gia_san_pham WHERE san_pham_id = ? AND nguoi_dung_id = ?");
                        $stmtCheck->execute([$productId, $_SESSION['user_id']]);
                        $hasReviewed = $stmtCheck->fetch();
                        
                        // Check if user purchased - cho phép đánh giá khi đơn đã giao hoặc hoàn thành
                        $stmtPurchase = $db->prepare("
                            SELECT dh.id FROM don_hang dh
                            INNER JOIN chi_tiet_don_hang ct ON dh.id = ct.don_hang_id
                            WHERE dh.nguoi_dung_id = ? AND ct.san_pham_id = ? AND dh.trang_thai IN ('delivered', 'completed')
                        ");
                        $stmtPurchase->execute([$_SESSION['user_id'], $productId]);
                        $hasPurchased = $stmtPurchase->fetch();
                        ?>
                        
                        <?php if ($hasReviewed): ?>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                                <i class="fas fa-check-circle text-green-500 text-2xl mb-2"></i>
                                <p class="text-green-700">Bạn đã đánh giá sản phẩm này</p>
                            </div>
                        <?php elseif (!$hasPurchased): ?>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                                <i class="fas fa-info-circle text-yellow-500 text-2xl mb-2"></i>
                                <p class="text-yellow-700">Bạn cần mua sản phẩm này trước khi đánh giá</p>
                            </div>
                        <?php else: ?>
                            <!-- Review Form -->
                            <div class="bg-gray-50 rounded-lg p-6">
                                <h4 class="font-semibold mb-4">Viết đánh giá của bạn</h4>
                                <form id="reviewForm">
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Đánh giá sao <span class="text-red-500">*</span></label>
                                        <div class="flex gap-2" id="starRating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <button type="button" onclick="setRating(<?php echo $i; ?>)" class="text-3xl text-gray-300 hover:text-yellow-400 transition star-btn" data-star="<?php echo $i; ?>">
                                                <i class="fas fa-star"></i>
                                            </button>
                                            <?php endfor; ?>
                                        </div>
                                        <input type="hidden" name="rating" id="ratingInput" value="0">
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Tiêu đề (không bắt buộc)</label>
                                        <input type="text" name="title" id="reviewTitle" placeholder="Tóm tắt đánh giá của bạn"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Nội dung đánh giá <span class="text-red-500">*</span></label>
                                        <textarea name="content" id="reviewContent" rows="4" placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm này..."
                                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"></textarea>
                                    </div>
                                    
                                    <button type="submit" class="bg-primary-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-primary-700 transition">
                                        <i class="fas fa-paper-plane mr-2"></i>Gửi đánh giá
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="bg-gray-50 rounded-lg p-6 text-center">
                            <p class="text-gray-600 mb-4">Đăng nhập để viết đánh giá</p>
                            <a href="<?php echo SITE_URL; ?>/pages/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                               class="inline-block bg-primary-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-primary-700 transition">
                                Đăng nhập
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Reviews List -->
            <?php if (empty($reviews)): ?>
                <p class="text-gray-600 text-center py-8">Chưa có đánh giá nào</p>
            <?php else: ?>
                <h4 class="font-semibold mb-4">Tất cả đánh giá</h4>
                <div class="space-y-6">
                    <?php foreach ($reviews as $review): ?>
                    <div class="border-b pb-6">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center overflow-hidden">
                                <?php if (!empty($review['avt'])): ?>
                                    <?php 
                                    // Kiểm tra nếu là URL đầy đủ (Google avatar) hay đường dẫn local
                                    $avtUrl = (strpos($review['avt'], 'http') === 0) ? $review['avt'] : UPLOAD_URL . '/' . $review['avt'];
                                    ?>
                                    <img src="<?php echo $avtUrl; ?>" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <span class="w-full h-full bg-primary-500 text-white font-bold text-lg items-center justify-center hidden">
                                        <?php echo strtoupper(mb_substr($review['ho_ten'], 0, 1)); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="w-full h-full bg-primary-500 text-white font-bold text-lg flex items-center justify-center">
                                        <?php echo strtoupper(mb_substr($review['ho_ten'], 0, 1)); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold"><?php echo htmlspecialchars($review['ho_ten']); ?></h4>
                                <div class="star-rating my-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $review['diem_danh_gia'] ? '' : 'text-gray-300'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-sm text-gray-600 mb-2"><?php echo formatDate($review['created_at']); ?></p>
                                <?php if ($review['tieu_de']): ?>
                                    <h5 class="font-semibold mb-1"><?php echo htmlspecialchars($review['tieu_de']); ?></h5>
                                <?php endif; ?>
                                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($review['noi_dung'])); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6">Sản phẩm liên quan</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($relatedProducts as $product): ?>
            <?php include __DIR__ . '/../components/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
const productId = <?php echo $productId; ?>;
const maxQty = <?php echo $product['so_luong_ton']; ?>;

function increaseQty() {
    const input = document.getElementById('quantity');
    if (parseInt(input.value) < maxQty) {
        input.value = parseInt(input.value) + 1;
    }
}

function decreaseQty() {
    const input = document.getElementById('quantity');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
    }
}

function addToCartFromDetail() {
    const quantity = parseInt(document.getElementById('quantity').value);
    const variantInput = document.querySelector('input[name="variant"]:checked');
    const variantId = variantInput ? variantInput.value : null;
    
    addToCart(productId, variantId, quantity);
}

function buyNow() {
    addToCartFromDetail();
    setTimeout(() => {
        window.location.href = '<?php echo SITE_URL; ?>/pages/cart.php';
    }, 500);
}

function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('[id^="tab-"]').forEach(el => {
        el.classList.remove('border-primary-600', 'text-primary-600');
        el.classList.add('border-transparent', 'text-gray-600');
    });
    
    // Show selected tab
    document.getElementById('content-' + tabName).classList.remove('hidden');
    document.getElementById('tab-' + tabName).classList.remove('border-transparent', 'text-gray-600');
    document.getElementById('tab-' + tabName).classList.add('border-primary-600', 'text-primary-600');
}

// Review functions
let selectedRating = 0;

function setRating(rating) {
    selectedRating = rating;
    document.getElementById('ratingInput').value = rating;
    
    document.querySelectorAll('.star-btn').forEach((btn, index) => {
        if (index < rating) {
            btn.classList.remove('text-gray-300');
            btn.classList.add('text-yellow-400');
        } else {
            btn.classList.remove('text-yellow-400');
            btn.classList.add('text-gray-300');
        }
    });
}

// Review form submit
const reviewForm = document.getElementById('reviewForm');
if (reviewForm) {
    reviewForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const rating = parseInt(document.getElementById('ratingInput').value);
        const title = document.getElementById('reviewTitle').value.trim();
        const content = document.getElementById('reviewContent').value.trim();
        
        if (rating < 1) {
            showNotification('error', 'Vui lòng chọn số sao đánh giá');
            return;
        }
        
        if (!content) {
            showNotification('error', 'Vui lòng nhập nội dung đánh giá');
            return;
        }
        
        const submitBtn = reviewForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang gửi...';
        
        fetch('<?php echo SITE_URL; ?>/api/review.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                product_id: productId,
                rating: rating,
                title: title,
                content: content
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification('success', data.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('error', data.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Gửi đánh giá';
            }
        })
        .catch(err => {
            showNotification('error', 'Có lỗi xảy ra');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Gửi đánh giá';
        });
    });
}
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
