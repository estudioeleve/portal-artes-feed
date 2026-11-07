"""Ajusta a foto para a moldura do Instagram (Israel, 06/11/2026: "tá ficando borda... se tiver uma pessoa na imagem, coloca a pessoa no centro").
1) Montagem de 2 fotos lado a lado (ou em cima/embaixo): detecta a emenda e fica SÓ com uma parte
   (a que tem rosto; senão a maior). Assim não aparece a faixa da outra foto na lateral da arte.
2) Rosto/pessoa: calcula o `foco` para deixar a pessoa no centro da moldura e grava no item do lote.
uso: python3 robo/colagem.py <id> [<id> ...]        (automático)
     python3 robo/colagem.py <id> --lado esq|dir|cima|baixo   (força a parte)
Rode depois do coletar.py e antes do artes.js. Depois, OLHE a arte (Read) do mesmo jeito."""
import sys, json
import numpy as np
from PIL import Image

LOTE = 'dados/lote.json'

def emenda(a, eixo):
    """Procura uma linha reta (emenda) que corta a foto inteira entre 20% e 80%. Devolve a posição ou None."""
    g = a.mean(axis=2)
    if eixo == 'v':   # emenda vertical: diferença entre colunas vizinhas
        d = np.abs(np.diff(g, axis=1))            # H x (W-1)
        forte = (d > 25).mean(axis=0)              # fração da altura com salto forte
        n = g.shape[1]
    else:
        d = np.abs(np.diff(g, axis=0))
        forte = (d > 25).mean(axis=1)
        n = g.shape[0]
    ini, fim = int(n * 0.2), int(n * 0.8)
    if fim <= ini: return None
    i = ini + int(np.argmax(forte[ini:fim]))
    viz = np.r_[forte[max(0, i - 15):max(0, i - 3)], forte[i + 4:i + 16]]
    if forte[i] > 0.45 and (viz.size == 0 or forte[i] > 3 * (viz.mean() + 0.02)):
        return i + 1
    # faixa lisa (branca/preta) separando as fotos
    lisa = (np.std(g, axis=0 if eixo == 'v' else 1) < 4)
    idx = np.where(lisa[ini:fim])[0]
    if idx.size >= 3: return ini + int(idx.mean())
    return None

def faixas(im):
    """Tira faixa lisa de qualquer cor (ex.: tarja azul em cima) que termina num degrau brusco. Devolve a foto aparada."""
    a = np.asarray(im).astype(float); H, W = a.shape[:2]; corte = {}
    for lado in ('t', 'b', 'l', 'r'):
        lin = {'t': lambda i: a[i], 'b': lambda i: a[H - 1 - i], 'l': lambda i: a[:, i], 'r': lambda i: a[:, W - 1 - i]}[lado]
        n_max = int((H if lado in 'tb' else W) * 0.3); ref = lin(0).mean(axis=0); n = 0
        while n < n_max:
            l = lin(n)
            if l.std(axis=0).max() > 12 or np.abs(l.mean(axis=0) - ref).sum() > 12: break
            n += 1
        if n >= max(4, (H if lado in 'tb' else W) * 0.02) and n < n_max:
            prox = lin(min(n + 2, n_max)).std(axis=0).max()
            if prox > 20: corte[lado] = n + 2
    if not corte: return im, None
    t, b, l, r = (corte.get(k, 0) for k in 'tblr')
    return im.crop((l, t, W - r, H - b)), corte

