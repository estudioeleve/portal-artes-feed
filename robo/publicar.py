#!/usr/bin/env python3
"""Publica no @portalonenews pelo painel da Eleve (com collab automática por moldura).

Uso:
  ROBO_CHAVE=... python3 robo/publicar.py --portal luziense --legenda-arquivo /tmp/leg.txt --imagem URL [--imagem URL2 ...]
  ROBO_CHAVE=... python3 robo/publicar.py --portal one --legenda-arquivo /tmp/leg.txt --video URL
Imprime na última linha: OK <media_id> <link>   (ou ERRO <mensagem> e sai com código 1)
A chave fica só no prompt da tarefa agendada, nunca neste repositório.
"""
import argparse, json, os, sys, time, urllib.request, urllib.error

API = 'https://eleve-automacao.netlify.app/api/publicar'
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
    chave = os.environ.get('ROBO_CHAVE', '').strip()
    if not chave:
        print('ERRO falta ROBO_CHAVE'); sys.exit(1)
    legenda = open(a.legenda_arquivo, encoding='utf-8').read().strip()
    collab = collabs_do_portal(a.portal, a.collab)
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
        print('ERRO', e); sys.exit(1)
    print('collab:', ', '.join('@' + c for c in collab) or 'nenhuma')
    print('OK', r.get('id'), r.get('link', ''))


if __name__ == '__main__':
    main()
