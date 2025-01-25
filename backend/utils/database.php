<?php
require_once __DIR__ . '/../config/config.php';

/**
 * データベース接続を取得
 */
function getDbConnection() {
    try {
        $dsn = sprintf(
            "mysql:host=%s;dbname=%s;port=%s;charset=%s",
            DB_HOST,
            DB_NAME,
            DB_PORT,
            DB_CHARSET
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        logError('Database connection failed', ['error' => $e->getMessage()]);
        throw new Exception('データベース接続に失敗しました。');
    }
}

/**
 * トランザクションを実行
 */
function executeTransaction($pdo, callable $callback) {
    try {
        $pdo->beginTransaction();
        $result = $callback($pdo);
        $pdo->commit();
        return $result;
    } catch (Exception $e) {
        $pdo->rollBack();
        logError('Transaction failed', ['error' => $e->getMessage()]);
        throw $e;
    }
}
