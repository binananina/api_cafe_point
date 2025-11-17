<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

require_once "../../config/config.php";

// Preflight
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

$db = new Database();
$conn = $db->connect();

// TOKEN WAJIB
verifyToken($conn);

$method = $_SERVER["REQUEST_METHOD"];
$action = $_GET['action'] ?? '';

switch ($action) {

//riwayat
    case 'keuangan-hari-ini':
        $today = date("Y-m-d");

        $stmt = $conn->prepare("
            SELECT SUM(totalHarga) AS totalPemasukan
            FROM transactions
            WHERE DATE(tanggal) = ?
        ");
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        echo json_encode([
            "status" => "success",
            "totalPemasukan" => $result['totalPemasukan'] ?? 0
        ]);
        break;

//riwayat
    case 'pesanan-hari-ini':
        $today = date("Y-m-d");

        $stmt = $conn->prepare("
            SELECT COUNT(*) AS totalPesanan
            FROM transactions
            WHERE DATE(tanggal) = ?
        ");
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        echo json_encode([
            "status" => "success",
            "totalPesanan" => $result['totalPesanan']
        ]);
        break;


//riwayat
    case 'riwayat':
        $sql = "
            SELECT * FROM transactions 
            ORDER BY tanggal DESC
        ";
        $query = $conn->query($sql);

        $data = [];
        while ($row = $query->fetch_assoc()) {
            $data[] = $row;
        }

        echo json_encode([
            "status" => "success",
            "data" => $data
        ]);
        break;


    default:
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
        break;
}
