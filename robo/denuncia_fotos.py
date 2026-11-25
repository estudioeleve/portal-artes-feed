"""Denúncia com VÁRIAS FOTOS -> carrossel (Israel, 25/11/2026: "quando vier mais de uma imagem, fazer carrossel; não pode deixar de postar").

O site manda em "fotos_url" todas as fotos da denúncia (até 3; a 1ª já está na arte_feed).
Este script monta as fotos 2 e 3 como slides 1080x1350 (4:5, igual à arte), foto cheia
preenchendo o quadro (sem faixa, sem fundo desfocado) e a mesma aba do logo do portal
copiada da própria arte_feed (Portal one news ou Portal luziense), no mesmo lugar.

uso:
  python3 robo/denuncia_fotos.py <id> <arte_feed_url> <foto2_url> [<foto3_url>] [--foco 0.5,0.5 0.6,0.6]
      --foco: um par X,Y (0 a 1) por foto extra, onde está o assunto (padrão: centro).
Saída: artes/denuncias/<data>/denuncia_<id>_2.jpg, _3.jpg (uma linha por arquivo).
Depois: OLHE cada slide (Read). O assunto tem que aparecer inteiro e no centro.
"""
import sys, os, io, datetime, requests
from PIL import Image, ImageOps, ImageDraw, ImageFilter

B = os.path.dirname(os.path.abspath(__file__)); R = os.path.dirname(B)
H = {'User-Agent': 'Mozilla/5.0 (robo Portal ONE News)'}
W, HH = 1080, 1350
ABA = (76, 0, 136, 412)  # aba azul do logo na arte_feed (ONE e Luziense)


def abrir(u):
    r = requests.get(u, headers=H, timeout=120); r.raise_for_status()
    return ImageOps.exif_transpose(Image.open(io.BytesIO(r.content))).convert('RGB')


def cobrir(im, fx=0.5, fy=0.5):
    """Corta a foto em 4:5 em volta do ponto (fx,fy) e amplia para 1080x1350."""
    w, h = im.size; alvo = W / HH
    if w / h > alvo:
        nw, nh = int(h * alvo), h
    else:
        nw, nh = w, int(w / alvo)
    x = min(max(int(fx * w - nw / 2), 0), w - nw)
    y = min(max(int(fy * h - nh / 2), 0), h - nh)
    im = im.crop((x, y, x + nw, y + nh)).resize((W, HH), Image.LANCZOS)
    return im.filter(ImageFilter.UnsharpMask(radius=1.2, percent=60, threshold=3))


def main():
    a = sys.argv[1:]
    foco = []
    if '--foco' in a:
        k = a.index('--foco'); foco = [tuple(float(v) for v in p.split(',')) for p in a[k + 1:]]; a = a[:k]
    i, arte_url, fotos = a[0], a[1], a[2:]
    arte = abrir(arte_url).resize((W, HH))
    aba = arte.crop(ABA)
    mask = Image.new('L', aba.size, 0)
    ImageDraw.Draw(mask).rounded_rectangle((0, -20, aba.size[0] - 1, aba.size[1] - 1), radius=9, fill=255)
    hoje = datetime.datetime.now(datetime.timezone(datetime.timedelta(hours=-3))).strftime('%Y-%m-%d')
    os.makedirs(f'{R}/artes/denuncias/{hoje}', exist_ok=True)
    for n, u in enumerate(fotos[:2], start=2):
        fx, fy = foco[n - 2] if len(foco) >= n - 1 else (0.5, 0.5)
        im = cobrir(abrir(u), fx, fy)
        im.paste(aba, ABA[:2], mask)
        out = f'artes/denuncias/{hoje}/denuncia_{i}_{n}.jpg'
        im.save(f'{R}/{out}', 'JPEG', quality=92)
        print(out)


if __name__ == '__main__':
    if len(sys.argv) < 4: sys.exit(__doc__)
    main()
