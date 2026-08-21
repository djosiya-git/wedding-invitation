<?php
require __DIR__.'/lib.php';

$slug = $_GET['slug'] ?? '';
$inv = load_invitation($slug);
if (!$inv) {
    http_response_code(404);
    exit('Undangan tidak ditemukan');
}
require_invitation_access($slug);
$isAdmin = is_admin_user();

function download_csv(string $filename, array $headers, array $rows): void {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, $headers);
    foreach ($rows as $row) fputcsv($out, $row);
    exit;
}

if (($_GET['action'] ?? '') === 'template') {
    download_csv('template-import-tamu.csv', ['name', 'phone', 'group_label', 'status', 'note'], [
        ['Bapak Andi dan Ibu', '6281234567890', 'Keluarga', 'pending', 'Contoh catatan'],
        ['Siti', '6289876543210', 'Teman Kantor', 'pending', ''],
    ]);
}

if (($_GET['action'] ?? '') === 'export') {
    $rows = [];
    foreach (all_guests($slug) as $g) {
        $rows[] = [$g['name'], $g['phone'], $g['group_label'], $g['status'], $g['note'], guest_link($inv, $g)];
    }
    download_csv('tamu-'.$slug.'.csv', ['name', 'phone', 'group_label', 'status', 'note', 'personal_link'], $rows);
}

