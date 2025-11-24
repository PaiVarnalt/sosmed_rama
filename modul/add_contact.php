<?php
session_start();
include "../lib/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil semua user lain yang belum jadi kontak
$sql = $pdo->prepare("
SELECT id, username 
FROM users
WHERE id != ? 
AND id NOT IN (
    SELECT contact_id FROM contacts WHERE user_id = ?
)
");
$sql->execute([$user_id, $user_id]);
$users = $sql->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Tambah Kontak</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container p-4">
    <h3>Tambah Kontak</h3>
    <a href="index.php" class="btn btn-secondary btn-sm mb-3">Kembali</a>

    <?php if (empty($users)): ?>
        <div class="alert alert-info">Tidak ada user yang bisa ditambahkan.</div>
    <?php endif; ?>

    <?php foreach ($users as $u): ?>
        <div class="card p-2 mb-2 d-flex flex-row justify-content-between align-items-center">
            <strong><?= htmlspecialchars($u['username']) ?></strong>
            <a href="add_contact_action.php?id=<?= $u['id'] ?>" class="btn btn-primary btn-sm">Tambah</a>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>
