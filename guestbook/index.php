<?php
require __DIR__.'/../lib.php';
$inv = require_guestbook_customer();
$guests = all_guests($inv['slug']);
$stats = guestbook_stats($inv['slug']);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Guestbook - <?=e($inv['title'])?></title>
  <link rel="icon" href="../assets/brand/d-webin-logo.svg" sizes="any" type="image/svg+xml">
  <link rel="stylesheet" href="../assets/admin.css?v=<?=e((string)filemtime(__DIR__.'/../assets/admin.css'))?>">
</head>
<body class="guestbook-body">
<aside class="sidebar">
  <div class="logo"><img src="../assets/brand/d-webin-logo.svg" alt="D-Webin"></div>
  <nav>
    <a class="active" href="index.php">Dashboard</a>
    <a href="scan.php">Scan Undangan</a>
    <a href="../guests.php?slug=<?=urlencode($inv['slug'])?>">Kelola Tamu</a>
    <a href="logout.php">Keluar</a>
  </nav>
</aside>
<main class="app">
  <header>
    <div>
      <h1>Guestbook Web</h1>
      <p><?=e($inv['title'])?> &middot; <?=e((string)$stats['checked_in'])?> dari <?=e((string)$stats['total'])?> tamu sudah check-in</p>
    </div>
    <a class="btn primary" href="scan.php">Buka Scanner</a>
  </header>

  <section class="guestbook-stats">
    <div><b><?=e((string)$stats['total'])?></b><span>Total tamu</span></div>
    <div><b><?=e((string)$stats['checked_in'])?></b><span>Sudah check-in</span></div>
    <div><b><?=e((string)$stats['remaining'])?></b><span>Belum hadir</span></div>
  </section>

  <section class="panel">
    <div class="panel-head">
      <div>
        <h2>Daftar Tamu</h2>
        <p class="panel-note">Data tamu yang tampil mengikuti akun pelanggan yang sedang login.</p>
      </div>
      <a class="btn" href="scan.php">Scan QR</a>
    </div>
    <?php if(!$guests): ?>
      <div class="empty">Belum ada data tamu untuk undangan ini.</div>
    <?php else: ?>
      <div class="table-wrap guestbook-table">
        <table>
          <thead>
            <tr><th>Nama</th><th>Grup</th><th>Kode</th><th>Status Check-in</th><th>Waktu</th></tr>
          </thead>
          <tbody>
            <?php foreach($guests as $guest): ?>
              <tr>
                <td><b><?=e($guest['name'])?></b><br><small><?=e($guest['phone'] ?? '')?></small></td>
                <td><?=e($guest['group_label'] ?? '')?></td>
                <td><code><?=e(guest_checkin_code($inv, $guest))?></code></td>
                <td>
                  <?php if(!empty($guest['checked_in_at'])): ?>
                    <span class="badge checked-in">Hadir</span>
                  <?php else: ?>
                    <span class="badge pending-checkin">Belum hadir</span>
                  <?php endif ?>
                </td>
                <td><small><?=e(!empty($guest['checked_in_at']) ? format_datetime_label($guest['checked_in_at']) : '-')?></small></td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    <?php endif ?>
  </section>
</main>
</body>
</html>
