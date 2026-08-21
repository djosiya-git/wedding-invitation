<?php
require __DIR__.'/lib.php';
$k = normalize_template_key($_GET['template'] ?? '');
$t = templates()[$k] ?? null;
if (!$t) {
    http_response_code(404);
    exit('Template tidak ditemukan');
}

$html = file_get_contents(__DIR__.'/templates/'.$t['file']);
$html = apply_template_runtime_fixes($html);
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
if (isset($_GET['thumb'])) {
    $thumbCss = <<<'HTML'
<style id="dwebin-thumbnail-mode">
html, body { scrollbar-width: none !important; }
html::-webkit-scrollbar, body::-webkit-scrollbar, *::-webkit-scrollbar { width: 0 !important; height: 0 !important; display: none !important; }
</style>
HTML;
    $html = str_ireplace('</head>', $thumbCss.'</head>', $html);
}
echo $html;
