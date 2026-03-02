(function () {
  "use strict";

  window.PORUCH = window.PORUCH || { lib: {}, pages: {} };

  window.PORUCH.pages.admin = {
    init: function () {
      var dom = window.PORUCH.lib.dom;

      dom.qsa("form[data-confirm], a[data-confirm], button[data-confirm]").forEach(function (el) {
        var tag = (el.tagName || "").toLowerCase();

        if (tag === "form") {
          dom.on(el, "submit", function (e) {
            var msg = el.getAttribute("data-confirm") || "Підтвердити дію?";
            if (!confirm(msg)) e.preventDefault();
          });
        } else {
          dom.on(el, "click", function (e) {
            var msg = el.getAttribute("data-confirm") || "Підтвердити дію?";
            if (!confirm(msg)) {
              e.preventDefault();
              e.stopPropagation();
            }
          });
        }
      });
    }
  };

})();