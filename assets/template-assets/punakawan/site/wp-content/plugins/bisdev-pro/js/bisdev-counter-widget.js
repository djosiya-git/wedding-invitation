(function(){
  "use strict";

  function clamp(v, min, max){
    return Math.min(max, Math.max(min, v));
  }

  function parseNumber(raw, fallback){
    if (raw === null || raw === undefined || raw === "") return fallback;
    raw = String(raw).replace(",", ".").trim();
    var n = Number(raw);
    return Number.isFinite(n) ? n : fallback;
  }

  function formatWithThousands(value, mode){
    var sign = value < 0 ? "-" : "";
    var digits = String(Math.abs(Math.round(value)));

    if (mode === "default") {
      try {
        return sign + Number(digits).toLocaleString();
      } catch (e) {
        return sign + digits.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
      }
    }

    var sep = ",";
    if (mode === "dot") sep = ".";
    else if (mode === "space") sep = " ";
    else if (mode === "apostrophe") sep = "'";
    else if (mode === "comma") sep = ",";

    return sign + digits.replace(/\B(?=(\d{3})+(?!\d))/g, sep);
  }

  function formatValue(value, useThousands, mode, prefix, suffix){
    var body = useThousands
      ? formatWithThousands(value, mode)
      : String(Math.round(value));

    return (prefix || "") + body + (suffix || "");
  }

  function formatRangeValue(a, b, useThousands, mode, prefix, suffix){
    var left = useThousands ? formatWithThousands(a, mode) : String(Math.round(a));
    var right = useThousands ? formatWithThousands(b, mode) : String(Math.round(b));
    return (prefix || "") + left + " - " + right + (suffix || "");
  }

  function animateCounter(root){
    var numberEl = root.querySelector(".idb-counter__number");
    if (!numberEl) return;

    var start = parseNumber(root.getAttribute("data-start"), 0);
    var end = parseNumber(root.getAttribute("data-end"), 100);
    var endSecondary = parseNumber(root.getAttribute("data-end-secondary"), null);
    var hasRange = Number.isFinite(endSecondary);
    var duration = parseNumber(root.getAttribute("data-duration"), 2000);
    duration = clamp(duration, 100, 600000);

    var prefix = root.getAttribute("data-prefix") || "";
    var suffix = root.getAttribute("data-suffix") || "";
    var mode = root.getAttribute("data-separator-mode") || "default";
    var useThousands = (root.getAttribute("data-thousand") || "1") === "1";

    if (root.__idbCounterRaf) {
      cancelAnimationFrame(root.__idbCounterRaf);
      root.__idbCounterRaf = null;
    }

    var startedAt = 0;
    function step(ts){
      if (!startedAt) startedAt = ts;
      var progress = clamp((ts - startedAt) / duration, 0, 1);
      var current = start + ((end - start) * progress);
      if (hasRange) {
        var currentSecondary = start + ((endSecondary - start) * progress);
        numberEl.textContent = formatRangeValue(current, currentSecondary, useThousands, mode, prefix, suffix);
      } else {
        numberEl.textContent = formatValue(current, useThousands, mode, prefix, suffix);
      }

      if (progress < 1) {
        root.__idbCounterRaf = requestAnimationFrame(step);
      } else {
        root.__idbCounterRaf = null;
      }
    }

    if (hasRange) {
      numberEl.textContent = formatRangeValue(start, start, useThousands, mode, prefix, suffix);
    } else {
      numberEl.textContent = formatValue(start, useThousands, mode, prefix, suffix);
    }
    root.__idbCounterRaf = requestAnimationFrame(step);
  }

  function mount(root){
    if (!root) return;
    if (root.__idbCounterPlayed) return;
    root.__idbCounterPlayed = true;
    animateCounter(root);
  }

  function observeVisibility(root){
    if (!("IntersectionObserver" in window)) {
      mount(root);
      return;
    }

    var io = new IntersectionObserver(function(entries){
      for (var i = 0; i < entries.length; i++) {
        if (entries[i].isIntersecting) {
          mount(root);
          io.disconnect();
          break;
        }
      }
    }, { threshold: 0.2 });

    io.observe(root);
  }

  function scan(ctx){
    var root = ctx || document;
    if (!root.querySelectorAll) return;
    var list = root.querySelectorAll(".idb-counter");
    for (var i = 0; i < list.length; i++) {
      observeVisibility(list[i]);
    }
  }

  function bindElementor(){
    try {
      if (window.elementorFrontend && window.elementorFrontend.hooks && window.elementorFrontend.hooks.addAction) {
        window.elementorFrontend.hooks.addAction("frontend/element_ready/bisdev_counter.default", function($scope){
          var node = $scope && ($scope[0] || $scope);
          if (!node) return;
          var widgets = node.querySelectorAll ? node.querySelectorAll(".idb-counter") : [];
          for (var i = 0; i < widgets.length; i++) {
            widgets[i].__idbCounterPlayed = false;
          }
          scan(node);
        });
        return true;
      }
    } catch (e) {}
    return false;
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function(){ scan(document); });
  } else {
    scan(document);
  }

  if (!bindElementor()) {
    var tries = 0;
    var t = setInterval(function(){
      tries++;
      if (bindElementor() || tries >= 20) clearInterval(t);
    }, 250);
  }

  if (window.jQuery) {
    jQuery(window).on("elementor/frontend/init", function(){
      scan(document);
      bindElementor();
    });
  }
})();
