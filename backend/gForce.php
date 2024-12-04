<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header("Access-Control-Allow-Origin: http://2024isc1231028.weblike.jp");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = 'mysql309.phy.lolipop.lan';
$dbname = 'LAA1593707-testlogin';
$username = 'LAA1593707';
$password = 'password';
$port = '3306';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;port=$port;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'データベース接続失敗']));
}

$data = json_decode(file_get_contents('php://input'), true);
$startTime=$data['startTime'];
$endTime=$data['endTime'];

$stmt = $pdo->prepare("SELECT x,y,z,time FROM G WHERE time >= (SELECT time FROM G ORDER BY ABS(TIMESTAMPDIFF(SECOND, time, :startTime)) ASC LIMIT 1)AND time <= (SELECT time FROM G ORDER BY ABS(TIMESTAMPDIFF(SECOND, time, :endTime)) ASC LIMIT 1)");
$stmt->bindParam(':startTime', $startTime);
$stmt->bindParam(':endTime', $endTime);
if ($stmt->execute()) {
    $gForce = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response=[
        'success'=>true,
        'message'=>'G取得成功',
        'gForce'=>$gForce,
    ];
} else {
    $response = [
        'success' => false,
        'message'=>'gps取得失敗',
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>