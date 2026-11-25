#!/usr/bin/env python3
"""Publica no @portalonenews com collab automática por moldura.

Ordem (Israel, 25/11/2026): 1º a API oficial da Meta (app "Robo Portal ONE"; collab funciona
também em Reels); se ela não estiver disponível, 2º o painel da Eleve. A chave da Meta fica no
cofre do ambiente (Segredos de rede, graph.facebook.com) e entra sozinha nas chamadas — nunca aqui.

Uso:
  ROBO_CHAVE=... python3 robo/publicar.py --portal luziense --legenda-arquivo /tmp/leg.txt --imagem URL [--imagem URL2 ...]
  ROBO_CHAVE=... python3 robo/publicar.py --portal one --legenda-arquivo /tmp/leg.txt --video URL
Imprime na última linha: OK <media_id> <link>   (ou ERRO <mensagem> e sai com código 1)
A chave fica só no prompt da tarefa agendada, nunca neste repositório.
"""
import argparse, json, os, sys, time, urllib.request, urllib.error

API = 'https://eleve-automacao.netlify.app/api/publicar'
META = 'https://graph.facebook.com/v25.0/'
IG_ONE = '17841461417663539'  # @portalonenews (não é segredo)
AQUI = os.path.dirname(os.path.abspath(__file__))


def chamar(corpo, chave):
    req = urllib.request.Request(API, data=json.dumps(corpo).encode(), method='POST',
                                 headers={'content-type': 'application/json', 'x-chave-robo': chave})
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            return json.loads(r.read().decode() or '{}')
    except urllib.error.HTTPError as e:
        try:
            j = json.loads(e.read().decode() or '{}')
        except Exception:
            j = {}
        raise RuntimeError(j.get('erro') or f'HTTP {e.code}')


class SemMeta(Exception):
    pass


def meta(metodo, caminho, dados=None):
    import urllib.parse
    corpo = urllib.parse.urlencode(dados or {}).encode()
    if metodo == 'GET':
        req = urllib.request.Request(META + caminho + ('?' + corpo.decode() if dados else ''))
    else:
        req = urllib.request.Request(META + caminho, data=corpo, method='POST')
    try:
        with urllib.request.urlopen(req, timeout=120) as r:
            return json.loads(r.read().decode() or '{}')
    except urllib.error.HTTPError as e:
        try:
            j = json.loads(e.read().decode() or '{}').get('error', {})
        except Exception:
            j = {}
        cod = j.get('code')
        if cod in (190, 2500, 102, 104) or 'access token' in str(j.get('message', '')).lower():
            raise SemMeta(j.get('message') or f'HTTP {e.code}')
        raise RuntimeError(f"Meta {cod}/{j.get('error_subcode')}: {j.get('message') or e.code}")


def meta_esperar(cid, limite_s):
    fim = time.time() + limite_s
    while True:
        st = meta('GET', cid, {'fields': 'status_code,status'})
        c = st.get('status_code')
        if c in ('FINISHED', 'PUBLISHED'):
            return
        if c in ('ERROR', 'EXPIRED'):
            raise RuntimeError('o Instagram não conseguiu preparar a mídia: ' + str(st.get('status', '')))
        if time.time() > fim:
            raise RuntimeError('o Instagram demorou demais para preparar a mídia')
        time.sleep(6)


def publicar_meta(legenda, imagens, video, collab):
    co = {'collaborators': json.dumps(collab)} if collab else {}
    if video:
        d = {'media_type': 'REELS', 'video_url': video, 'caption': legenda, 'share_to_feed': 'true'}
        d.update(co)
        cid = meta('POST', IG_ONE + '/media', d)['id']
        meta_esperar(cid, 900)
    elif len(imagens) == 1:
        d = {'image_url': imagens[0], 'caption': legenda}
        d.update(co)
        cid = meta('POST', IG_ONE + '/media', d)['id']
        meta_esperar(cid, 120)
    else:
        filhos = []
        for u in imagens[:10]:
            f = meta('POST', IG_ONE + '/media', {'image_url': u, 'is_carousel_item': 'true'})['id']
            meta_esperar(f, 120)
            filhos.append(f)
        d = {'media_type': 'CAROUSEL', 'children': ','.join(filhos), 'caption': legenda}
        d.update(co)
        cid = meta('POST', IG_ONE + '/media', d)['id']
        meta_esperar(cid, 300)
    mid = meta('POST', IG_ONE + '/media_publish', {'creation_id': cid})['id']
    try:
        link = meta('GET', mid, {'fields': 'permalink'}).get('permalink', '')
    except Exception:
        link = ''
    return mid, link


