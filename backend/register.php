<?php
header("Access-Control-Allow-Origin: http://2024isc1231028.weblike.jp");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

// 入力値のバリデーション
if (!isset($data['userId']) || !isset($data['name']) || !isset($data['password']) ||
    empty($data['userId']) || empty($data['name']) || empty($data['password'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => '必須項目が入力されていません。'
    ]);
    exit();
}

$userId = filter_var($data['userId'], FILTER_SANITIZE_STRING);
$name = filter_var($data['name'], FILTER_SANITIZE_STRING);
$userpass = $data['password'];

// データベース接続情報を定数として定義
define('DB_HOST', 'mysql309.phy.lolipop.lan');
define('DB_NAME', 'LAA1593625-test');
define('DB_USER', 'LAA1593625');
define('DB_PASS', 'testTEST');
define('DB_PORT', '3306');

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
    die(json_encode(['success' => false, 'message' => 'データベース接続失敗']));
}

$stmt = $pdo->prepare("SELECT name, password FROM users WHERE userId = :userId");
$stmt->bindParam(':userId', $userId);
$stmt->execute();
$user = $stmt->fetch();

if ($user) {
    if (!empty($user['name'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'すでに登録済みです。'
        ]);
        exit();
    }
    
    if (!password_verify($userId, $user['password'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => '管理者に連絡してください。'
        ]);
        exit();
    }
}

// パスワードの検証
if (strlen($userpass) < 8) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'パスワードは8文字以上である必要があります。'
    ]);
    exit();
}

if (password_verify($userpass, $user['password'])){
    $response=[
        'success'=>false,
        'message'=>'パスワードが変更されていません。'
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

$hashpass = password_hash($userpass, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET name = :name,password=:hashpass WHERE userId = :userId");
$stmt->bindParam(':name', $name);
$stmt->bindParam(':userId', $userId);
$stmt->bindParam(':hashpass',$hashpass);

if ($stmt->execute()) {
    $response = [
        'success' => true,
        'message' => '登録完了',
        'name' => $data['name']
    ];
} else {
    $response = [
        'success' => false,
        'message' => 'ユーザIDまたはパスワードが間違っています。'
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>