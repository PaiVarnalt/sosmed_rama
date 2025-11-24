<?php
session_start();
include "lib/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

$me = $pdo->prepare("SELECT * FROM users WHERE id=?");
$me->execute([$user_id]);
$me = $me->fetch(PDO::FETCH_ASSOC);

// ambil kontak
$q = $pdo->prepare("
    SELECT c.contact_id, u.username
    FROM contacts c
    JOIN users u ON u.id=c.contact_id
    WHERE c.user_id=?
");
$q->execute([$user_id]);
$contacts = $q->fetchAll(PDO::FETCH_ASSOC);

// ambil grup
$g = $pdo->prepare("
    SELECT g.group_id, g.group_name
    FROM chats_groups g
    JOIN group_members gm ON gm.group_id=g.group_id
    WHERE gm.user_id=?
");
$g->execute([$user_id]);
$groups = $g->fetchAll(PDO::FETCH_ASSOC);

$dest = isset($_GET['chat']) ? intval($_GET['chat']) : 0;
$group = isset($_GET['group']) ? intval($_GET['group']) : 0;
$messages = [];
$chat_title = "Telegram";

// PRIVATE CHAT
if ($dest) {
    $stmt = $pdo->prepare("
        SELECT m.*, u.username 
        FROM messages m
        JOIN users u ON u.id=m.sender_id
        WHERE (m.sender_id=? AND m.receiver_id=?)
        OR (m.sender_id=? AND m.receiver_id=?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$user_id, $dest, $dest, $user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $nm = $pdo->prepare("SELECT username FROM users WHERE id=?");
    $nm->execute([$dest]);
    $nm = $nm->fetch(PDO::FETCH_ASSOC);
    $chat_title = $nm['username'] ?? "Chat";
}

// GROUP CHAT
if ($group) {
    $stmt = $pdo->prepare("
        SELECT gm.*, u.username
        FROM group_messages gm
        JOIN users u ON u.id=gm.sender_id
        WHERE gm.group_id=?
        ORDER BY gm.created_at ASC
    ");
    $stmt->execute([$group]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $nm = $pdo->prepare("SELECT group_name FROM chats_groups WHERE group_id=?");
    $nm->execute([$group]);
    $nm = $nm->fetch(PDO::FETCH_ASSOC);
    $chat_title = $nm['group_name'] ?? "Group";
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Telegram Clone</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background:#e9f1f9;
    height:100vh;
    overflow:hidden;
    font-family: Arial, sans-serif;
}

.sidebar {
    height:100vh;
    background:white;
    border-right:1px solid #d7e0ea;
    display:flex;
    flex-direction:column;
}

.header {
    background:#0088cc;
    color:white;
    font-size:18px;
    font-weight:bold;
    padding:14px;
}

.section-title {
    padding:10px 15px;
    font-size:13px;
    font-weight:bold;
    color:#727f8c;
    text-transform:uppercase;
}

.contact-item {
    padding:14px;
    display:flex;
    gap:12px;
    align-items:center;
    cursor:pointer;
    font-size:15px;
    border-radius:6px;
    margin:2px 10px;
}
.contact-item:hover {
    background:#e6f3ff;
}
.active {
    background:#0088cc !important;
    color:white !important;
}

.chat-box {
    height:calc(100vh - 120px);
    overflow-y:auto;
    padding:20px;
    background:#dfe9f4;
}

.msg {
    max-width:65%;
    padding:12px 16px;
    border-radius:14px;
    margin-bottom:12px;
    font-size:15px;
    box-shadow:0 2px 4px rgba(0,0,0,0.08);
}
.me {
    background:#b3defb;
    margin-left:auto;
    border-bottom-right-radius:0;
}
.them {
    background:white;
    margin-right:auto;
    border-bottom-left-radius:0;
}
.text-time {
    display:block;
    font-size:11px;
    margin-top:5px;
    opacity:0.7;
}

.profile-box {
    margin-top:auto;
    padding:15px;
    border-top:1px solid #d7e0ea;
    display:flex;
    gap:10px;
    align-items:center;
    background:#f9fcff;
}
.profile-box .avatar {
    width:38px;
    height:38px;
    background:#0088cc;
    color:white;
    border-radius:50%;
    display:flex;
    justify-content:center;
    align-items:center;
    font-weight:bold;
    font-size:17px;
}
</style>

</head>
<body>

<div class="row g-0">
    <div class="col-3 sidebar">
        <div class="header">Telegram</div>

        <div class="p-2 text-muted fw-bold">Private Chat</div>
        <a href="modul/add_contact.php" class="btn btn-success m-2">+ Tambah Kontak</a>

        <?php foreach ($contacts as $c): ?>
            <a href="?chat=<?= $c['contact_id'] ?>" class="text-dark text-decoration-none">
                <div class="contact-item <?= ($dest==$c['contact_id'])?'active':'' ?>">
                    👤 <?= htmlspecialchars($c['username']) ?>
                </div>
            </a>
        <?php endforeach; ?>

        <div class="p-2 text-muted fw-bold">Group Chat</div>
        <a href="modul/new_group.php" class="btn btn-primary m-2">+ Grup Baru</a>

        <?php foreach ($groups as $gr): ?>
            <a href="?group=<?= $gr['group_id'] ?>" class="text-dark text-decoration-none">
                <div class="contact-item <?= ($group==$gr['group_id'])?'active':'' ?>">
                    📌 <?= htmlspecialchars($gr['group_name']) ?>
                </div>
            </a>
        <?php endforeach; ?>
        <a href="modul/profile.php" class="btn btn-success m-2">Profil</a>

    </div>

    <div class="col-9">
        <div class="header"><?= htmlspecialchars($chat_title) ?>
            <a href="logout.php" class="btn btn-light btn-sm float-end">Logout</a>
        </div>

        <div class="chat-box" id="chatBox">
            <?php foreach ($messages as $m): ?>
                <div class="msg <?= ($m['sender_id']==$user_id)?'me':'them' ?>">
                    <?php if ($m['sender_id'] != $user_id): ?>
                        <b><?= htmlspecialchars($m['username']) ?></b><br>
                    <?php endif ?>
                    <?= nl2br(htmlspecialchars($m['message'])) ?>
                    <div class="text-muted small"><?= $m['created_at'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if($dest): ?>
        <form method="POST" action="modul/send.php" class="p-2 d-flex">
            <input type="hidden" name="to" value="<?= $dest ?>">
            <input type="text" name="msg" class="form-control me-2" placeholder="Ketik pesan..." required>
            <button class="btn btn-primary">Kirim</button>
        </form>
        <?php elseif($group): ?>
        <form method="POST" action="modul/send_group.php" class="p-2 d-flex">
            <input type="hidden" name="group_id" value="<?= $group ?>">
            <input type="text" name="msg" class="form-control me-2" placeholder="Ketik pesan..." required>
            <button class="btn btn-primary">Kirim</button>
        </form>
        <?php endif; ?>

    </div>
</div>

<script>
const cb = document.getElementById("chatBox");
if(cb){ cb.scrollTop = cb.scrollHeight; }
</script>

</body>
</html>
