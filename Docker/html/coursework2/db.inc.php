<?php // taken from module material
$servername = "mariadb";
$username = "root";
$password = "rootpwd";
$dbname = "coursework2";

$conn = new mysqli($servername, $username, $password, $dbname);

// connection check
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>