/* مفترق الطرق — home: pixel sunrise scenes, carousel, scroll arrows */
(function () {
  'use strict';

  /* ───────── scroll arrows ───────── */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-next]');
    if (!b) return;
    var cur = b.closest('.fs'), nxt = cur && cur.nextElementSibling;
    while (nxt && !nxt.classList.contains('fs')) nxt = nxt.nextElementSibling;
    if (nxt) nxt.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
  /* an arrow is visible only while its own section sits at the top of the screen;
     the moment you scroll away it fades out, and the next section shows its own */
  (function () {
    var arrows = [].slice.call(document.querySelectorAll('.scroll-arrow')), ticking = false;
    if (!arrows.length) return;
    function upd() {
      ticking = false;
      arrows.forEach(function (a) {
        var s = a.closest('.fs'); if (!s) return;
        var top = s.getBoundingClientRect().top;
        a.classList.toggle('is-hidden', Math.abs(top) > 40);
      });
    }
    window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(upd); } }, { passive: true });
    window.addEventListener('resize', upd);
    upd();
  })();

  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ───────── pixel sunrise scene ───────── */
  var PAL = {
    light: {
      sky: ['#FF9F45', '#FFAB52', '#FFB963', '#FFC874', '#FFD786', '#FFE69B', '#FFF2B8'],
      ring: '#FFF3B0', cloud: '#FFFFFF', cloudSh: '#FFD9A3',
      far: '#EE8B3C', mid: '#CE5F1E', near: '#9A3A12', fore: '#4F1E09', tree: '#2E1204', bird: '#5A2509', spark: '#FFFBD6'
    },
    dark: {
      sky: ['#1B2A4A', '#2A3358', '#4A3A5E', '#7A4A5A', '#B5624E', '#E58B3A', '#FFC15A'],
      ring: '#FFD27A', cloud: '#E9B38A', cloudSh: '#A9604A',
      far: '#6B3A33', mid: '#4B2828', near: '#2F1A1C', fore: '#1B0F10', tree: '#0E0708', bird: '#2A1410', spark: '#FFE6A8'
    }
  };
  var SUN = { edge: '#F26A0F', rim: '#FF8A12', body: '#FFC21F', hi: '#FFE266', core: '#FFF8C6' };
  function rnd(a, b) { return a + Math.random() * (b - a); }
  function curPal() { return document.documentElement.getAttribute('data-theme') === 'dark' ? PAL.dark : PAL.light; }

  function Scene(cv) {
    var ctx = cv.getContext('2d');
    var sunFrac = parseFloat(cv.getAttribute('data-sun')) || 0.5;
    var W = 0, H = 0, px = 6, hz = 0, cx = 0, cy = 0, R = 0, t = rnd(0, 10), last = 0;
    var sparks = [], clouds = [], birds = [], stars = [];

    function dot(x, y, c) { ctx.fillStyle = c; ctx.fillRect(x | 0, y | 0, 1, 1); }
    function rect(x, y, w, h, c) { ctx.fillStyle = c; ctx.fillRect(x | 0, y | 0, w, h); }

    function setup() {
      var bw = cv.clientWidth || 600, bh = cv.clientHeight || 300;
      px = Math.max(3, Math.min(7, Math.round(bw / 150)));
      W = Math.ceil(bw / px); H = Math.ceil(bh / px);
      cv.width = W; cv.height = H;
      ctx.imageSmoothingEnabled = false;
      hz = Math.floor(H * 0.76);
      R = Math.max(9, Math.floor(Math.min(W * 0.2, H * 0.27)));
      cx = Math.floor(W * sunFrac);
      cy = hz - Math.floor(R * 0.78);
      sparks = []; stars = [];
      for (var i = 0; i < Math.max(10, W / 8); i++) sparks.push({ x: rnd(0, W), y: rnd(0, hz), v: rnd(1.2, 3.2), p: rnd(0, 6.28), big: Math.random() < 0.25 });
      for (i = 0; i < Math.max(12, W / 6); i++) stars.push({ x: Math.floor(rnd(0, W)), y: Math.floor(rnd(0, hz * 0.5)), p: rnd(0, 6.28) });
      clouds = [{ x: W * 0.1, y: H * 0.14, v: 1.5, s: 1 }, { x: W * 0.6, y: H * 0.08, v: 1.0, s: 1.4 }, { x: W * 0.88, y: H * 0.3, v: 2.0, s: 0.8 }];
      birds = [{ x: W * 0.8, y: H * 0.26, v: -5, p: 0 }, { x: W * 0.9, y: H * 0.3, v: -5.6, p: 1.7 }, { x: W * 0.7, y: H * 0.22, v: -4.6, p: 3.1 }];
    }

    function drawSky(P, dark) {
      var n = P.sky.length, bh = Math.ceil(hz / n), b, y, x;
      for (b = 0; b < n; b++) rect(0, b * bh, W, bh + 1, P.sky[b]);
      rect(0, hz, W, H - hz, P.sky[n - 1]);
      for (b = 1; b < n; b++) {
        var y0 = b * bh;
        for (y = y0; y < y0 + 2; y++) for (x = 0; x < W; x++) { if (y === y0 || ((x + y) & 1) === 0) dot(x, y, P.sky[b - 1]); }
      }
      if (dark) for (var i = 0; i < stars.length; i++) { var s = stars[i]; if (Math.floor(t * 2 + s.p) % 3) dot(s.x, s.y, '#FFF4D0'); }
    }
    function drawRings(P) {
      for (var k = 0; k < 3; k++) {
        var prog = ((t * 0.28) + k / 3) % 1, rr = R + 5 + prog * R * 1.8, alpha = 1 - prog;
        if (alpha < 0.12) continue;
        var steps = Math.ceil(rr * 7.5);
        for (var i = 0; i < steps; i++) {
          var a = (i / steps) * 6.2832, x = Math.round(cx + Math.cos(a) * rr), y = Math.round(cy + Math.sin(a) * rr);
          if (y >= hz) continue;
          var keep = alpha > 0.66 ? true : alpha > 0.38 ? ((x + y) & 1) === 0 : ((x & 1) === 0 && (y & 1) === 0);
          if (keep) dot(x, y, P.ring);
        }
      }
    }
    function drawRays() {
      var N = 18, rot = t * 0.22;
      for (var i = 0; i < N; i++) {
        var ang = rot + i * 6.2832 / N, long = (i % 2 === 0), pulse = 0.78 + 0.22 * Math.sin(t * 2.4 + i * 1.3);
        var len = (long ? R * 0.95 : R * 0.5) * pulse, cs = Math.cos(ang), sn = Math.sin(ang), start = R + 3;
        for (var s = 0; s < len; s += 0.5) {
          var p = s / len, x = Math.round(cx + cs * (start + s)), y = Math.round(cy + sn * (start + s));
          if (y >= hz || y < 0) continue;
          if (p > 0.55 && (Math.floor(s) % 4 === 3)) continue;
          var c = p < 0.34 ? SUN.hi : p < 0.7 ? SUN.body : SUN.rim;
          dot(x, y, c);
          if (long && p < 0.6) dot(x + (Math.abs(cs) < Math.abs(sn) ? 1 : 0), y + (Math.abs(cs) >= Math.abs(sn) ? 1 : 0), c);
        }
        if (long) { var tx = Math.round(cx + cs * (start + len)), ty = Math.round(cy + sn * (start + len)); if (ty < hz) dot(tx, ty, SUN.core); }
      }
    }
    function drawSun() {
      var r = R + (Math.sin(t * 1.6) > 0.7 ? 1 : 0);
      for (var y = -r; y <= r; y++) for (var x = -r; x <= r; x++) {
        var d = Math.sqrt(x * x + y * y);
        if (d > r) continue;
        var n = (d / r) * 0.86 + ((x + y) / (2 * r)) * 0.22 + 0.05, chk = ((x + y) & 1) === 0, c;
        if (d > r - 1.1) c = SUN.edge;
        else if (n < 0.2) c = SUN.core;
        else if (n < 0.27) c = chk ? SUN.core : SUN.hi;
        else if (n < 0.46) c = SUN.hi;
        else if (n < 0.53) c = chk ? SUN.hi : SUN.body;
        else if (n < 0.76) c = SUN.body;
        else if (n < 0.83) c = chk ? SUN.body : SUN.rim;
        else c = SUN.rim;
        dot(cx + x, cy + y, c);
      }
      var gx = cx - Math.floor(r * 0.42), gy = cy - Math.floor(r * 0.45);
      dot(gx, gy, '#fff'); dot(gx + 1, gy, '#fff'); dot(gx, gy + 1, '#fff');
    }
    function drawSparks(P, dt) {
      for (var i = 0; i < sparks.length; i++) {
        var s = sparks[i];
        s.y -= s.v * dt; s.x += Math.sin(t * 0.8 + s.p) * 0.12;
        if (s.y < 0) { s.y = hz - 1; s.x = rnd(0, W); }
        var tw = Math.floor(t * 3 + s.p) % 3;
        if (tw === 0) continue;
        dot(s.x, s.y, P.spark);
        if (s.big && tw === 1) { dot(s.x - 1, s.y, P.spark); dot(s.x + 1, s.y, P.spark); dot(s.x, s.y - 1, P.spark); dot(s.x, s.y + 1, P.spark); }
      }
    }
    var CLOUD = [[4, 7], [2, 11], [0, 14], [1, 13]];
    function drawClouds(P, dt) {
      for (var i = 0; i < clouds.length; i++) {
        var c = clouds[i]; c.x += c.v * dt; if (c.x > W + 18) c.x = -18 * c.s;
        var u = Math.max(1, Math.round(c.s));
        for (var r = 0; r < CLOUD.length; r++) rect(c.x + CLOUD[r][0] * c.s, c.y + r * u, Math.round(CLOUD[r][1] * c.s), u, r === CLOUD.length - 1 ? P.cloudSh : P.cloud);
      }
    }
    function drawBirds(P, dt) {
      for (var i = 0; i < birds.length; i++) {
        var b = birds[i]; b.x += b.v * dt;
        if (b.x < -8) { b.x = W + 6; b.y = rnd(H * 0.18, H * 0.34); }
        var f = Math.floor(t * 4 + b.p) % 2, y = b.y + Math.sin(t * 1.2 + b.p) * 1.5;
        if (f === 0) { dot(b.x, y + 1, P.bird); dot(b.x + 1, y, P.bird); dot(b.x + 2, y + 1, P.bird); dot(b.x + 3, y, P.bird); dot(b.x + 4, y + 1, P.bird); }
        else { dot(b.x, y, P.bird); dot(b.x + 1, y + 1, P.bird); dot(b.x + 2, y + 1, P.bird); dot(b.x + 3, y + 1, P.bird); dot(b.x + 4, y, P.bird); }
      }
    }
    function ground(x, base, a1, f1, p1, a2, f2, p2) { return Math.floor(base + a1 * Math.sin(x * f1 + p1) + a2 * Math.sin(x * f2 + p2)); }
    function layer(color, base, a1, f1, p1, a2, f2, p2) { for (var x = 0; x < W; x++) { var y = ground(x, base, a1, f1, p1, a2, f2, p2); rect(x, y, 1, H - y, color); } }
    function pine(x, base, h, color) {
      rect(x, base - 2, 1, 2, color);
      for (var i = 0; i < h; i++) { var w = Math.floor(i / 2) + 1; rect(x - w, base - 3 - (h - 1 - i), w * 2 + 1, 1, color); }
      dot(x, base - 3 - h, color);
    }
    function drawLand(P) {
      layer(P.far, hz + 1, 2.6, 0.045, 1.0, 1.4, 0.12, 2.3);
      layer(P.mid, hz + 6, 3.0, 0.06, 4.2, 1.4, 0.17, 0.4);
      layer(P.near, hz + 11, 2.6, 0.05, 2.1, 1.2, 0.21, 5.1);
      layer(P.fore, hz + 16, 2.0, 0.07, 0.3, 1.0, 0.26, 3.3);
      var xs = [0.06, 0.1, 0.15, 0.9, 0.95, 0.3, 0.72];
      for (var i = 0; i < xs.length; i++) {
        var x = Math.floor(W * xs[i]);
        pine(x, ground(x, hz + 11, 2.6, 0.05, 2.1, 1.2, 0.21, 5.1), 4 + (i % 3) * 2, P.tree);
      }
      var sx = Math.floor(W * 0.22), sy = ground(sx, hz + 16, 2.0, 0.07, 0.3, 1.0, 0.26, 3.3);
      rect(sx, sy - 12, 1, 12, P.tree);
      rect(sx - 6, sy - 11, 7, 3, '#FFC21F'); dot(sx - 7, sy - 10, '#FFC21F'); rect(sx - 6, sy - 9, 7, 1, '#F26A0F');
      rect(sx + 1, sy - 7, 7, 3, '#FF8A12'); dot(sx + 8, sy - 6, '#FF8A12'); rect(sx + 1, sy - 5, 7, 1, '#C2410C');
    }
    this.render = function (dt) {
      var dark = document.documentElement.getAttribute('data-theme') === 'dark', P = dark ? PAL.dark : PAL.light;
      drawSky(P, dark); drawSparks(P, dt); drawClouds(P, dt); drawRings(P); drawRays(); drawSun(); drawBirds(P, dt); drawLand(P);
    };
    this.tick = function (now) {
      if (!last) last = now;
      if (now - last < 66) return;                       // ~15 fps: chunky retro motion
      var dt = Math.min(0.2, (now - last) / 1000); last = now; t += dt;
      var r = cv.getBoundingClientRect();
      if (r.bottom < 0 || r.top > window.innerHeight || r.width === 0) return;
      this.render(dt);
    };
    this.setup = setup;
    setup();
    if (reduce) t = 2;
    this.render(0);                                        // first frame immediately
  }

  var scenes = [];
  document.querySelectorAll('canvas.px-scene').forEach(function (cv) { if (cv.getContext) scenes.push(new Scene(cv)); });
  if (scenes.length) {
    var rt;
    window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(function () { scenes.forEach(function (s) { s.setup(); if (reduce) s.render(0); }); }, 150); });
    if (!reduce) (function loop(now) { requestAnimationFrame(loop); if (!document.hidden) scenes.forEach(function (s) { s.tick(now); }); })(0);
  }

  /* ───────── carousel ───────── */
  document.querySelectorAll('.car').forEach(function (car) {
    var track = car.querySelector('.car-track'), slides = [].slice.call(car.querySelectorAll('.car-slide'));
    if (!track || !slides.length) return;
    var idx = 0, timer = null, hover = false, visible = true, DELAY = 6500;
    var bar = car.querySelector('.car-progress span');

    function setActive(i) {
      idx = i;
      slides.forEach(function (s, k) { s.classList.toggle('is-active', k === i); });
      if (bar) { bar.style.animation = 'none'; void bar.offsetWidth; bar.style.animation = reduce ? 'none' : 'carFill ' + DELAY + 'ms linear forwards'; }
    }
    function go(i, smooth) {
      i = (i + slides.length) % slides.length;
      setActive(i);
      slides[i].scrollIntoView({ behavior: smooth === false ? 'auto' : 'smooth', inline: 'center', block: 'nearest' });
    }
    function nearest() {
      var c = track.getBoundingClientRect(), mid = c.left + c.width / 2, best = 0, bd = 1e9;
      slides.forEach(function (s, k) { var r = s.getBoundingClientRect(), d = Math.abs(r.left + r.width / 2 - mid); if (d < bd) { bd = d; best = k; } });
      return best;
    }
    var st;
    track.addEventListener('scroll', function () { clearTimeout(st); st = setTimeout(function () { var n = nearest(); if (n !== idx) setActive(n); }, 90); }, { passive: true });
    car.querySelectorAll('.car-next').forEach(function (b) { b.addEventListener('click', function () { go(idx + 1); restart(); }); });
    car.querySelectorAll('.car-prev').forEach(function (b) { b.addEventListener('click', function () { go(idx - 1); restart(); }); });
    slides.forEach(function (s, k) { s.addEventListener('click', function (e) { if (!s.classList.contains('is-active')) { e.preventDefault(); go(k); restart(); } }); });

    function stop() { clearInterval(timer); timer = null; }
    function start() { if (reduce || slides.length < 2) return; stop(); timer = setInterval(function () { if (!hover && visible && !document.hidden) go(idx + 1); }, DELAY); }
    function restart() { setActive(idx); start(); }
    car.addEventListener('mouseenter', function () { hover = true; });
    car.addEventListener('mouseleave', function () { hover = false; });
    if ('IntersectionObserver' in window) new IntersectionObserver(function (en) { visible = en[0].isIntersecting; }, { threshold: 0.35 }).observe(car);
    window.addEventListener('load', function () { track.scrollLeft = 0; slides[0].scrollIntoView({ behavior: 'auto', inline: 'center', block: 'nearest' }); window.scrollTo(window.scrollX, 0); setActive(0); start(); });
    setActive(0);
  });
})();
