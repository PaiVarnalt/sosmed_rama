<?php
session_start();
include "../lib/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $chat_id = intval($_POST['chat_id']);
    $message = trim($_POST['message']);
    $sender_id = $_SESSION['user_id'];

    if ($chat_id > 0 && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO messages (chat_id, user_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $chat_id, $sender_id, $message);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: index.php?chat=" . $chat_id);
    exit();
}
?>
