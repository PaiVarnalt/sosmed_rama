<?php
session_start();
include "../lib/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$to = intval($_POST['to'] ?? 0);
$msg = trim($_POST['msg'] ?? '');

if ($to && $msg !== '') {
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, message, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $to, $msg]);
}

header("Location: ../index.php?chat=" . $to);
exit;
