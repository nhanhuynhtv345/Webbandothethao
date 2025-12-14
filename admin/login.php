<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isAdmin()) {
    redirect(SITE_URL . '/admin/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'] ?? $admin['username'];
            $_SESSION['admin_role'] = 'admin';
            
            redirect(SITE_URL . '/admin/index.php');
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        .input-focus:focus {
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.4);
        }
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-4">
    <!-- Background Shapes -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 left-20 w-72 h-72 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 right-20 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl"></div>
    </div>
    
    <div class="glass-card p-8 md:p-10 rounded-3xl shadow-2xl w-full max-w-md relative z-10">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="floating w-20 h-20 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i class="fas fa-store text-white text-3xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-800">Chào mừng trở lại!</h1>
            <p class="text-slate-500 mt-2">Đăng nhập vào Admin Panel</p>
        </div>
        
        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-500"></i>
            </div>
            <p class="text-sm"><?php echo $error; ?></p>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-slate-700 font-medium mb-2 text-sm">Tên đăng nhập</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" name="username" required
                           class="input-focus w-full pl-12 pr-4 py-3.5 border border-slate-200 rounded-xl focus:border-indigo-500 focus:outline-none transition"
                           placeholder="Nhập tên đăng nhập">
                </div>
            </div>
            
            <div>
                <label class="block text-slate-700 font-medium mb-2 text-sm">Mật khẩu</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="password" required id="password"
                           class="input-focus w-full pl-12 pr-12 py-3.5 border border-slate-200 rounded-xl focus:border-indigo-500 focus:outline-none transition"
                           placeholder="Nhập mật khẩu">
                    <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>
            
            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" class="w-4 h-4 text-indigo-600 rounded border-slate-300">
                    <span class="text-slate-600">Ghi nhớ đăng nhập</span>
                </label>
            </div>
            
            <button type="submit" class="btn-gradient w-full text-white py-4 rounded-xl font-semibold text-lg shadow-lg">
                <i class="fas fa-sign-in-alt mr-2"></i>Đăng nhập
            </button>
        </form>
        
        <div class="mt-8 pt-6 border-t border-slate-200 text-center">
            <a href="<?php echo SITE_URL; ?>" class="inline-flex items-center gap-2 text-slate-500 hover:text-indigo-600 transition">
                <i class="fas fa-arrow-left"></i>
                <span>Quay về trang chủ</span>
            </a>
        </div>
    </div>
    
    <script>
    function togglePassword() {
        const password = document.getElementById('password');
        const icon = document.getElementById('toggleIcon');
        
        if (password.type === 'password') {
            password.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            password.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    </script>
</body>
</html>
