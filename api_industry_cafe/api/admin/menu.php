<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

require_once "../../config/config.php";

// Preflight (OPTIONS)
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

$db = new Database();
$conn = $db->connect();

verifyToken($conn);

$method = $_SERVER["REQUEST_METHOD"];

// ------------------ READ (GET) ------------------
if ($method === "GET") {
    $stmt = $conn->prepare("SELECT * FROM menus");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(true, "Daftar menu", $data);
}

// ------------------ CREATE (POST) ------------------
if ($method === "POST") {
    $input = inputJSON();

    $namaMenu = $input->namaMenu ?? "";
    $kategori = $input->kategori ?? "";
    $harga = $input->harga ?? "";
    $stok = $stok->stok ?? "";

    if (!$namaMenu || !$kategori || !$harga) {
        sendResponse(false, "All fields are required");
    }

    $stmt = $conn->prepare("INSERT INTO menus (namaMenu, kategori, harga, stok) VALUES (?, ?, ?, ?)");
    $stmt->execute([$namaMenu, $kategori, $harga, $stok]);

    sendResponse(true, "Menu berhasil ditambahkan");
}

// ------------------ UPDATE (PUT) ------------------
if ($method === "PUT") {
    parse_str($_SERVER["QUERY_STRING"], $params);
    $id = $params["id"] ?? 0;

    $input = inputJSON();

    $query = "UPDATE menus SET ";
    $values = [];

    foreach ($input as $field => $val) {
        $query .= "$field = ?, ";
        $values[] = $val;
    }

    $query = rtrim($query, ", ") . " WHERE id = ?";
    $values[] = $id;

    $stmt = $conn->prepare($query);
    $stmt->execute($values);

    sendResponse(true, "Menu updated");
}

// ------------------ DELETE (DELETE) ------------------
if ($method === "DELETE") {
    parse_str($_SERVER["QUERY_STRING"], $params);
    $id = $params["id"] ?? 0;

    $stmt = $conn->prepare("DELETE FROM menus WHERE id=?");
    $stmt->execute([$id]);

    sendResponse(true, "Menu deleted");
}
