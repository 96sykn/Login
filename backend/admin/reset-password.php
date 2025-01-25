<?php
require_once '../utils/database.php';
require_once '../utils/security.php';

header('Content-Type: application/json');
setCorsHeaders();

try {
    // リクエストボディを取得
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = sanitizeInput($data['userId'] ?? '');
    
    if (empty($userId)) {
        throw new Exception('User ID is required');
    }
    
    $pdo = getDbConnection();
    
    // ユーザーの存在確認
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    if (!$stmt->fetch()) {
        throw new Exception('User not found');
    }
    
    // 新しいパスワードを生成（8文字のランダムな英数字）
    $newPassword = bin2hex(random_bytes(4));
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // パスワードを更新
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, $userId]);
    
    // ログを記録
    logAccess($userId, 'password_reset');
    
    sendJsonResponse(true, 'Password reset successfully', ['newPassword' => $newPassword]);
} catch (Exception $e) {
    sendJsonResponse(false, $e->getMessage());
}
