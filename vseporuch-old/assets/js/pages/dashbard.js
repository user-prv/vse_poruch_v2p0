(function () {
  "use strict";

  window.PORUCH = window.PORUCH || { lib: {}, pages: {} };

  window.PORUCH.pages.dashboard = {
    init: function () {
      var dom = window.PORUCH.lib.dom;

      // Приклад: всі форми з data-confirm покажуть confirm
      dom.qsa("form[data-confirm]").forEach(function (f) {
        dom.on(f, "submit", function (e) {
          var msg = f.getAttribute("data-confirm") || "Підтвердити дію?";
          if (!confirm(msg)) e.preventDefault();
        });
      });
    }
  };

})();