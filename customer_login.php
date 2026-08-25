<?php
require __DIR__.'/lib.php';
start_session();
if (!empty($_SESSION['customer_slug'])) {
    header('Location: guests.php?slug='.urlencode((string)$_SESSION['customer_slug']));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $inv = invitation_by_customer_username($username);
    if ($inv && !empty($inv['customer_password_hash']) && password_verify($password, $inv['customer_password_hash'])) {
        unset($_SESSION['admin'], $_SESSION['guestbook_username']);
        $_SESSION['customer_slug'] = $inv['slug'];
        header('Location: guests.php?slug='.urlencode($inv['slug']));
        exit;
    }
    $error = 'Username atau password pelanggan salah.';
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login Pelanggan</title><?=app_favicon_tags()?><?=app_stylesheet_tags()?>
</head>
<body class="login-body">
  <main class="login-card">
    <?=app_logo_mark('brand-mark')?>
    <h1>Login Pelanggan</h1>
    <p>Masuk untuk mengelola daftar tamu undangan.</p>
    <?php if($error):?><div class="alert"><?=e($error)?></div><?php endif?>
    <form method="post">
      <label>Username<input name="username" required autofocus></label>
      <label>Password<input type="password" name="password" required></label>
      <button class="btn primary">Masuk Kelola Tamu</button>
    </form>
    <small><a href="login.php">Login admin</a></small>
  </main>
</body>
</html>
