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

// Token
verifyToken($conn);

$method = $_SERVER["REQUEST_METHOD"];

// ------------------ READ (GET) ------------------
if ($method === "GET") {
    $stmt = $conn->prepare("SELECT id, namaUser, username, role FROM users WHERE role='Kasir'");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(true, "List karyawan", $data);
}

// ------------------ CREATE (POST) ------------------
if ($method === "POST") {
    $input = inputJSON();

    $namaUser = $input->namaUser ?? "";
    $username = $input->username ?? "";
    $password = $input->password ?? "";

    if (!$namaUser || !$username || !$password) {
        sendResponse(false, "All fields are required");
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (namaUser, username, password, role) VALUES (?, ?, ?, 'Kasir')");
    $stmt->execute([$namaUser, $username, $hashed]);

    sendResponse(true, "Karyawan berhasil ditambahkan");
}

// ------------------ UPDATE (PUT) ------------------
if ($method === "PUT") {
    parse_str($_SERVER["QUERY_STRING"], $params);
    $id = $params["id"] ?? 0;

    $input = inputJSON();

    $query = "UPDATE users SET ";
    $values = [];

    if (isset($input->namaUser)) {
        $query .= "namaUser=?, ";
        $values[] = $input->namaUser;
    }
    if (isset($input->username)) {
        $query .= "username=?, ";
        $values[] = $input->username;
    }
    if (isset($input->password)) {
        $query .= "password=?, ";
        $values[] = password_hash($input->password, PASSWORD_DEFAULT);
    }

    $query = rtrim($query, ", ") . " WHERE id=?";
    $values[] = $id;

    $stmt = $conn->prepare($query);
    $stmt->execute($values);

    sendResponse(true, "Karyawan updated");
}

// ------------------ DELETE (DELETE) ------------------
if ($method === "DELETE") {
    parse_str($_SERVER["QUERY_STRING"], $params);
    $id = $params["id"] ?? 0;

    $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='Kasir'");
    $stmt->execute([$id]);

    sendResponse(true, "Karyawan deleted");
}
