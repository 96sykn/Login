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
$selectedTime = $data['selectedTime'];

$host = 'mysql309.phy.lolipop.lan';
$dbname = 'LAA1593625-test';
$username = 'LAA1593625';
$password = 'testTEST';
$port = '3306';

if ($userId == "testuser") {
    $host = 'mysql309.phy.lolipop.lan';
    $dbname = 'LAA1593707-testlogin';
    $username = 'LAA1593707';
    $password = 'password';
    $port = '3306';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;port=$port;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'データベース接続失敗']));
}

$stmt = $pdo->prepare("WITH endTime AS ( SELECT id,ign,VehicleSpeed,ldw,time FROM CAN WHERE time > :selectedTime AND ign = 'IGN-OFF' ORDER BY time LIMIT 1) SELECT id,ign,VehicleSpeed,ldw,time FROM CAN WHERE time >= :selectedTime AND time <= (SELECT time FROM endTime) ORDER BY time");
$stmt->bindParam('selectedTime', $selectedTime);
if ($stmt->execute()) {
    $can = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response = [
        'success' => true,
        'can' => $can,
    ];
} else {
    $response = [
        'success' => false
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>