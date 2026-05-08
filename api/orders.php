<?php
require_once 'session_init.php';
require_once 'config/database.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["message" => "Unauthorized access."]);
        exit;
    }

    if ($_SESSION['role'] === 'admin') {
        // Admin: fetch all orders with user details
        $query = "SELECT o.*, u.username, u.email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch items for each order
        foreach ($orders as &$order) {
            $item_query = "SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :order_id";
            $item_stmt = $db->prepare($item_query);
            $item_stmt->bindParam(":order_id", $order['id']);
            $item_stmt->execute();
            $order['items'] = $item_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode($orders);
    } else {
        // User: fetch own orders
        $query = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($orders as &$order) {
            $item_query = "SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :order_id";
            $item_stmt = $db->prepare($item_query);
            $item_stmt->bindParam(":order_id", $order['id']);
            $item_stmt->execute();
            $order['items'] = $item_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode($orders);
    }
} elseif ($method === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["message" => "Unauthorized access."]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->items) && !empty($data->total_amount) && !empty($data->address) && !empty($data->delivery_time)) {
        try {
            $db->beginTransaction();
            
            // Create order
            $query = "INSERT INTO orders (user_id, total_amount, address, delivery_time) VALUES (:user_id, :total_amount, :address, :delivery_time)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $_SESSION['user_id']);
            $stmt->bindParam(":total_amount", $data->total_amount);
            $stmt->bindParam(":address", $data->address);
            $stmt->bindParam(":delivery_time", $data->delivery_time);
            $stmt->execute();
            
            $order_id = $db->lastInsertId();
            
            // Create order items
            $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)";
            $item_stmt = $db->prepare($item_query);
            
            foreach ($data->items as $item) {
                $item_stmt->bindParam(":order_id", $order_id);
                $item_stmt->bindParam(":product_id", $item->id);
                $item_stmt->bindParam(":quantity", $item->quantity);
                $item_stmt->bindParam(":price", $item->price);
                $item_stmt->execute();
            }
            
            $db->commit();
            http_response_code(201);
            echo json_encode(["message" => "Order placed successfully.", "order_id" => $order_id]);
        } catch(PDOException $e) {
            $db->rollBack();
            http_response_code(400);
            echo json_encode(["message" => "Error: " . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Incomplete data."]);
    }
} elseif ($method === 'PUT') {
    // Admin update order status
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["message" => "Unauthorized access. Admin only."]);
        exit;
    }
    
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->order_id) && !empty($data->status)) {
        try {
            $query = "UPDATE orders SET status = :status WHERE id = :order_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":status", $data->status);
            $stmt->bindParam(":order_id", $data->order_id);
            
            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode(["message" => "Order status updated."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to update status."]);
            }
        } catch(PDOException $e) {
            http_response_code(400);
            echo json_encode(["message" => "Error: " . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Incomplete data."]);
    }
}
?>
