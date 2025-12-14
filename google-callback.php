<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check for errors from Google
if (isset($_GET['error'])) {
    $errorMsg = urlencode('Đăng nhập Google thất bại: ' . $_GET['error']);
    redirect('/Web/pages/login.php?error=' . $errorMsg);
}

// Check if we have the authorization code
if (!isset($_GET['code'])) {
    redirect('/Web/pages/login.php?error=' . urlencode('Không nhận được mã xác thực từ Google'));
}

$code = $_GET['code'];

// Exchange code for access token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    error_log('cURL Error: ' . $curlError);
    redirect('/Web/pages/login.php?error=' . urlencode('Lỗi kết nối đến Google'));
}

$tokenInfo = json_decode($response, true);

if (!isset($tokenInfo['access_token'])) {
    $errorDetail = isset($tokenInfo['error_description']) ? $tokenInfo['error_description'] : 'Không nhận được access token';
    error_log('Token Error: ' . $errorDetail . ' | Response: ' . $response);
    redirect('/Web/pages/login.php?error=' . urlencode('Xác thực Google thất bại: ' . $errorDetail));
}

// Get user info from Google
$userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init($userInfoUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $tokenInfo['access_token']
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$userInfoResponse = curl_exec($ch);
curl_close($ch);

$userInfo = json_decode($userInfoResponse, true);

if (!isset($userInfo['email'])) {
    error_log('User Info Error: No email | Response: ' . $userInfoResponse);
    redirect('/Web/pages/login.php?error=' . urlencode('Không lấy được email từ tài khoản Google'));
}

// Process user data
$email = $userInfo['email'];
$name = $userInfo['name'] ?? '';
$picture = $userInfo['picture'] ?? null;
$googleId = $userInfo['id'] ?? null;

try {
    $db = getDB();
    
    // Check if user exists by email
    $stmt = $db->prepare("SELECT * FROM nguoi_dung WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // User exists - always update avatar from Google if available
        if (!empty($picture)) {
            $updateStmt = $db->prepare("UPDATE nguoi_dung SET avt = ? WHERE id = ?");
            $updateStmt->execute([$picture, $user['id']]);
        }
        
        // Login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['ho_ten'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_avatar'] = $picture ?? $user['avt'];
        
        redirect('/Web/index.php?login=google_success');
    } else {
        // Create new user with random password (Google users don't need password)
        $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("
            INSERT INTO nguoi_dung (ho_ten, email, mat_khau, avt)
            VALUES (?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$name, $email, $randomPassword, $picture])) {
            $userId = $db->lastInsertId();
            
            // Login
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_avatar'] = $picture;
            
            redirect('/Web/index.php?register=google_success');
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log('Create user error: ' . print_r($errorInfo, true));
            redirect('/Web/pages/login.php?error=' . urlencode('Không thể tạo tài khoản'));
        }
    }
} catch (Exception $e) {
    error_log('Google login exception: ' . $e->getMessage());
    redirect('/Web/pages/login.php?error=' . urlencode('Lỗi cơ sở dữ liệu: ' . $e->getMessage()));
}
?>
