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

$cacheKey = md5($userId . $selectedTime);
$cacheFile = __DIR__ . "/../cache/{$cacheKey}.json";
$cacheTime = 3600;

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
    header('Content-Type: application/json');
    echo file_get_contents($cacheFile);
    exit();
}


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

$sql = "WITH RECURSIVE 
    filtered_data AS (
        SELECT DISTINCT ign, VehicleSpeed, ldw, time
        FROM CAN 
        WHERE time >= :selectedTime
        ORDER BY time
    ),
    endTime AS (
        SELECT MIN(time) as end_time
        FROM filtered_data
        WHERE time > :selectedTime AND ign = 'IGN-OFF'
    )
SELECT ign, VehicleSpeed, ldw, time 
FROM filtered_data
WHERE time <= (SELECT end_time FROM endTime)
ORDER BY time";

if ($userId == 'testuser') {
    $sql = "WITH RECURSIVE 
        filtered_data AS (
            SELECT DISTINCT ign, VehicleSpeed, ldw, time
            FROM testCAN 
            WHERE time >= :selectedTime
            ORDER BY time
        ),
        endTime AS (
            SELECT MIN(time) as end_time
            FROM filtered_data
            WHERE time > :selectedTime AND ign = 'IGN-OFF'
        )
    SELECT ign, VehicleSpeed, ldw, time 
    FROM filtered_data
    WHERE time <= (SELECT end_time FROM endTime)
    ORDER BY time";
}
$stmt = $pdo->prepare($sql);
$stmt->bindParam('selectedTime', $selectedTime);
if ($stmt->execute()) {
    $can = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($can != []) {
        $response = [
            'success' => true,
            'can' => $can,
            'message' => 'CAN取得成功'
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'CAN取得失敗'
        ];
    }
} else {
    $response = [
        'success' => false,
        'message' => 'CAN取得失敗'
    ];
}

file_put_contents($cacheFile, json_encode($response));

header('Content-Type: application/json');
echo json_encode($response);
?>