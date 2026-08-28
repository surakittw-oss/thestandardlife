/* THE STANDARD LIFE — theme interactions */
(function () {
  'use strict';

  // Theme toggle with localStorage + system preference (run early to avoid flash)
  (function () {
    var root = document.documentElement;
    var saved = localStorage.getItem('tsl-theme');
    var systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    root.setAttribute('data-theme', saved || (systemDark ? 'dark' : 'light'));

    document.addEventListener('DOMContentLoaded', function () {
      var btn = document.getElementById('themeToggle');
      if (!btn) return;
      btn.addEventListener('click', function () {
        var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        localStorage.setItem('tsl-theme', next);
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
      var onScroll = function () {
        var h = document.documentElement;
        var max = h.scrollHeight - h.clientHeight;
        bar.style.width = (max > 0 ? (h.scrollTop / max) * 100 : 0) + '%';
      };
      window.addEventListener('scroll', onScroll, { passive: true });
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
  });
})();
