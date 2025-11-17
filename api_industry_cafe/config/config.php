<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit(); // untuk preflight browser
}

class Database {
    private $host = "localhost";
    private $db = "dbindustrycafe";
    private $user = "root";
    private $pass = "";

    public $conn;

    public function connect() {
        try {
            $this->conn = new PDO(
                "mysql:host=$this->host;dbname=$this->db",
                $this->user,
                $this->pass
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->conn;

        } catch (PDOException $e) {
            sendResponse(false, "Database error: " . $e->getMessage());
        }
    }
}

function sendResponse($success, $message, $data = null) {
    $res = [
        "success" => $success,
        "message" => $message
    ];

    if ($data !== null) {
        $res["data"] = $data;
    }

    echo json_encode($res);
    exit();
}


function inputJSON() {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw);

    if ($data === null) {
        sendResponse(false, "Invalid JSON format");
    }

    return $data;
}

function generateToken($user_id, $role) {
    $time = time();
    return base64_encode("$user_id:$role:$time");
}

function verifyToken($conn) {
    $headers = getallheaders();

    if (!isset($headers["Authorization"])) {
        sendResponse(false, "Token required");
    }

    $token = str_replace("Bearer ", "", $headers["Authorization"]);
    $decoded = base64_decode($token);
    $parts = explode(":", $decoded);

    if (count($parts) !== 3) {
        sendResponse(false, "Invalid token");
    }

    [$id, $role, $time] = $parts;

    // Token expiry (24 jam)
    if (time() - $time > 86400) {
        sendResponse(false, "Token expired");
    }

    // Cek user masih ada
    $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendResponse(false, "Unauthorized user");
    }

    return $user; // return user data
}
?>
