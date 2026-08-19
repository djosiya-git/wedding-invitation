/**
 * Bisdev Konfirmasi Transfer Widget
 * - Handle form submit
 * - Redirect ke WhatsApp dengan pesan yang diisi user
 */
(function () {
  'use strict';

  function init() {
    document.querySelectorAll('.idb-konfirmasi-transfer').forEach(function (wrap) {
      if (wrap.dataset.inited === '1') return;
      wrap.dataset.inited = '1';

      var form = wrap.querySelector('.idb-konfirmasi-transfer__form');
      if (!form) return;

      form.addEventListener('submit', function (e) {
        e.preventDefault();

        var namaEl = form.querySelector('[name="nama"]');
        var nominalEl = form.querySelector('[name="nominal"]');
        var ucapanEl = form.querySelector('[name="ucapan"]');
        if (!namaEl || !nominalEl || !ucapanEl) return;

        var nama = (namaEl.value || '').trim();
        var nominal = (nominalEl.value || '').trim();
        var ucapan = (ucapanEl.value || '').trim();

        if (!nama) {
          namaEl.focus();
          return;
        }
        if (!nominal) {
          nominalEl.focus();
          return;
        }

        var waOn = wrap.dataset.wa === '1';
        var waNumber = (wrap.dataset.waNumber || '').trim();
        var waTemplate = wrap.dataset.waPrefix || 'Hai, saya %nama%. Saya ingin mengonfirmasi bahwa saya sudah melakukan transfer wedding gift untuk acara pernikahan Anda.\nDetail transfer: %detail%\nSaya ucapkan: %pesan%.\nTerima kasih ya.';

        if (waOn && waNumber) {
          var msg = String(waTemplate)
            .replace(/%nama%/gi, nama)
            .replace(/%detail%/gi, nominal)
            .replace(/%pesan%/gi, ucapan);

          // Nomor sudah dinormalisasi di sisi PHP (helper idb_normalize_wa_number).
          var clean = waNumber.replace(/\D/g, '');
          if (!clean) return;

          var url = 'https://wa.me/' + clean + '?text=' + encodeURIComponent(msg);
          window.open(url, '_blank', 'noopener,noreferrer');
        } else {
          // Tanpa WhatsApp: bisa trigger custom event untuk integrasi lain
          var ev = new CustomEvent('bisdev-konfirmasi-transfer-submit', {
            detail: { nama: nama, nominal: nominal, ucapan: ucapan }
          });
          wrap.dispatchEvent(ev);
        }

        form.reset();
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Elementor live edit: re-init saat frontend reload
  if (window.elementorFrontend && elementorFrontend.hooks) {
    elementorFrontend.hooks.addAction('frontend/element_ready/bisdev_konfirmasi_transfer.default', function () {
      init();
    });
  }
})();
