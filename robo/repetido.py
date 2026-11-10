"""Trava contra post repetido (pedido do Israel, 01/11/2026: "Post repetido. Não deixa isso acontecer mais").
uso: python3 robo/repetido.py <id> [<id> ...]   (itens do dados/lote.json)
     python3 robo/repetido.py --link <URL>
Compara com tudo o que já saiu nos últimos 10 dias (dados/historico.json, Instagram e site):
  - mesmo link da matéria (sem ?query, sem / no fim);
  - título nosso ou título original parecido (palavras importantes em comum).
Sai com código 1 e imprime REPETIDO se achar. O links.py e o coletar.py usam a mesma função."""
import json,os,re,sys,time,unicodedata
B=os.path.dirname(os.path.abspath(__file__));R=os.path.dirname(B)
PARE=set('a o as os de da do das dos e em no na nos nas um uma por para pela pelo com sem que se ao aos sua seu apos após é na grande bh mg rádio itatiaia g1 tempo estado minas veja diz'.split())
def norm_link(u):
  u=(u or '').strip().lower().split('?')[0].split('#')[0].rstrip('/')
  return re.sub(r'^https?://(www\.)?','',u)
def palavras(t):
  t=unicodedata.normalize('NFKD',(t or '').lower()).encode('ascii','ignore').decode()
  t=t.split('|')[0]
  return {w for w in re.findall(r'[a-z0-9]+',t) if len(w)>2 and w not in PARE}
def parecido(a,b):
  A,Bb=palavras(a),palavras(b)
  if len(A)<3 or len(Bb)<3: return False
  inter=len(A&Bb);return inter>=5 and inter/min(len(A),len(Bb))>=0.6
def historico(dias=10):
  H=json.load(open(f'{R}/dados/historico.json')) if os.path.exists(f'{R}/dados/historico.json') else []
  lim=time.strftime('%Y-%m-%d',time.gmtime(time.time()-3*3600-dias*86400))
  return [h for h in H if h.get('data','')>=lim and h.get('media_id')!='NAO_PUBLICADO_leva_teste']
# Assuntos proibidos (Israel, 01/11/2026 22h): nada sobre o "Plantão Fake" / plantões fantasmas de Santa Luzia,
# nem no Instagram nem no site.
def proibido(o):
  t=' '.join(str(o.get(k) or '') for k in ('titulo','titulo_orig','link','linha_fina','texto','texto_site','legenda'))
  t=unicodedata.normalize('NFKD',t.lower()).encode('ascii','ignore').decode().replace('-',' ')
  if re.search(r'plantao fake|plantoes? fantasmas?|plantoes? ?.?fantasmas?',t): return True
  if 'santa luzia' in t and 'plant' in t and re.search(r'fraude|fantasma|fake|madalena parrillo|sao benedito',t): return True
  return False
def achar(o,H=None):
  """Devolve o registro já publicado igual/parecido ao item o (ou None)."""
  if proibido(o): return dict(data='PROIBIDO',hora='',media_id='PROIBIDO',titulo='Assunto proibido pelo Israel: Plantão Fake / plantões fantasmas de Santa Luzia')
  H=historico() if H is None else H
  lk=norm_link(o.get('link'))
  for h in H:
    if h.get('id')==o.get('id'): continue
    if lk and norm_link(h.get('link'))==lk: return h
    # pedido_israel: matéria que o Israel mandou na conversa (fato novo de tema já publicado): só o link igual barra
    if o.get('pedido_israel'): continue
    for x in (o.get('titulo'),o.get('titulo_orig')):
      for y in (h.get('titulo'),h.get('titulo_orig')):
        if x and y and parecido(x,y): return h
  return None
if __name__=='__main__':
  args=sys.argv[1:];ruim=0
  if args[:1]==['--link']: itens=[dict(id='?',link=args[1])]
  else:
    L={o['id']:o for o in json.load(open(f'{R}/dados/lote.json'))};itens=[L[i] for i in args]
  for o in itens:
    h=achar(o)
    if h: ruim=1;print('REPETIDO',o['id'],'-> já saiu em',h['data'],h.get('hora',''),'(',h.get('media_id'),')',h.get('titulo','')[:90])
    else: print('ok',o['id'])
  sys.exit(ruim)
