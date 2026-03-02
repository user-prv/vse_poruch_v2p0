(function () {
  "use strict";

  window.PORUCH = window.PORUCH || { lib: {}, pages: {} };

  // =========================================================
  // localStorage helper
  // =========================================================
  function get(key, fallback) {
    try {
      var raw = localStorage.getItem(key);
      if (!raw) return fallback;
      return JSON.parse(raw);
    } catch (_) {
      return fallback;
    }
  }

  function set(key, val) {
    try { localStorage.setItem(key, JSON.stringify(val)); } catch (_) {}
  }

  function del(key) {
    try { localStorage.removeItem(key); } catch (_) {}
  }

  window.PORUCH.lib.storage = { get: get, set: set, del: del };

})();