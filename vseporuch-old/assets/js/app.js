(function () {
  "use strict";

  // =========================================================
  // app.js — єдиний файл, який підключаєш на всіх сторінках.
  // Він завантажує lib/* і потрібний pages/*.js за body[data-page]
  // =========================================================

  // Глобальний неймспейс
  window.PORUCH = window.PORUCH || { lib: {}, pages: {} };

  // Визначаємо сторінку
  var page = (document.body && document.body.dataset && document.body.dataset.page) ? document.body.dataset.page : "";

  // Якщо data-page не поставили — нічого не робимо (щоб не ламати)
  if (!page) return;

  // Базовий список файлів, які треба підвантажити
  var baseLibs = [
    "/assets/js/lib/dom.js",
    "/assets/js/lib/http.js",
    "/assets/js/lib/storage.js",
    "/assets/js/lib/geo.js"
  ];

  // JS сторінок
  var pageMap = {
    home: "/assets/js/pages/home.js",
    item: "/assets/js/pages/item.js",
    dashboard: "/assets/js/pages/dashboard.js",
    admin: "/assets/js/pages/admin.js"
  };

  var pageFile = pageMap[page];
  if (!pageFile) return;

  // ---------------------------------------------------------
  // Завантаження скриптів ПО ЧЕРЗІ (щоб lib точно були готові)
  // ---------------------------------------------------------
  function loadScript(src) {
    return new Promise(function (resolve, reject) {
      var s = document.createElement("script");
      s.src = src + (src.indexOf("?") === -1 ? "?v=1" : "");
      s.defer = true;
      s.onload = resolve;
      s.onerror = function () { reject(new Error("Failed to load: " + src)); };
      document.head.appendChild(s);
    });
  }

  (async function bootstrap() {
    try {
      for (var i = 0; i < baseLibs.length; i++) {
        await loadScript(baseLibs[i]);
      }
      await loadScript(pageFile);

      // Якщо сторінка зареєструвала init — запускаємо
      var initFn = window.PORUCH.pages && window.PORUCH.pages[page] && window.PORUCH.pages[page].init;
      if (typeof initFn === "function") initFn();

    } catch (e) {
      console.error(e);
    }
  })();

})();