<?php
require __DIR__.'/../lib.php';
start_session();

if (!empty($_SESSION['customer_slug'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $inv = invitation_by_customer_username($username);
    if ($inv && !empty($inv['customer_password_hash']) && password_verify($password, $inv['customer_password_hash'])) {
        $_SESSION['customer_slug'] = $inv['slug'];
        $_SESSION['guestbook_username'] = $username;
        header('Location: index.php');
        exit;
    }
    $error = 'Username atau password tidak sesuai.';
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login Guestbook</title>
  <link rel="icon" href="../assets/brand/d-webin-logo.svg" sizes="any" type="image/svg+xml">
  <link rel="stylesheet" href="../assets/admin.css?v=<?=e((string)filemtime(__DIR__.'/../assets/admin.css'))?>">
</head>
<body class="login-body guestbook-login">
  <main class="login-card">
    <div class="brand-mark"><img src="../assets/brand/d-webin-logo.svg" alt="D-Webin"></div>
    <h1>Login Guestbook</h1>
    <p>Masuk menggunakan akun pelanggan untuk mengakses daftar tamu dan scanner undangan.</p>
    <?php if($error): ?><div class="alert"><?=e($error)?></div><?php endif ?>
    <form method="post">
      <label>Username<input name="username" required autofocus autocomplete="username"></label>
      <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
      <button class="btn primary">Masuk Guestbook</button>
    </form>
    <small><a href="../customer_login.php">Kelola tamu pelanggan</a></small>
  </main>
</body>
</html>
