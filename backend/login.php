<?php
session_start();
include 'lib/koneksi.php';

if (isset($_POST['login'])) {
  $username = $_POST['username'];
  $password = $_POST['password'];

  $result = mysqli_query($conn, "SELECT * FROM user WHERE username='$username'");
  $user = mysqli_fetch_assoc($result);

  if ($user && password_verify($password, $user['password'])) {
    $_SESSION['userID'] = $user['userID'];
    $_SESSION['username'] = $user['username'];
    header("Location: index.php");
  } else {
    $error = "Username atau password salah!";
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login | Instagram Clone</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height:100vh;">
  <form method="POST" class="p-4 bg-white rounded shadow" style="width:320px;">
    <h3 class="text-center mb-3">Instagram Clone</h3>
    <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
    <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
    <button name="login" class="btn btn-primary w-100">Login</button>
    <p class="text-center mt-3">Belum punya akun? <a href="register.php">Daftar</a></p>
  </form>
</body>
</html>
