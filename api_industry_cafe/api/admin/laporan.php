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
 // koneksi + fungsi helpers

$action = $_GET['action'] ?? '';

switch ($action) {
//laporan harian
    case 'harian':
        $tanggal = $_GET['tanggal'] ?? date('Y-m-d'); // default hari ini

        $stmt = $conn->prepare("
            SELECT 
                DATE(tanggal) AS tgl,
                SUM(totalHarga) AS totalPemasukan,
                COUNT(*) AS totalTransaksi
            FROM transactions
            WHERE DATE(tanggal) = ?
        ");
        $stmt->bind_param("s", $tanggal);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        echo json_encode([
            "status" => "success",
            "data" => $result
        ]);
        break;

//laporan bulanan
    case 'bulanan':
        $bulan = $_GET['bulan'] ?? date('m');
        $tahun = $_GET['tahun'] ?? date('Y');

        $stmt = $conn->prepare("
            SELECT 
                MONTH(tanggal) AS bulan,
                YEAR(tanggal) AS tahun,
                SUM(totalHarga) AS totalPemasukan,
                COUNT(*) AS totalTransaksi
            FROM transactions
            WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?
        ");
        $stmt->bind_param("ii", $bulan, $tahun);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        echo json_encode([
            "status" => "success",
            "data" => $result
        ]);
        break;

//produk terlaris
    case 'terlaris':

        $stmt = $conn->query("
            SELECT 
                m.id,
                m.namaMenu,
                m.harga,
                SUM(td.jumlah) AS total_terjual
            FROM transactionDetails td
            JOIN menus m ON m.id = td.menuId
            GROUP BY td.menuId
            ORDER BY total_terjual DESC
            LIMIT 5
        ");

        $data = [];
        while ($row = $stmt->fetch_assoc()) {
            $data[] = $row;
        }

        echo json_encode([
            "status" => "success",
            "data" => $data
        ]);
        break;

//produk kurang diminati
    case 'kurang-diminati':

        $stmt = $conn->query("
            SELECT 
                m.id,
                m.namaMenu,
                m.harga,
                COALESCE(SUM(td.qty), 0) AS total_terjual
            FROM menu m
            LEFT JOIN transactionDetails td ON td.menu_id = m.id
            GROUP BY m.id
            ORDER BY total_terjual ASC
            LIMIT 5
        ");

        $data = [];
        while ($row = $stmt->fetch_assoc()) {
            $data[] = $row;
        }

        echo json_encode([
            "status" => "success",
            "data" => $data
        ]);
        break;

    default:
        echo json_encode([
            "status" => "error",
            "message" => "Action tidak valid!"
        ]);
        break;
}
