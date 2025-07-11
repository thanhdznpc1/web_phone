<?php
$servername = "localhost"; // hoặc 127.0.0.1
$username = "root"; // user database
$password = ""; // mật khẩu database
$database = "web_ban_dien_thoai";

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
?>
