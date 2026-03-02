(function () {
  "use strict";

  window.PORUCH = window.PORUCH || { lib: {}, pages: {} };

  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, function (s) {
      return ({ "&":"&amp;", "<":"&lt;", ">":"&gt;", '"':"&quot;", "'":"&#039;" })[s];
    });
  }

  function isMobile() {
    return window.matchMedia && window.matchMedia("(max-width: 900px)").matches;
  }

  function normalizePhotoUrl(url) {
    if (!url) return null;
    var s = String(url).trim().replace(/\\/g, '/');
    if (!s) return null;
    if (/^(https?:\/\/|data:)/i.test(s)) return s;
    if (s.charAt(0) !== '/') s = '/' + s.replace(/^\/+/, '');
    return s;
  }

  window.PORUCH.pages.home = {
    init: function () {
      var dom = window.PORUCH.lib.dom;
      var http = window.PORUCH.lib.http;
      var storage = window.PORUCH.lib.storage;
      var geo = window.PORUCH.lib.geo;

      // --- Перевірки
      if (typeof L === "undefined") {
        console.error("Leaflet не завантажився");
        return;
      }
      if (!window.APP || !window.APP.apiListingsUrl) {
        console.error("Нема window.APP.apiListingsUrl (додай в header.php)");
        return;
      }

      // --- UI
      var qEl = dom.qs("#q");
      var btnSearch = dom.qs("#btnSearch");
      var listEl = dom.qs("#list");
      var countEl = dom.qs("#count");
      var statusEl = dom.qs("#status");

      function setStatus(msg) {
        if (!statusEl) return;
        statusEl.textContent = msg ? ("— " + msg) : "";
      }

      // --- Anchor (точка пошуку)
      var anchor = storage.get("poruch_anchor", null) || { lat: 50.4501, lng: 30.5234 };

      // --- Данні пошуку
      var lastItems = [];
      var markers = [];
      var markerById = new Map();

      // --- Map init
      var map = L.map("map").setView([anchor.lat, anchor.lng], 13);
      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "© OpenStreetMap"
      }).addTo(map);

      // Якір (можна тягнути на десктопі)
      var anchorMarker = L.marker([anchor.lat, anchor.lng], { draggable: true }).addTo(map);
      anchorMarker.on("dragend", function () {
        var p = anchorMarker.getLatLng();
        anchor = { lat: p.lat, lng: p.lng };
        storage.set("poruch_anchor", anchor);
        fetchListings();
      });

      function clearMarkers() {
        markers.forEach(function (m) { map.removeLayer(m); });
        markers = [];
        markerById.clear();
      }

      function renderMarkers(items) {
        clearMarkers();

        items.forEach(function (it) {
          var m = L.marker([it.lat, it.lng]).addTo(map);

          // Підтримка двох форматів: photo_urls[] або photo
          var firstPhotoRaw = (it.photo_urls && it.photo_urls.length) ? it.photo_urls[0] : (it.photo || null);
          var firstPhoto = normalizePhotoUrl(firstPhotoRaw);

          var imgHtml = firstPhoto
            ? '<div style="margin-top:8px;"><img src="' + escapeHtml(firstPhoto) + '" onerror="this.parentNode.remove()" style="max-width:220px;border-radius:10px;border:1px solid #e5e7eb;display:block;"></div>'
            : "";

          m.bindPopup(
            '<div style="font-weight:900;font-size:13px;">' + escapeHtml(it.title) + '</div>' +
            '<div style="font-size:12px;color:#6b7280;margin-top:4px;">' +
              escapeHtml(it.category || "") + ' • ' + Number(it.distanceKm || 0).toFixed(1) + ' км' +
            '</div>' +
            '<div style="margin-top:6px;font-weight:900;">' +
              (it.price != null ? (it.price + ' ' + escapeHtml(it.currency || "UAH")) : "Ціна не вказана") +
            '</div>' +
            imgHtml +
            '<div style="margin-top:8px;">' +
              '<a href="/item.php?id=' + it.id + '" style="display:inline-block;font-size:12px;font-weight:900;color:#2563eb;text-decoration:none;">Детальніше →</a>' +
            '</div>'
          );

          markers.push(m);
          markerById.set(String(it.id), m);
        });
      }

      // --- Render list (список — головний режим)
      function renderList(items) {
        if (!listEl) return;

        if (!items.length) {
          listEl.innerHTML = '<div class="pad muted">Нічого не знайдено. Зміни запит.</div>';
          return;
        }

        listEl.innerHTML = items.map(function (it) {
          var photoRaw = (it.photo_urls && it.photo_urls.length) ? it.photo_urls[0] : (it.photo || null);
          var photo = normalizePhotoUrl(photoRaw);

          return (
            '<div class="item" data-id="' + it.id + '">' +
              '<div style="display:flex;gap:10px;">' +
                '<div style="width:78px;">' +
                  (photo ? '<img src="' + escapeHtml(photo) + '" onerror="this.style.display=\'none\'" style="width:72px;height:56px;object-fit:cover;border-radius:10px;border:1px solid #e5e7eb;display:block;">' : '') +
                '</div>' +
                '<div style="flex:1;display:flex;justify-content:space-between;gap:10px;">' +
                  '<div style="min-width:0;">' +
                    '<div class="title">' + escapeHtml(it.title) + '</div>' +
                    '<div class="meta">' + escapeHtml(it.category || "Категорія") + ' • ' + Number(it.distanceKm || 0).toFixed(1) + ' км</div>' +
                    '<div class="row mt10" style="gap:12px;">' +
                      '<a class="details-btn" href="/item.php?id=' + it.id + '" onclick="event.stopPropagation();">Детальніше →</a>' +
                      '<a class="details-btn" href="#" data-action="onmap" onclick="event.preventDefault(); event.stopPropagation();">На мапі</a>' +
                    '</div>' +
                  '</div>' +
                  '<div style="text-align:right;min-width:90px;">' +
                    '<div class="price">' + (it.price != null ? (it.price + ' ' + escapeHtml(it.currency || "UAH")) : "—") + '</div>' +
                  '</div>' +
                '</div>' +
              '</div>' +
            '</div>'
          );
        }).join("");

        // Клік “На мапі” — показуємо карту з УСІМА результатами і фокус на вибраному
        dom.qsa(".item", listEl).forEach(function (row) {
          var btn = row.querySelector('[data-action="onmap"]');
          if (!btn) return;

          dom.on(btn, "click", function () {
            var id = row.getAttribute("data-id");
            var it = items.find(function (x) { return String(x.id) === String(id); });
            if (!it) return;

            // Переходимо у map-mode на мобільному
            if (isMobile()) {
              document.body.classList.add("map-mode");
              ensureBackButton();

              // Карта стала видимою → треба invalidateSize
              setTimeout(function () {
                map.invalidateSize();
                // Малюємо ВСІ маркери поточного пошуку
                renderMarkers(lastItems);
                focusItem(it);
              }, 220);
            } else {
              // Десктоп: маркери теж малюємо по запиту
              renderMarkers(lastItems);
              focusItem(it);
            }
          });
        });
      }

      function focusItem(it) {
        map.setView([it.lat, it.lng], 16);
        var m = markerById.get(String(it.id));
        if (m) m.openPopup();
      }

      // --- “До списку” кнопка
      function ensureBackButton() {
        if (!isMobile()) return;

        if (document.getElementById("btnBackToList")) return;

        var b = document.createElement("button");
        b.id = "btnBackToList";
        b.className = "back-to-list";
        b.type = "button";
        b.textContent = "← До списку";

        dom.on(b, "click", function () {
          document.body.classList.remove("map-mode");
          window.scrollTo({ top: 0, behavior: "smooth" });
        });

        document.body.appendChild(b);
      }

      // --- API fetch
      async function fetchListings() {
        var q = qEl ? qEl.value.trim() : "";

        setStatus("завантаження...");

        try {
          var qs = http.buildQuery({ q: q, lat: anchor.lat, lng: anchor.lng });
          var url = window.APP.apiListingsUrl + "?" + qs;

          var data = await http.fetchJson(url);
          var items = Array.isArray(data.items) ? data.items : [];

          // Ранжуємо від близьких до дальніх
          items.sort(function (a, b) { return Number(a.distanceKm || 0) - Number(b.distanceKm || 0); });

          lastItems = items;

          if (countEl) countEl.textContent = String(items.length);

          // Завжди рендеримо список
          renderList(items);

          // Карта на мобільному — тільки у map-mode
          if (document.body.classList.contains("map-mode")) {
            renderMarkers(items);
          } else {
            clearMarkers();
          }

          setStatus("");
        } catch (e) {
          console.log(e);
          setStatus("помилка API (" + window.APP.apiListingsUrl + ")");
        }
      }

      // --- Пошук
      dom.on(btnSearch, "click", fetchListings);
      dom.on(qEl, "keydown", function (e) {
        if (e.key === "Enter") fetchListings();
      });

      // --- Одноразова авто-геолокація (якщо дозволили)
      (async function start() {
        ensureBackButton();
        document.body.classList.remove("map-mode"); // мобільний старт — список

        try {
          var p = await geo.getPositionOnce({ enableHighAccuracy: true, timeout: 8000, maximumAge: 60000 });
          anchor = { lat: p.lat, lng: p.lng };
          storage.set("poruch_anchor", anchor);

          anchorMarker.setLatLng([anchor.lat, anchor.lng]);
          map.setView([anchor.lat, anchor.lng], 14);
        } catch (_) {
          // ок — лишаємось на anchor з localStorage або дефолті
        }

        await fetchListings();

        // Якщо на мобільному повернулися з map-mode, а потім повернули телефон — актуалізуємо розміри
        window.addEventListener("resize", function () {
          ensureBackButton();
          if (isMobile() && document.body.classList.contains("map-mode")) {
            setTimeout(function () { map.invalidateSize(); }, 200);
          }
        });
      })();
    }
  };

})();