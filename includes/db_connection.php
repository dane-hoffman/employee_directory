<?php
// includes/db_connection.php

function getDBConnection() {
    $db_host = getenv('POSTGRES_HOST');
    $db_name = getenv('POSTGRES_DB');
    $db_user = getenv('POSTGRES_USER');
    $db_password = getenv('POSTGRES_PASSWORD');
    $db_port = getenv('POSTGRES_PORT') ?: '5432';

    // Debug: Print environment variables (comment out in production)
    error_log("Host: $db_host");
    error_log("DB Name: $db_name");
    error_log("User: $db_user");
    error_log("Port: $db_port");
    // Don't log the password for security reasons

    try {
        $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";
        error_log("Attempting connection with DSN: $dsn");
        
        $pdo = new PDO($dsn, $db_user, $db_password, [
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