def collabs_do_portal(portal, extras):
    try:
        mapa = json.load(open(os.path.join(AQUI, 'collabs.json')))
    except Exception:
        mapa = {}
    lista = list(mapa.get(portal, [])) + list(extras or [])
    vistos = []
    for u in lista:
        u = u.strip().lstrip('@').lower()
        if u and u not in vistos:
            vistos.append(u)
    return vistos[:3]


def main():
    p = argparse.ArgumentParser()
    p.add_argument('--portal', default='one', help='one, luziense, politica ou comercio')
    p.add_argument('--imagem', action='append', default=[])
    p.add_argument('--video', default='')
    p.add_argument('--legenda-arquivo', required=True)
    p.add_argument('--collab', action='append', default=[], help='@ extra (opcional)')
    a = p.parse_args()
    legenda = open(a.legenda_arquivo, encoding='utf-8').read().strip()
    collab = collabs_do_portal(a.portal, a.collab)
    # 1º caminho: API oficial da Meta (Israel, 25/11/2026)
    try:
        mid, link = publicar_meta(legenda, a.imagem, a.video, collab)
        print('via: API da Meta (Robo Portal ONE)')
        print('collab:', ', '.join('@' + c for c in collab) or 'nenhuma')
        print('OK', mid, link)
        return
    except SemMeta as e:
        print('aviso: API da Meta sem chave neste ambiente (' + str(e)[:80] + '); tentando o painel')
    except Exception as e:
        if '2207042' in str(e):
            print('ERRO limite de 100 posts em 24h do Instagram (vale para Meta, painel e Windsor)'); sys.exit(1)
        print('aviso: API da Meta falhou (' + str(e)[:200] + '); tentando o painel')
    trava = '/tmp/painel_bloqueado'
    if os.path.exists(trava) and time.time() - os.path.getmtime(trava) < 3 * 3600:
        print('ERRO painel bloqueado pelo Instagram nesta sessão (too many actions); use o Windsor'); sys.exit(1)
    chave = os.environ.get('ROBO_CHAVE', '').strip()
    if not chave:
        print('ERRO falta ROBO_CHAVE'); sys.exit(1)
    corpo = {'etapa': 'criar', 'conta': 'portalonenews', 'legenda': legenda, 'colaboradores': collab}
    if a.video:
        corpo['video'] = a.video
    else:
        corpo['imagens'] = a.imagem
    try:
        criacao = chamar(corpo, chave)['criacao']
        limite = time.time() + (300 if a.video else 90)
        while True:
            s = chamar({'etapa': 'status', 'conta': 'portalonenews', 'criacao': criacao}, chave)
            st = s.get('status', '')
            if st in ('FINISHED', 'PUBLISHED', ''):
                break
            if st in ('ERROR', 'EXPIRED'):
                raise RuntimeError('o Instagram não conseguiu preparar a mídia: ' + str(s.get('detalhe', '')))
            if time.time() > limite:
                raise RuntimeError('o Instagram demorou demais para preparar a mídia')
            time.sleep(5)
        r = chamar({'etapa': 'publicar', 'conta': 'portalonenews', 'criacao': criacao}, chave)
    except Exception as e:
        if 'too many actions' in str(e).lower():
            open(trava, 'w').write(str(e))
        print('ERRO', e); sys.exit(1)
    print('collab:', ', '.join('@' + c for c in collab) or 'nenhuma')
    print('OK', r.get('id'), r.get('link', ''))


if __name__ == '__main__':
    main()
