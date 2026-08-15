<?php
require __DIR__.'/lib.php'; require_login();
$slug=$_GET['slug']??''; $inv=load_invitation($slug);
if(!$inv){http_response_code(404);exit('Undangan tidak ditemukan');}
$edit=null;
if(isset($_GET['edit'])) $edit=guest_by_id($slug,(int)$_GET['edit']);
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(($_POST['action']??'')==='delete'){ delete_guest($slug,(int)($_POST['id']??0)); header('Location: guests.php?slug='.urlencode($slug).'&deleted=1'); exit; }
    $name=trim($_POST['name']??'');
    if($name!==''){
        save_guest($slug,[
            'id'=>(int)($_POST['id']??0),
            'name'=>$name,
            'phone'=>trim($_POST['phone']??''),
            'group_label'=>trim($_POST['group_label']??''),
            'note'=>trim($_POST['note']??''),
            'status'=>$_POST['status']??'pending',
        ]);
    }
    header('Location: guests.php?slug='.urlencode($slug).'&saved=1'); exit;
}
$guests=all_guests($slug);
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Kelola Tamu</title><link rel="stylesheet" href="assets/admin.css"></head><body><aside class="sidebar"><div class="logo">DW</div><nav><a href="index.php">Dashboard</a><a href="templates.php">Template</a><a href="new.php">Buat Undangan</a><a class="active" href="#">Tamu</a><a href="logout.php">Keluar</a></nav></aside><main class="app"><header><div><h1>Kelola Tamu</h1><p><?=e($inv['title'])?> · <?=count($guests)?> tamu</p></div><div class="header-actions"><a class="btn" href="editor.php?slug=<?=urlencode($slug)?>">Editor</a><a class="btn primary" target="_blank" href="view.php?slug=<?=urlencode($slug)?>">Preview</a></div></header><?php if(isset($_GET['saved'])):?><div class="success">Data tamu tersimpan.</div><?php endif?><?php if(isset($_GET['deleted'])):?><div class="success">Data tamu dihapus.</div><?php endif?><section class="guest-grid"><form class="panel form" method="post"><h2><?= $edit?'Edit Tamu':'Tambah Tamu' ?></h2><input type="hidden" name="id" value="<?=e((string)($edit['id']??0))?>"><label>Nama tamu<input name="name" value="<?=e($edit['name']??'')?>" placeholder="Bapak Andi dan Ibu" required></label><label>No. WhatsApp<input name="phone" value="<?=e($edit['phone']??'')?>" placeholder="62812..."></label><label>Grup / kategori<input name="group_label" value="<?=e($edit['group_label']??'')?>" placeholder="Keluarga, teman kantor, VIP"></label><label>Status<select name="status"><option value="pending" <?=($edit['status']??'pending')==='pending'?'selected':''?>>Pending</option><option value="sent" <?=($edit['status']??'')==='sent'?'selected':''?>>Terkirim</option><option value="confirmed" <?=($edit['status']??'')==='confirmed'?'selected':''?>>Konfirmasi</option></select></label><label>Catatan<textarea name="note" rows="3"><?=e($edit['note']??'')?></textarea></label><button class="btn primary">Simpan Tamu</button><?php if($edit):?><a class="btn" href="guests.php?slug=<?=urlencode($slug)?>">Batal Edit</a><?php endif?></form><section class="panel"><div class="panel-head"><h2>Daftar Tamu</h2></div><?php if(!$guests):?><div class="empty">Belum ada tamu untuk pesanan ini.</div><?php else:?><div class="table-wrap"><table><thead><tr><th>Nama</th><th>Grup</th><th>Status</th><th>Link Personal</th><th></th></tr></thead><tbody><?php foreach($guests as $g):?><tr><td><b><?=e($g['name'])?></b><br><small><?=e($g['phone'])?></small></td><td><?=e($g['group_label'])?></td><td><span class="badge <?=e($g['status'])?>"><?=e($g['status'])?></span></td><td><input class="copy-field" readonly value="<?=e(guest_link($inv,$g))?>"></td><td class="actions"><a href="guests.php?slug=<?=urlencode($slug)?>&edit=<?=(int)$g['id']?>">Edit</a><form method="post" onsubmit="return confirm('Hapus tamu ini?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=(int)$g['id']?>"><button class="link-button">Hapus</button></form></td></tr><?php endforeach?></tbody></table></div><?php endif?></section></section></main></body></html>
