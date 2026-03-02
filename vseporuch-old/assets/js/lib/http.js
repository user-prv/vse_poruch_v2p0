(function () {
  "use strict";

  window.PORUCH = window.PORUCH || { lib: {}, pages: {} };

  // =========================================================
  // HTTP утиліти: fetchJson + querystring
  // =========================================================

  function buildQuery(params) {
    var usp = new URLSearchParams();
    Object.keys(params || {}).forEach(function (k) {
      var v = params[k];
      if (v === undefined || v === null || v === "") return;
      usp.set(k, String(v));
    });
    return usp.toString();
  }

  async function fetchJson(url, opts) {
    var res = await fetch(url, Object.assign({ cache: "no-store" }, (opts || {})));
    var text = await res.text();

    // Завжди намагаємось повернути JSON (або помилку)
    var data = null;
    try { data = JSON.parse(text); } catch (_) {}

    if (!res.ok) {
      var err = new Error("HTTP " + res.status);
      err.status = res.status;
      err.data = data;
      err.raw = text;
      throw err;
    }

    if (!data) {
      var e = new Error("Invalid JSON response");
      e.raw = text;
      throw e;
    }

    return data;
  }

  window.PORUCH.lib.http = { buildQuery: buildQuery, fetchJson: fetchJson };

})();