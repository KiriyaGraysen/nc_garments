<?php
// --- CENTRALIZED SESSION ENGINE ---
$lifetime = 60 * 60 * 24 * 30; // 30 Days in seconds

// 1. Tell the SERVER not to delete the session file for 30 days
ini_set('session.gc_maxlifetime', $lifetime);

// 2. Tell the BROWSER to keep the cookie for 30 days on every single page load
session_set_cookie_params([
    'lifetime' => $lifetime,
    'path' => '/',
    'secure' => false,       // Change to true if using HTTPS
    'httponly' => true,      // Blocks XSS attacks
    'samesite' => 'Strict'   // Blocks CSRF attacks
]);

// 3. Start the session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- DATABASE CONNECTION LOGIC BELOW ---
// (Keep your existing $servername, $username, $password, $dbname, etc. here!)

$localhost = "127.0.0.1";
$username = "root";
$password = "";
$database = "nc_garments";

$conn = new mysqli($localhost, $username, $password, $database);

$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}