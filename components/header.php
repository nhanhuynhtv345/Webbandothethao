<?php
if (!isset($pageTitle)) {
    $pageTitle = SITE_NAME;
}
$currentUser = getCurrentUser();
$cartCount = getCartCount();
$wishlistCount = getWishlistCount();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo $metaDescription ?? 'NTH SPORT - Đồ thể thao chính hãng, giá tốt'; ?>">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    
    <!-- Custom Styles -->
    <style>
        /* Product Card */
        .product-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }
        .product-card .product-image {
            transition: transform 0.5s ease;
        }
        .product-card:hover .product-image {
            transform: scale(1.05);
        }
        
        /* Star Rating */
        .star-rating { color: #fbbf24; }
        
        /* Line Clamp */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Swiper */
        .swiper-button-next, .swiper-button-prev {
            color: white;
            background: rgba(0,0,0,0.3);
            padding: 30px;
            border-radius: 50%;
        }
        .swiper-button-next:hover, .swiper-button-prev:hover {
            background: rgba(0,0,0,0.5);
        }
        .swiper-pagination-bullet-active { background: #0284c7; }
        
        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeInUp {
            animation: fadeInUp 0.6s ease forwards;
        }
        
        @keyframes pulse-slow {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .animate-pulse-slow {
            animation: pulse-slow 3s ease-in-out infinite;
        }
        
        /* Gradient Text */
        .gradient-text {
            background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Glass Effect */
        .glass {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
        
        /* Button Hover Effect */
        .btn-primary {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        .btn-primary:hover::before {
            left: 100%;
        }
        
        /* Smooth Scroll */
        html { scroll-behavior: smooth; }
        
        /* Category Card Hover */
        .category-card {
            transition: all 0.3s ease;
        }
        .category-card:hover {
            transform: translateY(-5px) scale(1.02);
        }
        .category-card:hover .category-icon {
            transform: rotate(10deg) scale(1.1);
        }
        .category-icon {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="font-sans bg-gray-50">
    
    <!-- Top Bar -->
    <div class="bg-primary-700 text-white py-2">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center text-sm">
                <div class="flex items-center gap-6">
                    <span><i class="fas fa-phone mr-2"></i>0888 899 107</span>
                    <span><i class="fas fa-envelope mr-2"></i>NTHsport@gmail.com</span>
                </div>
                <div class="flex items-center gap-4">
                    <?php if (isLoggedIn()): ?>
                        <a href="/Web/pages/account.php" class="flex items-center gap-2 hover:text-primary-200">
                            <?php if (!empty($currentUser['avt'])): ?>
                                <img src="<?php echo htmlspecialchars($currentUser['avt']); ?>" 
                                     alt="Avatar" 
                                     class="w-6 h-6 rounded-full object-cover border-2 border-white"
                                     onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22white%22%3E%3Cpath d=%22M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z%22/%3E%3C/svg%3E';">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($currentUser['ho_ten'] ?? $_SESSION['user_name'] ?? 'User'); ?>
                        </a>
                        <a href="/Web/pages/logout.php" class="hover:text-primary-200">
                            <i class="fas fa-sign-out-alt mr-1"></i>Đăng xuất
                        </a>
                    <?php else: ?>
                        <a href="/Web/pages/login.php" class="hover:text-primary-200">
                            <i class="fas fa-sign-in-alt mr-1"></i>Đăng nhập
                        </a>
                        <a href="/Web/pages/register.php" class="hover:text-primary-200">
                            <i class="fas fa-user-plus mr-1"></i>Đăng ký
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Header -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-2">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <a href="/Web/index.php" class="flex items-center gap-2">
                    <img src="<?php echo SITE_URL; ?>/img/logocuahang.jpg" alt="<?php echo SITE_NAME; ?>" class="h-20 w-auto">
                </a>
                
                <!-- Search Bar -->
                <div class="flex-1 max-w-2xl mx-8">
                    <form action="/Web/pages/products.php" method="GET" class="relative">
                        <input type="text" 
                               name="search" 
                               placeholder="Tìm kiếm sản phẩm..." 
                               class="w-full pl-4 pr-12 py-3 border-2 border-gray-300 rounded-full focus:border-primary-500 focus:outline-none">
                        <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-primary-600 text-white px-4 py-2 rounded-full hover:bg-primary-700">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <!-- Header Actions -->
                <div class="flex items-center gap-6">
                    <!-- Wishlist -->
                    <a href="/Web/pages/wishlist.php" class="relative hover:text-primary-600 transition">
                        <i class="fas fa-heart text-2xl"></i>
                        <span class="wishlist-count absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $wishlistCount; ?></span>
                    </a>
                    
                    <!-- Cart -->
                    <a href="/Web/pages/cart.php" class="relative hover:text-primary-600 transition">
                        <i class="fas fa-shopping-cart text-2xl"></i>
                        <span class="cart-count absolute -top-2 -right-2 bg-primary-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $cartCount; ?></span>
                    </a>
                    
                    <!-- Account -->
                    <?php if (isLoggedIn()): ?>
                        <a href="/Web/pages/account.php" class="relative hover:opacity-80 transition group">
                            <?php if (!empty($currentUser['avt'])): ?>
                                <img src="<?php echo htmlspecialchars($currentUser['avt']); ?>" 
                                     alt="Avatar" 
                                     class="w-10 h-10 rounded-full object-cover border-2 border-primary-600 group-hover:border-primary-700"
                                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-user-circle text-2xl text-gray-700\'></i>';">
                            <?php else: ?>
                                <i class="fas fa-user-circle text-2xl text-gray-700"></i>
                            <?php endif; ?>
                            <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
                        </a>
                    <?php else: ?>
                        <a href="/Web/pages/login.php" class="hover:text-primary-600 transition">
                            <i class="fas fa-user-circle text-2xl"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="bg-gray-100 border-t border-gray-200">
            <div class="container mx-auto px-4">
                <ul class="flex items-center justify-center gap-8 py-3">
                    <li>
                        <a href="/Web/index.php" class="flex items-center gap-2 text-gray-700 hover:text-primary-600 font-medium">
                            <i class="fas fa-home"></i>
                            <span>Trang chủ</span>
                        </a>
                    </li>
                    <li>
                        <a href="/Web/pages/products.php" class="flex items-center gap-2 text-gray-700 hover:text-primary-600 font-medium">
                            <i class="fas fa-box"></i>
                            <span>Sản phẩm</span>
                        </a>
                    </li>
                    <li class="relative group">
                        <a href="#" class="flex items-center gap-2 text-gray-700 hover:text-primary-600 font-medium">
                            <i class="fas fa-list"></i>
                            <span>Danh mục</span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </a>
                        <!-- Dropdown -->
                        <div class="absolute top-full left-0 bg-white shadow-lg rounded-lg py-2 w-48 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                            <?php
                            $db = getDB();
                            $stmt = $db->query("SELECT * FROM danh_muc WHERE parent_id IS NULL AND active = 1 ORDER BY sort_order ASC LIMIT 10");
                            $categories = $stmt->fetchAll();
                            foreach ($categories as $cat):
                            ?>
                            <a href="/Web/pages/products.php?danh_muc=<?php echo $cat['slug']; ?>" 
                               class="block px-4 py-2 text-gray-700 hover:bg-primary-50 hover:text-primary-600">
                                <?php echo htmlspecialchars($cat['ten_danh_muc']); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </li>
                    <li>
                        <a href="/Web/pages/promotions.php" class="flex items-center gap-2 text-gray-700 hover:text-primary-600 font-medium">
                            <i class="fas fa-tags"></i>
                            <span>Khuyến mãi</span>
                        </a>
                    </li>
                    <li>
                        <a href="/Web/pages/news.php" class="flex items-center gap-2 text-gray-700 hover:text-primary-600 font-medium">
                            <i class="fas fa-newspaper"></i>
                            <span>Tin tức</span>
                        </a>
                    </li>
                    <li>
                        <a href="/Web/pages/contact.php" class="flex items-center gap-2 text-gray-700 hover:text-primary-600 font-medium">
                            <i class="fas fa-phone"></i>
                            <span>Liên hệ</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    
    <!-- Flash Messages -->
    <?php
    $flash = getFlash();
    if ($flash):
        $bgColor = $flash['type'] === 'success' ? 'bg-green-500' : 'bg-red-500';
    ?>
    <div class="<?php echo $bgColor; ?> text-white py-3 px-4 mb-4">
        <div class="container mx-auto flex items-center justify-between">
            <span><?php echo htmlspecialchars($flash['message']); ?></span>
            <button onclick="this.parentElement.parentElement.remove()" class="text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main>
