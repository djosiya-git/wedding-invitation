<?php require __DIR__.'/lib.php'; require_login(); $groups=templates_by_category(); ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Harga Template</title><?=app_favicon_tags()?><?=app_stylesheet_tags()?>
</head>
<body>
<aside class="sidebar">
  <?=app_logo_mark()?>
  <nav>
    <a href="index.php">Dashboard</a>
    <a href="templates.php">Template</a>
    <a class="active" href="prices.php">Harga Template</a>
    <a href="new.php">Buat Undangan</a>
    <a href="customers.php">Pelanggan</a>
    <a href="logout.php">Keluar</a>
  </nav>
</aside>
<main class="app">
  <header>
    <div>
      <h1>Harga Template</h1>
      <p>Harga mengikuti kategori template yang dipilih pelanggan.</p>
    </div>
    <a class="btn primary" href="templates.php">Lihat Template</a>
  </header>
  <section class="price-grid">
    <?php foreach($groups as $group): ?>
      <article class="price-card <?=$group['templates']?'':'muted-card'?>">
        <span><?=count($group['templates'])?> template</span>
        <h2><?=e($group['label'])?></h2>
        <div class="price-display"><?=e($group['price_label'])?></div>
        <p><?=e($group['templates'] ? 'Kategori siap dipakai untuk pesanan baru.' : 'Kategori sudah disiapkan, template menyusul.')?></p>
        <a class="btn" href="templates.php">Buka Template</a>
      </article>
    <?php endforeach ?>
  </section>
</main>
</body>
</html>
