<?php
// データベース設定
define('DB_HOST', 'mysql309.phy.lolipop.lan');
define('DB_NAME', 'LAA1593625-test');
define('DB_USER', 'LAA1593625');
define('DB_PASS', 'testTEST');
define('DB_PORT', '3306');
define('DB_CHARSET', 'utf8');

// セキュリティ設定
define('ALLOWED_ORIGINS', ['http://2024isc1231028.weblike.jp']);
define('SESSION_LIFETIME', 3600); // 1時間
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 300); // 5分

// アプリケーション設定
define('DEBUG_MODE', false);
define('LOG_ERRORS', true);
define('ERROR_LOG_FILE', __DIR__ . '/../logs/error.log');
define('ACCESS_LOG_FILE', __DIR__ . '/../logs/access.log');
