/* JB Optician storefront behaviour — vanilla JS, no build step. */
(function () {
  'use strict';
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));
  const csrf = () => ($('meta[name=csrf-token]') || {}).content || '';
  const post = (url, body, method = 'POST') => fetch(url, {
    method, headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(body instanceof FormData ? {} : { 'Content-Type': 'application/json' }) },
    body: body instanceof FormData ? body : JSON.stringify(body || {}),
  });
  const toast = (msg, err) => { const d = document.createElement('div'); d.className = 'flash ' + (err ? 'flash--err' : 'flash--ok'); d.textContent = msg; d.style.cssText = 'position:fixed;top:12px;left:50%;transform:translateX(-50%);z-index:120;box-shadow:0 6px 20px rgba(0,0,0,.2);margin:0'; document.body.appendChild(d); setTimeout(() => d.remove(), 3500); };

  /* mobile nav */
  const burger = $('.burger'), nav = $('.nav');
  if (burger && nav) burger.addEventListener('click', () => { const o = nav.classList.toggle('open'); burger.setAttribute('aria-expanded', o); });

  /* overlays */
  const openOv = (el) => { if (!el) return; el.classList.add('open'); el.setAttribute('aria-hidden', 'false'); const f = $('input,button,a', el); f && setTimeout(() => f.focus(), 30); };
  const closeOv = (el) => { if (!el) return; el.classList.remove('open'); el.setAttribute('aria-hidden', 'true'); };
  $$('[data-open]').forEach(b => b.addEventListener('click', e => { e.preventDefault(); const t = $(b.dataset.open); openOv(t); if (b.dataset.open === '#cart-drawer') loadDrawer(); }));
  $$('[data-close]').forEach(b => b.addEventListener('click', () => closeOv(b.closest('.overlay,.drawer,.search-ov'))));
  $$('.overlay').forEach(o => o.addEventListener('click', e => { if (e.target === o) closeOv(o); }));
  document.addEventListener('keydown', e => { if (e.key === 'Escape') $$('.overlay.open,.drawer.open,.search-ov.open,.chat.open').forEach(closeOv); });
  if (document.body.dataset.openLogin === '1') openOv($('#login-modal'));

  /* cart drawer */
  const drawer = $('#cart-drawer');
  function setCount(n) { $$('[data-cart-count]').forEach(b => { b.textContent = n; b.hidden = !n; }); }
  async function loadDrawer() { const r = await fetch('/cart/drawer', { headers: { Accept: 'application/json' } }); if (r.ok) { const j = await r.json(); $('.drawer__content', drawer).innerHTML = j.html; setCount(j.count); bindDrawer(); } }
  function bindDrawer() { $$('[data-drawer-remove]', drawer).forEach(b => b.addEventListener('click', async () => { await post(b.dataset.drawerRemove, {}, 'DELETE'); loadDrawer(); })); }
  $$('form[data-ajax-cart]').forEach(f => f.addEventListener('submit', async e => {
    if (e.submitter && e.submitter.name === 'buy_now') return; // normal submit → checkout
    e.preventDefault();
    const r = await post(f.action, new FormData(f));
    const j = await r.json().catch(() => ({}));
    if (!r.ok) return toast(j.message || 'Could not add to cart.', true);
    setCount(j.count); $('.drawer__content', drawer).innerHTML = j.html; bindDrawer(); openOv(drawer); drawer.classList.add('open');
  }));
  $$('[data-qty]').forEach(w => { const i = $('input', w); $$('button', w).forEach(b => b.addEventListener('click', () => { i.value = Math.max(1, (+i.value || 1) + (b.dataset.step === '+' ? 1 : -1)); i.dispatchEvent(new Event('change', { bubbles: true })); })); });
  $$('[data-autosubmit]').forEach(i => i.addEventListener('change', () => i.form.submit()));

  /* hero carousel: smooth cinematic cross-fade + continuous auto-switch (no sliding, no dots) */
  const hero = $('[data-hero]');
  if (hero) {
    const slides = $$('.hero__slide', hero), dots = $$('.hero__dots button', hero);
    let i = 0, timer = null;

    const go = n => {
      if (!slides.length) return;
      i = (n + slides.length) % slides.length;
      dots.forEach((d, k) => d.setAttribute('aria-current', k === i));
      slides.forEach((s, k) => {
        s.setAttribute('aria-hidden', k !== i);
        if (k === i) {
          s.classList.add('is-active');
          const img = $('img.bg', s);
          if (img) {
            img.style.animation = 'none';
            img.offsetHeight; /* trigger reflow to cleanly re-play Ken Burns zoom */
            img.style.animation = '';
          }
        } else {
          s.classList.remove('is-active');
        }
      });
    };

    const next = () => go(i + 1);

    const start = () => {
      stop();
      if (slides.length > 1) {
        timer = setInterval(next, 4500);
      }
    };

    const stop = () => {
      if (timer) {
        clearInterval(timer);
        timer = null;
      }
    };

    dots.forEach((d, k) => d.addEventListener('click', () => { go(k); start(); }));
    $('.hero__nav--prev', hero) && $('.hero__nav--prev', hero).addEventListener('click', () => { go(i - 1); start(); });
    $('.hero__nav--next', hero) && $('.hero__nav--next', hero).addEventListener('click', () => { go(i + 1); start(); });

    // Keyboard navigation
    hero.setAttribute('tabindex', '0');
    hero.addEventListener('keydown', e => {
      if (e.key === 'ArrowLeft') { go(i - 1); start(); }
      if (e.key === 'ArrowRight') { go(i + 1); start(); }
    });

    // Touch swipe support
    let x0 = null;
    hero.addEventListener('touchstart', e => x0 = e.touches[0].clientX, { passive: true });
    hero.addEventListener('touchend', e => {
      if (x0 === null) return;
      const dx = e.changedTouches[0].clientX - x0;
      if (Math.abs(dx) > 40) { go(i + (dx < 0 ? 1 : -1)); start(); }
      x0 = null;
    });

    // Pause on background tabs, resume on visible
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) stop();
      else start();
    });

    go(0);
    start();
  }

  /* product row carousels */
  $$('[data-row-carousel]').forEach(rc => { const t = $('.row-carousel__track', rc); const step = () => t.clientWidth * .9;
    $('.row-carousel__btn--prev', rc).addEventListener('click', () => t.scrollBy({ left: -step(), behavior: 'smooth' }));
    $('.row-carousel__btn--next', rc).addEventListener('click', () => t.scrollBy({ left: step(), behavior: 'smooth' })); });

  /* search overlay + suggestions */
  const sov = $('#search-ov'), sin = $('#search-input'), sug = $('#suggest'); let st;
  if (sin) sin.addEventListener('input', () => { clearTimeout(st); st = setTimeout(async () => { const q = sin.value.trim(); if (q.length < 2) { sug.innerHTML = ''; return; }
    const r = await fetch('/search/suggest?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } }); if (!r.ok) return; const j = await r.json();
    sug.innerHTML = j.map(p => `<a href="${p.url}"><img src="${p.image}" alt=""><span></span></a>`).join(''); $$('#suggest a span').forEach((s, k) => s.textContent = j[k].name); }, 220); });

  /* shop: price range + view more */
  const rmin = $('#rng-min'), rmax = $('#rng-max'), lab = $('#rng-label');
  if (rmin && rmax) { const upd = () => { let a = +rmin.value, b = +rmax.value; if (a > b) [a, b] = [b, a]; lab.textContent = '₹' + a + ' — ₹' + b; $('#f-min').value = a; $('#f-max').value = b; }; rmin.addEventListener('input', upd); rmax.addEventListener('input', upd); upd(); }
  const more = $('#view-more');
  if (more) more.addEventListener('click', async () => { more.disabled = true; const r = await fetch(more.dataset.next, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });
    if (r.ok) { const j = await r.json(); $('#product-grid').insertAdjacentHTML('beforeend', j.html); $('#showing').textContent = j.showing; if (j.next) { more.dataset.next = j.next; more.disabled = false; } else more.remove(); bindCardForms(); } });
  function bindCardForms() { $$('form[data-ajax-cart]:not([data-bound])').forEach(f => { f.dataset.bound = 1; f.addEventListener('submit', async e => { e.preventDefault(); const r = await post(f.action, new FormData(f)); const j = await r.json().catch(() => ({})); if (!r.ok) return toast(j.message || 'Could not add to cart.', true); setCount(j.count); $('.drawer__content', drawer).innerHTML = j.html; bindDrawer(); drawer.classList.add('open'); }); }); }
  $$('form[data-ajax-cart]').forEach(f => f.dataset.bound = 1);

  /* product page: gallery, variants, tabs */
  $$('[data-gallery] .gallery__thumbs button').forEach(b => b.addEventListener('click', () => { $('[data-gallery] .gallery__main img').src = b.dataset.src; $$('[data-gallery] .gallery__thumbs button').forEach(x => x.setAttribute('aria-current', x === b)); }));
  const vs = $('#variant-select');
  if (vs) { const out = $('#variant-price'); const upd = () => { const o = vs.selectedOptions[0]; if (o && o.dataset.html) out.innerHTML = o.dataset.html; }; vs.addEventListener('change', upd); upd(); }
  $$('[role=tablist]').forEach(tl => $$('[role=tab]', tl).forEach(t => t.addEventListener('click', () => { $$('[role=tab]', tl).forEach(x => x.setAttribute('aria-selected', x === t)); $$('[data-panel]').forEach(p => p.hidden = p.dataset.panel !== t.dataset.tab); })));

  /* checkout: payment choice, billing toggle */
  $$('.choice input[type=radio]').forEach(r => r.addEventListener('change', () => $$('.choice').forEach(c => c.classList.toggle('on', $('input', c).checked))));
  const same = $('#same_billing'), bill = $('#billing-fields'); if (same && bill) { const u = () => bill.hidden = same.checked; same.addEventListener('change', u); u(); }

  /* help centre */
  $$('[data-faq]').forEach(a => a.addEventListener('click', e => { e.preventDefault(); const p = $('#' + a.dataset.faq); p && p.classList.toggle('open'); }));
  const hs = $('#help-search'); if (hs) hs.addEventListener('input', () => { const q = hs.value.toLowerCase(); $$('[data-faq-item]').forEach(i => i.hidden = q && !i.textContent.toLowerCase().includes(q)); });

  /* newsletter (works without JS too) */
  $$('form[data-newsletter]').forEach(f => f.addEventListener('submit', async e => { e.preventDefault(); const r = await post(f.action, new FormData(f)); const j = await r.json().catch(() => ({}));
    if (r.ok) { const box = f.closest('.news'); const parts = (j.message || '').split('! '); box.innerHTML = '<div class="news__ok"></div>'; const o = $('.news__ok', box); o.textContent = parts[0] + (parts[1] ? '!' : ''); if (parts[1]) { const s = document.createElement('span'); s.textContent = parts.slice(1).join('! '); o.appendChild(s); } }
    else toast((j.errors && ((j.errors.consent && j.errors.consent[0]) || (j.errors.email && j.errors.email[0]))) || 'Please check your email and tick the consent box.', true); }));

  /* back to top */
  const top = $('#to-top'); if (top) { addEventListener('scroll', () => top.classList.toggle('show', scrollY > 500), { passive: true }); top.addEventListener('click', () => scrollTo({ top: 0, behavior: 'smooth' })); }

  /* native chat widget (used only when Chatbase is not configured) */
  const chat = $('#chat'); if (chat) { const body = $('.chat__body', chat), form = $('form', chat);
    const add = (t, me, links) => { const m = document.createElement('div'); m.className = 'msg' + (me ? ' msg--me' : ''); m.textContent = t; (links || []).forEach(l => { const a = document.createElement('a'); a.textContent = l.label; a.href = l.url; m.appendChild(a); }); body.appendChild(m); body.scrollTop = body.scrollHeight; };
    $('#chat-open').addEventListener('click', () => { chat.classList.toggle('open'); if (!body.children.length) add('Hi! What can I help you with?'); });
    $('[data-chat-close]', chat).addEventListener('click', () => chat.classList.remove('open'));
    form.addEventListener('submit', async e => { e.preventDefault(); const i = $('input', form), t = i.value.trim(); if (!t) return; add(t, true); i.value = ''; const r = await post('/chat', { message: t }); const j = await r.json().catch(() => null); j ? add(j.reply, false, j.links) : add('Sorry, something went wrong.'); }); }

  /* Try-On: product-specific MyWebAR iframe when a project URL is set, otherwise a camera overlay that stays in the browser. */
  const ty = $('#tryon-overlay');
  if (ty) {
    let stream; const vid = $('video', ty), frame = $('.tryon-frame', ty), size = $('#tryon-size', ty), ifr = $('#tryon-iframe', ty),
      stage = $('.tryon-stage', ty), ctl = $('.tryon-ctl', ty), msg = $('#tryon-msg', ty), title = $('#tryon-title', ty);
    const stopCam = () => { stream && stream.getTracks().forEach(t => t.stop()); stream = null; vid.srcObject = null; };
    const reset = () => { stopCam(); ifr.src = 'about:blank'; };
    async function show(b) {
      reset(); title.textContent = b.dataset.name || '';
      $$('[data-tryon-btn]', ty).forEach(x => x.setAttribute('aria-pressed', x.dataset.name === b.dataset.name));
      if (b.dataset.tryonUrl) {
        stage.hidden = true; ctl.hidden = true; ifr.hidden = false; ifr.src = b.dataset.tryonUrl;
        msg.textContent = 'Allow camera access when asked. If nothing appears, open this page in Safari/Chrome (not an in-app browser).';
      } else {
        ifr.hidden = true; stage.hidden = false; ctl.hidden = false; frame.src = b.dataset.tryon; frame.style.left = '50%'; frame.style.top = '42%';
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) { msg.textContent = 'This browser cannot access the camera (HTTPS is required).'; return; }
        try { stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false }); vid.srcObject = stream; msg.textContent = 'Drag the frame to line it up with your eyes. Your camera feed never leaves your device.'; }
        catch (e) { msg.textContent = e && e.name === 'NotAllowedError' ? 'Camera permission was denied. Allow camera access in your browser settings and try again.' : 'Camera unavailable on this device.'; }
      }
    }
    $$('[data-tryon-btn]').forEach(b => b.addEventListener('click', () => show(b)));
    $$('[data-close]', ty).forEach(b => b.addEventListener('click', reset));
    ty.addEventListener('click', e => { if (e.target === ty) reset(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') reset(); });
    size.addEventListener('input', () => frame.style.width = size.value + '%');
    let drag = null; frame.addEventListener('pointerdown', e => { drag = { x: e.clientX, y: e.clientY, l: frame.offsetLeft, t: frame.offsetTop }; frame.setPointerCapture(e.pointerId); });
    frame.addEventListener('pointermove', e => { if (!drag) return; frame.style.left = (drag.l + e.clientX - drag.x) + 'px'; frame.style.top = (drag.t + e.clientY - drag.y) + 'px'; });
    frame.addEventListener('pointerup', () => drag = null);
  }
})();
