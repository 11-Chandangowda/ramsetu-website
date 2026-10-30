/* RamSetu theme scripts */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Header: shadow on scroll ---------- */
  var header = document.querySelector('[data-header]');
  if (header) {
    var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 8); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ---------- Mobile navigation ---------- */
  var toggle = document.querySelector('[data-nav-toggle]');
  var nav = document.querySelector('[data-nav]');
  if (toggle && nav) {
    var setOpen = function (open) {
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      document.body.classList.toggle('nav-open', open);
    };
    toggle.addEventListener('click', function () { setOpen(toggle.getAttribute('aria-expanded') !== 'true'); });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && document.body.classList.contains('nav-open')) { setOpen(false); toggle.focus(); }
    });
    nav.addEventListener('click', function (e) {
      var a = e.target.closest('a');
      if (a && a.getAttribute('href') && a.getAttribute('href').indexOf('#') !== -1) setOpen(false);
    });

    // Sub-menus: add a toggle button to each parent item (works for touch + keyboard).
    nav.querySelectorAll('.menu-item-has-children').forEach(function (li, i) {
      var link = li.querySelector(':scope > a');
      var sub = li.querySelector(':scope > .sub-menu');
      if (!link || !sub) return;
      sub.id = sub.id || 'submenu-' + i;
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'submenu-toggle';
      btn.setAttribute('aria-expanded', 'false');
      btn.setAttribute('aria-controls', sub.id);
      btn.setAttribute('aria-label', 'Show ' + link.textContent.trim() + ' menu');
      btn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
      link.after(btn);
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var open = btn.getAttribute('aria-expanded') !== 'true';
        nav.querySelectorAll('.submenu-toggle[aria-expanded="true"]').forEach(function (b) {
          // keep the parent menu open when opening a sub-menu inside it
          if (b !== btn && !b.parentElement.contains(btn)) { b.setAttribute('aria-expanded', 'false'); b.parentElement.classList.remove('is-open'); }
        });
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        li.classList.toggle('is-open', open);
      });
    });
    // Long founder message: show the first two paragraphs, then "Read more".
    document.querySelectorAll('[data-readmore]').forEach(function (box) {
      if (box.querySelectorAll(':scope > p').length <= 2) return;
      box.classList.add('is-collapsed');
      var more = document.createElement('button');
      more.type = 'button';
      more.className = 'readmore-btn';
      more.setAttribute('aria-expanded', 'false');
      more.textContent = 'Read more';
      box.after(more);
      more.addEventListener('click', function () {
        var open = box.classList.toggle('is-collapsed') === false;
        more.setAttribute('aria-expanded', open ? 'true' : 'false');
        more.textContent = open ? 'Read less' : 'Read more';
      });
    });
    document.addEventListener('click', function (e) {
      if (!e.target.closest('.menu-item-has-children')) {
        nav.querySelectorAll('.menu-item-has-children.is-open').forEach(function (li) {
          li.classList.remove('is-open');
          var b = li.querySelector('.submenu-toggle'); if (b) b.setAttribute('aria-expanded', 'false');
        });
      }
    });
  }

  /* ---------- Hero slider ---------- */
  var slider = document.querySelector('[data-slider]');
  if (slider) {
    var slides = slider.querySelectorAll('[data-slide]');
    var dots = slider.querySelectorAll('[data-dot]');
    var pauseBtn = slider.querySelector('[data-pause]');
    var current = 0, timer = null, paused = reduceMotion, DELAY = 4000;

    var show = function (n) {
      current = (n + slides.length) % slides.length;
      slides.forEach(function (s, i) {
        var on = i === current;
        s.classList.toggle('is-active', on);
        if (on) s.removeAttribute('aria-hidden'); else s.setAttribute('aria-hidden', 'true');
        s.querySelectorAll('a,button').forEach(function (el) { if (on) el.removeAttribute('tabindex'); else el.setAttribute('tabindex', '-1'); });
      });
      dots.forEach(function (d, i) {
        d.classList.toggle('is-active', i === current);
        d.setAttribute('aria-selected', i === current ? 'true' : 'false');
      });
    };
    var start = function () {
      stop();
      if (!paused) timer = setInterval(function () { show(current + 1); }, DELAY);
      slider.classList.toggle('is-paused', paused);
    };
    var stop = function () { if (timer) clearInterval(timer); timer = null; };

    dots.forEach(function (d) {
      d.addEventListener('click', function () { show(parseInt(d.getAttribute('data-dot'), 10)); start(); });
    });
    if (pauseBtn) {
      pauseBtn.addEventListener('click', function () {
        paused = !paused;
        pauseBtn.setAttribute('aria-label', paused ? 'Play slideshow' : 'Pause slideshow');
        pauseBtn.innerHTML = paused ? '<span aria-hidden="true">▶</span>' : '<span aria-hidden="true">❚❚</span>';
        start();
      });
      if (paused) { pauseBtn.setAttribute('aria-label', 'Play slideshow'); pauseBtn.innerHTML = '<span aria-hidden="true">▶</span>'; }
    }
    slider.addEventListener('mouseenter', stop);
    slider.addEventListener('mouseleave', start);
    slider.addEventListener('focusin', stop);
    slider.addEventListener('focusout', start);

    // Swipe
    var x0 = null;
    slider.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
    slider.addEventListener('touchend', function (e) {
      if (x0 === null) return;
      var dx = e.changedTouches[0].clientX - x0;
      if (Math.abs(dx) > 50) { show(current + (dx < 0 ? 1 : -1)); start(); }
      x0 = null;
    });
    document.addEventListener('visibilitychange', function () { if (document.hidden) stop(); else start(); });
    start();
  }

  /* ---------- Apply Now modal ---------- */
  var modal = document.querySelector('[data-apply-modal]');
  if (modal && typeof modal.showModal === 'function') {
    var lastTrigger = null;
    var form = modal.querySelector('form');

    document.addEventListener('click', function (e) {
      var trigger = e.target.closest('[data-apply]');
      if (!trigger || e.metaKey || e.ctrlKey || e.shiftKey) return;
      e.preventDefault();
      lastTrigger = trigger;
      document.body.classList.remove('nav-open');
      if (toggle) toggle.setAttribute('aria-expanded', 'false');
      if (form) {
        if (form.classList.contains('is-done') && window.RSLResetForm) window.RSLResetForm(form);
        var cat = trigger.getAttribute('data-apply-category');
        var sel = form.querySelector('[name="loan_category"]');
        if (sel && cat) sel.value = cat;
      }
      modal.showModal();
      document.documentElement.classList.add('modal-open');
      var first = modal.querySelector('input:not([type=hidden]):not([tabindex="-1"])');
      if (first) setTimeout(function () { first.focus(); }, 50);
    });

    var close = function () { modal.close(); };
    modal.querySelectorAll('[data-apply-close]').forEach(function (b) { b.addEventListener('click', close); });
    modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
    modal.addEventListener('close', function () {
      document.documentElement.classList.remove('modal-open');
      if (lastTrigger) lastTrigger.focus();
    });
  }

  /* ---------- Branch locator ---------- */
  var loc = document.querySelector('[data-locator]');
  if (loc) {
    var stateSel = loc.querySelector('[data-loc-state]');
    var citySel = loc.querySelector('[data-loc-city]');
    var status = loc.querySelector('[data-loc-status]');
    var cards = Array.prototype.slice.call(loc.querySelectorAll('.branch'));

    var map = loc.querySelector('[data-loc-map]');
    var showOnMap = function (card) {
      if (!map || !card) return;
      var src = 'https://maps.google.com/maps?q=' + encodeURIComponent(card.dataset.mapQ) + '&z=13&output=embed';
      if (map.src !== src) map.src = src;
      cards.forEach(function (c) { c.classList.toggle('is-selected', c === card); });
    };
    loc.addEventListener('click', function (e) {
      var b = e.target.closest('[data-show-map]');
      if (!b) return;
      var card = b.closest('.branch');
      showOnMap(card);
      if (window.matchMedia('(max-width: 900px)').matches && map) map.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
    });

    var filter = function () {
      var st = stateSel.value, ct = citySel.value, n = 0, first = null;
      cards.forEach(function (c) {
        var ok = (!st || c.dataset.state === st) && (!ct || c.dataset.city === ct);
        c.hidden = !ok;
        if (ok) { n++; if (!first) first = c; }
      });
      if (first && (st || ct)) showOnMap(first);
      if (!st) status.textContent = 'Showing all ' + cards.length + ' branches';
      else status.textContent = n + (n === 1 ? ' branch' : ' branches') + ' in ' + (ct ? ct + ', ' : '') + st;
    };

    stateSel.addEventListener('change', function () {
      var st = stateSel.value;
      var cities = [];
      cards.forEach(function (c) { if (c.dataset.state === st && cities.indexOf(c.dataset.city) === -1) cities.push(c.dataset.city); });
      citySel.innerHTML = '';
      var o = document.createElement('option');
      o.value = ''; o.textContent = st ? 'All cities' : 'Select state first';
      citySel.appendChild(o);
      cities.sort().forEach(function (c) {
        var opt = document.createElement('option'); opt.value = c; opt.textContent = c; citySel.appendChild(opt);
      });
      citySel.disabled = !st;
      filter();
    });
    citySel.addEventListener('change', filter);
  }

  /* ---------- Reveal on scroll ---------- */
  if (!reduceMotion && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -8% 0px' });
    document.querySelectorAll('.section__head, .brandcard, .offer, .vm__card, .leader, .steps__item, .branch, .founder').forEach(function (el) {
      el.classList.add('reveal');
      io.observe(el);
    });
  }
})();
