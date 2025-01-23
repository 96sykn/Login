<?php
require_once __DIR__ . '/../config/config.php';

/**
 * CORSヘッダーを設定
 */
function setCorsHeaders() {
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    
    if (in_array($origin, ALLOWED_ORIGINS)) {
        header("Access-Control-Allow-Origin: {$origin}");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
        header("Access-Control-Allow-Credentials: true");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

/**
 * 入力値をサニタイズ
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * SQLインジェクション対策
 */
function escapeSql($pdo, $value) {
    return $pdo->quote($value);
}

/**
 * パスワードの強度をチェック
 */
function validatePassword($password) {
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return false;
    }
    
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/', $password)) {
        return false;
    }
    
    return true;
}

/**
 * ログイン試行回数をチェック
 */
function checkLoginAttempts($userId) {
    $attempts = isset($_SESSION['login_attempts'][$userId]) ? $_SESSION['login_attempts'][$userId] : 0;
    $lastAttempt = isset($_SESSION['last_attempt'][$userId]) ? $_SESSION['last_attempt'][$userId] : 0;
    
    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
        if (time() - $lastAttempt < LOGIN_TIMEOUT) {
            return false;
        }
        // タイムアウト後にリセット
        $_SESSION['login_attempts'][$userId] = 0;
        $_SESSION['last_attempt'][$userId] = 0;
    }
    
    return true;
}

/**
 * ログイン試行回数を記録
 */
function recordLoginAttempt($userId) {
    if (!isset($_SESSION['login_attempts'][$userId])) {
        $_SESSION['login_attempts'][$userId] = 0;
    }
    
    $_SESSION['login_attempts'][$userId]++;
    $_SESSION['last_attempt'][$userId] = time();
}

/**
 * エラーをログに記録
 */
function logError($message, $context = []) {
    if (!LOG_ERRORS) return;
    
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    $logMessage = "[{$timestamp}] {$message} {$contextStr}\n";
    
    error_log($logMessage, 3, ERROR_LOG_FILE);
}

/**
 * アクセスログを記録
 */
function logAccess($userId, $action) {
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'];
    $logMessage = "[{$timestamp}] User: {$userId} Action: {$action} IP: {$ip}\n";
    
    error_log($logMessage, 3, ACCESS_LOG_FILE);
}

/**
 * JSONレスポンスを返す
 */
function sendJsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit();
}
