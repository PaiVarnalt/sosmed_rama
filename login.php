<?php
session_start();
include "lib/koneksi.php";

if (isset($_POST['login'])) {
    $u = $_POST['username'];
    $p = $_POST['password'];

    $q = $pdo->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
    $q->execute([$u]);
    $user = $q->fetch();

    if ($user && password_verify($p, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Username atau password salah.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5" style="max-width:400px;">
    <div class="card p-4 shadow">
        <h3 class="text-center">Login</h3>

        <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label>Username</label>
                <input name="username" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Password</label>
                <input name="password" type="password" class="form-control" required>
            </div>

            <button name="login" class="btn btn-primary w-100">Login</button>

            <div class="mt-3 text-center">
                <a href="register.php">Buat akun</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
