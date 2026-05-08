<?php
$dbFile = __DIR__ . '/database/database.sqlite';
$schemaFile = __DIR__ . '/database/schema.sql';

try {
    // Connect to SQLite database (creates it if it doesn't exist)
    $pdo = new PDO("sqlite:" . $dbFile);
    // Enable exceptions on errors
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Read the schema file
    $schema = file_get_contents($schemaFile);

    // Execute the SQL statements
    $pdo->exec($schema);
    echo "Database initialized successfully.\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
