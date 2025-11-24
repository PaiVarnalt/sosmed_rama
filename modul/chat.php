<?php
session_start();
include "../lib/koneksi.php";

$user_id = $_SESSION['user_id'];
$chat_id = $_GET['chat_id'] ?? null;

$stmt = $pdo->prepare("
    SELECT m.*, u.username 
    FROM messages m
    JOIN users u ON m.user_id=u.id
    WHERE m.chat_id=?
    ORDER BY m.created_at ASC
");
$stmt->execute([$chat_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Chat</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.chat-box { height:80vh; overflow-y:auto; background:#eef4ff; padding:14px; }
.bubble-me { background:#2a8af7; color:white; padding:8px 12px; border-radius:12px; margin:6px 0; margin-left:auto; width:max-content; }
.bubble-other { background:white; padding:8px 12px; border-radius:12px; margin:6px 0; width:max-content; }
</style>
</head>
<body>

<div class="chat-box">
<?php foreach($messages as $m): ?>
    <?php if($m['user_id']==$user_id): ?>
        <div class="bubble-me"><?= htmlspecialchars($m['message']) ?></div>
    <?php else: ?>
        <div class="bubble-other"><strong><?= htmlspecialchars($m['username']) ?>:</strong> <?= htmlspecialchars($m['message']) ?></div>
    <?php endif ?>
<?php endforeach ?>
</div>

<form method="POST" action="send.php">
    <input type="hidden" name="chat_id" value="<?= $chat_id ?>">
    <div class="input-group">
        <input class="form-control" name="message" placeholder="Ketik pesan...">
        <button class="btn btn-primary">Kirim</button>
    </div>
</form>

</body>
</html>
