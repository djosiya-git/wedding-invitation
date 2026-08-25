<?php
require __DIR__.'/../lib.php';
$inv = require_guestbook_customer();
$guests = all_guests($inv['slug']);
$checkedGuests = array_values(array_filter($guests, fn($guest) => !empty($guest['checked_in_at'])));
$stats = guestbook_stats($inv['slug']);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Guestbook - <?=e($inv['title'])?></title>
  <?=app_favicon_tags('../')?>
  <?=app_stylesheet_tags('../')?>
</head>
<body class="guestbook-body">
<aside class="sidebar">
  <?=app_logo_mark('logo', '../')?>
  <nav>
    <a class="active" href="index.php">Dashboard</a>
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
    <a class="btn primary" href="#scanner">Scan Undangan</a>
  </header>

  <section class="guestbook-stats">
    <div><b><?=e((string)$stats['total'])?></b><span>Total tamu</span></div>
    <div><b id="statChecked"><?=e((string)$stats['checked_in'])?></b><span>Sudah check-in</span></div>
    <div><b id="statRemaining"><?=e((string)$stats['remaining'])?></b><span>Belum hadir</span></div>
  </section>

  <section class="guestbook-event-grid" id="scanner">
    <div class="panel scanner-panel">
      <div class="panel-head">
        <div>
          <h2>Scan & Validasi Tamu</h2>
          <p class="panel-note">Arahkan kamera ke QR undangan, atau masukkan kode/link secara manual.</p>
        </div>
      </div>
      <div class="camera-shell">
        <video id="camera" playsinline muted></video>
        <div class="camera-overlay"><span></span></div>
        <div class="camera-placeholder" id="cameraPlaceholder">Kamera belum aktif.</div>
      </div>
      <div class="scanner-actions">
        <button class="btn primary" type="button" id="startScanner">Aktifkan Kamera</button>
        <button class="btn" type="button" id="stopScanner">Matikan Kamera</button>
      </div>

      <form id="manualForm" class="form scanner-manual-form">
        <label>Kode / Link Undangan
          <textarea id="manualPayload" rows="5" placeholder="Contoh: https://.../view.php?slug=...&guest=5"></textarea>
        </label>
        <button class="btn primary">Check-in Tamu</button>
      </form>
      <div class="scan-result" id="scanResult">
        <b>Menunggu scan</b>
        <p>Data tamu akan muncul setelah kode berhasil divalidasi.</p>
      </div>
      <small class="scanner-help">Jika browser belum mendukung scanner otomatis, gunakan input manual di bawah kamera.</small>
    </div>

    <section class="panel guestbook-attendance-panel">
      <div class="panel-head">
        <div>
          <h2>Daftar Hadir</h2>
          <p class="panel-note">Hanya menampilkan tamu yang sudah berhasil check-in.</p>
        </div>
      </div>
      <?php if(!$checkedGuests): ?>
        <div class="empty" id="attendanceEmpty">Belum ada tamu yang hadir.</div>
      <?php else: ?>
        <div class="table-wrap guestbook-table" id="attendanceWrap">
          <table>
            <thead>
              <tr><th>Nama</th><th>Waktu</th></tr>
            </thead>
            <tbody id="attendanceRows">
              <?php foreach($checkedGuests as $guest): ?>
                <tr data-guest-id="<?=(int)$guest['id']?>">
                  <td><b><?=e($guest['name'])?></b></td>
                  <td><small><?=e(format_datetime_label($guest['checked_in_at']))?></small></td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      <?php endif ?>
      <?php if(!$checkedGuests): ?>
        <div class="table-wrap guestbook-table is-hidden" id="attendanceWrap">
          <table>
            <thead>
              <tr><th>Nama</th><th>Waktu</th></tr>
            </thead>
            <tbody id="attendanceRows"></tbody>
          </table>
        </div>
      <?php endif ?>
    </section>
  </section>

