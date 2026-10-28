"""Prepara a notícia para o site (WordPress), que busca dados/site.json de 15 em 15 minutos.
uso: python3 robo/site.py <id> [<id> ...]   (itens do dados/lote.json já com titulo, linha_fina, categoria, texto_site)
- copia a foto (sem moldura) para site/AAAA-MM-DD/<id>.jpg
- acrescenta a notícia em dados/site.json (guarda as últimas 400)"""
import json,sys,os,time
from PIL import Image
B=os.path.dirname(os.path.abspath(__file__));R=os.path.dirname(B)
L={o['id']:o for o in json.load(open(f'{R}/dados/lote.json'))}
SF=f'{R}/dados/site.json'
S=json.load(open(SF)) if os.path.exists(SF) else []
ja={s['id'] for s in S}
dia=time.strftime('%Y-%m-%d',time.gmtime(time.time()-3*3600))
CATS={'santa-luzia','grande-bh','belo-horizonte','regiao-metropolitana','contagem','betim','vespasiano','lagoa-santa','ribeirao-das-neves','sabara','minas-gerais','politica','brasil','mundo','esporte','atletico','cruzeiro','america','entretenimento','saude','educacao','luto'}
for i in sys.argv[1:]:
  o=L[i]
  falta=[k for k in ('titulo','linha_fina','categoria','texto_site') if not o.get(k)]
  if falta: print(i,'FALTA',falta);continue
  if o['categoria'] not in CATS: print(i,'categoria inválida:',o['categoria'],'| use uma de',sorted(CATS));continue
  if i in ja: print(i,'já está no site.json');continue
  foto=''
  if o.get('foto') and os.path.exists(f"{R}/{o['foto']}"):
    os.makedirs(f'{R}/site/{dia}',exist_ok=True)
    im=Image.open(f"{R}/{o['foto']}").convert('RGB');im.thumbnail((1280,1280))
    fn=f'site/{dia}/{i}.jpg';im.save(f'{R}/{fn}',quality=84);foto='https://raw.githubusercontent.com/estudioeleve/portal-artes-feed/main/'+fn
  cred=o.get('credito_site') or ''
  S.append(dict(id=i,data=time.strftime('%Y-%m-%dT%H:%M:%S-03:00',time.gmtime(time.time()-3*3600)),titulo=o['titulo'],linha_fina=o['linha_fina'],
    categoria=o['categoria'],texto=o['texto_site'],foto=foto,credito_foto=cred,fonte=o.get('fonte_site',''),resumos=o.get('resumos','')))
  print(i,'ok | foto' if foto else 'ok | SEM FOTO (o site usa só o texto)')
S=S[-400:]
json.dump(S,open(SF,'w'),ensure_ascii=False,indent=1)
