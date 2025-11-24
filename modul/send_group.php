<?php
session_start();
include "../lib/koneksi.php";
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$user_id = (int) $_SESSION['user_id'];

$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
$msg = trim($_POST['msg'] ?? '');

if ($group_id && $msg !== '') {
    $stmt = $pdo->prepare("INSERT INTO group_messages (group_id, sender_id, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$group_id, $user_id, $msg]);
}

header("Location: index.php?group=" . $group_id);
exit();
