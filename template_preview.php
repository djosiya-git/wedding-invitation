<?php
require __DIR__.'/lib.php';
$k = $_GET['template'] ?? '';
$t = templates()[$k] ?? null;
if (!$t) {
    http_response_code(404);
    exit('Template tidak ditemukan');
}

$html = file_get_contents(__DIR__.'/templates/'.$t['file']);
if (isset($_GET['mute'])) {
    $mute = <<<'HTML'
<script>
(function () {
  function muteMedia() {
    document.querySelectorAll('audio,video').forEach(function (media) {
      media.muted = true;
      media.volume = 0;
      media.removeAttribute('autoplay');
      media.pause();
    });
    document.querySelectorAll('iframe[src*="youtube"],iframe[src*="spotify"],iframe[src*="soundcloud"]').forEach(function (frame) {
      frame.remove();
    });
  }
  muteMedia();
  document.addEventListener('DOMContentLoaded', muteMedia);
  setInterval(muteMedia, 800);
})();
</script>
HTML;
    $html = str_ireplace('</body>', $mute.'</body>', $html);
}
echo $html;
