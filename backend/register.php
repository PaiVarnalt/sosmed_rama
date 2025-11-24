<?php
include 'lib/koneksi.php';
if (isset($_POST['register'])) {
  $username = $_POST['username'];
  $email = $_POST['email'];
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  $q = "INSERT INTO user(username,email,password) VALUES('$username','$email','$password')";
  if (mysqli_query($conn, $q)) {
    header("Location: login.php");
  } else {
    $error = "Gagal mendaftar!";
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Register | Instagram Clone</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height:100vh;">
  <form method="POST" class="p-4 bg-white rounded shadow" style="width:320px;">
    <h3 class="text-center mb-3">Daftar Akun</h3>
    <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
    <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
    <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
    <button name="register" class="btn btn-primary w-100">Daftar</button>
    <p class="text-center mt-3"><a href="login.php">Kembali ke Login</a></p>
  </form>
</body>
</html>
