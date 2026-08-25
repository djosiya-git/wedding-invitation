<?php
require __DIR__.'/lib.php';
require_login();

$error = '';
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = trim((string)($_POST['slug'] ?? ''));
    $username = trim((string)($_POST['customer_username'] ?? ''));
    $password = (string)($_POST['customer_password'] ?? '');
    $guestbookEnabled = isset($_POST['guestbook_enabled']) ? 1 : 0;
    $inv = $slug !== '' ? load_invitation($slug) : null;

    if (!$inv) {
        $error = 'Undangan tidak ditemukan.';
    } elseif ($username === '') {
        $error = 'Username pelanggan wajib diisi.';
    } else {
        $existing = invitation_by_customer_username($username);
        if ($existing && ($existing['slug'] ?? '') !== $slug) {
            $error = 'Username pelanggan sudah dipakai undangan lain.';
        } else {
            $inv['customer_username'] = $username;
            $inv['guestbook_enabled'] = $guestbookEnabled;
            if ($password !== '') $inv['customer_password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            save_invitation($inv);
            $saved = true;
        }
    }
}

$items = all_invitations();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Akun Pelanggan</title><?=app_favicon_tags()?>
  <?=app_stylesheet_tags()?>
</head>
<body>
<aside class="sidebar">
  <?=app_logo_mark()?>
  <nav>
    <a href="index.php">Dashboard</a>
    <a href="templates.php">Template</a>
    <a href="prices.php">Harga Template</a>
    <a href="new.php">Buat Undangan</a>
    <a class="active" href="customers.php">Pelanggan</a>
    <a href="logout.php">Keluar</a>
  </nav>
</aside>
<main class="app">
  <header>
    <div>
      <h1>Akun Pelanggan</h1>
      <p>Kelola username dan reset password pelanggan untuk akses menu tamu.</p>
    </div>
    <a class="btn primary" href="guestbook/login.php" target="_blank">Buka Login Pelanggan</a>
  </header>

  <?php if($saved):?><div class="success">Akun pelanggan tersimpan.</div><?php endif?>
  <?php if($error):?><div class="alert"><?=e($error)?></div><?php endif?>

  <section class="panel">
    <div class="panel-head">
      <h2>Daftar Akun</h2>
      <span class="panel-count"><?=count($items)?> undangan</span>
    </div>
    <?php if(!$items):?>
      <div class="empty">Belum ada undangan.</div>
    <?php else:?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Undangan</th><th>Slug</th><th>Username</th><th>Password Baru</th><th>Akses</th><th>Status Akun</th><th></th></tr>
          </thead>
          <tbody>
          <?php foreach($items as $it):?>
            <tr>
              <td><b><?=e($it['title'] ?? $it['slug'])?></b></td>
              <td>/<?=e($it['slug'])?></td>
              <td colspan="5">
                <form class="customer-row-form" method="post">
                  <input type="hidden" name="slug" value="<?=e($it['slug'])?>">
                  <input name="customer_username" value="<?=e($it['customer_username'] ?? '')?>" placeholder="username-pelanggan" required>
                  <input type="password" name="customer_password" placeholder="Kosongkan jika tidak diganti">
                  <label class="toggle-inline"><input type="checkbox" name="guestbook_enabled" value="1" <?=invitation_guestbook_enabled($it)?'checked':''?>> Guestbook</label>
                  <span class="badge <?=!empty($it['customer_password_hash'])?'confirmed':''?>"><?=!empty($it['customer_password_hash'])?'Aktif':'Belum ada password'?></span>
                  <button class="btn primary" type="submit">Simpan</button>
                </form>
              </td>
            </tr>
          <?php endforeach?>
          </tbody>
        </table>
      </div>
    <?php endif?>
  </section>
</main>
</body>
</html>
