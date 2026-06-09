<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "songforyou";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    http_response_code(500);
    die(json_encode([
        "success" => false,
        "message" => "Koneksi gagal: " . mysqli_connect_error()
    ]));
}

mysqli_set_charset($conn, "utf8mb4");
?>