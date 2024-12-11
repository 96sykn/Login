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

$sql="SELECT time FROM CAN WHERE ign='IGN-ON'";
if($userId=='testuser')$sql="SELECT time FROM testCAN WHERE ign='IGN-ON'";
$stmt = $pdo->prepare($sql);
if ($stmt->execute()) {
    $startline = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $response = [
        'success' => true,
        'message' => 'startline取得成功',
        'startline' => $startline,
    ];
} else {
    $response = [
        'success' => false,
        'message' => 'startline取得失敗',
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>