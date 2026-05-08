<?php
require_once 'session_init.php';
require_once 'config/database.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if ($action === 'register') {
        if (!empty($data->username) && !empty($data->email) && !empty($data->password)) {
            try {
                $query = "INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password)";
                $stmt = $db->prepare($query);
                
                $password_hash = password_hash($data->password, PASSWORD_BCRYPT);
                
                $stmt->bindParam(":username", $data->username);
                $stmt->bindParam(":email", $data->email);
                $stmt->bindParam(":password", $password_hash);

                if ($stmt->execute()) {
                    http_response_code(201);
                    echo json_encode(["message" => "User was created."]);
                } else {
                    http_response_code(503);
                    echo json_encode(["message" => "Unable to create user."]);
                }
            } catch(PDOException $e) {
                http_response_code(400);
                echo json_encode(["message" => "Error: " . $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Unable to create user. Data is incomplete."]);
        }
    } elseif ($action === 'login') {
        if (!empty($data->email) && !empty($data->password)) {
            $query = "SELECT id, username, email, password_hash, role FROM users WHERE email = :email";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":email", $data->email);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                if (password_verify($data->password, $row['password_hash'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = $row['role'];
                    
                    http_response_code(200);
                    echo json_encode([
                        "message" => "Login successful.",
                        "user" => [
                            "id" => $row['id'],
                            "username" => $row['username'],
                            "email" => $row['email'],
                            "role" => $row['role']
                        ]
                    ]);
                } else {
                    http_response_code(401);
                    echo json_encode(["message" => "Login failed. Incorrect password."]);
                }
            } else {
                http_response_code(401);
                echo json_encode(["message" => "Login failed. User not found."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete data."]);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'check') {
        if (isset($_SESSION['user_id'])) {
            http_response_code(200);
            echo json_encode([
                "authenticated" => true,
                "user" => [
                    "id" => $_SESSION['user_id'],
                    "username" => $_SESSION['username'],
                    "role" => $_SESSION['role']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["authenticated" => false, "message" => "Not logged in."]);
        }
    } elseif ($action === 'logout') {
        session_destroy();
        http_response_code(200);
        echo json_encode(["message" => "Logged out successfully."]);
    }
}
?>
