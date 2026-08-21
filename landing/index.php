<?php
$adminPrefix = '/admin';
$libPath = __DIR__.'/../lib.php';
if (!is_file($libPath)) $libPath = __DIR__.'/admin/lib.php';
require $libPath;

$groups = templates_by_category();
$totalTemplates = count(templates());
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="description" content="Undangan digital D-Webin untuk pernikahan, engagement, dan acara spesial dengan katalog template ringan dan preview cepat.">
  <title>D-Webin Digital Invitation</title>
  <link rel="icon" href="<?=$adminPrefix?>/assets/brand/d-webin-logo.svg" sizes="any" type="image/svg+xml">
  <link rel="apple-touch-icon" href="<?=$adminPrefix?>/assets/brand/d-webin-logo.svg">
  <meta name="msapplication-TileImage" content="<?=$adminPrefix?>/assets/brand/d-webin-logo.svg">
  <style>
    *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:#f2fbff;color:#14324a;font:15px/1.6 Inter,ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}a{color:inherit;text-decoration:none}.wrap{width:min(1160px,calc(100% - 36px));margin:auto}.nav{position:fixed;z-index:20;top:0;left:0;right:0;background:rgba(242,251,255,.9);backdrop-filter:blur(16px);border-bottom:1px solid rgba(22,136,199,.18)}.nav-inner{height:68px;display:flex;align-items:center;justify-content:space-between;gap:18px}.brand{display:inline-flex;align-items:center;gap:10px;font-weight:900;letter-spacing:.08em}.brand img{width:32px;height:32px}.nav-links{display:flex;gap:22px;color:#2f5f7c;font-size:14px}.nav-cta,.btn{display:inline-flex;align-items:center;justify-content:center;border:1px solid rgba(20,50,74,.24);border-radius:8px;padding:11px 16px;font-weight:900;transition:transform .22s ease,box-shadow .22s ease,background .22s ease}.nav-cta,.btn.primary{background:#1688c7;border-color:#1688c7;color:#fff}.btn:hover,.nav-cta:hover{transform:translateY(-2px);box-shadow:0 12px 28px rgba(22,136,199,.18)}.hero{position:relative;min-height:92vh;overflow:hidden;background:linear-gradient(135deg,#dff5ff 0%,#b8e9ff 45%,#effbff 100%);padding:118px 0 72px}.hero:after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,rgba(242,251,255,.96) 0%,rgba(226,247,255,.86) 45%,rgba(196,236,255,.2) 100%)}.hero-stage{position:absolute;inset:0;opacity:.78}.phone-shot{position:absolute;width:292px;height:620px;border:1px solid rgba(22,136,199,.2);border-radius:18px;overflow:hidden;background:#fff;box-shadow:0 26px 70px rgba(22,91,138,.18);padding:16px}.phone-ui{height:100%;border-radius:12px;background:linear-gradient(180deg,#eaf8ff,#fff);display:grid;grid-template-rows:1fr auto;overflow:hidden}.phone-art{display:grid;place-items:center;background:radial-gradient(circle at 30% 20%,rgba(86,196,244,.3),transparent 42%),linear-gradient(135deg,#f8fdff,#d7f3ff)}.phone-art img{width:92px;height:92px}.phone-copy{padding:16px}.phone-copy b{display:block;font-size:18px}.phone-copy span{color:#2f5f7c}.shot-a{right:8%;top:88px;transform:rotate(3deg)}.shot-b{right:31%;top:210px;transform:rotate(-5deg)}.shot-c{right:-4%;top:310px;transform:rotate(7deg)}.hero-content{position:relative;z-index:2;width:min(690px,100%)}.eyebrow{display:inline-flex;border:1px solid rgba(22,136,199,.24);border-radius:999px;background:rgba(255,255,255,.62);padding:7px 12px;color:#1688c7;font-weight:900;font-size:13px;margin-bottom:22px}.hero h1{font-size:clamp(46px,8vw,86px);line-height:.95;margin:0 0 22px;letter-spacing:0}.hero p{font-size:18px;color:#2f5f7c;width:min(620px,100%);margin:0 0 28px}.hero-actions{display:flex;gap:12px;flex-wrap:wrap}.btn.ghost{background:rgba(255,255,255,.46);color:#14324a}.hero-metrics{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:46px;width:min(650px,100%)}.metric{border-top:1px solid rgba(22,136,199,.22);padding-top:14px}.metric b{display:block;font-size:28px}.metric span{color:#2f5f7c}.intro{padding:56px 0 30px}.intro-grid{display:grid;grid-template-columns:1.1fr .9fr;gap:36px;align-items:end}.section-kicker{color:#1688c7;font-size:13px;font-weight:900;letter-spacing:.12em;text-transform:uppercase}.section-title{font-size:clamp(32px,5vw,58px);line-height:1;margin:8px 0 0;letter-spacing:0}.lead{font-size:17px;color:#2f5f7c;margin:0}.band{padding:74px 0}.cream{background:#eaf8ff}.dark{background:#dff4ff}.features,.steps{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}.steps{grid-template-columns:repeat(3,1fr)}.feature,.step,.package{border:1px solid rgba(22,136,199,.18);border-radius:8px;padding:20px;background:rgba(255,255,255,.9);box-shadow:0 16px 44px rgba(22,136,199,.08);transition:transform .25s ease,box-shadow .25s ease}.feature:hover,.step:hover,.package:hover{transform:translateY(-5px);box-shadow:0 20px 48px rgba(22,136,199,.14)}.feature b,.step b,.package b{display:block;font-size:18px;margin-bottom:6px}.feature p,.step p,.package p{margin:0;color:#2f5f7c}.template-toolbar{position:sticky;top:68px;z-index:5;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;margin:32px 0 24px;padding:8px;background:rgba(224,246,255,.9);border:1px solid rgba(22,136,199,.18);border-radius:8px;backdrop-filter:blur(14px);box-shadow:0 16px 44px rgba(22,136,199,.12)}.template-tabs{display:flex;gap:10px;overflow:auto}.template-search{min-width:230px;border:1px solid rgba(22,136,199,.2);border-radius:8px;padding:0 12px;font:inherit;color:#14324a}.template-tab{display:inline-flex;align-items:center;gap:10px;flex:0 0 auto;border:1px solid rgba(22,136,199,.18);background:rgba(255,255,255,.7);color:#2f5f7c;border-radius:8px;padding:12px 18px;font:inherit;font-weight:900;cursor:pointer;transition:transform .22s ease,background .22s ease,color .22s ease}.template-tab span{font-size:12px;opacity:.78}.template-tab.active{background:#1688c7;color:#fff;border-color:#1688c7}.template-category{display:none}.template-category.active{display:block;animation:panelIn .28s ease both}.template-category h3{font-size:24px;margin:0 0 14px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}.category-price,.template-price{display:inline-flex;align-items:center;border-radius:999px;background:rgba(22,136,199,.12);color:#1688c7;font-size:12px;font-weight:900;padding:4px 9px}.templates{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}.template{background:#f8fdff;border-radius:8px;overflow:hidden;color:#14324a;border:1px solid rgba(22,136,199,.18);box-shadow:0 18px 44px rgba(22,136,199,.12);transition:transform .24s ease,box-shadow .24s ease}.template:hover{transform:translateY(-8px);box-shadow:0 24px 58px rgba(22,136,199,.22)}.template.is-hidden,.template.search-hidden{display:none}.template-thumb{height:360px;background:linear-gradient(180deg,#f8fdff,#d9f4ff);display:grid;place-items:center;padding:14px}.thumb-phone{width:min(100%,170px);height:320px;border:1px solid rgba(22,136,199,.2);border-radius:16px;background:#fff;box-shadow:0 16px 36px rgba(22,136,199,.16);overflow:hidden;display:grid;grid-template-rows:1fr auto}.thumb-art{display:grid;place-items:center;background:radial-gradient(circle at 30% 20%,rgba(86,196,244,.34),transparent 42%),linear-gradient(135deg,#fff,#dff5ff)}.thumb-art img{width:64px;height:64px}.thumb-caption{padding:12px;text-align:center}.thumb-caption small{color:#2f5f7c;font-weight:900}.template-info{padding:14px;display:flex;align-items:flex-start;justify-content:space-between;gap:10px}.template-copy{display:grid;gap:5px}.template-info b{font-size:14px}.template-info a{font-size:12px;color:#1688c7;font-weight:900}.template-empty{border:1px dashed rgba(22,136,199,.26);border-radius:8px;padding:28px;color:#2f5f7c;background:rgba(255,255,255,.5)}.show-more{margin-top:18px}.packages{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px}.package.highlight{background:#d3f1ff;border-color:#79cef4}.price{font-size:clamp(24px,3vw,34px);font-weight:900;margin:12px 0;color:#14324a}.list{display:grid;gap:8px;margin:18px 0;color:#2f5f7c}.list div{border-top:1px solid rgba(22,136,199,.14);padding-top:8px}.cta{padding:78px 0;background:linear-gradient(135deg,#56c4f4,#1688c7);color:#fff}.cta-grid{display:grid;grid-template-columns:1fr auto;gap:24px;align-items:center}.cta h2{font-size:clamp(34px,5vw,64px);line-height:1;margin:0}.cta p{margin:12px 0 0;color:#eaf8ff;font-size:17px}.footer{background:#dff4ff;color:#2f5f7c;padding:30px 0}.footer .wrap{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap}.admin-link{color:#1688c7}@keyframes panelIn{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}@media(prefers-reduced-motion:no-preference){.shot-a{animation:floatA 7s ease-in-out infinite}.shot-b{animation:floatB 8s ease-in-out infinite}.shot-c{animation:floatC 7.4s ease-in-out infinite}.hero-content>*{animation:heroIn .7s ease both}.hero-content>*:nth-child(2){animation-delay:.08s}.hero-content>*:nth-child(3){animation-delay:.16s}.hero-content>*:nth-child(4){animation-delay:.24s}.hero-content>*:nth-child(5){animation-delay:.32s}}@keyframes heroIn{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}@keyframes floatA{0%,100%{transform:translate3d(0,0,0) rotate(3deg)}50%{transform:translate3d(0,-18px,0) rotate(1deg)}}@keyframes floatB{0%,100%{transform:translate3d(0,0,0) rotate(-5deg)}50%{transform:translate3d(-8px,16px,0) rotate(-3deg)}}@keyframes floatC{0%,100%{transform:translate3d(0,0,0) rotate(7deg)}50%{transform:translate3d(10px,-14px,0) rotate(5deg)}}@media(max-width:1100px){.templates{grid-template-columns:repeat(3,1fr)}.template-thumb{height:410px}.thumb-phone{height:370px}}@media(max-width:900px){.nav-links{display:none}.hero-stage{opacity:.28}.phone-shot{width:220px;height:470px}.shot-a{right:-24px}.shot-b{right:120px}.shot-c{display:none}.hero-metrics,.features,.templates,.steps,.packages,.intro-grid,.cta-grid,.template-toolbar{grid-template-columns:1fr}.template-toolbar{top:62px}.template-search{min-height:44px}.template-thumb{height:520px}.thumb-phone{height:480px}.cta-grid .btn{width:100%}}@media(max-width:560px){.wrap{width:min(100% - 28px,1160px)}.nav-inner{height:62px}.brand img{width:28px;height:28px}.brand span{max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.hero{padding-top:104px}.hero-actions .btn{width:100%}.hero-metrics{gap:18px}.phone-shot{width:168px;height:360px}.shot-a{right:-44px;top:130px}.shot-b,.shot-c{display:none}.band{padding:54px 0}.template-info{flex-direction:column}.template-thumb{height:500px}.thumb-phone{height:460px}}
  </style>