$edit = null;
if (isset($_GET['edit'])) $edit = guest_by_id($slug, (int)$_GET['edit']);
$activeTab = $_GET['tab'] ?? ($edit ? 'guest-list' : 'guest-list');
if (!in_array($activeTab, ['guest-list', 'message-template', 'guest-import'], true)) $activeTab = 'guest-list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        delete_guest($slug, (int)($_POST['id'] ?? 0));
        header('Location: guests.php?slug='.urlencode($slug).'&deleted=1');
        exit;
    }

    if ($action === 'save_whatsapp_template') {
        $inv['whatsapp_message_template'] = trim($_POST['whatsapp_message_template'] ?? '') ?: default_whatsapp_message_template();
        save_invitation($inv);
        header('Location: guests.php?slug='.urlencode($slug).'&template_saved=1&tab=message-template');
        exit;
    }

    if ($action === 'import') {
        $imported = 0;
        if (($_FILES['guest_csv']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $fh = fopen($_FILES['guest_csv']['tmp_name'], 'r');
            $header = $fh ? fgetcsv($fh) : false;
            if ($header) {
                $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
                $map = array_flip(array_map(fn($x) => strtolower(trim((string)$x)), $header));
                while (($row = fgetcsv($fh)) !== false) {
                    $get = fn($key) => isset($map[$key], $row[$map[$key]]) ? trim((string)$row[$map[$key]]) : '';
                    $name = $get('name');
                    if ($name === '') continue;
                    $status = $get('status') ?: 'pending';
                    if (!in_array($status, ['pending', 'sent', 'confirmed'])) $status = 'pending';
                    save_guest($slug, [
                        'id' => 0,
                        'name' => $name,
                        'phone' => $get('phone'),
                        'group_label' => $get('group_label'),
                        'note' => $get('note'),
                        'status' => $status,
                    ]);
                    $imported++;
                }
            }
            if ($fh) fclose($fh);
        }
        header('Location: guests.php?slug='.urlencode($slug).'&imported='.$imported.'&tab=guest-import');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    if ($name !== '') {
        save_guest($slug, [
            'id' => (int)($_POST['id'] ?? 0),
            'name' => $name,
            'phone' => trim($_POST['phone'] ?? ''),
            'group_label' => trim($_POST['group_label'] ?? ''),
            'note' => trim($_POST['note'] ?? ''),
            'status' => $_POST['status'] ?? 'pending',
        ]);
    }
    header('Location: guests.php?slug='.urlencode($slug).'&saved=1');
    exit;
}

$guests = all_guests($slug);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Kelola Tamu</title><?=app_favicon_tags()?>
  <?=app_stylesheet_tags()?>
</head>
<body>
<aside class="sidebar">
  <?=app_logo_mark()?>
  <nav>
    <?php if($isAdmin):?>
      <a href="index.php">Dashboard</a>
      <a href="templates.php">Template</a>
      <a href="prices.php">Harga Template</a>
      <a href="new.php">Buat Undangan</a>
      <a href="customers.php">Pelanggan</a>
    <?php endif?>
    <a class="active" href="#">Tamu</a>
    <a href="logout.php">Keluar</a>
  </nav>
</aside>
<main class="app">
  <header>
    <div><h1>Kelola Tamu</h1><p><?=e($inv['title'])?> · <?=count($guests)?> tamu</p></div>
    <?php if($isAdmin):?>
    <div class="header-actions">
      <a class="btn" href="editor.php?slug=<?=urlencode($slug)?>">Editor</a>
      <a class="btn primary" target="_blank" href="view.php?slug=<?=urlencode($slug)?>">Preview</a>
    </div>
    <?php endif?>
  </header>
  <?php if(isset($_GET['saved'])):?><div class="success">Data tamu tersimpan.</div><?php endif?>
  <?php if(isset($_GET['deleted'])):?><div class="success">Data tamu dihapus.</div><?php endif?>
  <?php if(isset($_GET['imported'])):?><div class="success"><?=e((string)(int)$_GET['imported'])?> tamu berhasil diimport.</div><?php endif?>
  <?php if(isset($_GET['template_saved'])):?><div class="success">Template pesan WhatsApp tersimpan.</div><?php endif?>
  <nav class="guest-tabs" aria-label="Menu tamu">
    <button class="guest-tab <?=$activeTab==='guest-list'?'active':''?>" type="button" data-tab="guest-list">Daftar Tamu</button>
    <button class="guest-tab <?=$activeTab==='message-template'?'active':''?>" type="button" data-tab="message-template">Template Pesan</button>
    <button class="guest-tab <?=$activeTab==='guest-import'?'active':''?>" type="button" data-tab="guest-import">Import/Export</button>
  </nav>

  <section class="guest-tab-panel <?=$activeTab==='guest-list'?'active':''?>" id="guest-list">
    <form class="panel form guest-entry-form" method="post">
      <div class="panel-head"><h2><?= $edit?'Edit Tamu':'Tambah Tamu' ?></h2></div>
      <input type="hidden" name="id" value="<?=e((string)($edit['id']??0))?>">
      <div class="guest-entry-grid">
        <label>Nama tamu<input name="name" value="<?=e($edit['name']??'')?>" placeholder="Bapak Andi dan Ibu" required></label>
        <label>No. WhatsApp<input name="phone" value="<?=e($edit['phone']??'')?>" placeholder="62812..."></label>
        <label>Grup / kategori<input name="group_label" value="<?=e($edit['group_label']??'')?>" placeholder="Keluarga, teman kantor, VIP"></label>
        <label>Status<select name="status"><option value="pending" <?=($edit['status']??'pending')==='pending'?'selected':''?>>Pending</option><option value="sent" <?=($edit['status']??'')==='sent'?'selected':''?>>Terkirim</option><option value="confirmed" <?=($edit['status']??'')==='confirmed'?'selected':''?>>Konfirmasi</option></select></label>
        <label class="span-2">Catatan<textarea name="note" rows="3"><?=e($edit['note']??'')?></textarea></label>
        <div class="form-actions">
          <button class="btn primary">Simpan Tamu</button>
          <?php if($edit):?><a class="btn" href="guests.php?slug=<?=urlencode($slug)?>">Batal Edit</a><?php endif?>
        </div>
      </div>
    </form>

    <section class="panel">
      <div class="panel-head">
        <h2>Daftar Tamu</h2>
        <span class="panel-count"><?=count($guests)?> tamu</span>
      </div>
      <?php if(!$guests):?>
        <div class="empty">Belum ada tamu untuk pesanan ini.</div>
      <?php else:?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Nama</th><th>Grup</th><th>Status</th><th>Link Personal</th><th>Pesan WhatsApp</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach($guests as $g):?>
                <?php $personalLink = guest_link($inv, $g); $waMessage = render_whatsapp_message($inv, $g); $waLink = guest_whatsapp_link($inv, $g); ?>
                <tr>
                  <td>
                    <b><?=e($g['name'])?></b><br>
                    <?php if($waLink):?>
                      <a class="wa-link" target="_blank" rel="noopener" href="<?=e($waLink)?>"><?=e($g['phone'])?></a>
                    <?php else:?>
                      <small><?=e($g['phone'])?></small>
                    <?php endif?>
                  </td>
                  <td><?=e($g['group_label'])?></td>
                  <td><span class="badge <?=e($g['status'])?>"><?=e($g['status'])?></span></td>
                  <td>
                    <div class="copy-group">
                      <input class="copy-field" readonly value="<?=e($personalLink)?>">
                      <button class="btn copy-btn" type="button" data-copy="<?=e($personalLink)?>">Salin</button>
                    </div>
                  </td>
                  <td>
                    <div class="message-copy">
                      <textarea class="message-preview" readonly rows="5"><?=e($waMessage)?></textarea>
                      <button class="btn copy-btn" type="button" data-copy="<?=e($waMessage)?>">Salin Pesan</button>
                    </div>
                  </td>
                  <td class="actions">
                    <?php if($waLink):?><a target="_blank" rel="noopener" href="<?=e($waLink)?>">Buka WA</a><?php endif?>
                    <a href="guests.php?slug=<?=urlencode($slug)?>&edit=<?=(int)$g['id']?>">Edit</a>
                    <form method="post" onsubmit="return confirm('Hapus tamu ini?')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?=(int)$g['id']?>">
                      <button class="link-button">Hapus</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach?>
            </tbody>
          </table>
        </div>
      <?php endif?>
    </section>
  </section>

  <section class="guest-tab-panel <?=$activeTab==='message-template'?'active':''?>" id="message-template">
    <form class="panel form message-template-form" method="post">
      <div class="panel-head">
        <h2>Template Pesan WhatsApp</h2>
        <span class="panel-count">Siap pakai di tombol WA dan salin pesan</span>
      </div>
      <input type="hidden" name="action" value="save_whatsapp_template">
      <label>Isi pesan
        <textarea name="whatsapp_message_template" rows="15"><?=e($inv['whatsapp_message_template'] ?? default_whatsapp_message_template())?></textarea>
      </label>
      <small>Pakai <b>{nama_tamu}</b> untuk nama tamu dan <b>{link_undangan}</b> untuk link personal. Bisa juga pakai <b>{judul}</b> untuk nama pesanan.</small>
      <button class="btn primary">Simpan Template Pesan</button>
    </form>
  </section>

  <section class="guest-tab-panel <?=$activeTab==='guest-import'?'active':''?>" id="guest-import">
    <div class="import-export-grid">
      <section class="panel">
        <div class="panel-head"><h2>Export Data</h2></div>
        <p class="panel-note">Download semua tamu beserta status, catatan, dan link personal.</p>
        <a class="btn primary" href="guests.php?slug=<?=urlencode($slug)?>&action=export">Export CSV</a>
      </section>
      <section class="panel">
        <div class="panel-head"><h2>Template Import</h2></div>
        <p class="panel-note">Ambil format CSV yang benar sebelum mengisi data tamu massal.</p>
        <a class="btn" href="guests.php?slug=<?=urlencode($slug)?>&action=template">Download Template</a>
      </section>
      <form class="panel form import-form" method="post" enctype="multipart/form-data">
        <div class="panel-head"><h2>Import Tamu</h2></div>
        <input type="hidden" name="action" value="import">
        <label>File CSV<input type="file" name="guest_csv" accept=".csv,text/csv" required></label>
        <button class="btn primary">Import CSV</button>
      </form>
    </div>
  </section>
</main>
<script>
document.querySelectorAll('.guest-tab').forEach(function (tab) {
  tab.addEventListener('click', function () {
    document.querySelectorAll('.guest-tab').forEach(function (item) { item.classList.remove('active'); });
    document.querySelectorAll('.guest-tab-panel').forEach(function (panel) { panel.classList.remove('active'); });
    tab.classList.add('active');
    document.getElementById(tab.dataset.tab).classList.add('active');
    if (window.history && window.history.replaceState) {
      var url = new URL(window.location.href);
      url.searchParams.set('tab', tab.dataset.tab);
      window.history.replaceState(null, '', url.toString());
    }
  });
});

document.addEventListener('click', function (event) {
  var button = event.target.closest('.copy-btn');
  if (!button) return;
  var text = button.getAttribute('data-copy') || '';
  var done = function () {
    var old = button.textContent;
    button.textContent = 'Tersalin';
    setTimeout(function () { button.textContent = old; }, 1400);
  };
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(text).then(done).catch(function () {});
    return;
  }
  var holder = button.closest('.copy-group') || button.closest('.message-copy');
  var field = holder ? holder.querySelector('.copy-field, .message-preview') : null;
  if (!field) return;
  field.focus();
  field.select();
  try { document.execCommand('copy'); done(); } catch (e) {}
});
</script>
</body>
</html>
