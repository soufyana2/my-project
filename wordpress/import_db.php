<?php
// Temporarily define a logger function if not exists, just in case db.php needs it and it's not working as expected
// But db.php requires 'logger_setup.php' which should provide it.

require_once __DIR__ . '/db.php';

echo "Connected to database.\n";
echo "Importing stores.sql...\n";

$sql = file_get_contents(__DIR__ . '/stores.sql');

if (!$sql) {
    die("Error: Could not read stores.sql\n");
}

try {
    // Enable multiple statements
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);
    
    $pdo->exec($sql);
    
    echo "Database imported successfully!\n";
} catch (PDOException $e) {
    echo "Error importing database: " . $e->getMessage() . "\n";
    exit(1);
}
