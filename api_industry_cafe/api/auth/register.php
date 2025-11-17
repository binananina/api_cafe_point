<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST");

require_once "../../config/config.php";

$db = new Database();
$conn = $db->connect();

$input = inputJSON();

$username = $input->username ?? "";
$namaUser    = $input->namaUser ?? "";
$password = $input->password ?? "";
$role     = $input->role ?? "";

// Validasi
if (empty($username) || empty($namaUser) || empty($password) || empty($role)) {
    sendResponse(false, "All fields are required");
}

// Cek username sudah dipakai
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);

if ($stmt->rowCount() > 0) {
    sendResponse(false, "Username already taken");
}

// Validasi role ENUM
$allowedRoles = ["Admin", "Kasir", "Owner"];

if (!in_array($role, $allowedRoles)) {
    sendResponse(false, "Invalid role");
}

// Hash password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// INSERT user dengan role
$stmt = $conn->prepare("INSERT INTO users (username, namaUser, password, role) VALUES (?, ?, ?, ?)");

if ($stmt->execute([$username, $namaUser, $hashed, $role])) {
    sendResponse(true, "Registration successful");
} else {
    sendResponse(false, "Registration failed");
}
