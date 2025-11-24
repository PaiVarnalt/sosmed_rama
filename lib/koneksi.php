<?php
$host = "localhost";
$user = "root";
$pass = "rpl12345";
$db   = "db_medsos";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    die("Koneksi gagal: " . $e->getMessage());
}
