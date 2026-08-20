<?php
require __DIR__.'/lib.php';
require_login();
$groups = templates_by_category();
$ts = templates();
$selected = $_GET['template'] ?? array_key_first($ts);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = slugify($_POST['slug'] ?: $_POST['title']);
    if (load_invitation($slug)) $slug .= '-'.substr((string)time(), -4);
    $inv = [
        'slug' => $slug,
        'title' => trim($_POST['title']),
        'template' => $_POST['template'],
        'status' => 'draft',
        'replacements' => [],
    ];
    save_invitation($inv);
    header('Location: editor.php?slug='.urlencode($slug));
    exit;
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pesanan Baru</title><?=app_favicon_tags()?><link rel="stylesheet" href="assets/admin.css"></head><body><aside class="sidebar"><?=app_logo_mark()?><nav><a href="index.php">Dashboard</a><a href="templates.php">Template</a><a class="active" href="new.php">Buat Undangan</a><a href="logout.php">Keluar</a></nav></aside><main class="app narrow"><header><div><h1>Buat Undangan Baru</h1><p>Template yang dipilih akan menjadi dasar tampilan undangan.</p></div></header><form class="panel form" method="post"><label>Nama pesanan / judul<input name="title" placeholder="Contoh: Pernikahan Budi & Siti" required></label><label>Slug URL<input name="slug" placeholder="budi-siti"><small>Boleh kosong, otomatis dari judul.</small></label><label>Pilih template<select name="template"><?php foreach($groups as $group): if(!$group['templates']) continue; ?><optgroup label="<?=e($group['label'])?>"><?php foreach($group['templates'] as $k=>$t):?><option value="<?=e($k)?>" <?=$selected===$k?'selected':''?>><?=e($t['name'])?></option><?php endforeach?></optgroup><?php endforeach?></select></label><button class="btn primary">Buat & Buka Editor</button></form></main></body></html>
