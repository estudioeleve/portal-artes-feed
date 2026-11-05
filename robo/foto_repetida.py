"""Trava contra FOTO repetida no feed (Israel, 05/11/2026: a mesma foto da fachada da ALMG saiu em 2 posts seguidos,
07h03 e 07h13. "Não deixa acontecer novamente").
uso: python3 robo/foto_repetida.py <id> [<id> ...]   (itens do dados/lote.json)
Compara a foto do item com as fotos dos posts do Instagram dos últimos 3 dias (site/<data>/<id>.jpg).
Pega a mesma foto mesmo com outro corte ou outro tamanho. Sai com código 1 e imprime FOTO REPETIDA se achar.
O links.py usa a mesma função e não dá link para publicar."""
import json,os,sys,time
from PIL import Image,ImageOps
B=os.path.dirname(os.path.abspath(__file__));R=os.path.dirname(B)
DIAS=3
# cortes centrais: a mesma foto pode chegar aparada (bordas, sites diferentes)
CORTES=(1.0,0.9,0.8,0.7)
def hashes(fn):
  im=ImageOps.exif_transpose(Image.open(fn)).convert('L');w,h=im.size;S=[]
  for c in CORTES:
    cw,ch=int(w*c),int(h*c);x,y=(w-cw)//2,(h-ch)//2
    p=list(im.crop((x,y,x+cw,y+ch)).resize((17,16)).getdata())
    S.append([p[l*17+k]>p[l*17+k+1] for l in range(16) for k in range(16)])
  return S
def dist(A,B_):
  return min(sum(x!=y for x,y in zip(a,b)) for a in A for b in B_)
def posts(dias=DIAS):
  H=json.load(open(f'{R}/dados/historico.json'))
  lim=time.strftime('%Y-%m-%d',time.gmtime(time.time()-3*3600-dias*86400))
  return [h for h in H if h.get('data','')>=lim and str(h.get('media_id','')).isdigit()]
def foto_do_item(o):
  for fn in (o.get('foto') and f"{R}/{o['foto']}",):
    if fn and os.path.exists(fn): return fn
  return None
def achar_foto(o,limite=38):
  """Devolve o post já publicado com a mesma foto (ou None). Limite 38 de 256 bits, calibrado em 157 posts: fotos iguais deram 0 a 13; fotos diferentes, 76 ou mais."""
  fn=foto_do_item(o)
  if not fn: return None
  A=hashes(fn)
  for h in posts():
    if h.get('id')==o.get('id'): continue
    g=f"{R}/site/{h['data']}/{h['id']}.jpg"
    if not os.path.exists(g): continue
    if dist(A,hashes(g))<=limite: return h
  return None
if __name__=='__main__':
  L={o['id']:o for o in json.load(open(f'{R}/dados/lote.json'))};ruim=0
  for i in sys.argv[1:]:
    h=achar_foto(L[i])
    if h: ruim=1;print('FOTO REPETIDA',i,'-> mesma foto de',h['data'],h.get('hora',''),h.get('titulo','')[:80])
    else: print('ok',i)
  sys.exit(ruim)
