<?php
if (!isAdmin()) {
    redirect(SITE_URL . '/admin/login.php');
}
$db = getDB();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin'; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc',
                            400: '#38bdf8', 500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1',
                            800: '#075985', 900: '#0c4a6e'
                        },
                        sidebar: {
                            bg: '#1e293b',
                            hover: '#334155',
                            active: '#0ea5e9'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .sidebar-link {
            transition: all 0.2s ease;
        }
        .sidebar-link:hover {
            background: linear-gradient(90deg, rgba(14,165,233,0.2) 0%, transparent 100%);
            border-left: 3px solid #0ea5e9;
        }
        .sidebar-link.active {
            background: linear-gradient(90deg, rgba(14,165,233,0.3) 0%, transparent 100%);
            border-left: 3px solid #0ea5e9;
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stat-card {
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-800 text-white fixed h-full shadow-xl z-50">
            <!-- Logo -->
            <div class="p-5 border-b border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary-400 to-primary-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-store text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-white">Admin Panel</h1>
                        <p class="text-xs text-slate-400">Quản lý cửa hàng</p>
                    </div>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="p-4 space-y-1">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 px-3">Menu chính</p>
                
                <a href="<?php echo SITE_URL; ?>/admin/index.php" 
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active bg-slate-700/50 text-white' : ''; ?>">
                    <i class="fas fa-chart-pie w-5 text-center"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="<?php echo SITE_URL; ?>/admin/products.php" 
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) === 'products.php' || basename($_SERVER['PHP_SELF']) === 'product-form.php' ? 'active bg-slate-700/50 text-white' : ''; ?>">
                    <i class="fas fa-box w-5 text-center"></i>
                    <span>Sản phẩm</span>
                </a>
                
                <a href="<?php echo SITE_URL; ?>/admin/categories.php" 
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active bg-slate-700/50 text-white' : ''; ?>">
                    <i class="fas fa-folder-open w-5 text-center"></i>
                    <span>Danh mục</span>
                </a>
                
                <a href="<?php echo SITE_URL; ?>/admin/brands.php" 
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) === 'brands.php' ? 'active bg-slate-700/50 text-white' : ''; ?>">
                    <i class="fas fa-tags w-5 text-center"></i>
                    <span>Thương hiệu</span>
                </a>
                
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 mt-6 px-3">Bán hàng</p>
                
                <a href="<?php echo SITE_URL; ?>/admin/orders.php" 
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' || basename($_SERVER['PHP_SELF']) === 'order-detail.php' ? 'active bg-slate-700/50 text-white' : ''; ?>">
                    <i class="fas fa-shopping-bag w-5 text-center"></i>
                    <span>Đơn hàng</span>
                </a>
                
                <a href="<?php echo SITE_URL; ?>/admin/users.php" 
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active bg-slate-700/50 text-white' : ''; ?>">
                    <i class="fas fa-users w-5 text-center"></i>
                    <span>Khách hàng</span>
                </a>
                
                <a href="<?php echo SITE_URL; ?>/admin/promotions.php" 
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) === 'promotions.php' || basename($_SERVER['PHP_SELF']) === 'promotion-form.php' ? 'active bg-slate-700/50 text-white' : ''; ?>">
                    <i class="fas fa-percent w-5 text-center"></i>
                    <span>Khuyến mãi</span>
                </a>
                
                <a href="<?php echo SITE_URL; ?>/admin/invoices.php" 
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) === 'invoices.php' || basename($_SERVER['PHP_SELF']) === 'invoice-detail.php' ? 'active bg-slate-700/50 text-white' : ''; ?>">
                    <i class="fas fa-file-invoice w-5 text-center"></i>
                    <span>Hóa đơn</span>
                </a>
                
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 mt-6 px-3">Nội dung</p>
                
                <a href="<?php echo SITE_URL; ?>/admin/news.php" 
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) === 'news.php' || basename($_SERVER['PHP_SELF']) === 'news-form.php' ? 'active bg-slate-700/50 text-white' : ''; ?>">
                    <i class="fas fa-newspaper w-5 text-center"></i>
                    <span>Tin tức</span>
                </a>
                
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 mt-6 px-3">Hỗ trợ</p>
                
                <?php
                // Count pending contacts
                $pendingContacts = 0;
                try {
                    $contactCheck = $db->query("SHOW TABLES LIKE 'lien_he'");
                    if ($contactCheck->rowCount() > 0) {
                        $pendingContacts = $db->query("SELECT COUNT(*) FROM lien_he WHERE status = 'pending'")->fetchColumn();
                    }
                } catch (Exception $e) {}
                ?>
                <a href="<?php echo SITE_URL; ?>/admin/contacts.php" 
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) === 'contacts.php' || basename($_SERVER['PHP_SELF']) === 'contact-detail.php' ? 'active bg-slate-700/50 text-white' : ''; ?>">
                    <i class="fas fa-envelope w-5 text-center"></i>
                    <span>Liên hệ</span>
                    <?php if ($pendingContacts > 0): ?>
                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full"><?php echo $pendingContacts; ?></span>
                    <?php endif; ?>
                </a>
            </nav>
            
            <!-- Bottom -->
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-slate-700">
                <a href="<?php echo SITE_URL; ?>" target="_blank" 
                   class="flex items-center gap-2 text-slate-400 hover:text-white text-sm mb-3 transition">
                    <i class="fas fa-external-link-alt"></i>
                    <span>Xem trang web</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="flex-1 ml-64">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm sticky top-0 z-40 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800"><?php echo $pageTitle ?? 'Dashboard'; ?></h2>
                        <p class="text-sm text-slate-500 mt-1">
                            <?php echo date('l, d/m/Y'); ?>
                        </p>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <!-- Notifications -->
                        <button class="relative p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition">
                            <i class="fas fa-bell text-xl"></i>
                            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>
                        
                        <!-- User Menu -->
                        <div class="flex items-center gap-3 pl-4 border-l border-slate-200">
                            <div class="w-10 h-10 bg-gradient-to-br from-primary-400 to-primary-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <div class="hidden md:block">
                                <p class="text-sm font-semibold text-slate-700"><?php echo $_SESSION['admin_name'] ?? 'Admin'; ?></p>
                                <p class="text-xs text-slate-500">Quản trị viên</p>
                            </div>
                            <a href="<?php echo SITE_URL; ?>/admin/logout.php" 
                               class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition"
                               title="Đăng xuất">
                                <i class="fas fa-sign-out-alt"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <main class="p-6">