</head>
<body>
  <nav class="nav">
    <div class="wrap nav-inner">
      <a class="brand" href="#"><img src="<?=$adminPrefix?>/assets/brand/d-webin-logo.svg" alt="D-Webin"><span>D-WEBIN DIGITAL INVITATION</span></a>
      <div class="nav-links">
        <a href="#fitur">Fitur</a>
        <a href="#template">Template</a>
        <a href="#harga">Harga</a>
      </div>
      <a class="nav-cta" href="#harga">Mulai Pesan</a>
    </div>
  </nav>

  <section class="hero">
    <div class="hero-stage" aria-hidden="true">
      <?php foreach([['Animation','Rp75.000','shot-a'],['Minimalist','Rp90.000','shot-b'],['Vintage','Rp105.000','shot-c']] as $shot): ?>
        <div class="phone-shot <?=$shot[2]?>"><div class="phone-ui"><div class="phone-art"><img src="<?=$adminPrefix?>/assets/brand/d-webin-logo.svg" alt=""></div><div class="phone-copy"><b><?=$shot[0]?></b><span><?=$shot[1]?></span></div></div></div>
      <?php endforeach ?>
    </div>
    <div class="wrap hero-content">
      <span class="eyebrow">Undangan digital siap sebar</span>
      <h1>D-Webin Digital Invitation</h1>
      <p>Landing undangan pernikahan yang elegan, cepat dibagikan, dan mudah dikelola dari dashboard. Katalog template dibuat ringan supaya tetap cepat walau koleksi desain terus bertambah.</p>
      <div class="hero-actions">
        <a class="btn primary" href="#template">Lihat Template</a>
        <a class="btn ghost" href="#harga">Cek Harga</a>
      </div>
      <div class="hero-metrics">
        <div class="metric"><b><?=$totalTemplates?></b><span>template siap pakai</span></div>
        <div class="metric"><b><?=count($groups)?></b><span>kategori desain</span></div>
        <div class="metric"><b>Unlimited</b><span>link tamu personal</span></div>
      </div>
    </div>
  </section>

  <section class="intro">
    <div class="wrap intro-grid">
      <div>
        <span class="section-kicker">Untuk acara yang berasa personal</span>
        <h2 class="section-title">Bagikan undangan yang terlihat matang sejak link pertama dibuka.</h2>
      </div>
      <p class="lead">D-Webin membantu kamu membuat undangan digital dengan template premium, nama tamu otomatis, pengelolaan daftar tamu, dan export/import CSV untuk operasional yang lebih cepat.</p>
    </div>
  </section>

  <section id="fitur" class="band cream">
    <div class="wrap features">
      <div class="feature"><b>Nama tamu personal</b><p>Setiap tamu bisa menerima link dengan sapaan khusus.</p></div>
      <div class="feature"><b>Template per kategori</b><p>Pilih gaya Animation, Minimalist, Luxury, atau Vintage sesuai konsep acara.</p></div>
      <div class="feature"><b>Kelola tamu</b><p>Tambah manual, import CSV, export data, dan salin link personal.</p></div>
      <div class="feature"><b>Preview cepat</b><p>Katalog landing tidak memuat iframe berat sampai user membuka preview.</p></div>
    </div>
  </section>

  <section id="template" class="band dark">
    <div class="wrap">
      <span class="section-kicker">Pilihan desain</span>
      <h2 class="section-title">Template siap jalan</h2>
      <div class="template-toolbar">
        <div class="template-tabs" role="tablist" aria-label="Kategori template">
          <?php $first=true; foreach($groups as $key=>$group): ?>
            <button class="template-tab <?=$first?'active':''?>" type="button" data-template-tab="<?=e($key)?>"><?=e($group['label'])?> <span><?=e($group['price_label'])?></span></button>
          <?php $first=false; endforeach ?>
        </div>
        <input class="template-search" type="search" placeholder="Cari template..." aria-label="Cari template">
      </div>

      <?php $first=true; foreach($groups as $key=>$group): ?>
        <div class="template-category <?=$first?'active':''?>" data-template-panel="<?=e($key)?>">
          <h3><?=e($group['label'])?> <span class="category-price"><?=e($group['price_label'])?></span></h3>
          <?php if($group['templates']): ?>
            <div class="templates">
              <?php $i=0; foreach($group['templates'] as $templateKey=>$template): $i++; ?>
                <article class="template <?=$i>5?'is-hidden':''?>" data-name="<?=e(strtolower($template['name']))?>">
                  <a class="template-thumb" target="_blank" rel="noopener" href="<?=$adminPrefix?>/template_preview.php?template=<?=urlencode($templateKey)?>">
                    <div class="thumb-phone">
                      <div class="thumb-art"><img src="<?=$adminPrefix?>/assets/brand/d-webin-logo.svg" alt=""></div>
                      <div class="thumb-caption"><small><?=e($template['category'])?></small></div>
                    </div>
                  </a>
                  <div class="template-info">
                    <div class="template-copy"><b><?=e($template['name'])?></b><span class="template-price"><?=e($template['price_label'])?></span></div>
                    <a target="_blank" rel="noopener" href="<?=$adminPrefix?>/template_preview.php?template=<?=urlencode($templateKey)?>">Buka Preview</a>
                  </div>
                </article>
              <?php endforeach ?>
            </div>
            <?php if(count($group['templates']) > 5): ?>
              <button class="btn show-more" type="button" data-show-more="<?=e($key)?>">Lihat semua <?=count($group['templates'])?> template</button>
            <?php endif ?>
          <?php else: ?>
            <div class="template-empty">Template <?=e($group['label'])?> segera hadir.</div>
          <?php endif ?>
        </div>
      <?php $first=false; endforeach ?>
    </div>
  </section>

  <section class="band">
    <div class="wrap">
      <span class="section-kicker">Alur kerja</span>
      <h2 class="section-title">Dari data acara ke link siap sebar.</h2>
      <div style="height:28px"></div>
      <div class="steps">
        <div class="step"><b>1. Pilih template</b><p>Tentukan desain yang paling cocok dengan karakter acara.</p></div>
        <div class="step"><b>2. Isi konten</b><p>Nama pasangan, tanggal, lokasi, foto, video, dan link peta.</p></div>
        <div class="step"><b>3. Sebar link</b><p>Import daftar tamu, salin link personal, lalu kirim ke WhatsApp.</p></div>
      </div>
    </div>
  </section>

  <section id="harga" class="band cream">
    <div class="wrap">
      <span class="section-kicker">Kategori & harga</span>
      <h2 class="section-title">Harga mengikuti kategori template.</h2>
      <div style="height:28px"></div>
      <div class="packages">
        <?php foreach($groups as $key=>$group): ?>
          <article class="package <?=$key==='animation'?'highlight':''?>"><b><?=e($group['label'])?></b><div class="price"><?=e($group['price_label'])?></div><p><?=e($group['templates'] ? 'Template siap pakai dengan nama tamu personal dan dashboard pengelolaan.' : 'Kategori disiapkan untuk koleksi template berikutnya.')?></p><div class="list"><div>Nama tamu personal</div><div>Kelola tamu + import CSV</div><div>Preview sebelum publish</div></div></article>
        <?php endforeach ?>
      </div>
    </div>
  </section>

  <section class="cta">
    <div class="wrap cta-grid">
      <div><h2>Siap bikin undangan yang enak dibuka dan mudah disebar?</h2><p>Kirim brief acara, pilih template, lalu biarkan dashboard D-Webin yang merapikan sisanya.</p></div>
      <a class="btn primary" href="mailto:halo@d-webindigital.web.id?subject=Pesan%20Undangan%20Digital%20D-Webin">Konsultasi Sekarang</a>
    </div>
  </section>
  <footer class="footer"><div class="wrap"><span>D-Webin Digital Invitation</span><a class="admin-link" href="<?=$adminPrefix?>/login.php">Admin</a></div></footer>
  <script>
    var searchInput = document.querySelector('.template-search');
    function activePanel() { return document.querySelector('.template-category.active'); }
    function applySearch() {
      var query = (searchInput.value || '').trim().toLowerCase();
      var panel = activePanel();
      if (!panel) return;
      panel.querySelectorAll('.template').forEach(function (card) {
        card.classList.toggle('search-hidden', query && card.getAttribute('data-name').indexOf(query) === -1);
      });
    }
    document.querySelectorAll('[data-template-tab]').forEach(function (tab) {
      tab.addEventListener('click', function () {
        var target = tab.getAttribute('data-template-tab');
        document.querySelectorAll('[data-template-tab]').forEach(function (item) { item.classList.toggle('active', item === tab); });
        document.querySelectorAll('[data-template-panel]').forEach(function (panel) { panel.classList.toggle('active', panel.getAttribute('data-template-panel') === target); });
        applySearch();
      });
    });
    document.querySelectorAll('[data-show-more]').forEach(function (button) {
      button.addEventListener('click', function () {
        var panel = document.querySelector('[data-template-panel="' + button.getAttribute('data-show-more') + '"]');
        if (!panel) return;
        panel.querySelectorAll('.template.is-hidden').forEach(function (card) { card.classList.remove('is-hidden'); });
        button.remove();
        applySearch();
      });
    });
    if (searchInput) searchInput.addEventListener('input', applySearch);
  </script>
</body>
</html>
