<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Giỏ hàng';
$db = getDB();
$cartItems = [];
$total = 0;

// Only logged in users can access cart
if (!isLoggedIn()) {
    redirect('/Web/pages/login.php?redirect=' . urlencode('/Web/pages/cart.php'));
}

// Get cart from database
$stmt = $db->prepare("
    SELECT gh.*, sp.ten_san_pham, sp.gia_ban, sp.so_luong_ton, sp.slug,
           bienthe.ten_bien_the, bienthe.gia_ban as bien_the_gia,
           (SELECT url FROM hinh_anh_san_pham WHERE san_pham_id = sp.id AND is_primary = 1 LIMIT 1) as anh_dai_dien
    FROM gio_hang gh
    INNER JOIN san_pham sp ON gh.san_pham_id = sp.id
    LEFT JOIN bien_the_san_pham bienthe ON gh.bien_the_id = bienthe.id
    WHERE gh.nguoi_dung_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

// Calculate total
foreach ($cartItems as $item) {
    $price = $item['bien_the_gia'] ?? $item['gia_ban'];
    $total += $price * $item['so_luong'];
}

include __DIR__ . '/../components/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-sm">
            <a href="<?php echo SITE_URL; ?>/index.php" class="text-gray-600 hover:text-primary-600">Trang chủ</a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-900">Giỏ hàng</span>
        </nav>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Giỏ hàng của bạn</h1>
    
    <?php if (empty($cartItems)): ?>
        <div class="bg-white rounded-lg shadow-md p-12 text-center">
            <i class="fas fa-shopping-cart text-gray-400 text-6xl mb-4"></i>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Giỏ hàng trống</h3>
            <p class="text-gray-600 mb-6">Bạn chưa có sản phẩm nào trong giỏ hàng</p>
            <a href="<?php echo SITE_URL; ?>/pages/products.php" class="inline-flex items-center justify-center bg-primary-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-primary-700 transition-all duration-200">
                <i class="fas fa-shopping-bag mr-2"></i>
                Tiếp tục mua sắm
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cart Items -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Sản phẩm</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold text-gray-900">Đơn giá</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold text-gray-900">Số lượng</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold text-gray-900">Tổng</th>
                                <th class="px-6 py-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($cartItems as $item): 
                                $price = $item['bien_the_gia'] ?? $item['gia_ban'];
                                $subtotal = $price * $item['so_luong'];
                            ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $item['san_pham_id']; ?>">
                                            <?php if ($item['anh_dai_dien']): ?>
                                                <img src="<?php echo UPLOAD_URL . '/' . $item['anh_dai_dien']; ?>" 
                                                     alt="<?php echo htmlspecialchars($item['ten_san_pham']); ?>"
                                                     class="w-20 h-20 object-cover rounded">
                                            <?php else: ?>
                                                <div class="w-20 h-20 bg-gray-200 rounded flex items-center justify-center">
                                                    <i class="fas fa-image text-gray-400"></i>
                                                </div>
                                            <?php endif; ?>
                                        </a>
                                        <div>
                                            <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $item['san_pham_id']; ?>" 
                                               class="font-semibold text-gray-900 hover:text-primary-600">
                                                <?php echo htmlspecialchars($item['ten_san_pham']); ?>
                                            </a>
                                            <?php if (isset($item['ten_bien_the'])): ?>
                                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($item['ten_bien_the']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center font-semibold">
                                    <?php echo formatCurrency($price); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button onclick="updateCartQty(<?php echo $item['san_pham_id']; ?>, <?php echo $item['so_luong'] - 1; ?>)" 
                                                class="w-8 h-8 border border-gray-300 rounded hover:bg-gray-100">
                                            <i class="fas fa-minus text-xs"></i>
                                        </button>
                                        <input type="number" value="<?php echo $item['so_luong']; ?>" 
                                               min="1" max="<?php echo $item['so_luong_ton']; ?>"
                                               class="w-16 text-center border border-gray-300 rounded py-1"
                                               onchange="updateCartQty(<?php echo $item['san_pham_id']; ?>, this.value)">
                                        <button onclick="updateCartQty(<?php echo $item['san_pham_id']; ?>, <?php echo $item['so_luong'] + 1; ?>)" 
                                                class="w-8 h-8 border border-gray-300 rounded hover:bg-gray-100">
                                            <i class="fas fa-plus text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-primary-600">
                                    <?php echo formatCurrency($subtotal); ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button onclick="removeFromCart(<?php echo $item['san_pham_id']; ?>)" 
                                            class="text-red-600 hover:text-red-700">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-6">
                    <a href="<?php echo SITE_URL; ?>/pages/products.php" class="inline-flex items-center justify-center bg-gray-200 text-gray-900 px-4 py-2 rounded-lg font-medium hover:bg-gray-300 active:bg-gray-400 transition-all duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Tiếp tục mua sắm
                    </a>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div>
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                    <h3 class="text-xl font-bold mb-4">Tổng đơn hàng</h3>
                    
                    <div class="space-y-3 mb-4 pb-4 border-b">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tạm tính</span>
                            <span class="font-semibold"><?php echo formatCurrency($total); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Phí vận chuyển</span>
                            <span class="font-semibold">
                                <?php echo $total >= 500000 ? 'Miễn phí' : formatCurrency(30000); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between mb-6">
                        <span class="text-lg font-bold">Tổng cộng</span>
                        <span class="text-2xl font-bold text-primary-600">
                            <?php echo formatCurrency($total + ($total >= 500000 ? 0 : 30000)); ?>
                        </span>
                    </div>
                    
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo SITE_URL; ?>/pages/checkout.php" class="block w-full bg-primary-600 text-white px-4 py-3 rounded-lg font-medium hover:bg-primary-700 active:bg-primary-800 transition-all duration-200 text-center mb-3">
                            <i class="fas fa-credit-card mr-2"></i>
                            Tiến hành thanh toán
                        </a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/pages/login.php?redirect=/Web/pages/checkout.php" 
                           class="block w-full bg-primary-600 text-white px-4 py-3 rounded-lg font-medium hover:bg-primary-700 active:bg-primary-800 transition-all duration-200 text-center mb-3">
                            Đăng nhập để thanh toán
                        </a>
                    <?php endif; ?>
                    
                    <!-- Free Shipping Notice -->
                    <?php if ($total < 500000): ?>
                    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            Mua thêm <strong><?php echo formatCurrency(500000 - $total); ?></strong> để được miễn phí vận chuyển
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function updateCartQty(productId, quantity) {
    if (quantity < 1) return;
    
    fetch(`${window.location.origin}/Web/api/cart.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'update',
            product_id: productId,
            quantity: quantity
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

function removeFromCart(productId) {
    if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) return;
    
    fetch(`${window.location.origin}/Web/api/cart.php`, {
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
