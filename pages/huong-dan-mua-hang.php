<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Hướng dẫn mua hàng';
include __DIR__ . '/../components/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-sm">
            <a href="<?php echo SITE_URL; ?>/index.php" class="text-gray-600 hover:text-primary-600">Trang chủ</a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-900">Hướng dẫn mua hàng</span>
        </nav>
    </div>
</div>

<div class="container mx-auto px-4 py-12">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-8">
            <h1 class="text-3xl font-bold mb-6 text-primary-600">
                <i class="fas fa-shopping-cart mr-3"></i>Hướng dẫn mua hàng
            </h1>
            
            <div class="prose max-w-none">
                <p class="text-lg mb-8">
                    Hướng dẫn chi tiết các bước mua hàng tại <?php echo SITE_NAME; ?>
                </p>

                <div class="space-y-8">
                    <!-- Bước 1 -->
                    <div class="border-l-4 border-primary-500 pl-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-primary-600 text-white rounded-full flex items-center justify-center font-bold">1</div>
                            <h2 class="text-2xl font-bold">Tìm kiếm sản phẩm</h2>
                        </div>
                        <p class="mb-3">Bạn có thể tìm sản phẩm theo nhiều cách:</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Thanh tìm kiếm:</strong> Nhập tên sản phẩm hoặc từ khóa ở thanh tìm kiếm trên header</li>
                            <li><strong>Danh mục:</strong> Chọn danh mục sản phẩm từ menu (Giày, Quần áo, Phụ kiện...)</li>
                            <li><strong>Thương hiệu:</strong> Lọc theo thương hiệu yêu thích (Nike, Adidas, Puma...)</li>
                            <li><strong>Bộ lọc giá:</strong> Sử dụng bộ lọc khoảng giá phù hợp với ngân sách</li>
                        </ul>
                        <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                            <p class="text-sm"><i class="fas fa-lightbulb text-yellow-500 mr-2"></i><strong>Mẹo:</strong> Xem trang <a href="/Web/pages/promotions.php" class="text-primary-600 underline">Khuyến mãi</a> để săn sản phẩm giảm giá!</p>
                        </div>
                    </div>

                    <!-- Bước 2 -->
                    <div class="border-l-4 border-primary-500 pl-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-primary-600 text-white rounded-full flex items-center justify-center font-bold">2</div>
                            <h2 class="text-2xl font-bold">Chọn sản phẩm & Thêm vào giỏ</h2>
                        </div>
                        <p class="mb-3">Khi tìm thấy sản phẩm ưng ý:</p>
                        <ol class="list-decimal pl-6 space-y-2">
                            <li>Click vào sản phẩm để xem chi tiết</li>
                            <li>Chọn size, màu sắc (nếu có)</li>
                            <li>Chọn số lượng muốn mua</li>
                            <li>Click nút <span class="bg-primary-600 text-white px-2 py-1 rounded text-sm">Thêm vào giỏ hàng</span></li>
                            <li>Tiếp tục mua sắm hoặc vào giỏ hàng để thanh toán</li>
                        </ol>
                        <div class="mt-4 grid md:grid-cols-2 gap-4">
                            <div class="border rounded-lg p-3">
                                <i class="fas fa-heart text-red-500 mr-2"></i>
                                <strong>Thêm yêu thích:</strong> Click icon trái tim để lưu sản phẩm xem sau
                            </div>
                            <div class="border rounded-lg p-3">
                                <i class="fas fa-share-alt text-blue-500 mr-2"></i>
                                <strong>Chia sẻ:</strong> Chia sẻ sản phẩm cho bạn bè qua mạng xã hội
                            </div>
                        </div>
                    </div>

                    <!-- Bước 3 -->
                    <div class="border-l-4 border-primary-500 pl-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-primary-600 text-white rounded-full flex items-center justify-center font-bold">3</div>
                            <h2 class="text-2xl font-bold">Kiểm tra giỏ hàng</h2>
                        </div>
                        <p class="mb-3">Trước khi thanh toán, hãy kiểm tra lại:</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>Sản phẩm, size, màu sắc đã chính xác chưa</li>
                            <li>Số lượng mỗi sản phẩm</li>
                            <li>Tổng tiền tạm tính</li>
                            <li>Xóa sản phẩm không muốn mua nữa</li>
                        </ul>
                        <div class="mt-4 p-4 bg-green-50 rounded-lg">
                            <p class="text-sm"><i class="fas fa-tag text-green-600 mr-2"></i>Nhập <strong>mã giảm giá</strong> (nếu có) để được ưu đãi thêm!</p>
                        </div>
                    </div>

                    <!-- Bước 4 -->
                    <div class="border-l-4 border-primary-500 pl-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-primary-600 text-white rounded-full flex items-center justify-center font-bold">4</div>
                            <h2 class="text-2xl font-bold">Đặt hàng & Thanh toán</h2>
                        </div>
                        <p class="mb-3">Điền thông tin giao hàng:</p>
                        <ul class="list-disc pl-6 space-y-2 mb-4">
                            <li>Họ tên người nhận</li>
                            <li>Số điện thoại</li>
                            <li>Địa chỉ nhận hàng (càng chi tiết càng tốt)</li>
                            <li>Ghi chú đơn hàng (nếu có yêu cầu đặc biệt)</li>
                        </ul>
                        <p class="mb-3">Chọn phương thức thanh toán:</p>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="border-2 border-gray-200 rounded-lg p-4 hover:border-primary-500 transition">
                                <div class="flex items-center gap-3 mb-2">
                                    <i class="fas fa-money-bill-wave text-green-600 text-2xl"></i>
                                    <h4 class="font-bold">COD</h4>
                                </div>
                                <p class="text-sm text-gray-600">Thanh toán khi nhận hàng</p>
                            </div>
                            <div class="border-2 border-gray-200 rounded-lg p-4 hover:border-primary-500 transition">
                                <div class="flex items-center gap-3 mb-2">
                                    <i class="fas fa-credit-card text-blue-600 text-2xl"></i>
                                    <h4 class="font-bold">Chuyển khoản</h4>
                                </div>
                                <p class="text-sm text-gray-600">Chuyển khoản ngân hàng</p>
                            </div>
                        </div>
                    </div>

                    <!-- Bước 5 -->
                    <div class="border-l-4 border-primary-500 pl-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-primary-600 text-white rounded-full flex items-center justify-center font-bold">5</div>
                            <h2 class="text-2xl font-bold">Xác nhận & Theo dõi đơn hàng</h2>
                        </div>
                        <p class="mb-3">Sau khi đặt hàng thành công:</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>Nhận email/SMS xác nhận đơn hàng</li>
                            <li>Shop sẽ liên hệ xác nhận lại trong 1-2 giờ</li>
                            <li>Theo dõi trạng thái đơn hàng tại mục <strong>Tài khoản > Đơn hàng</strong></li>
                            <li>Nhận hàng và kiểm tra kỹ trước khi thanh toán (COD)</li>
                        </ul>
                        <div class="mt-4 p-4 bg-purple-50 rounded-lg">
                            <p class="text-sm font-semibold mb-2">Thời gian giao hàng dự kiến:</p>
                            <ul class="text-sm space-y-1">
                                <li>• Nội thành TP.HCM: 1-2 ngày</li>
                                <li>• Tỉnh thành khác: 3-5 ngày</li>
                                <li>• Vùng xa: 5-7 ngày</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="mt-10 p-6 bg-gradient-to-r from-orange-50 to-red-50 rounded-lg border-2 border-orange-200">
                    <h3 class="text-xl font-bold mb-3 text-orange-800">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Lưu ý quan trọng
                    </h3>
                    <ul class="space-y-2 text-sm">
                        <li>✓ Kiểm tra kỹ size, màu sắc trước khi đặt</li>
                        <li>✓ Điền đầy đủ thông tin địa chỉ để ship nhanh</li>
                        <li>✓ Lưu mã đơn hàng để tra cứu và hỗ trợ</li>
                        <li>✓ Quay video khi mở hàng để bảo vệ quyền lợi</li>
                    </ul>
                </div>

                <div class="mt-8 p-6 bg-gradient-to-r from-primary-50 to-blue-50 rounded-lg">
                    <h3 class="text-xl font-bold mb-3">Cần hỗ trợ mua hàng?</h3>
                    <p class="mb-4">Đội ngũ tư vấn sẵn sàng hỗ trợ bạn 24/7:</p>
                    <div class="flex flex-wrap gap-4">
                        <a href="tel:<?php echo SITE_PHONE; ?>" class="inline-flex items-center gap-2 bg-white px-4 py-2 rounded-lg hover:shadow-md transition">
                            <i class="fas fa-phone text-primary-600"></i>
                            <span><?php echo SITE_PHONE; ?></span>
                        </a>
                        <a href="/Web/pages/contact.php" class="inline-flex items-center gap-2 bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition">
                            <i class="fas fa-comment"></i>
                            <span>Chat với tư vấn viên</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
