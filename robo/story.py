"""Gera a arte de story (1080x1920) a partir da arte do feed.
uso: python3 robo/story.py <id> [<id> ...]
Lê o item em dados/historico.json (ou dados/lote.json) para saber o portal e a arte.
Fundo: one e luziense = azul-escuro; politica = preto; comercio = laranja (regra do Israel, 13/11/2026).
Grava stories/AAAA-MM-DD/<id>.jpg. Só a imagem: sem botão "MATÉRIA COMPLETA" e sem @
(Israel, 13/11/2026: a API não aceita figurinha de link, então não desenhar o botão)."""
import json,os,sys
from PIL import Image,ImageDraw,ImageFont,ImageFilter
B=os.path.dirname(os.path.abspath(__file__));R=os.path.dirname(B)
FUNDO={'one':((6,21,62),(2,8,26)),'luziense':((6,21,62),(2,8,26)),
       'politica':((26,26,26),(0,0,0)),'comercio':((255,84,0),(224,29,13))}
FB='/usr/share/fonts/truetype/google-fonts/Poppins-Bold.ttf'
def itens():
  d={}
  for f in ('historico.json','lote.json'):
    p=f'{R}/dados/{f}'
    if os.path.exists(p):
      for o in json.load(open(p)): d.setdefault(o['id'],{}).update({k:v for k,v in o.items() if v})
  return d
def gerar(i,o):
  arte=o.get('arte')
  if not arte or not os.path.exists(f'{R}/{arte}'): print(i,'SEM ARTE: não faço story');return
  c1,c2=FUNDO.get(o.get('portal','one'),FUNDO['one'])
  W,H=1080,1920;bg=Image.new('RGB',(W,H))
  dr=ImageDraw.Draw(bg)
  for y in range(H):
    t=y/H;dr.line([(0,y),(W,y)],fill=tuple(int(c1[k]+(c2[k]-c1[k])*t) for k in range(3)))
  a=Image.open(f'{R}/{arte}').convert('RGB');w=960;a=a.resize((w,int(a.height*w/a.width)))
  x=(W-w)//2;y=(H-a.height)//2
  m=Image.new('L',a.size,0);ImageDraw.Draw(m).rounded_rectangle([0,0,a.width,a.height],36,fill=255)
  sh=Image.new('L',(W,H),0);ImageDraw.Draw(sh).rounded_rectangle([x,y+12,x+w,y+12+a.height],36,fill=140)
  bg.paste((0,0,0),(0,0),sh.filter(ImageFilter.GaussianBlur(24)))
  bg.paste(a,(x,y),m)
  dia=os.path.basename(os.path.dirname(arte));os.makedirs(f'{R}/stories/{dia}',exist_ok=True)
  fn=f'stories/{dia}/{i}.jpg';bg.save(f'{R}/{fn}',quality=90);print(i,'story:',fn)
D=itens()
for i in sys.argv[1:]: gerar(i,D.get(i,{}))