</main>
<script>
(function () {
  var video = document.getElementById('camera');
  var placeholder = document.getElementById('cameraPlaceholder');
  var startButton = document.getElementById('startScanner');
  var stopButton = document.getElementById('stopScanner');
  var form = document.getElementById('manualForm');
  var input = document.getElementById('manualPayload');
  var resultBox = document.getElementById('scanResult');
  var checkedStat = document.getElementById('statChecked');
  var remainingStat = document.getElementById('statRemaining');
  var attendanceRows = document.getElementById('attendanceRows');
  var attendanceWrap = document.getElementById('attendanceWrap');
  var attendanceEmpty = document.getElementById('attendanceEmpty');
  var stream = null;
  var detector = null;
  var scanning = false;
  var lastPayload = '';
  var lastScanAt = 0;

  function setResult(type, title, message, detail) {
    resultBox.className = 'scan-result ' + type;
    resultBox.innerHTML = '<b>' + title + '</b><p>' + message + '</p>' + (detail ? '<small>' + detail + '</small>' : '');
  }

  function updateStats(stats) {
    if (!stats) return;
    if (checkedStat) checkedStat.textContent = stats.checked_in;
    if (remainingStat) remainingStat.textContent = stats.remaining;
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
    });
  }

  function upsertAttendance(guest) {
    if (!attendanceRows || !guest || !guest.id) return;
    if (attendanceEmpty) attendanceEmpty.style.display = 'none';
    if (attendanceWrap) attendanceWrap.classList.remove('is-hidden');
    var existing = attendanceRows.querySelector('[data-guest-id="' + guest.id + '"]');
    var html = '<td><b>' + escapeHtml(guest.name) + '</b></td><td><small>' + escapeHtml(guest.checked_in_label || '-') + '</small></td>';
    if (existing) {
      existing.innerHTML = html;
      return;
    }
    var row = document.createElement('tr');
    row.setAttribute('data-guest-id', guest.id);
    row.innerHTML = html;
    attendanceRows.insertBefore(row, attendanceRows.firstChild);
  }

  function submitPayload(payload) {
    payload = String(payload || '').trim();
    if (!payload) return;
    var now = Date.now();
    if (payload === lastPayload && now - lastScanAt < 3500) return;
    lastPayload = payload;
    lastScanAt = now;
    setResult('loading', 'Memvalidasi', 'Sistem sedang mengecek data tamu.');
    var body = new URLSearchParams();
    body.set('payload', payload);
    fetch('checkin.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: body.toString()
    })
      .then(function (res) {
        return res.json().then(function (data) {
          if (!res.ok || !data.ok) throw data;
          return data;
        });
      })
      .then(function (data) {
        var guest = data.guest || {};
        var detail = guest.group_label ? guest.group_label + ' - ' + guest.checked_in_label : guest.checked_in_label;
        updateStats(data.stats);
        upsertAttendance(guest);
        setResult(data.status === 'already_checked_in' ? 'warning' : 'success', guest.name || 'Tamu valid', data.message, detail);
      })
      .catch(function (err) {
        setResult('error', 'Check-in gagal', (err && err.message) || 'Kode undangan tidak dapat divalidasi.');
      });
  }

  function scanLoop() {
    if (!scanning || !detector) return;
    detector.detect(video).then(function (codes) {
      if (codes && codes.length) submitPayload(codes[0].rawValue || '');
    }).catch(function () {}).finally(function () {
      if (scanning) requestAnimationFrame(scanLoop);
    });
  }

  startButton.addEventListener('click', function () {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      setResult('error', 'Kamera tidak tersedia', 'Browser ini belum mendukung akses kamera.');
      return;
    }
    if (!('BarcodeDetector' in window)) {
      setResult('warning', 'Scanner otomatis belum didukung', 'Kamera tetap dapat digunakan, namun kode perlu dimasukkan secara manual.');
    } else {
      detector = new BarcodeDetector({ formats: ['qr_code', 'code_128', 'code_39', 'ean_13'] });
    }
    navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false })
      .then(function (mediaStream) {
        stream = mediaStream;
        video.srcObject = stream;
        return video.play();
      })
      .then(function () {
        placeholder.style.display = 'none';
        scanning = !!detector;
        if (scanning) scanLoop();
      })
      .catch(function () {
        setResult('error', 'Kamera gagal dibuka', 'Pastikan izin kamera sudah diberikan pada browser.');
      });
  });

  stopButton.addEventListener('click', function () {
    scanning = false;
    if (stream) stream.getTracks().forEach(function (track) { track.stop(); });
    stream = null;
    video.srcObject = null;
    placeholder.style.display = 'grid';
  });

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    submitPayload(input.value);
  });
})();
</script>
</body>
</html>
