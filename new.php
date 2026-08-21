<?php
require __DIR__.'/lib.php';
require_login();

$groups = templates_by_category();
$ts = templates();
$selected = $_POST['template'] ?? ($_GET['template'] ?? array_key_first($ts));
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $customerUsername = trim($_POST['customer_username'] ?? '');
    $customerPassword = (string)($_POST['customer_password'] ?? '');

    if ($customerUsername === '' || $customerPassword === '') {
        $error = 'Username dan password pelanggan wajib diisi.';
    } elseif (invitation_by_customer_username($customerUsername)) {
        $error = 'Username pelanggan sudah dipakai. Gunakan username lain.';
    } else {
        $slug = slugify($_POST['slug'] ?: $title);
        if (load_invitation($slug)) $slug .= '-'.substr((string)time(), -4);
        $inv = [
            'slug' => $slug,
            'title' => $title,
            'template' => $_POST['template'],
            'status' => 'draft',
            'customer_username' => $customerUsername,
            'customer_password_hash' => password_hash($customerPassword, PASSWORD_DEFAULT),
            'replacements' => [],
        ];
        save_invitation($inv);
        header('Location: editor.php?slug='.urlencode($slug));
        exit;
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pesanan Baru</title><?=app_favicon_tags()?><?=app_stylesheet_tags()?>
</head>
<body>
<aside class="sidebar"><?=app_logo_mark()?><nav><a href="index.php">Dashboard</a><a href="templates.php">Template</a><a href="prices.php">Harga Template</a><a class="active" href="new.php">Buat Undangan</a><a href="customers.php">Pelanggan</a><a href="logout.php">Keluar</a></nav></aside>
<main class="app narrow">
  <header>
    <div><h1>Buat Undangan Baru</h1><p>Template yang dipilih akan menjadi dasar tampilan undangan.</p></div>
  </header>
  <?php if($error):?><div class="alert"><?=e($error)?></div><?php endif?>
  <form class="panel form" method="post">
    <label>Nama pesanan / judul<input name="title" value="<?=e($_POST['title'] ?? '')?>" placeholder="Contoh: Pernikahan Budi & Siti" required></label>
    <label>Slug URL<input name="slug" value="<?=e($_POST['slug'] ?? '')?>" placeholder="budi-siti"><small>Boleh kosong, otomatis dari judul.</small></label>
    <label>Pilih template<select name="template"><?php foreach($groups as $group): if(!$group['templates']) continue; ?><optgroup label="<?=e($group['label'].' - '.$group['price_label'])?>"><?php foreach($group['templates'] as $k=>$t):?><option value="<?=e($k)?>" <?=$selected===$k?'selected':''?>><?=e($t['name'].' - '.$t['price_label'])?></option><?php endforeach?></optgroup><?php endforeach?></select></label>
    <div class="form-split">
      <label>Username pelanggan<input name="customer_username" value="<?=e($_POST['customer_username'] ?? '')?>" placeholder="contoh: budi-siti" required></label>
      <label>Password pelanggan<input type="password" name="customer_password" placeholder="Password akses tamu" required></label>
    </div>
    <small>Pelanggan login memakai akun ini dan hanya bisa membuka menu Kelola Tamu untuk undangan ini.</small>
    <button class="btn primary">Buat & Buka Editor</button>
  </form>
</main>
</body>
</html>
