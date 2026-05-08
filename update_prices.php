<?php
require 'api/config/database.php';
$db = (new Database())->getConnection();

// Update all prices by multiplying by 80
$query = "UPDATE products SET price = price * 80";
$stmt = $db->query($query);

if ($stmt) {
    echo "Prices updated to INR successfully!\n";
} else {
    echo "Failed to update prices.\n";
}
