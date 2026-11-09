"""Publica no site as colunas que o Israel manda em zip (ChatGPT organiza: manifesto.json + materias/*.txt + imagens/*.png).
uso: python3 robo/colunas.py <arquivo.zip>
- cada matéria vai para dados/colunas.json (o tema 2.4.4+ busca esse arquivo de 15 em 15 minutos e publica no nome do colunista)
- a imagem vai para site/AAAA-MM-DD/col_<data>_<id>.jpg
- não repete: o id é a data da edição + o nome-base do arquivo"""
import json,sys,os,time,zipfile,tempfile,html,re,unicodedata
from PIL import Image
B=os.path.dirname(os.path.abspath(__file__));R=os.path.dirname(B)
CF=f'{R}/dados/colunas.json'
C=json.load(open(CF)) if os.path.exists(CF) else []
ja={c['id'] for c in C}
COL={c['nome']:c for c in json.load(open(f'{R}/colunistas/colunistas.json'))} if os.path.exists(f'{R}/colunistas/colunistas.json') else {}
def norm(s): return re.sub(r'[^a-z]','',unicodedata.normalize('NFKD',s or '').encode('ascii','ignore').decode().lower())
def slug_de(nome):
  n=norm(nome)
  for k,c in COL.items():
    if norm(k)==n: return c['slug']
  sob=norm(nome.split()[-1]) if nome.split() else ''
  for k,c in COL.items():
    if sob and norm(k.split()[-1])==sob: return c['slug']   # "J. Bertoldo" = "Jota Bertoldo"
  return re.sub(r'[^a-z0-9]+','-',unicodedata.normalize('NFKD',nome).encode('ascii','ignore').decode().lower()).strip('-')
def corpo(txt):
  """Só o texto da matéria: entre a linha 'Texto' e a linha 'Fonte:' (ou 'Resumo para redes sociais')."""
  L=txt.replace('\r','').split('\n')
  try: a=next(i for i,l in enumerate(L) if l.strip()=='Texto')+1
  except StopIteration: a=0
  b=len(L)
  for i in range(a,len(L)):
    if L[i].strip().startswith('Fonte:') or L[i].strip()=='Resumo para redes sociais': b=i;break
  pars=[p.strip() for p in '\n'.join(L[a:b]).split('\n\n') if p.strip()]
  return ''.join(f'<p>{html.escape(" ".join(p.split()))}</p>' for p in pars)
z=sys.argv[1]
T=tempfile.mkdtemp()
zipfile.ZipFile(z).extractall(T)
raiz=T
for d,_,fs in os.walk(T):
  if 'manifesto.json' in fs: raiz=d;break
M=json.load(open(f'{raiz}/manifesto.json'))
ed=M.get('edicao') or time.strftime('%Y-%m-%d',time.gmtime(time.time()-3*3600))
dia=time.strftime('%Y-%m-%d',time.gmtime(time.time()-3*3600))
for m in M['materias']:
  cid=f"col_{ed}_{m['id']}"
  if cid in ja: print(cid,'já está no colunas.json');continue
  txt=open(f"{raiz}/{m['arquivo']}",encoding='utf-8').read()
  texto=corpo(txt)
  if m.get('abertura'): texto=f"<h2>{html.escape(m['abertura'])}</h2>"+texto
  foto='';cred=''
  img=(m.get('imagem') or {}).get('arquivo')
  if img and os.path.exists(f'{raiz}/{img}'):
    os.makedirs(f'{R}/site/{dia}',exist_ok=True)
    im=Image.open(f'{raiz}/{img}').convert('RGB');im.thumbnail((1280,1280))
    fn=f'site/{dia}/{cid}.jpg';im.save(f'{R}/{fn}',quality=86)
    foto='https://raw.githubusercontent.com/estudioeleve/portal-artes-feed/main/'+fn
    cred=(m.get('imagem') or {}).get('credito','')
  C.append(dict(id=cid,data=time.strftime('%Y-%m-%dT%H:%M:%S-03:00',time.gmtime(time.time()-3*3600)),
    colunista=m['colunista'],colunista_slug=slug_de(m['colunista']),area=m.get('area',''),
    titulo=m['titulo'],linha_fina=m.get('subtitulo',''),chamada=m.get('chamada',''),texto=texto,
    foto=foto,credito_foto=cred,alt=(m.get('imagem') or {}).get('texto_alternativo',''),
    resumo=m.get('resumo',''),categoria='',fonte=f"Coluna {m['colunista']}"))
  print(cid,'ok |',m['colunista'],'|',len(re.sub('<[^>]+>','',texto).split()),'palavras |','foto' if foto else 'SEM FOTO')
C=C[-200:]
json.dump(C,open(CF,'w'),ensure_ascii=False,indent=1)
