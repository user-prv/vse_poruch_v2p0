(function () {
  "use strict";

  function $(id) { return document.getElementById(id); }

  const gallery = document.querySelector(".item-gallery");
  if (!gallery) return;

  let photos = [];
  try {
    photos = JSON.parse(gallery.getAttribute("data-photos") || "[]");
  } catch { photos = []; }

  const img = $("itemMainPhoto");
  const thumbsWrap = $("itemThumbs");
  const counterEl = $("itemCounter");
  const prevBtn = gallery.querySelector(".item-nav--prev");
  const nextBtn = gallery.querySelector(".item-nav--next");

  if (!img || !photos.length) return;

  let idx = 0;

  function setActive(i) {
    idx = (i + photos.length) % photos.length;
    img.src = photos[idx];
    if (counterEl) counterEl.textContent = String(idx + 1);

    if (thumbsWrap) {
      thumbsWrap.querySelectorAll(".item-thumb-btn").forEach((b) => b.classList.remove("is-active"));
      const active = thumbsWrap.querySelector(`.item-thumb-btn[data-idx="${idx}"]`);
      if (active) {
        active.classList.add("is-active");
        // мʼяко прокручуємо стрічку превʼю до активного
        active.scrollIntoView({ behavior: "smooth", inline: "center", block: "nearest" });
      }
    }
  }

  // Клік по превʼю
  if (thumbsWrap) {
    thumbsWrap.addEventListener("click", (e) => {
      const btn = e.target.closest(".item-thumb-btn");
      if (!btn) return;
      e.preventDefault();
      const i = parseInt(btn.getAttribute("data-idx") || "0", 10);
      if (!Number.isNaN(i)) setActive(i);
    });
  }

  // Prev/Next
  if (prevBtn) prevBtn.addEventListener("click", () => setActive(idx - 1));
  if (nextBtn) nextBtn.addEventListener("click", () => setActive(idx + 1));

  // Свайп на мобільному (по головному фото)
  let x0 = null;
  img.addEventListener("touchstart", (e) => {
    if (!e.touches || !e.touches.length) return;
    x0 = e.touches[0].clientX;
  }, { passive: true });

  img.addEventListener("touchend", (e) => {
    if (x0 === null) return;
    const x1 = (e.changedTouches && e.changedTouches.length) ? e.changedTouches[0].clientX : x0;
    const dx = x1 - x0;
    x0 = null;

    // поріг свайпу
    if (Math.abs(dx) < 35) return;
    if (dx < 0) setActive(idx + 1);
    else setActive(idx - 1);
  }, { passive: true });

  // Старт
  setActive(0);
})();