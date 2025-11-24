<?php
session_start();
include "../lib/koneksi.php";
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$user_id = (int) $_SESSION['user_id'];

// Ambil list user lain untuk dipilih sebagai member
$users = $pdo->prepare("SELECT id, username FROM users WHERE id != ? ORDER BY username");
$users->execute([$user_id]);
$users = $users->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['group_name'] ?? '');
    $members = $_POST['members'] ?? []; // array of user ids

    if ($name !== '') {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO chats_groups (group_name, created_at) VALUES (?, NOW())");
            $stmt->execute([$name]);
            $group_id = $pdo->lastInsertId();

            // add current user as member
            $ins = $pdo->prepare("INSERT INTO group_members (group_id, user_id, joined_at) VALUES (?, ?, NOW())");
            $ins->execute([$group_id, $user_id]);

            // add selected members
            if (!empty($members) && is_array($members)) {
                foreach ($members as $m) {
                    $ins->execute([$group_id, intval($m)]);
                }
            }
            $pdo->commit();
            header("Location: ../index.php?group=" . $group_id);

            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $err = $e->getMessage();
        }
    } else {
        $err = "Nama grup tidak boleh kosong.";
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Buat Grup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container" style="max-width:720px">
    <h3>Buat Grup Baru</h3>
    <?php if (!empty($err)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Nama Grup</label>
            <input class="form-control" name="group_name" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Pilih Anggota</label>
            <select class="form-control" name="members[]" multiple size="8">
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
                <?php endforeach; ?>
            </select>
            <small class="form-text text-muted">Gunakan Ctrl / Cmd untuk memilih beberapa user.</small>
        </div>
        <button class="btn btn-primary">Buat Grup</button>
        <a href="index.php" class="btn btn-secondary">Batal</a>
    </form>
</div>
</body>
</html>
