"""Radar: lê as fontes e gera dados/radar.json com notícias das últimas horas que ainda não foram postadas.
uso: python3 robo/radar.py [horas=8]"""
import json,re,sys,time,calendar,urllib.parse,os,unicodedata
import feedparser,requests
B=os.path.dirname(os.path.abspath(__file__));R=os.path.dirname(B)
F=json.load(open(f'{B}/fontes.json'))
H=float(sys.argv[1]) if len(sys.argv)>1 else 8
UA={'User-Agent':'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/124 Safari/537.36'}
now=time.time();out=[]
def norm(s):
  s=unicodedata.normalize('NFD',s.lower());s=''.join(c for c in s if unicodedata.category(c)!='Mn')
  return re.sub(r'[^a-z0-9 ]','',s)
def conc(*t):
  x=' '.join(norm(a or '') for a in t)
  return any(norm(c) in x for c in F['concorrentes'])
def add(bloco,fonte,titulo,link,ts,gn=False):
  titulo=re.sub(r'\s+-\s+[^-]{2,40}$','',titulo.strip()) if gn else titulo.strip()
  if now-ts>H*3600: return
  out.append(dict(bloco=bloco,fonte=fonte,titulo=titulo,link=link,ts=ts,gnews=gn,concorrente=conc(fonte,link,titulo)))
for bloco,u in F['rss']:
  try:
    d=feedparser.parse(u,agent=UA['User-Agent'])
    for e in d.entries:
      t=e.get('published_parsed') or e.get('updated_parsed')
      if not t: continue
      add(bloco,u.split('/')[2],e.get('title',''),e.get('link'),calendar.timegm(t))
  except Exception as ex: print('erro',u,ex,file=sys.stderr)
for bloco,u in F['paginas_g1']:
  try:
    s=requests.get(u,headers=UA,timeout=25).text
    for t,l in re.findall(r'"title":"([^"]{20,180})","url":"(https://g1\.globo\.com/mg/[^"]+?/noticia/(\d{4}/\d{2}/\d{2})/[^"]+?\.ghtml)',s) and [(a,b) for a,b,c in re.findall(r'"title":"([^"]{20,180})","url":"(https://g1\.globo\.com/mg/[^"]+?/noticia/(\d{4}/\d{2}/\d{2})/[^"]+?\.ghtml)',s)] or []:
      dt=re.search(r'/noticia/(\d{4})/(\d{2})/(\d{2})/',l);ts=calendar.timegm((int(dt[1]),int(dt[2]),int(dt[3]),15,0,0))
      add(bloco,'g1.globo.com',t.encode().decode('unicode_escape') if '\\u' in t else t,l.split('?')[0],min(ts,now))
  except Exception as ex: print('erro',u,ex,file=sys.stderr)
for bloco,q in F['gnews']:
  u='https://news.google.com/rss/search?q='+urllib.parse.quote(q+' when:1d')+'&hl=pt-BR&gl=BR&ceid=BR:pt-419'
  try:
    d=feedparser.parse(u)
    for e in d.entries:
      t=e.get('published_parsed');src=(e.get('source') or {}).get('title','')
      if t: add(bloco,src,e.title,e.link,calendar.timegm(t),gn=True)
  except Exception as ex: print('erro',q,ex,file=sys.stderr)
# remove já postadas e duplicadas
hist=json.load(open(f'{R}/dados/historico.json')) if os.path.exists(f'{R}/dados/historico.json') else []
vistos=[set(norm(h.get('titulo_orig','')).split()) for h in hist]+[set(norm(h.get('titulo','')).split()) for h in hist]
def parecido(a,lst):
  a=set(w for w in a if len(w)>3)
  return any(len(a & set(w for w in b if len(w)>3))>=max(4,0.55*len(a)) for b in lst)
uniq=[];vist=[]
for o in sorted(out,key=lambda x:(x['concorrente'],-x['ts'])):
  w=norm(o['titulo']).split()
  if parecido(w,vistos) or parecido(w,vist): continue
  vist.append(w);uniq.append(o)
uniq.sort(key=lambda x:-x['ts'])
for o in uniq: o['hora']=time.strftime('%d/%m %H:%M',time.gmtime(o['ts']-3*3600))
os.makedirs(f'{R}/dados',exist_ok=True)
json.dump(uniq,open(f'{R}/dados/radar.json','w'),ensure_ascii=False,indent=1)
from collections import Counter
print(len(uniq),'notícias novas',dict(Counter(o['bloco'] for o in uniq)),'| concorrentes:',sum(o['concorrente'] for o in uniq))
