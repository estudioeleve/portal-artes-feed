/* Banner "Acompanhe ao vivo" na página inicial — só aparece quando tem jogo
   do Atlético, do Cruzeiro ou do América hoje. Some sozinho quando não tem. */
(function () {
  'use strict';
  if (typeof poPlacar === 'undefined') return;

  function horaLocal(iso) {
    try { return new Date(iso).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }); }
    catch (e) { return ''; }
  }

  function buscaJogosDeHoje() {
    return fetch(poPlacar.ajax + '?action=po_placar_dados')
      .then(function (r) { return r.json(); })
      .then(function (d) { return (d && d.success && d.data.times) ? d.data.times : []; })
      .catch(function () { return []; });
  }

  function escolheJogo(jogos) {
    if (!jogos.length) return null;
    var vivo = jogos.filter(function (j) { return j.estado === 'in'; });
    if (vivo.length) return vivo[0];
    var futuro = jogos.filter(function (j) { return j.estado === 'pre'; });
    if (futuro.length) return futuro[0];
    return jogos[0];
  }

  function render(container, jogo) {
    if (!jogo) { container.classList.remove('pv-ativo'); container.innerHTML = ''; return; }
    var vivo = jogo.estado === 'in';
    var placar = (jogo.estado === 'pre') ? ('Hoje, ' + horaLocal(jogo.data)) : (jogo.placar_casa + ' x ' + jogo.placar_fora);
    var situacao = vivo
      ? '<span class="pv-home-bolinha"></span>AO VIVO · ' + (jogo.relogio || '')
      : (jogo.estado === 'pre' ? jogo.competicao : jogo.situacao + ' · ' + jogo.competicao);
    var link = (poPlacar.categorias && poPlacar.categorias[jogo.time_categoria]) ? poPlacar.categorias[jogo.time_categoria] : '#';
    container.innerHTML = (
      '<a class="pv-home" href="' + link + '">' +
      '<img src="' + jogo.time_escudo + '" alt="">' +
      '<span class="pv-home-tx">' +
      '<span class="pv-home-ed">' + (vivo ? jogo.competicao : jogo.time_nome) + '</span>' +
      '<span class="pv-home-jogo">' + jogo.casa + ' ' + placar + ' ' + jogo.fora + '</span>' +
      '<span class="pv-home-sit">' + situacao + '</span>' +
      '</span>' +
      '<span class="pv-home-cta">Acompanhe em tempo real ›</span>' +
      '</a>'
    );
    container.classList.add('pv-ativo');
  }

  function iniciar() {
    var container = document.getElementById('placar-home');
    if (!container) return;
    var atualizando = false;
    function atualizar() {
      if (atualizando) return;
      atualizando = true;
      buscaJogosDeHoje().then(function (jogos) {
        var jogo = escolheJogo(jogos);
        render(container, jogo);
        atualizando = false;
        setTimeout(atualizar, (jogo && jogo.estado === 'in') ? 25000 : 180000);
      }).catch(function () { atualizando = false; setTimeout(atualizar, 60000); });
    }
    atualizar();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
  } else {
    iniciar();
  }
})();
