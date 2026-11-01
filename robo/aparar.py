"""Tira bordas/faixas lisas da foto (preta, branca, cinza ou borrada) para a arte preencher a moldura inteira.
uso: python3 robo/aparar.py fotos/<id>.jpg [...]   (o coletar.py já chama sozinho em toda foto baixada)
Também usado pelo Israel em 01/11/2026: "nunca deixar borda nas imagens do Instagram"."""
import sys
from PIL import Image, ImageStat, ImageFilter

def _lisa(im, box, ref):
    r = im.crop(box); st = ImageStat.Stat(r)
    desvio = max(st.stddev)
    media = st.mean
    neutra = max(media) - min(media) < 20   # só faixa preta, branca ou cinza (fundo colorido da foto não é borda)
    perto = ref is None or sum(abs(a - b) for a, b in zip(media, ref)) < 12
    return desvio < 6 and neutra and perto, media

def aparar(caminho):
    im = Image.open(caminho).convert('RGB')
    W, H = im.size
    g = im.filter(ImageFilter.BoxBlur(1))
    corte = {}
    for lado in ('t', 'b', 'l', 'r'):
        n = 0; ref = None
        lim = (H if lado in 'tb' else W) * 0.30
        while n < lim:
            if lado == 't': box = (0, n, W, n + 1)
            elif lado == 'b': box = (0, H - n - 1, W, H - n)
            elif lado == 'l': box = (n, 0, n + 1, H)
            else: box = (W - n - 1, 0, W - n, H)
            ok, m = _lisa(g, box, ref)
            if not ok: break
            ref = ref or m; n += 1
        corte[lado] = n + (2 if n else 0)
    t, b, l, r = corte['t'], corte['b'], corte['l'], corte['r']
    if not any((t, b, l, r)): return False
    nova = im.crop((l, t, W - r, H - b))
    if nova.size[0] < W * 0.4 or nova.size[1] < H * 0.4: return False
    nova.save(caminho, quality=92)
    print('   bordas tiradas:', caminho, dict(cima=t, baixo=b, esq=l, dir=r))
    return True

if __name__ == '__main__':
    for c in sys.argv[1:]:
        if not aparar(c): print('   sem borda:', c)
