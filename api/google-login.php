<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['credential'])) {
    echo json_encode(['success' => false, 'message' => 'Missing credential']);
    exit;
}

// Decode JWT token from Google
$credential = $data['credential'];
$parts = explode('.', $credential);

if (count($parts) !== 3) {
    echo json_encode(['success' => false, 'message' => 'Invalid token format']);
    exit;
}

// Decode payload
$payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

if (!$payload) {
    echo json_encode(['success' => false, 'message' => 'Cannot decode token']);
    exit;
}

// Extract user info
$email = $payload['email'] ?? null;
$name = $payload['name'] ?? null;
$googleId = $payload['sub'] ?? null;
$picture = $payload['picture'] ?? null;

if (!$email || !$googleId) {
    echo json_encode(['success' => false, 'message' => 'Missing user information']);
    exit;
}

try {
    $db = getDB();
    
    // Check if user exists by email
    $stmt = $db->prepare("SELECT * FROM nguoi_dung WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // User exists - update avatar if from Google
        if (!empty($picture) && empty($user['avt'])) {
            $updateStmt = $db->prepare("UPDATE nguoi_dung SET avt = ? WHERE id = ?");
            $updateStmt->execute([$picture, $user['id']]);
        }
        
        // Login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['ho_ten'];
        $_SESSION['user_email'] = $user['email'];
        
        echo json_encode(['success' => true, 'message' => 'Login successful']);
    } else {
        // Create new user
        $stmt = $db->prepare("
            INSERT INTO nguoi_dung (ho_ten, email, avt)
            VALUES (?, ?, ?)
        ");
        
        if ($stmt->execute([$name, $email, $picture])) {
            $userId = $db->lastInsertId();
            
            // Login
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            
            echo json_encode(['success' => true, 'message' => 'Account created and logged in']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cannot create account']);
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
