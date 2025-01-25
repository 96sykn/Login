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
    
    // 現在のステータスを確認して反転
    $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    $newStatus = $user['is_active'] ? 0 : 1;
    
    // ステータスを更新
    $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmt->execute([$newStatus, $userId]);
    
    sendJsonResponse(true, 'User status updated successfully');
} catch (Exception $e) {
    sendJsonResponse(false, $e->getMessage());
}
