<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Chính sách bảo hành';
include __DIR__ . '/../components/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-sm">
            <a href="<?php echo SITE_URL; ?>/index.php" class="text-gray-600 hover:text-primary-600">Trang chủ</a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-900">Chính sách bảo hành</span>
        </nav>
    </div>
</div>

<div class="container mx-auto px-4 py-12">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-8">
            <h1 class="text-3xl font-bold mb-6 text-primary-600">
                <i class="fas fa-shield-alt mr-3"></i>Chính sách bảo hành
            </h1>
            
            <div class="prose max-w-none">
                <p class="text-lg mb-6">
                    Chúng tôi cam kết bảo hành sản phẩm chính hãng 100% theo quy định của nhà sản xuất
                </p>

                <h2 class="text-2xl font-bold mt-8 mb-4">1. Thời gian bảo hành</h2>
                <div class="grid md:grid-cols-2 gap-4 mb-6">
                    <div class="border-2 border-primary-200 rounded-lg p-4">
                        <h4 class="font-bold text-primary-600 mb-2">Giày dép thể thao</h4>
                        <p class="text-2xl font-bold mb-1">3 tháng</p>
                        <p class="text-sm text-gray-600">Bảo hành lỗi keo, đường chỉ</p>
                    </div>
                    <div class="border-2 border-primary-200 rounded-lg p-4">
                        <h4 class="font-bold text-primary-600 mb-2">Quần áo thể thao</h4>
                        <p class="text-2xl font-bold mb-1">1 tháng</p>
                        <p class="text-sm text-gray-600">Bảo hành lỗi đường may, vải</p>
                    </div>
                    <div class="border-2 border-primary-200 rounded-lg p-4">
                        <h4 class="font-bold text-primary-600 mb-2">Phụ kiện điện tử</h4>
                        <p class="text-2xl font-bold mb-1">6 tháng</p>
                        <p class="text-sm text-gray-600">Bảo hành lỗi kỹ thuật</p>
                    </div>
                    <div class="border-2 border-primary-200 rounded-lg p-4">
                        <h4 class="font-bold text-primary-600 mb-2">Túi xách, balo</h4>
                        <p class="text-2xl font-bold mb-1">6 tháng</p>
                        <p class="text-sm text-gray-600">Bảo hành khóa kéo, quai xách</p>
                    </div>
                </div>

                <h2 class="text-2xl font-bold mt-8 mb-4">2. Điều kiện bảo hành</h2>
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Sản phẩm còn trong thời gian bảo hành</li>
                        <li>Có phiếu bảo hành hoặc hóa đơn mua hàng</li>
                        <li>Lỗi do nhà sản xuất (không do người dùng)</li>
                        <li>Sản phẩm chưa qua sửa chữa ở nơi khác</li>
                    </ul>
                </div>

                <h2 class="text-2xl font-bold mt-8 mb-4">3. Các lỗi được bảo hành</h2>
                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-check-circle text-green-500 text-xl mt-1"></i>
                        <div>
                            <h4 class="font-semibold">Giày dép</h4>
                            <p class="text-gray-600">Bong tróc keo, bể mũi giày, đứt đường may chính</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-check-circle text-green-500 text-xl mt-1"></i>
                        <div>
                            <h4 class="font-semibold">Quần áo</h4>
                            <p class="text-gray-600">Bung chỉ, bạc màu bất thường, lỗi vải không đều</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-check-circle text-green-500 text-xl mt-1"></i>
                        <div>
                            <h4 class="font-semibold">Phụ kiện</h4>
                            <p class="text-gray-600">Lỗi kỹ thuật, không hoạt động đúng chức năng</p>
                        </div>
                    </div>
                </div>

                <h2 class="text-2xl font-bold mt-8 mb-4">4. Các lỗi KHÔNG được bảo hành</h2>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Sản phẩm bị hư hỏng do sử dụng sai cách</li>
                        <li>Bị nứt, vỡ do va đập mạnh</li>
                        <li>Bị mốc, phai màu do bảo quản không đúng cách</li>
                        <li>Mòn tự nhiên do sử dụng lâu ngày</li>
                        <li>Tự ý sửa chữa hoặc can thiệp vào sản phẩm</li>
                        <li>Mất tem, mất phiếu bảo hành</li>
                    </ul>
                </div>

                <h2 class="text-2xl font-bold mt-8 mb-4">5. Quy trình bảo hành</h2>
                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-primary-600 font-bold text-lg">1</span>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-1">Mang sản phẩm đến cửa hàng</h4>
                            <p class="text-gray-600">Mang theo sản phẩm + phiếu bảo hành/hóa đơn</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-primary-600 font-bold text-lg">2</span>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-1">Nhân viên kiểm tra</h4>
                            <p class="text-gray-600">Xác định lỗi và thời gian xử lý (1-7 ngày)</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-primary-600 font-bold text-lg">3</span>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-1">Sửa chữa/Đổi mới</h4>
                            <p class="text-gray-600">Sửa chữa miễn phí hoặc đổi sản phẩm mới tương đương</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-primary-600 font-bold text-lg">4</span>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-1">Nhận sản phẩm</h4>
                            <p class="text-gray-600">Nhận lại sản phẩm đã bảo hành, kiểm tra kỹ trước khi về</p>
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mt-6">
                    <p class="text-sm">
                        <strong class="text-yellow-800">Lưu ý:</strong> Thời gian bảo hành có thể kéo dài hơn tùy theo mức độ hư hỏng và khả năng sửa chữa
                    </p>
                </div>

                <div class="mt-8 p-6 bg-gradient-to-r from-primary-50 to-blue-50 rounded-lg">
                    <h3 class="text-xl font-bold mb-3">Cần hỗ trợ bảo hành?</h3>
                    <p class="mb-4">Liên hệ ngay với chúng tôi:</p>
                    <div class="flex flex-wrap gap-4">
                        <a href="tel:<?php echo SITE_PHONE; ?>" class="inline-flex items-center gap-2 bg-white px-4 py-2 rounded-lg hover:shadow-md transition">
                            <i class="fas fa-phone text-primary-600"></i>
                            <span><?php echo SITE_PHONE; ?></span>
                        </a>
                        <a href="/Web/pages/contact.php" class="inline-flex items-center gap-2 bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition">
                            <i class="fas fa-comment"></i>
                            <span>Gửi yêu cầu bảo hành</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
