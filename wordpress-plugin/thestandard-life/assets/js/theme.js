/* THE STANDARD LIFE — theme interactions */
(function () {
  'use strict';

  // Theme toggle with localStorage + system preference (run early to avoid flash)
  //
  // Every localStorage call is wrapped, because reading it is not merely
  // unreliable — with site data blocked (Chrome's "block all cookies", some
  // privacy extensions, an embedded webview) the property access itself throws
  // a SecurityError. This runs first inside the file's single outer IIFE, so an
  // uncaught throw here takes the whole of theme.js down with it: no menu, no
  // table of contents, no album, no reels. Losing the remembered theme is a
  // fair price; losing the page is not.
  (function () {
    var root = document.documentElement;
    var saved = null;
    try { saved = localStorage.getItem('tsl-theme'); } catch (e) { /* no stored preference available */ }
    var systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    root.setAttribute('data-theme', saved || (systemDark ? 'dark' : 'light'));

    document.addEventListener('DOMContentLoaded', function () {
      var btn = document.getElementById('themeToggle');
      if (!btn) return;
      btn.addEventListener('click', function () {
        var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        try { localStorage.setItem('tsl-theme', next); } catch (e) { /* toggle still works, it just won't be remembered */ }
      });
    });
  })();

  document.addEventListener('DOMContentLoaded', function () {
    // Hamburger toggle
    (function () {
      var btn = document.getElementById('navHamburger');
      var nav = document.getElementById('mainNav');
      if (!btn || !nav) return;
      var openIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>';
      var closeIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>';
      btn.addEventListener('click', function () {
        var open = nav.classList.toggle('open');
        btn.setAttribute('aria-expanded', open);
        btn.innerHTML = open ? closeIcon : openIcon;
      });
      nav.querySelectorAll('ul a').forEach(function (a) {
        a.addEventListener('click', function () {
          nav.classList.remove('open');
          btn.setAttribute('aria-expanded', 'false');
          btn.innerHTML = openIcon;
        });
      });
    })();

    // Masthead date
    //
    // The date is printed into the page when it is generated, so once a page
    // cache holds a copy every later visitor is shown the day that copy was
    // built. Rewriting it here costs nothing and makes the date independent of
    // how long the HTML has been sitting in a cache.
    (function () {
      var el = document.querySelector('.mh-date');
      if (!el) return;
      try {
        var today = new Date().toLocaleDateString(document.documentElement.lang || undefined, {
          weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
        if (today && today !== el.textContent.trim()) el.textContent = today;
      } catch (e) {
        // Leave the server-rendered date if the browser cannot format one.
      }
    })();

    // Sticky nav shadow on scroll
    (function () {
      var nav = document.querySelector('.nav');
      if (!nav) return;
      var onScroll = function () { nav.classList.toggle('scrolled', window.scrollY > 10); };
      window.addEventListener('scroll', onScroll, { passive: true });
      onScroll();
    })();

    // Reading progress bar (article pages)
    (function () {
      var bar = document.getElementById('progress');
      if (!bar) return;
      var nav = document.querySelector('.nav');

      var onScroll = function () {
        var h = document.documentElement;
        var max = h.scrollHeight - h.clientHeight;
        bar.style.width = (max > 0 ? (h.scrollTop / max) * 100 : 0) + '%';

        // Ride the nav's bottom edge. That edge travels up the screen until the
        // nav pins to the top, so it is read each time rather than assumed —
        // and the nav is taller on a phone with the menu drawer open.
        if (nav) {
          var edge = nav.getBoundingClientRect().bottom;
          bar.style.top = Math.max(0, Math.round(edge)) + 'px';
        }
      };

      window.addEventListener('scroll', onScroll, { passive: true });
      window.addEventListener('resize', onScroll);
      onScroll();
    })();

    // Auto-build the table of contents from the article H2 headings
    (function () {
      var toc = document.getElementById('toc');
      var prose = document.querySelector('.prose');
      if (!toc || !prose) return;
      var headings = prose.querySelectorAll('h2');
      if (!headings.length) {
        // Hiding the sidebar takes it out of the grid, which would otherwise
        // leave the article auto-placed into the narrow first column.
        toc.style.display = 'none';
        var wrap = toc.closest('.art-wrap');
        if (wrap) wrap.classList.add('no-toc');
        return;
      }
      var ol = toc.querySelector('ol');
      if (!ol) return;
      ol.innerHTML = '';
      headings.forEach(function (h, i) {
        if (!h.id) h.id = 's' + (i + 1);
        var li = document.createElement('li');
        var a = document.createElement('a');
        a.href = '#' + h.id;
        a.textContent = h.textContent;
        li.appendChild(a);
        ol.appendChild(li);
      });

      // Collapsible on mobile
      var h6 = toc.querySelector('h6');
      if (h6) h6.addEventListener('click', function () { toc.classList.toggle('open'); });

      // Scroll-spy: highlight the current section
      var links = ol.querySelectorAll('a');
      if ('IntersectionObserver' in window) {
        var byId = {};
        links.forEach(function (l) { byId[l.getAttribute('href').slice(1)] = l; });
        var obs = new IntersectionObserver(function (entries) {
          entries.forEach(function (e) {
            if (e.isIntersecting) {
              links.forEach(function (l) { l.parentElement.classList.remove('active'); });
              var cur = byId[e.target.id];
              if (cur) cur.parentElement.classList.add('active');
            }
          });
        }, { rootMargin: '-96px 0px -70% 0px' });
        headings.forEach(function (h) { obs.observe(h); });
      }
    })();

    // View counter
    //
    // Reported from here rather than counted while the page renders, because a
    // page served from cache never reaches PHP. Waiting for a sign the article
    // is actually being read — a few seconds, or a scroll — keeps bounces and
    // the many bots that never run JavaScript out of the number.
    (function () {
      var el = document.querySelector('.tsl-views[data-post-id]');
      if (!el || !window.TSL_VIEWS || !window.TSL_VIEWS.endpoint) return;

      var id = el.getAttribute('data-post-id');
      var key = 'tsl-viewed-' + id;
      var DAY = 86400000;

      // Don't report the same article twice from one browser in a day, so a
      // reader refreshing does not run the number up.
      try {
        var seen = parseInt(localStorage.getItem(key) || '0', 10);
        if (seen && Date.now() - seen < DAY) return;
      } catch (e) { /* private mode — fall through and just report it */ }

      var sent = false;
      function report() {
        if (sent) return;
        sent = true;
        clearTimeout(timer);
        window.removeEventListener('scroll', onScroll);

        fetch(window.TSL_VIEWS.endpoint + id, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' }
        })
          .then(function (r) { return r.ok ? r.json() : null; })
          .then(function (data) {
            if (!data || typeof data.views !== 'number') return;
            try { localStorage.setItem(key, String(Date.now())); } catch (e) {}

            // Write the fresh total in, so the figure is not the one that was
            // cached with the page.
            var n = el.querySelector('.tsl-views-n');
            if (n) n.textContent = data.views.toLocaleString();
            el.hidden = false;
            var dot = document.querySelector('.tsl-views-dot');
            if (dot) dot.hidden = false;
          })
          .catch(function () { /* a missed count is not worth bothering the reader about */ });
      }

      function onScroll() {
        var h = document.documentElement;
        var max = h.scrollHeight - h.clientHeight;
        if (max > 0 && h.scrollTop / max > 0.15) report();
      }

      var timer = setTimeout(report, 5000);
      window.addEventListener('scroll', onScroll, { passive: true });
    })();

    // Photo albums (Classic Editor galleries)
    (function () {
      var ICON = {
        prev: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5 8 12l7 7"/></svg>',
        next: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m9 5 7 7-7 7"/></svg>',
        close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>'
      };

      // Full-screen viewer, built once and reused by every album on the page.
      function openLightbox(shots, startAt, onClose) {
        var at = startAt;

        var box = document.createElement('div');
        box.className = 'tsl-lightbox';
        box.setAttribute('role', 'dialog');
        box.setAttribute('aria-modal', 'true');

        var fig = document.createElement('figure');
        fig.className = 'tsl-lightbox-fig';
        var img = document.createElement('img');
        var cap = document.createElement('figcaption');
        fig.appendChild(img);
        fig.appendChild(cap);
        box.appendChild(fig);

        var count = document.createElement('span');
        count.className = 'tsl-lightbox-count';
        box.appendChild(count);

        function button(cls, label, icon) {
          var b = document.createElement('button');
          b.type = 'button';
          b.className = 'tsl-lightbox-btn ' + cls;
          b.setAttribute('aria-label', label);
          b.innerHTML = icon;
          box.appendChild(b);
          return b;
        }
        var prev = button('tsl-lightbox-prev', 'Previous photo', ICON.prev);
        var next = button('tsl-lightbox-next', 'Next photo', ICON.next);
        var close = button('tsl-lightbox-close', 'Close', ICON.close);
        if (shots.length < 2) { prev.style.display = 'none'; next.style.display = 'none'; }

        function render(i) {
          at = (i + shots.length) % shots.length;
          img.src = shots[at].src;
          img.alt = shots[at].alt || '';
          cap.textContent = shots[at].caption || '';
          cap.style.display = shots[at].caption ? '' : 'none';
          count.textContent = (at + 1) + ' / ' + shots.length;
        }

        function shut() {
          box.remove();
          document.removeEventListener('keydown', onKey);
          document.documentElement.classList.remove('tsl-lightbox-open');
          document.body.classList.remove('tsl-lightbox-open');
          if (onClose) onClose(at);
        }

        function onKey(e) {
          if (e.key === 'Escape') { shut(); }
          if (e.key === 'ArrowLeft') { e.preventDefault(); render(at - 1); }
          if (e.key === 'ArrowRight') { e.preventDefault(); render(at + 1); }
        }

        prev.addEventListener('click', function () { render(at - 1); });
        next.addEventListener('click', function () { render(at + 1); });
        close.addEventListener('click', shut);
        // Clicking the backdrop closes; clicking the photo itself must not.
        box.addEventListener('click', function (e) { if (e.target === box) shut(); });
        document.addEventListener('keydown', onKey);

        var lx = null;
        box.addEventListener('touchstart', function (e) { lx = e.touches[0].clientX; }, { passive: true });
        box.addEventListener('touchend', function (e) {
          if (lx === null) return;
          var dx = e.changedTouches[0].clientX - lx;
          if (Math.abs(dx) > 40) render(dx < 0 ? at + 1 : at - 1);
          lx = null;
        }, { passive: true });

        render(at);
        document.documentElement.classList.add('tsl-lightbox-open');
        document.body.classList.add('tsl-lightbox-open');
        document.body.appendChild(box);
        close.focus();
      }

      document.querySelectorAll('.tsl-album').forEach(function (album) {
        var slides = album.querySelectorAll('.tsl-album-slide');
        var thumbs = album.querySelectorAll('.tsl-album-thumb');
        var counter = album.querySelector('.tsl-album-current');
        var at = 0;

        // The album shows a copy sized for a 720px column; blown up full-screen
        // that would look soft. Read the widest candidate out of srcset instead
        // of taking currentSrc, which is only the one picked for this viewport.
        function widestSource(im) {
          if (!im) return '';
          var best = im.currentSrc || im.src;
          var set = im.getAttribute('srcset');
          if (set) {
            var widest = 0;
            set.split(',').forEach(function (part) {
              var m = part.trim().match(/^(\S+)\s+(\d+)w$/);
              if (m && parseInt(m[2], 10) > widest) { widest = parseInt(m[2], 10); best = m[1]; }
            });
          }
          return best;
        }

        var shots = [].map.call(slides, function (s) {
          var im = s.querySelector('img');
          var cp = s.querySelector('figcaption');
          return {
            src: widestSource(im),
            alt: im ? im.alt : '',
            caption: cp ? cp.textContent.trim() : ''
          };
        });

        function openAt(i) {
          openLightbox(shots, i, function (endedAt) {
            // Come back to whichever photo was left on screen.
            if (slides.length > 1) show(endedAt);
          });
        }

        [].forEach.call(slides, function (s, i) {
          var im = s.querySelector('img');
          if (!im) return;
          im.addEventListener('click', function () { openAt(i); });
        });

        // A single-image gallery still gets the viewer, just no carousel.
        if (slides.length < 2) return;

        function show(next) {
          at = (next + slides.length) % slides.length;
          slides.forEach(function (s, i) {
            var on = i === at;
            s.classList.toggle('is-active', on);
            if (on) { s.removeAttribute('aria-hidden'); } else { s.setAttribute('aria-hidden', 'true'); }
          });
          thumbs.forEach(function (t, i) {
            var on = i === at;
            t.classList.toggle('is-active', on);
            if (on) { t.setAttribute('aria-current', 'true'); } else { t.removeAttribute('aria-current'); }
          });
          if (counter) counter.textContent = at + 1;
          // Keep the active thumbnail in view when the strip scrolls.
          var thumb = thumbs[at];
          if (thumb && thumb.parentElement) {
            thumb.parentElement.scrollIntoView({ block: 'nearest', inline: 'nearest' });
          }
        }

        album.querySelector('.tsl-album-prev').addEventListener('click', function () { show(at - 1); });
        album.querySelector('.tsl-album-next').addEventListener('click', function () { show(at + 1); });
        thumbs.forEach(function (t) {
          t.addEventListener('click', function () { show(parseInt(t.dataset.index, 10) || 0); });
        });

        // Arrow keys, once the album has focus.
        album.setAttribute('tabindex', '0');
        album.addEventListener('keydown', function (e) {
          if (e.key === 'ArrowLeft') { e.preventDefault(); show(at - 1); }
          if (e.key === 'ArrowRight') { e.preventDefault(); show(at + 1); }
        });

        // Swipe, for phones.
        var x0 = null;
        var stage = album.querySelector('.tsl-album-stage');
        stage.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
        stage.addEventListener('touchend', function (e) {
          if (x0 === null) return;
          var dx = e.changedTouches[0].clientX - x0;
          if (Math.abs(dx) > 40) show(dx < 0 ? at + 1 : at - 1);
          x0 = null;
        }, { passive: true });
      });
    })();

    // YouTube blocks — the Reels strip of Shorts, and the Watch block of full
    // episodes. Same player, different shape.
    (function () {
      var groups = [
        { cards: document.querySelectorAll('.reels .reel'), portrait: true },
        { cards: document.querySelectorAll('.watch [data-video]'), portrait: false }
      ].filter(function (g) { return g.cards.length; });
      if (!groups.length) return;

      // YouTube generates maxresdefault for most videos but not all. A missing
      // one does not reliably 404: i.ytimg.com sometimes answers 200 with a
      // 120x90 grey placeholder instead, which no error event ever reports. So
      // both outcomes are checked, and hqdefault — which always exists — is
      // swapped in. The data-fallback attribute is cleared on use, so a failing
      // fallback cannot loop.
      document.querySelectorAll('.reel img, .watch img').forEach(function (img) {
        function useFallback() {
          var alt = img.getAttribute('data-fallback');
          if (!alt || img.src === alt) return;
          img.removeAttribute('data-fallback');
          img.src = alt;
        }

        img.addEventListener('error', useFallback);
        img.addEventListener('load', function () {
          if (img.naturalWidth > 0 && img.naturalWidth <= 120) useFallback();
        });
        // A cached image can finish before these listeners are attached.
        if (img.complete) {
          if (!img.naturalWidth) { useFallback(); }
          else if (img.naturalWidth <= 120) { useFallback(); }
        }
      });

      // ---- player ----------------------------------------------------------
      // Built on click and thrown away on close, so nothing from YouTube is
      // loaded until a reader actually asks for a clip. Shares the album
      // lightbox's classes to keep one visual language for "full screen".
      var ICONS = {
        prev: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5 8 12l7 7"/></svg>',
        next: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m9 5 7 7-7 7"/></svg>',
        close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>'
      };

      function play(cards, startAt, portrait) {
        var at = startAt;
        var opener = cards[startAt];

        var box = document.createElement('div');
        box.className = 'tsl-lightbox';
        box.setAttribute('role', 'dialog');
        box.setAttribute('aria-modal', 'true');

        var stage = document.createElement('div');
        stage.className = portrait ? 'tsl-lightbox-reel' : 'tsl-lightbox-wide';
        var frame = document.createElement('iframe');
        frame.setAttribute('allow', 'autoplay; encrypted-media; picture-in-picture');
        frame.setAttribute('allowfullscreen', '');
        frame.setAttribute('title', 'YouTube');
        stage.appendChild(frame);
        box.appendChild(stage);

        var count = document.createElement('span');
        count.className = 'tsl-lightbox-count';
        box.appendChild(count);

        function button(cls, label, icon) {
          var b = document.createElement('button');
          b.type = 'button';
          b.className = 'tsl-lightbox-btn ' + cls;
          b.setAttribute('aria-label', label);
          b.innerHTML = icon;
          box.appendChild(b);
          return b;
        }
        var prev = button('tsl-lightbox-prev', 'คลิปก่อนหน้า', ICONS.prev);
        var next = button('tsl-lightbox-next', 'คลิปถัดไป', ICONS.next);
        var close = button('tsl-lightbox-close', 'ปิด', ICONS.close);
        if (cards.length < 2) { prev.hidden = true; next.hidden = true; }

        function render(i) {
          at = (i + cards.length) % cards.length;
          // nocookie keeps YouTube from setting tracking cookies on a reader
          // who only watched, and never signed in.
          frame.src = 'https://www.youtube-nocookie.com/embed/' +
            encodeURIComponent(cards[at].getAttribute('data-video')) +
            '?autoplay=1&rel=0&playsinline=1&modestbranding=1';
          count.textContent = (at + 1) + ' / ' + cards.length;
        }

        function shut() {
          frame.src = 'about:blank'; // stop playback before the node goes away
          box.remove();
          document.removeEventListener('keydown', onKey);
          document.documentElement.classList.remove('tsl-lightbox-open');
          document.body.classList.remove('tsl-lightbox-open');
          if (opener) opener.focus();
        }

        function onKey(e) {
          if (e.key === 'Escape') { shut(); }
          if (e.key === 'ArrowLeft') { e.preventDefault(); render(at - 1); }
          if (e.key === 'ArrowRight') { e.preventDefault(); render(at + 1); }
        }

        prev.addEventListener('click', function () { render(at - 1); });
        next.addEventListener('click', function () { render(at + 1); });
        close.addEventListener('click', shut);
        box.addEventListener('click', function (e) { if (e.target === box) shut(); });
        document.addEventListener('keydown', onKey);

        render(at);
        document.documentElement.classList.add('tsl-lightbox-open');
        document.body.classList.add('tsl-lightbox-open');
        document.body.appendChild(box);
        close.focus();
      }

      // Each block is its own reel of clips: paging inside the player stays
      // within the block you opened it from.
      groups.forEach(function (group) {
        var cards = Array.prototype.slice.call(group.cards);
        cards.forEach(function (card, i) {
          card.addEventListener('click', function () { play(cards, i, group.portrait); });
        });
      });

      // ---- arrows and scroll bar (Reels only) ------------------------------
      var rail = document.querySelector('.reels-rail');
      if (!rail) return;

      var strip = rail.querySelector('.reels');
      var cards = Array.prototype.slice.call(rail.querySelectorAll('.reel'));
      var back = rail.querySelector('.reels-prev');
      var fwd = rail.querySelector('.reels-next');
      var bar = rail.querySelector('.reels-bar');
      var thumb = bar && bar.querySelector('.reels-bar-thumb');
      if (!strip || !cards.length || !back || !fwd) return;

      function slack() { return strip.scrollWidth - strip.clientWidth; }

      function step() {
        var card = cards[0].getBoundingClientRect();
        return Math.max(160, Math.round(card.width) + 20) * 2;
      }

      function sync() {
        // A rail that fits on screen needs neither arrows nor a bar; past
        // either end, the arrow that cannot move is hidden rather than left
        // dead.
        var room = slack();
        if (room < 8) {
          back.hidden = true;
          fwd.hidden = true;
          if (bar) bar.hidden = true;
          return;
        }
        back.hidden = strip.scrollLeft < 8;
        fwd.hidden = strip.scrollLeft > room - 8;

        if (!bar || !thumb) return;
        bar.hidden = false;
        // The thumb is as wide a share of the track as the visible row is of
        // the whole row — the usual scrollbar relationship — with a floor so it
        // stays catchable when there are a lot of clips.
        var track = bar.clientWidth;
        var w = Math.max(32, Math.round(track * strip.clientWidth / strip.scrollWidth));
        thumb.style.width = w + 'px';
        thumb.style.transform = 'translateX(' + ((track - w) * (strip.scrollLeft / room)) + 'px)';
      }

      back.addEventListener('click', function () { strip.scrollBy({ left: -step(), behavior: 'smooth' }); });
      fwd.addEventListener('click', function () { strip.scrollBy({ left: step(), behavior: 'smooth' }); });
      strip.addEventListener('scroll', sync, { passive: true });
      window.addEventListener('resize', sync);

      // Dragging the bar, and clicking anywhere along it to jump there. Pointer
      // events cover mouse, pen and touch in one path. The move/up listeners go
      // on the document rather than the bar, so a drag that wanders off a 2px
      // line — which every drag does — keeps working.
      if (bar && thumb && window.PointerEvent) {
        bar.addEventListener('pointerdown', function (e) {
          var room = slack();
          if (room < 8) return;
          e.preventDefault();

          var track = bar.getBoundingClientRect();
          var w = thumb.offsetWidth;
          var travel = track.width - w;

          function follow(clientX) {
            if (travel <= 0) return;
            // Grab the thumb by its middle, so it sits under the pointer.
            var at = (clientX - track.left - w / 2) / travel;
            strip.scrollLeft = Math.min(1, Math.max(0, at)) * room;
          }

          function move(ev) { follow(ev.clientX); }
          function release() {
            document.removeEventListener('pointermove', move);
            document.removeEventListener('pointerup', release);
            document.removeEventListener('pointercancel', release);
            rail.classList.remove('dragging');
          }

          rail.classList.add('dragging');
          document.addEventListener('pointermove', move);
          document.addEventListener('pointerup', release);
          document.addEventListener('pointercancel', release);
          follow(e.clientX);
        });
      }

      sync();
    })();
  });
})();
