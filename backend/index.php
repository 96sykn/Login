/**
 * ログイン処理を担当するエンドポイント
 * ユーザーの認証と基本情報の取得を行う
 */

<?php
// CORSヘッダーの設定
header("Access-Control-Allow-Origin: http://2024isc1231028.weblike.jp");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Credentials: true");

// エラーハンドリング設定
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

/**
 * カスタムエラーハンドラー
 * エラーメッセージの生成とログ記録を行う
 * 
 * @param string $message エラーメッセージ
 * @param int $status HTTPステータスコード
 */
function handleError($message, $status = 500) {
    $userMessage = "システムエラーが発生しました。しばらく時間をおいて再度お試しください。";
    if ($status === 401) {
        $userMessage = "認証に失敗しました。";
    } elseif ($status === 404) {
        $userMessage = "リクエストされたリソースが見つかりません。";
    } elseif ($status === 400) {
        $userMessage = "入力内容に誤りがあります。";
    }
    
    // エラーをログに記録
    error_log(json_encode([
        'error' => $message,
        'time' => date('Y-m-d H:i:s'),
        'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
    ]));
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $userMessage
    ]);
    exit();
}

// プリフライトリクエストの処理
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// リクエストボディの取得
$data = json_decode(file_get_contents('php://input'), true);

// 入力値のバリデーション
if (!isset($data['userId']) || !isset($data['password']) || empty($data['userId']) || empty($data['password'])) {
    handleError("必須項目が入力されていません。", 400);
}

// 入力値のサニタイズ
$userId = filter_var($data['userId'], FILTER_SANITIZE_STRING);
$userpass = $data['password'];

// データベース接続情報を定数として定義
define('DB_HOST', 'mysql309.phy.lolipop.lan');
define('DB_NAME', 'LAA1593625-test');
define('DB_USER', 'LAA1593625');
define('DB_PASS', 'testTEST');
define('DB_PORT', '3306');

// データベース接続
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . 
        ";dbname=" . DB_NAME . 
        ";port=" . DB_PORT . 
        ";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    handleError($e->getMessage());
}

// ユーザー情報の取得
$stmt = $pdo->prepare('SELECT * FROM users WHERE userId = :userId');
$stmt->bindParam(':userId', $userId);
$stmt->execute();
$user = $stmt->fetch();

// パスワードの検証と応答の生成
if ($user && password_verify($userpass, $user['password'])) {
    $response = [
        'success' => true,
        'message' => 'ログイン成功',
        'name' => $user['name']
    ];
} else {
    handleError("ユーザIDまたはパスワードが間違っています。", 401);
}

// JSONレスポンスの送信
header('Content-Type: application/json');
echo json_encode($response);
?>