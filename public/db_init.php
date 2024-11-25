<?php
// db_init.php

require_once '../includes/db_connection.php';

try {
    $pdo = getDBConnection();
    
    // Create employees table
    $sql = "CREATE TABLE IF NOT EXISTS employees (
        id SERIAL PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        department VARCHAR(50) NOT NULL,
        job_role VARCHAR(50) NOT NULL,
        phone_number VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "Table created successfully";
    
} catch (PDOException $e) {
    error_log("Table creation error: " . $e->getMessage());
    die("Error initializing database");
}
?>