<?php
require __DIR__.'/../lib.php';
$inv = require_guestbook_customer();
$stats = guestbook_stats($inv['slug']);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Scan Undangan - <?=e($inv['title'])?></title>
  <link rel="icon" href="../assets/brand/d-webin-logo.svg" sizes="any" type="image/svg+xml">
  <link rel="stylesheet" href="../assets/admin.css?v=<?=e((string)filemtime(__DIR__.'/../assets/admin.css'))?>">
</head>
<body class="guestbook-body">
<aside class="sidebar">
  <div class="logo"><img src="../assets/brand/d-webin-logo.svg" alt="D-Webin"></div>
  <nav>
    <a href="index.php">Dashboard</a>
    <a class="active" href="scan.php">Scan Undangan</a>
    <a href="../guests.php?slug=<?=urlencode($inv['slug'])?>">Kelola Tamu</a>
    <a href="logout.php">Keluar</a>
  </nav>
</aside>
<main class="app">
  <header>
    <div>
      <h1>Scan Undangan</h1>
      <p><?=e($inv['title'])?> &middot; <?=e((string)$stats['checked_in'])?> tamu sudah check-in</p>
    </div>
    <a class="btn" href="index.php">Kembali ke Dashboard</a>
  </header>

  <section class="guestbook-scan-grid">
    <div class="panel scanner-panel">
      <div class="panel-head">
        <div>
          <h2>Kamera Scanner</h2>
          <p class="panel-note">Arahkan kamera ke QR atau barcode undangan tamu.</p>
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
      <small class="scanner-help">Jika browser belum mendukung scanner otomatis, gunakan input manual di samping.</small>
    </div>

    <div class="panel checkin-panel">
      <div class="panel-head">
        <div>
          <h2>Validasi Tamu</h2>
          <p class="panel-note">Masukkan hasil scan, link undangan, ID tamu, atau kode tamu.</p>
        </div>
      </div>
      <form id="manualForm" class="form">
        <label>Kode / Link Undangan
          <textarea id="manualPayload" rows="5" placeholder="Contoh: https://.../view.php?slug=...&guest=5"></textarea>
        </label>
        <button class="btn primary">Check-in Tamu</button>
      </form>
      <div class="scan-result" id="scanResult">
        <b>Menunggu scan</b>
        <p>Data tamu akan muncul setelah kode berhasil divalidasi.</p>
      </div>
    </div>
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
  var stream = null;
  var detector = null;
  var scanning = false;
  var lastPayload = '';
  var lastScanAt = 0;

  function setResult(type, title, message, detail) {
    resultBox.className = 'scan-result ' + type;
    resultBox.innerHTML = '<b>' + title + '</b><p>' + message + '</p>' + (detail ? '<small>' + detail + '</small>' : '');
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
