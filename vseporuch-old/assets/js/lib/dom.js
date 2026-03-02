(function () {
  "use strict";

  window.PORUCH = window.PORUCH || { lib: {}, pages: {} };

  // =========================================================
  // DOM утиліти (коротко і зручно)
  // =========================================================
  function qs(sel, root) {
    return (root || document).querySelector(sel);
  }

  function qsa(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  function on(el, ev, fn) {
    if (!el) return;
    el.addEventListener(ev, fn);
  }

  function debounce(fn, ms) {
    var t = null;
    return function () {
      var args = arguments;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(null, args); }, ms);
    };
  }

  window.PORUCH.lib.dom = { qs: qs, qsa: qsa, on: on, debounce: debounce };

})();