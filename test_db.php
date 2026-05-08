<?php
require 'api/config/database.php';
$db = (new Database())->getConnection();
$query = "SELECT * FROM users";
$stmt = $db->query($query);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
