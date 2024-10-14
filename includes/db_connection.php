<?php
$host = 'localhost';
$dbname = 'hospital_employees';
$username = 'dhoffman';
$password = 'L6J6Gibbs6';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>