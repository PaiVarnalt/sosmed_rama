<?php
include "lib/koneksi.php";

$error = "";

if (isset($_POST['register'])) {
    $u = trim($_POST['username']);
    $e = trim($_POST['email']);
    $p = $_POST['password'];
    $cp = $_POST['confirm_password'];

    // Validasi password
    if ($p !== $cp) {
        $error = "Password dan Konfirmasi Password tidak sama!";
    } else {
        // Cek apakah username atau email sudah ada
        $check = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $check->execute([$u, $e]);

        if ($check->rowCount() > 0) {
            $error = "Username atau Email sudah terdaftar!";
        } else {
            // Insert data baru
            $hash = password_hash($p, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users(username, email, password, created_at)
                                   VALUES (?, ?, ?, NOW())");
            $stmt->execute([$u, $e, $hash]);

            header("Location: login.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5" style="max-width:400px;">
    <div class="card p-4 shadow">
        <h3 class="text-center">Register</h3>

        <?php if ($error != "") { ?>
        <div class="alert alert-danger"><?= $error ?></div>
        <?php } ?>

        <form method="POST">
            <div class="mb-3">
                <label>Username</label>
                <input name="username" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Email</label>
                <input name="email" type="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Password</label>
                <input name="password" type="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Konfirmasi Password</label>
                <input name="confirm_password" type="password" class="form-control" required>
            </div>

            <button name="register" class="btn btn-success w-100">Daftar</button>

            <div class="mt-3 text-center">
                <a href="login.php">Sudah punya akun?</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
