"""Corta uma foto/print na horizontal 16:9 (1280x720) para o site, mantendo o assunto principal.
uso: python3 robo/capa16x9.py entrada.jpg saida.jpg [--foco X,Y]
  X,Y = ponto do assunto principal na foto, de 0 a 1 (0,0 = canto de cima à esquerda).
  Sem --foco: centro na horizontal; na vertical, 22% do topo em foto em pé (cabeça da pessoa), meio em foto deitada.
Sempre olhe a saída (Read) antes de usar: a cabeça e o assunto não podem ficar cortados."""
import sys
from PIL import Image

def cortar(im, fx=None, fy=None, W=1280, H=720):
    im = im.convert('RGB'); w, h = im.size
    if fx is None: fx = 0.5
    if fy is None: fy = 0.22 if h > w else 0.5
    if w / h > W / H: cw, ch = round(h * W / H), h
    else: cw, ch = w, round(w * H / W)
    x = min(max(round(fx * w - cw / 2), 0), w - cw)
    y = min(max(round(fy * h - ch / 2), 0), h - ch)
    return im.crop((x, y, x + cw, y + ch)).resize((W, H), Image.LANCZOS)

if __name__ == '__main__':
    a = sys.argv[1:]; fx = fy = None
    if '--foco' in a:
        i = a.index('--foco'); fx, fy = (float(v) for v in a[i + 1].split(',')); del a[i:i + 2]
    cortar(Image.open(a[0]), fx, fy).save(a[1], quality=88)
    print('ok', a[1])
