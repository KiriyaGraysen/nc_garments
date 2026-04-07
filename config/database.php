<?php
$localhost = "127.0.0.1";
$username = "root";
$password = "";
$database = "nc_garments";

$conn = new mysqli($localhost, $username, $password, $database);

$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}