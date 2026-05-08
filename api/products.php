<?php
require_once 'session_init.php';
require_once 'config/database.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Fetch products, possibly with filters
    $query = "SELECT p.*, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id WHERE 1=1";
    $params = [];

    // Filters
    if (isset($_GET['brand_id']) && !empty($_GET['brand_id'])) {
        $query .= " AND p.brand_id = :brand_id";
        $params[':brand_id'] = $_GET['brand_id'];
    }
    if (isset($_GET['type']) && !empty($_GET['type'])) {
        $query .= " AND p.type = :type";
        $params[':type'] = $_GET['type'];
    }
    if (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) {
        $query .= " AND p.price >= :min_price";
        $params[':min_price'] = $_GET['min_price'];
    }
    if (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) {
        $query .= " AND p.price <= :max_price";
        $params[':max_price'] = $_GET['max_price'];
    }

    try {
        $stmt = $db->prepare($query);
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->execute();
        
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Filter by size (size is stored as comma separated string)
        if (isset($_GET['size']) && !empty($_GET['size'])) {
            $searchSize = $_GET['size'];
            $products = array_filter($products, function($p) use ($searchSize) {
                if (!$p['size']) return false;
                $sizes = explode(',', $p['size']);
                return in_array($searchSize, $sizes);
            });
            $products = array_values($products); // Re-index array
        }
        
        echo json_encode($products);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
} elseif ($method === 'POST') {
    // Check admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["message" => "Unauthorized access. Admin only."]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->name) && !empty($data->price) && !empty($data->brand_id)) {
        try {
            $query = "INSERT INTO products (brand_id, name, description, price, image_url, stock, size, type) VALUES (:brand_id, :name, :description, :price, :image_url, :stock, :size, :type)";
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(":brand_id", $data->brand_id);
            $stmt->bindParam(":name", $data->name);
            $stmt->bindParam(":description", $data->description);
            $stmt->bindParam(":price", $data->price);
            $stmt->bindParam(":image_url", $data->image_url);
            $stmt->bindParam(":stock", $data->stock);
            $stmt->bindParam(":size", $data->size);
            $stmt->bindParam(":type", $data->type);
            
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(["message" => "Product created."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to create product."]);
            }
        } catch(PDOException $e) {
            http_response_code(400);
            echo json_encode(["message" => "Error: " . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Incomplete data."]);
    }
} elseif ($method === 'DELETE') {
    // Check admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["message" => "Unauthorized access. Admin only."]);
        exit;
    }

    $id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if ($id) {
        try {
            $query = "DELETE FROM products WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $id);
            
            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode(["message" => "Product deleted."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to delete product."]);
            }
        } catch(PDOException $e) {
            http_response_code(400);
            echo json_encode(["message" => "Error: " . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Product ID required."]);
    }
}
?>