def rostos(im):
    try:
        import cv2
        import os
        arq = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'modelo', 'haarcascade_frontalface_default.xml')
        if not os.path.exists(arq): arq = cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
        c = cv2.CascadeClassifier(arq)
        g = cv2.cvtColor(np.array(im), cv2.COLOR_RGB2GRAY)
        m = max(30, min(im.size) // 12)
        f = c.detectMultiScale(g, 1.1, 6, minSize=(m, m))
        return [tuple(int(v) for v in r) for r in f]
    except Exception:
        return []

def ajustar(item, lado=None):
    cam = item.get('foto')
    if not cam: return
    im = Image.open(cam).convert('RGB')
    im, cf = faixas(im)
    if cf: im.save(cam, quality=92); print(f"{item['id']}: faixa lisa tirada {cf}")
    W, H = im.size; a = np.asarray(im).astype(float)
    partes = None
    x = emenda(a, 'v')
    if x and lado in (None, 'esq', 'dir'):
        partes = {'esq': (0, 0, x, H), 'dir': (x, 0, W, H)}
    else:
        y = emenda(a, 'h')
        if y and lado in (None, 'cima', 'baixo'):
            partes = {'cima': (0, 0, W, y), 'baixo': (0, y, W, H)}
    if partes:
        if lado not in partes:
            pont = {}
            for k, b in partes.items():
                p = im.crop(b); r = rostos(p)
                pont[k] = (sum(w * h for _, _, w, h in r), p.size[0] * p.size[1])
            lado = max(pont, key=lambda k: pont[k])
        b = partes[lado]
        # tira 4 px da emenda para não sobrar risco
        b = (b[0] + (4 if b[0] else 0), b[1] + (4 if b[1] else 0), b[2] - (4 if b[2] < W else 0), b[3] - (4 if b[3] < H else 0))
        im = im.crop(b); im.save(cam, quality=92)
        item['foco'] = 'center 30%'   # foco antigo era da montagem inteira
        print(f"{item['id']}: montagem detectada, fiquei com a parte '{lado}' {im.size}")
    r = rostos(im)
    if r:   # ignora 'rostos' pequenos (falso positivo): fica com os que têm pelo menos metade do maior
        mx = max(f[2] for f in r); r = [f for f in r if f[2] >= mx * 0.5]
        x0 = min(f[0] for f in r); x1 = max(f[0] + f[2] for f in r)
        y0 = min(f[1] for f in r); y1 = max(f[1] + f[3] for f in r)
        # object-position: X% alinha o ponto X% da foto com o X% da moldura (1080x1350).
        # Converte o centro do rosto em porcentagem que deixa o rosto no MEIO da moldura.
        w, h = im.size; ar = 1080 / 1350
        vis_w = min(w, h * ar); vis_h = min(h, w / ar)
        cx = (x0 + x1) / 2; cy = (y0 + y1) / 2 - (y1 - y0) * 0.3   # um pouco acima do rosto (título fica embaixo)
        px = 50 if w - vis_w < 1 else max(0, min(100, (cx - vis_w / 2) / (w - vis_w) * 100))
        py = 25 if h - vis_h < 1 else max(0, min(100, (cy - vis_h * 0.35) / (h - vis_h) * 100))
        item['foco'] = f"{px:.0f}% {py:.0f}%"
        print(f"{item['id']}: {len(r)} rosto(s), foco = {item['foco']}")
    else:
        # Israel, 07/11/2026 (moedas do Prospera MEI coladas na borda): sem rosto, acha o ASSUNTO PRINCIPAL
        # (onde há detalhe, contraste e cor diferente do fundo) e põe ele no centro da moldura.
        c = assunto(im)
        if c:
            w, h = im.size; ar = 1080 / 1350
            vis_w = min(w, h * ar); vis_h = min(h, w / ar)
            cx, cy = c
            esq = max(0, min(w - vis_w, cx - vis_w / 2)); pos = (cx - esq) / vis_w
            if abs(pos - 0.5) > 0.12:
                # assunto colado na borda da foto: object-position não resolve; recorta a foto em volta dele
                nw = 2 * min(cx, w - cx)
                if nw >= vis_w * 0.75 and nw >= 500:
                    x0 = int(cx - nw / 2); im = im.crop((x0, 0, int(x0 + nw), h)); im.save(cam, quality=92)
                    w = im.size[0]; cx = cx - x0; vis_w = min(w, h * ar); vis_h = min(h, w / ar)
                    print(f"{item['id']}: assunto na borda; foto recortada para centralizar ({im.size})")
                else:
                    item['assunto_borda'] = True
                    print(f"{item['id']}: ATENÇÃO ASSUNTO NA BORDA ({pos:.0%} da moldura): não dá para centralizar sem zoom alto. TROQUE A FOTO (ou só site).")
            px = 50 if w - vis_w < 1 else max(0, min(100, (cx - vis_w / 2) / (w - vis_w) * 100))
            py = 30 if h - vis_h < 1 else max(0, min(100, (cy - vis_h * 0.4) / (h - vis_h) * 100))
            item['foco'] = f"{px:.0f}% {py:.0f}%"
            print(f"{item['id']}: sem rosto; assunto principal em x={cx/w:.0%} y={cy/h:.0%} -> foco = {item['foco']}")
        else:
            print(f"{item['id']}: sem rosto detectado (foco mantido: {item.get('foco', 'padrão')})")

def assunto(im):
    """Centro do assunto principal: mapa de 'saliência' (bordas + cor diferente da média + um pouco de peso no centro).
    Devolve (cx, cy) em pixels da foto, ou None se a foto for uniforme."""
    w0, h0 = im.size; esc = 320 / max(w0, h0)
    p = im.resize((max(1, int(w0 * esc)), max(1, int(h0 * esc))))
    a = np.asarray(p).astype(float); g = a.mean(axis=2)
    gx = np.abs(np.diff(g, axis=1, append=g[:, -1:])); gy = np.abs(np.diff(g, axis=0, append=g[-1:, :]))
    borda = gx + gy
    cor = np.sqrt(((a - np.median(a.reshape(-1, 3), axis=0)) ** 2).sum(axis=2))
    def norm(m):
        m = m - m.min(); return m / (m.max() + 1e-6)
    # suaviza (média em blocos) para pegar regiões, não ruído/textura fina
    def borrar(m, k=9):
        from numpy.lib.stride_tricks import sliding_window_view
        pd = np.pad(m, k // 2, mode='edge'); return sliding_window_view(pd, (k, k)).mean(axis=(2, 3))
    s = borrar(norm(borrar(borda, 5)) * 0.6 + norm(cor) * 0.4)
    H, W = s.shape; yy, xx = np.mgrid[0:H, 0:W]
    s = s * (1 - 0.25 * (((xx / W - .5) ** 2 + (yy / H - .5) ** 2) / .5))   # leve preferência pelo centro
    lim = np.quantile(s, 0.85); m = np.where(s >= lim, s, 0)
    if m.sum() < 1e-6 or s.std() < 0.02: return None
    cx = (m * xx).sum() / m.sum(); cy = (m * yy).sum() / m.sum()
    return cx / esc, cy / esc

if __name__ == '__main__':
    args = sys.argv[1:]; lado = None
    if '--lado' in args:
        i = args.index('--lado'); lado = args[i + 1]; del args[i:i + 2]
    l = json.load(open(LOTE))
    for it in l:
        if it['id'] in args: ajustar(it, lado)
    json.dump(l, open(LOTE, 'w'), ensure_ascii=False, indent=1)
