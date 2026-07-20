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
      if (!headings.length) { toc.style.display = 'none'; return; }
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
  });
})();
