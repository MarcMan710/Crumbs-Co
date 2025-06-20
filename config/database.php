<?php
// Parse the config.ini file
$config = parse_ini_file(__DIR__ . '/config.ini');

if (!$config) {
    error_log("Failed to parse config.ini"); // Log error
    echo "Error: Database configuration could not be loaded. Please check server logs."; // User-friendly message
    exit;
}

// Assign database credentials from the parsed config
// The keys in $config will match those in the INI file, e.g., $config['DB_HOST']
$db_host = $config['DB_HOST'] ?? null;
$db_user = $config['DB_USER'] ?? null;
$db_pass = $config['DB_PASS'] ?? null;
$db_name = $config['DB_NAME'] ?? null;

if (!$db_host || !$db_user || !$db_name) { // DB_PASS can be empty
    error_log("Database configuration is incomplete in config.ini");
    echo "Error: Database configuration is incomplete. Please check server logs.";
    exit;
}

try {
    $conn = new PDO(
        "mysql:host=" . $db_host . ";dbname=" . $db_name,
        $db_user,
        $db_pass,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage()); // Log detailed error
    echo "Error: Could not connect to the database. Please check server logs."; // User-friendly message
    exit;
}
?> 