// Portal ONE News 2.0: menu lateral, busca e vídeos.
(function () {
  var g = document.getElementById('gaveta'), f = document.getElementById('fundo-gaveta'), ab = document.getElementById('abre-menu');
  function menu(abrir) {
    if (!g) return;
    g.hidden = !abrir; f.hidden = !abrir;
    ab.setAttribute('aria-expanded', abrir ? 'true' : 'false');
    document.body.style.overflow = abrir ? 'hidden' : '';
    if (abrir) { var b = document.getElementById('fecha-menu'); if (b) b.focus(); } else ab.focus();
  }
  if (ab) ab.addEventListener('click', function () { menu(true); });
  var fe = document.getElementById('fecha-menu'); if (fe) fe.addEventListener('click', function () { menu(false); });
  if (f) f.addEventListener('click', function () { menu(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && g && !g.hidden) menu(false); });

  var bb = document.getElementById('abre-busca'), bx = document.getElementById('busca');
  if (bb && bx) bb.addEventListener('click', function () {
    bx.hidden = !bx.hidden;
    bb.setAttribute('aria-expanded', bx.hidden ? 'false' : 'true');
    if (!bx.hidden) document.getElementById('q').focus();
  });

  document.querySelectorAll('.vid[data-embed]').forEach(function (b) {
    b.addEventListener('click', function () {
      var t = document.getElementById(b.dataset.embed);
      if (!t) return;
      var box = document.createElement('div'); box.className = 'vid';
      var d = document.createElement('div'); d.className = 'vid-aberto'; d.innerHTML = t.innerHTML;
      box.appendChild(d);
      var tit = b.querySelector('b'); if (tit) box.appendChild(tit.cloneNode(true));
      b.replaceWith(box);
    });
  });

  /* carrossel do destaque */
  var car = document.querySelector('.carrossel');
  if (car) {
    var sl = car.querySelectorAll('.slide'), pt = car.querySelectorAll('.car-pontos button'), i = 0, t = null;
    var parado = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function ir(n) {
      i = (n + sl.length) % sl.length;
      sl.forEach(function (s, k) {
        s.classList.toggle('ativo', k === i);
        s.setAttribute('aria-hidden', k === i ? 'false' : 'true');
        var a = s.querySelector('a'); if (a) { if (k === i) a.removeAttribute('tabindex'); else a.setAttribute('tabindex', '-1'); }
      });
      pt.forEach(function (p, k) { if (k === i) p.setAttribute('aria-current', 'true'); else p.removeAttribute('aria-current'); });
    }
    function auto() { if (parado || sl.length < 2) return; clearInterval(t); t = setInterval(function () { ir(i + 1); }, 6000); }
    var b1 = car.querySelector('.ant'), b2 = car.querySelector('.prox');
    if (b1) b1.addEventListener('click', function () { ir(i - 1); auto(); });
    if (b2) b2.addEventListener('click', function () { ir(i + 1); auto(); });
    pt.forEach(function (p, k) { p.addEventListener('click', function () { ir(k); auto(); }); });
    car.addEventListener('mouseenter', function () { clearInterval(t); });
    car.addEventListener('mouseleave', auto);
    car.addEventListener('focusin', function () { clearInterval(t); });
    var x0 = null;
    car.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
    car.addEventListener('touchend', function (e) { if (x0 === null) return; var d = e.changedTouches[0].clientX - x0; if (Math.abs(d) > 40) { ir(i + (d < 0 ? 1 : -1)); auto(); } x0 = null; }, { passive: true });
    auto();
  }

  /* compartilhar: copiar link (pensado pro Instagram/TikTok, que não têm link de compartilhar
     direto como WhatsApp/Facebook/X) e o compartilhar nativo do celular (abre o menu do
     aparelho com todos os apps instalados, Instagram e TikTok inclusive) */
  document.querySelectorAll('.compart').forEach(function (caixa) {
    var url = caixa.dataset.url, titulo = caixa.dataset.titulo, dica = caixa.querySelector('[data-compart-dica]');
    function mostraDica(texto) {
      if (!dica) return;
      dica.textContent = texto;
      dica.hidden = false;
      clearTimeout(dica._t);
      dica._t = setTimeout(function () { dica.hidden = true; }, 4000);
    }
    function copiar() {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(function () {
          mostraDica('Link copiado — é só colar nos stories, no TikTok ou onde você quiser postar.');
        }).catch(function () { prompt('Copie o link:', url); });
      } else {
        prompt('Copie o link:', url);
      }
    }
    var btCopiar = caixa.querySelector('[data-compart-copiar]');
    if (btCopiar) btCopiar.addEventListener('click', copiar);
    var btNativo = caixa.querySelector('[data-compart-nativo]');
    if (btNativo) btNativo.addEventListener('click', function () {
      if (navigator.share) {
        navigator.share({ title: titulo, url: url }).catch(function () {});
      } else {
        copiar();
      }
    });
  });
})();
