(function ($) {

  /**
   * Registry untuk jeda otomatis saat video undangan (YouTube) bersuara.
   */
  window.__bisdevUndanganMusik = window.__bisdevUndanganMusik || { entries: [] };

  window.__bisdevPauseUndanganMusicForVideo = function () {
    var list = window.__bisdevUndanganMusik && window.__bisdevUndanganMusik.entries;
    if (!list || !list.length) return;
    list.forEach(function (en) {
      if (typeof en.wasPlayingBeforeDuck !== 'undefined') return;
      en.wasPlayingBeforeDuck = en.audio && !en.audio.paused;
      if (en.wasPlayingBeforeDuck && typeof en.pauseWithFade === 'function') {
        en.pauseWithFade();
      }
    });
  };

  window.__bisdevResumeUndanganMusicAfterVideo = function () {
    var list = window.__bisdevUndanganMusik && window.__bisdevUndanganMusik.entries;
    if (!list || !list.length) return;
    list.forEach(function (en) {
      if (en.wasPlayingBeforeDuck && typeof en.playWithFade === 'function') {
        en.playWithFade();
      }
      en.wasPlayingBeforeDuck = undefined;
    });
  };

  function clamp(n, min, max){ return Math.max(min, Math.min(max, n)); }

  // Fade yang bisa dibatalkan (biar play/pause tidak tabrakan)
  function makeFader(audio){
    let rafId = 0;
    return function fadeTo(target, duration, cb){
      if (rafId) cancelAnimationFrame(rafId);
      rafId = 0;

      duration = Math.max(0, duration || 0);
      const startVol = clamp(audio.volume, 0, 1);
      const endVol = clamp(target, 0, 1);

      if (duration === 0) {
        audio.volume = endVol;
        if (cb) cb();
        return;
      }

      const start = performance.now();
      function step(now) {
        const t = clamp((now - start) / duration, 0, 1);
        audio.volume = startVol + (endVol - startVol) * t;
        if (t < 1) rafId = requestAnimationFrame(step);
        else {
          rafId = 0;
          if (cb) cb();
        }
      }
      rafId = requestAnimationFrame(step);
    };
  }

  function initOne($box) {
    if (!$box || !$box.length) return;
    if (($box.attr('data-inited') || '0') === '1') return;
    $box.attr('data-inited', '1');

    const audio = $box.find('.idb-audio-el').get(0) || $box.find('audio').get(0);
    if (!audio) return;

    const fadeTo = makeFader(audio);

    const start    = parseInt($box.attr('data-start') || '0', 10) || 0;
    const end      = parseInt($box.attr('data-end') || '0', 10) || 0;
    const autoplay = ($box.attr('data-autoplay') || '') === '1';
    const loop     = ($box.attr('data-loop') || '') === '1';
    const volMax   = clamp(parseFloat($box.attr('data-volume') || '0.8'), 0, 1);

    const fadeIn   = Math.max(0, parseInt($box.attr('data-fadein') || '600', 10) || 0);
    const fadeOut  = Math.max(0, parseInt($box.attr('data-fadeout') || '600', 10) || 0);

    const rotateOn = ($box.attr('data-rotate') || '') === '1';
    const rotateSpeed = Math.max(3, parseInt($box.attr('data-rotatespeed') || '12', 10) || 12);

    const $mute   = $box.find('.idb-mute-sound');    // tampil saat pause
    const $unmute = $box.find('.idb-unmute-sound');  // tampil saat play

    // ✅ Gate ketat: HARUS true
    function isAllowedToPlay(){
      return window.__BISDEV_MUSIC_ALLOWED === true;
    }

    function setUI(isPlaying) {
      if ($mute.length)   $mute.css('display', isPlaying ? 'none' : '');
      if ($unmute.length) $unmute.css('display', isPlaying ? '' : 'none');

      if (!rotateOn) return;

      if (isPlaying) {
        $box.addClass('idb-rotating');
        $box.get(0).style.setProperty('--idb-rotate-speed', rotateSpeed + 's');
      } else {
        $box.removeClass('idb-rotating');
        const el = $unmute.find('.elementor-icon').get(0);
        if (el) {
          el.style.animation = 'none';
          el.style.webkitAnimation = 'none';
          void el.offsetHeight;
          el.style.animation = '';
          el.style.webkitAnimation = '';
        }
      }
    }

    function seekToStartIfNeeded() {
      try {
        if (start > 0 && audio.currentTime < start) audio.currentTime = start;
      } catch (e) {}
    }

    audio.loop = (loop && !end);

    audio.addEventListener('loadedmetadata', function () {
      if (start > 0) {
        try { audio.currentTime = start; } catch (e) {}
      }
    });

    audio.addEventListener('timeupdate', function () {
      if (end > 0 && audio.currentTime >= end) {
        if (loop) {
          try {
            audio.currentTime = start > 0 ? start : 0;
            audio.play().catch(()=>{});
          } catch (e) {}
        } else {
          setUI(false);
          fadeTo(0, fadeOut, function(){
            audio.pause();
            try { audio.currentTime = start > 0 ? start : 0; } catch(e){}
          });
        }
      }
    });

    audio.addEventListener('play',  function(){ setUI(true); });
    audio.addEventListener('pause', function(){ setUI(false); });

    function playWithFade() {
      if (!isAllowedToPlay()) return;

      seekToStartIfNeeded();
      audio.volume = 0;

      const p = audio.play();
      if (p && typeof p.catch === 'function') {
        p.catch((err) => {
          if (err && err.name === 'AbortError') return;
          console.warn('[Bisdev Musik] play() blocked:', err);
          setUI(false);
        });
      }

      fadeTo(volMax, fadeIn);
    }

    function pauseWithFade() {
      setUI(false);
      fadeTo(0, fadeOut, function(){
        audio.pause();
      });
    }

    function toggle() {
      if (audio.paused) playWithFade();
      else pauseWithFade();
    }

    function onUserTrigger() {
      window.__BISDEV_MUSIC_ALLOWED = true;
      toggle();
    }

    if ($mute.length)   $mute.on('click', onUserTrigger);
    if ($unmute.length) $unmute.on('click', onUserTrigger);

    $mute.find('.elementor-icon').on('keydown', function(e){
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); onUserTrigger(); }
    });
    $unmute.find('.elementor-icon').on('keydown', function(e){
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); onUserTrigger(); }
    });

    // ✅ visibilitychange: hanya PAUSE saat hidden, TIDAK RESUME saat visible
    document.addEventListener('visibilitychange', function(){
      if (document.visibilityState === 'hidden') {
        if (!audio.paused) pauseWithFade();
      }
      // visible: do nothing (biar tetap pause)
    });

    // ✅ autoplay tetap patuh gate (kalau kamu mau benar-benar tidak pernah autoplay, matikan di widget)
    if (autoplay) {
      const tryAuto = () => {
        if (!isAllowedToPlay()) return;
        playWithFade();
      };
      if (audio.readyState >= 2) tryAuto();
      else audio.addEventListener('canplay', tryAuto, { once: true });
    }

    if (window.__bisdevUndanganMusik && Array.isArray(window.__bisdevUndanganMusik.entries)) {
      window.__bisdevUndanganMusik.entries.push({
        audio: audio,
        pauseWithFade: pauseWithFade,
        playWithFade: playWithFade,
        wasPlayingBeforeDuck: undefined
      });
    }

    setUI(false);
  }

  function bindElementor() {
    if (typeof elementorFrontend === 'undefined') return;

    // Elementor editor sometimes loads elementorFrontend.hooks later.
    // Guard + retry to avoid "Cannot read properties of undefined (reading 'addAction')".
    let tries = 0;
    const maxTries = 40; // ~4s
    (function waitForHooks(){
      const hooks = elementorFrontend && elementorFrontend.hooks;
      if (hooks && typeof hooks.addAction === 'function') {
        hooks.addAction(
          'frontend/element_ready/bisdev_musik.default',
          function ($scope) {
            const $box = $scope.find('.idb-audio-box');
            initOne($box);
          }
        );
        return;
      }
      tries++;
      if (tries < maxTries) setTimeout(waitForHooks, 100);
    })();
  }

  $(window).on('load', function () {
    bindElementor();
    $('.idb-audio-box').each(function () { initOne($(this)); });
  });

})(jQuery);