<?php
// includes/db_connection.php

function getDBConnection() {
    // Get the internal database URL from environment variable
    $database_url = getenv('INTERNAL_DATABASE_URL');
    
    if (!$database_url) {
        error_log("No database URL configured");
        die("Database configuration error");
    }

    try {
        error_log("Attempting connection with URL: " . preg_replace('/password=([^&]*)/', 'password=XXXXX', $database_url));
        
        $pdo = new PDO($database_url, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        error_log("Database connection successful");
        return $pdo;
    } catch (PDOException $e) {
        error_log("Detailed Database connection error: " . $e->getMessage());
        die("Connection failed. Please try again later.");
    }
}
?>