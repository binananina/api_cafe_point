<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST");

require_once "../../config/config.php";

$db = new Database();
$conn = $db->connect();

// Ambil token dari header Authorization
$headers = apache_request_headers();
$token = $headers['Authorization'] ?? "";

if (!$token) {
    sendResponse(false, "Token missing");
}

// Cek token valid
$stmt = $conn->prepare("SELECT id FROM users WHERE token = ?");
$stmt->execute([$token]);

if ($stmt->rowCount() == 0) {
    sendResponse(false, "Invalid token");
}

// Hapus token = logout
$stmt = $conn->prepare("UPDATE users SET token = NULL WHERE token = ?");
$stmt->execute([$token]);

sendResponse(true, "Logout successful");
