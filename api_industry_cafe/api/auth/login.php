<?php
// ===== CORS & Headers =====
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json");

// ===== Preflight Request (OPTIONS) =====
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../config/config.php";

$db = new Database();
$conn = $db->connect();

// ===== Baca JSON dari React =====
$input = inputJSON();

$username = $input->username ?? "";
$password = $input->password ?? "";

if (empty($username) || empty($password)) {
    sendResponse(false, "Username and password are required");
}

// ===== Ambil user dari database =====
$stmt = $conn->prepare("SELECT id, username, namaUser, password, role FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    sendResponse(false, "User not found");
}

// ===== Verifikasi password =====
if (!password_verify($password, $user["password"])) {
    sendResponse(false, "Wrong password");
}

// ===== Generate Token Aman =====
$token = bin2hex(random_bytes(32)); // Token 64 karakter

// ===== Simpan token ke database =====
$update = $conn->prepare("UPDATE users SET token = ? WHERE id = ?");
$update->execute([$token, $user["id"]]);

// ===== Response sukses =====
sendResponse(true, "Login successful", [
    "id"       => $user["id"],
    "namaUser" => $user["namaUser"],
    "username" => $user["username"],
    "role"     => $user["role"],
    "token"    => $token
]);
?>
