<?php
// Start session for login functionality
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Clever Cloud Online Database
$host = 'bhz3dmc3v25bbf3bz3ql-mysql.services.clever-cloud.com';
$port = 3306;
$dbname = 'bhz3dmc3v25bbf3bz3ql';
$username = 'usytfr2darbk4tum';
$password = 'n6IcjDmax1JQz4xRhxQw';

// Create connection
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>