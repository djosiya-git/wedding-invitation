<?php
require __DIR__.'/lib.php';
require_login();

$saved = false;
$error = '';
$categories = template_categories();
$templates = templates();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save_prices') {
            $prices = template_category_prices();
            foreach ($categories as $key => $label) {
                $raw = preg_replace('/\D+/', '', (string)($_POST['prices'][$key] ?? '0'));
                $prices[$key] = max(0, (int)$raw);
            }
            save_app_setting('template_category_prices', $prices);
            $saved = true;
        }

        if ($action === 'save_discounts') {
            $discounts = [];
            foreach (templates() as $key => $template) {
                $rawPrice = preg_replace('/\D+/', '', (string)($_POST['discount_price'][$key] ?? ''));
                $until = trim((string)($_POST['discount_until'][$key] ?? ''));
                if ($rawPrice === '' && $until === '') continue;
                if ($rawPrice === '' || $until === '') {
                    throw new RuntimeException('Harga diskon dan masa berlaku wajib diisi berpasangan.');
                }
                $price = (int)$rawPrice;
                $untilTime = strtotime($until);
                if ($price <= 0 || !$untilTime) {
                    throw new RuntimeException('Ada diskon yang nilainya belum valid.');
                }
                $discounts[$key] = [
                    'price' => $price,
                    'until' => date('Y-m-d\TH:i', $untilTime),
                ];
            }
            save_app_setting('template_discounts', $discounts);
            $saved = true;
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
    $templates = templates();
}

$groups = templates_by_category();
$discounts = template_discounts();
?>
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
      <p>Ubah harga kategori dan atur promo untuk template tertentu.</p>
    </div>
    <a class="btn primary" href="templates.php">Lihat Template</a>
  </header>

  <?php if($saved): ?><div class="success">Pengaturan harga berhasil disimpan.</div><?php endif ?>
  <?php if($error): ?><div class="alert"><?=e($error)?></div><?php endif ?>

  <form class="price-settings" method="post">
    <input type="hidden" name="action" value="save_prices">
    <div class="panel-head">
      <div>
        <h2>Harga Per Kategori</h2>
        <p class="panel-note">Harga ini otomatis dipakai di admin, landing page, dan pilihan template pesanan baru.</p>
      </div>
      <button class="btn primary">Simpan Harga</button>
    </div>
    <section class="price-grid editable-price-grid">
      <?php foreach($groups as $key => $group): ?>
        <article class="price-card <?=$group['templates']?'':'muted-card'?>">
          <span><?=count($group['templates'])?> template</span>
          <h2><?=e($group['label'])?></h2>
          <label>Harga kategori
            <input name="prices[<?=e($key)?>]" inputmode="numeric" value="<?=e((string)$group['price'])?>" placeholder="0">
          </label>
          <div class="price-display"><?=e($group['price_label'])?></div>
          <p><?=e($group['templates'] ? 'Kategori siap dipakai untuk pesanan baru.' : 'Kategori sudah disiapkan, template menyusul.')?></p>
        </article>
      <?php endforeach ?>
    </section>
  </form>

  <form class="panel discount-panel" method="post">
    <input type="hidden" name="action" value="save_discounts">
    <div class="panel-head">
      <div>
        <h2>Diskon Per Template</h2>
        <p class="panel-note">Isi harga diskon final dan tanggal berakhir. Kosongkan dua-duanya untuk menghapus promo.</p>
      </div>
      <button class="btn primary">Simpan Diskon</button>
    </div>
    <div class="discount-table">
      <table>
        <thead>
          <tr>
            <th>Template</th>
            <th>Kategori</th>
            <th>Harga Normal</th>
            <th>Harga Diskon</th>
            <th>Berlaku Sampai</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($templates as $key => $template): $discount = $discounts[$key] ?? []; ?>
          <tr>
            <td>
              <div class="discount-template-cell">
                <?php if(!empty($template['thumbnail_url'])): ?><img src="<?=e($template['thumbnail_url'])?>" alt=""><?php endif ?>
                <div><strong><?=e($template['name'])?></strong><small><?=e($key)?></small></div>
              </div>
            </td>
            <td><span class="badge"><?=e($template['category'])?></span></td>
            <td><strong><?=e($template['base_price_label'])?></strong></td>
            <td><input name="discount_price[<?=e($key)?>]" inputmode="numeric" value="<?=e((string)($discount['price'] ?? ''))?>" placeholder="Contoh: 65000"></td>
            <td><input type="datetime-local" name="discount_until[<?=e($key)?>]" value="<?=e((string)($discount['until'] ?? ''))?>"></td>
            <td>
              <?php if(!empty($template['has_discount'])): ?>
                <span class="badge promo">Aktif</span><small><?=e($template['discount_label'])?></small>
              <?php elseif(!empty($discount)): ?>
                <span class="badge expired">Nonaktif</span><small>Promo kedaluwarsa atau belum valid.</small>
              <?php else: ?>
                <span class="badge">Tidak ada</span>
              <?php endif ?>
            </td>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </form>
</main>
</body>
</html>
