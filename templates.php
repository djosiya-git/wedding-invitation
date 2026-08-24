<?php
require __DIR__.'/lib.php';
require_login();

$groups = templates_by_category();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Template</title><?=app_favicon_tags()?><?=app_stylesheet_tags()?>
</head>
<body>
<aside class="sidebar">
  <?=app_logo_mark()?>
  <nav>
    <a href="index.php">Dashboard</a>
    <a class="active" href="templates.php">Template</a>
    <a href="prices.php">Harga Template</a>
    <a href="new.php">Buat Undangan</a>
    <a href="customers.php">Pelanggan</a>
    <a href="logout.php">Keluar</a>
  </nav>
</aside>
<main class="app">
  <header>
    <div>
      <h1>Template Undangan</h1>
      <p>Katalog admin memakai thumbnail ringan seperti landing page.</p>
    </div>
    <a class="btn primary" href="prices.php">Atur Harga & Diskon</a>
  </header>

  <?php foreach($groups as $group): ?>
    <section class="template-category">
      <div class="category-head">
        <div>
          <h2><?=e($group['label'])?></h2>
          <span><?=count($group['templates'])?> template tersedia</span>
        </div>
        <strong class="price-chip"><?=e($group['price_label'])?></strong>
      </div>

      <?php if($group['templates']): ?>
        <div class="template-grid admin-template-grid">
          <?php foreach($group['templates'] as $key => $t): ?>
            <article class="template-card admin-template-card">
              <a class="template-thumb-link" target="_blank" href="template_preview.php?template=<?=urlencode($key)?>">
                <?php if(!empty($t['has_discount'])): ?><span class="discount-badge">Promo</span><?php endif ?>
                <?php if(!empty($t['thumbnail_url'])): ?>
                  <img src="<?=e($t['thumbnail_url'])?>" alt="<?=e($t['name'])?>" loading="lazy">
                <?php else: ?>
                  <div class="thumb-empty">Belum ada thumbnail</div>
                <?php endif ?>
              </a>
              <div class="template-info">
                <div class="template-meta">
                  <span><?=e($t['category'])?></span>
                  <?php if(!empty($t['has_discount'])): ?>
                    <strong class="sale-chip"><del><?=e($t['base_price_label'])?></del><?=e($t['price_label'])?></strong>
                  <?php else: ?>
                    <strong><?=e($t['price_label'])?></strong>
                  <?php endif ?>
                </div>
                <h3><?=e($t['name'])?></h3>
                <?php if(!empty($t['discount_label'])): ?><p class="discount-note"><?=e($t['discount_label'])?></p><?php endif ?>
                <div class="template-actions">
                  <a class="btn" target="_blank" href="template_preview.php?template=<?=urlencode($key)?>">Preview</a>
                  <a class="btn primary" href="new.php?template=<?=urlencode($key)?>">Pakai</a>
                </div>
              </div>
            </article>
          <?php endforeach ?>
        </div>
      <?php else: ?>
        <div class="panel empty">Template <?=e($group['label'])?> belum tersedia. Harga kategori: <?=e($group['price_label'])?>.</div>
      <?php endif ?>
    </section>
  <?php endforeach ?>
</main>
</body>
</html>
