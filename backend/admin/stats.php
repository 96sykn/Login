<?php
require_once '../utils/database.php';
require_once '../utils/security.php';

header('Content-Type: application/json');
setCorsHeaders();

try {
    $pdo = getDbConnection();
    
    // 総アカウント数を取得
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalAccounts = $stmt->fetch()['total'];
    
    // 未使用アカウント数を取得
    $stmt = $pdo->query("SELECT COUNT(*) as unused FROM users WHERE last_login IS NULL");
    $unusedAccounts = $stmt->fetch()['unused'];
    
    // 本日のログイン数を取得
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) as today_logins FROM login_history WHERE DATE(login_time) = ?");
    $stmt->execute([$today]);
    $todayLogins = $stmt->fetch()['today_logins'];

    // 過去7日間のログイン統計
    $stmt = $pdo->prepare("
        SELECT 
            DATE(login_time) as date,
            COUNT(*) as login_count
        FROM login_history
        WHERE login_time >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
        GROUP BY DATE(login_time)
        ORDER BY date DESC
    ");
    $stmt->execute();
    $weeklyLogins = $stmt->fetchAll();

    // アクティブユーザー数（過去30日以内にログインしたユーザー）
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT user_id) as active_users
        FROM login_history
        WHERE login_time >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $activeUsers = $stmt->fetch()['active_users'];

    // 最近のログイン失敗履歴（直近10件）
    $stmt = $pdo->query("
        SELECT 
            user_id,
            login_time,
            ip_address,
            error_message
        FROM login_failures
        ORDER BY login_time DESC
        LIMIT 10
    ");
    $recentFailures = $stmt->fetchAll();

    // アカウントステータス統計
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN is_active = 1 THEN 'active'
                ELSE 'inactive'
            END as status,
            COUNT(*) as count
        FROM users
        GROUP BY is_active
    ");
    $accountStatus = $stmt->fetchAll();

    // ユーザーロール統計
    $stmt = $pdo->query("
        SELECT 
            role,
            COUNT(*) as count
        FROM users
        GROUP BY role
    ");
    $roleStats = $stmt->fetchAll();
    
    sendJsonResponse(true, 'Stats retrieved successfully', [
        'stats' => [
            'totalAccounts' => (int)$totalAccounts,
            'unusedAccounts' => (int)$unusedAccounts,
            'todayLogins' => (int)$todayLogins,
            'activeUsers' => (int)$activeUsers,
            'weeklyLogins' => $weeklyLogins,
            'recentFailures' => $recentFailures,
            'accountStatus' => $accountStatus,
            'roleStats' => $roleStats
        ]
    ]);
} catch (Exception $e) {
    sendJsonResponse(false, 'Failed to retrieve stats: ' . $e->getMessage());
}
