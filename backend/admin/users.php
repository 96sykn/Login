<?php
require_once '../utils/database.php';
require_once '../utils/security.php';

header('Content-Type: application/json');
setCorsHeaders();

try {
    $pdo = getDbConnection();
    
    // ユーザー一覧を取得
    $stmt = $pdo->query("
        SELECT 
            u.id,
            CASE WHEN u.is_active = 1 THEN 'active' ELSE 'inactive' END as status,
            u.last_login
        FROM users u
        ORDER BY u.id ASC
    ");
    
    $users = $stmt->fetchAll();
    
    // 日付フォーマットを整形
    foreach ($users as &$user) {
        $user['lastLogin'] = $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : null;
        unset($user['last_login']);
    }
    
    sendJsonResponse(true, 'Users retrieved successfully', ['users' => $users]);
} catch (Exception $e) {
    sendJsonResponse(false, 'Failed to retrieve users');
}
