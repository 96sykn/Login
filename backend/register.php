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
$userId = $data['userId'];
$name = $data['name'];
$userpass=$data['password'];

$host = 'mysql309.phy.lolipop.lan';
$dbname = 'LAA1593625-test';
$username = 'LAA1593625';
$password = 'testTEST';
$port = '3306';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;port=$port;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'データベース接続失敗']));
}

$stmt = $pdo->prepare("SELECT name,password FROM users WHERE userId=:userId");
$stmt->bindParam(':userId', $userId);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user['name'] == "") {
    if (!password_verify($userId, $user['password'])) {
        $response = [
            'success' => false,
            'message' => '管理者に連絡してください。',
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
} else {
    $response = [
        'success' => false,
        'message' => 'すでに登録済みです。',
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
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