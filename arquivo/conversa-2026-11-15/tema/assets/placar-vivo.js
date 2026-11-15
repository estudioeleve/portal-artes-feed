/* Placar em tempo real — Atlético-MG, Cruzeiro e América-MG.
   Os dados vêm do próprio site (admin-ajax.php), que busca numa fonte
   pública gratuita e guarda em cache por alguns segundos. Atualiza sozinho:
   a cada 25s se tiver jogo ao vivo, a cada 3min se não tiver. */
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
      .then(function (d) { return (d && d.success) ? d.data : { times: [], outros: [] }; })
      .catch(function () { return { times: [], outros: [] }; });
  }

  function buscaLances(comp, id) {
    return fetch(poPlacar.ajax + '?action=po_placar_lances&comp=' + encodeURIComponent(comp) + '&id=' + encodeURIComponent(id))
      .then(function (r) { return r.json(); })
      .then(function (d) { return (d && d.success) ? d.data : []; })
      .catch(function () { return []; });
  }

  function cartaoHTML(jogo) {
    var vivo = jogo.estado === 'in';
    var placar = (jogo.estado === 'pre') ? horaLocal(jogo.data) : (jogo.placar_casa + ' x ' + jogo.placar_fora);
    var situacao = vivo ? (jogo.relogio || jogo.situacao) : jogo.situacao;
    var selo = vivo ? '<span class="pv-vivo"><span class="pv-bolinha"></span>AO VIVO</span>' : '<span class="pv-sit">' + situacao + '</span>';
    return (
      '<button class="pv-jogo" type="button" data-id="' + jogo.id + '" data-comp="' + jogo.competicao_slug + '" aria-expanded="false">' +
      '<span class="pv-ed">' + jogo.competicao + '</span>' +
      '<span class="pv-linha">' +
      '<span class="pv-time">' + jogo.casa + '</span>' +
      '<span class="pv-placar">' + placar + '</span>' +
      '<span class="pv-time">' + jogo.fora + '</span>' +
      '</span>' +
      '<span class="pv-rodape">' + selo + '</span>' +
      '<span class="pv-detalhe" hidden></span>' +
      '</button>'
    );
  }

  function render(container, jogos) {
    if (!jogos.length) {
      container.innerHTML = '<p class="pv-vazio">Sem jogo do Atlético, do Cruzeiro ou do América hoje.</p>';
      return;
    }
    container.innerHTML = jogos.map(cartaoHTML).join('');
    container.querySelectorAll('.pv-jogo').forEach(function (btn) {
      btn.addEventListener('click', function () { alternaDetalhe(btn); });
    });
  }

  function outroHTML(jogo) {
    var vivo = jogo.estado === 'in';
    var placar = (jogo.estado === 'pre') ? horaLocal(jogo.data) : (jogo.placar_casa + ' x ' + jogo.placar_fora);
    var sit = vivo ? (jogo.relogio || 'AO VIVO') : jogo.situacao;
    return (
      '<div class="pv-outro">' +
      '<span class="pv-outro-ed">' + jogo.competicao + '</span>' +
      '<span class="pv-outro-linha"><span>' + jogo.casa + '</span><span class="pv-outro-placar">' + placar + '</span><span>' + jogo.fora + '</span></span>' +
      '<span class="pv-outro-sit' + (vivo ? ' pv-outro-vivo' : '') + '">' + sit + '</span>' +
      '</div>'
    );
  }

  function renderOutros(container, outros) {
    if (!outros.length) {
      container.innerHTML = '<p class="pv-vazio">Sem outros jogos hoje.</p>';
      return;
    }
    container.innerHTML = outros.map(outroHTML).join('');
  }

  function alternaDetalhe(btn) {
    var painel = btn.querySelector('.pv-detalhe');
    var aberto = btn.getAttribute('aria-expanded') === 'true';
    if (aberto) { btn.setAttribute('aria-expanded', 'false'); painel.hidden = true; return; }
    btn.setAttribute('aria-expanded', 'true');
    painel.hidden = false;
    painel.textContent = 'Carregando lances…';
    buscaLances(btn.getAttribute('data-comp'), btn.getAttribute('data-id')).then(function (lances) {
      if (!lances.length) { painel.textContent = 'Ainda sem lances registrados.'; return; }
      painel.innerHTML = lances.map(function (ev) {
        return '<span class="pv-lance"><b>' + (ev.minuto || '') + '</b> ' + (ev.texto || '') + '</span>';
      }).join('');
    });
  }

  function iniciar() {
    var container = document.getElementById('placar-vivo');
    var containerOutros = document.getElementById('placar-outros');
    if (!container) return;
    var atualizando = false;
    function atualizar() {
      if (atualizando) return;
      atualizando = true;
      buscaJogosDeHoje().then(function (dados) {
        var jogos = dados.times || [];
        var outros = dados.outros || [];
        render(container, jogos);
        if (containerOutros) renderOutros(containerOutros, outros);
        var temVivo = jogos.concat(outros).some(function (j) { return j.estado === 'in'; });
        atualizando = false;
        setTimeout(atualizar, temVivo ? 25000 : 180000);
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
