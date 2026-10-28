"""Coleta as matérias escolhidas do radar.
uso: python3 robo/coletar.py 3 11 49   (números 'n' do dados/radar.json)
     python3 robo/coletar.py URL        (uma matéria avulsa)
Grava dados/lote.json com texto, foto baixada (fotos/) e crédito da foto."""
import json,sys,os,re,time,requests,trafilatura
from bs4 import BeautifulSoup
B=os.path.dirname(os.path.abspath(__file__));R=os.path.dirname(B)
UA={'User-Agent':'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/124 Safari/537.36'}
radar={o['n']:o for o in json.load(open(f'{R}/dados/radar.json'))} if os.path.exists(f'{R}/dados/radar.json') else {}
os.makedirs(f'{R}/fotos',exist_ok=True)
lote=json.load(open(f'{R}/dados/lote.json')) if os.path.exists(f'{R}/dados/lote.json') and '--anexar' in sys.argv else []
for a in [x for x in sys.argv[1:] if x!='--anexar']:
  o=dict(radar[int(a)]) if a.isdigit() else dict(link=a,titulo='',fonte=a.split('/')[2],bloco='?',concorrente=False,gnews=False)
  try:
    link=o['link']
    if 'news.google.com' in link:
      from googlenewsdecoder import gnewsdecoder
      link=gnewsdecoder(link,interval=1).get('decoded_url') or link
    o['link']=link
    rr=requests.get(link,headers=UA,timeout=25);h=rr.content.decode('utf-8','replace') if b'charset=utf-8' in rr.content[:3000].lower() or 'utf' in (rr.encoding or '').lower() or not rr.encoding else rr.text
    if 'Ã' in h[:20000]: h=rr.content.decode('utf-8','replace')
    s=BeautifulSoup(h,'html.parser')
    def meta(k): m=s.find('meta',property=k) or s.find('meta',attrs={'name':k}); return m.get('content','') if m else ''
    o['titulo_orig']=meta('og:title') or o.get('titulo','');o['img']=meta('og:image')
    o['texto']=(trafilatura.extract(h) or '')[:4000]
    cap=s.find('figcaption');o['credito_foto']=cap.get_text(' ',strip=True)[:300] if cap else ''
    o['id']=time.strftime('%H%M%S')+'_'+str(a)[-4:].replace('/','')
    o['foto']=''
    if o['img'] and not o.get('concorrente'):
      r=requests.get(o['img'],headers=UA,timeout=25)
      if r.ok and len(r.content)>15000:
        fn=f"fotos/{o['id']}.jpg";open(f'{R}/{fn}','wb').write(r.content);o['foto']=fn
    from ftfy import fix_text
    for k in ('texto','titulo_orig','credito_foto'): o[k]=fix_text(o[k])
    print(o['id'],'| fonte:',o['fonte'],'| conc' if o.get('concorrente') else '','| texto',len(o['texto']),'| foto',bool(o['foto']),'|',o['titulo_orig'][:80])
    print('   crédito:',o['credito_foto'][-120:])
    lote.append(o)
  except Exception as ex: print('ERRO',a,ex)
json.dump(lote,open(f'{R}/dados/lote.json','w'),ensure_ascii=False,indent=1)
