(function () {
  "use strict";

  window.PORUCH = window.PORUCH || { lib: {}, pages: {} };

  // =========================================================
  // Геолокація (одноразове визначення)
  // =========================================================
  function getPositionOnce(options) {
    options = options || { enableHighAccuracy: true, timeout: 8000, maximumAge: 60000 };

    return new Promise(function (resolve, reject) {
      if (!navigator.geolocation) {
        reject(new Error("Geolocation not supported"));
        return;
      }

      navigator.geolocation.getCurrentPosition(
        function (pos) {
          resolve({
            lat: pos.coords.latitude,
            lng: pos.coords.longitude,
            accuracy: pos.coords.accuracy
          });
        },
        function (err) {
          // err.code: 1 PERMISSION_DENIED, 2 POSITION_UNAVAILABLE, 3 TIMEOUT
          var e = new Error(err && err.code === 1 ? "Permission denied" : "Geolocation error");
          e.code = err && err.code;
          reject(e);
        },
        options
      );
    });
  }

  window.PORUCH.lib.geo = { getPositionOnce: getPositionOnce };

})();