<?php
require_once 'session_init.php';
require_once 'config/database.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized access."]);
    exit;
}

if ($method === 'GET') {
    // Fetch wishlist items
    $query = "SELECT w.id as wishlist_id, p.* FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    
    try {
        $stmt->execute();
        $wishlist = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($wishlist);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Error: " . $e->getMessage()]);
    }
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->product_id)) {
        // Check if already in wishlist
        $check_query = "SELECT id FROM wishlist WHERE user_id = :user_id AND product_id = :product_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":user_id", $_SESSION['user_id']);
        $check_stmt->bindParam(":product_id", $data->product_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(["message" => "Product already in wishlist."]);
            exit;
        }

        try {
            $query = "INSERT INTO wishlist (user_id, product_id) VALUES (:user_id, :product_id)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $_SESSION['user_id']);
            $stmt->bindParam(":product_id", $data->product_id);
            
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(["message" => "Added to wishlist."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to add."]);
            }
        } catch(PDOException $e) {
            http_response_code(400);
            echo json_encode(["message" => "Error: " . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Product ID required."]);
    }
} elseif ($method === 'DELETE') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if ($id) {
        try {
            $query = "DELETE FROM wishlist WHERE id = :id AND user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":user_id", $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode(["message" => "Removed from wishlist."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to remove."]);
            }
        } catch(PDOException $e) {
            http_response_code(400);
            echo json_encode(["message" => "Error: " . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Wishlist item ID required."]);
    }
}
?>
