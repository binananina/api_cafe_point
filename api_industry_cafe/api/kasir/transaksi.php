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

//add transaksi
    case 'create':
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) {
            echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
            exit;
        }

        $userId = $data['userId'];
        $namaCust = $data['namaCust'];
        $tanggal = date("Y-m-d");
        $totalHarga = $data['totalHarga'];
        $metodeBayar = $data['metodeBayar'];
        $items = $data['items']; // array: [{menuId, jumlah, subtotal}]

        // Insert transaksi utama
        $stmt = $conn->prepare("
            INSERT INTO transactions (userId, namaCust, tanggal, totalHarga, metodeBayar)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issds", $userId, $namaCust, $tanggal, $totalHarga, $metodeBayar);
        $stmt->execute();

        $transaksiId = $stmt->insert_id; // ambil ID buat detail

        // Insert detail transaksi
        $detailStmt = $conn->prepare("
            INSERT INTO transactionDetails (transaksiId, menuId, jumlah, subtotal)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($items as $item) {
            $detailStmt->bind_param("iiid", $transaksiId, $item['menuId'], $item['jumlah'], $item['subtotal']);
            $detailStmt->execute();
        }

        echo json_encode([
            "status" => "success",
            "message" => "Transaksi berhasil dibuat",
            "transaksiId" => $transaksiId
        ]);
        break;

//get all transaksi
    // case 'getall':
    //     $sql = "
    //         SELECT * FROM transactions ORDER BY tanggal DESC
    //     ";
    //     $result = $conn->query($sql);

    //     $data = [];
    //     while ($row = $result->fetch_assoc()) {
    //         $data[] = $row;
    //     }

    //     echo json_encode(["status" => "success", "data" => $data]);
    //     break;


//get by id
    case 'get':
        $id = $_GET['id'];

        // Ambil transaksi
        $stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $transaksi = $stmt->get_result()->fetch_assoc();

        // Ambil detail
        $sql = $conn->prepare("
            SELECT td.*, m.namaMenu AS menuNama 
            FROM transactionDetails td
            JOIN menus m ON m.id = td.menuId
            WHERE transaksiId = ?
        ");
        $sql->bind_param("i", $id);
        $sql->execute();
        $detailResult = $sql->get_result();

        $details = [];
        while ($row = $detailResult->fetch_assoc()) {
            $details[] = $row;
        }

        echo json_encode([
            "status" => "success",
            "transaksi" => $transaksi,
            "details" => $details
        ]);
        break;

//delete
    case 'delete':
        $id = $_GET['id'];

        $conn->query("DELETE FROM transactionDetails WHERE transaksiId = $id");
        $conn->query("DELETE FROM transactions WHERE id = $id");

        echo json_encode([
            "status" => "success",
            "message" => "Transaksi berhasil dihapus"
        ]);
        break;


    default:
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
        break;
}
