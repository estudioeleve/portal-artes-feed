"""Links da arte para publicar no Instagram, já testados.
uso: python3 robo/links.py <id>
Imprime dois links (um por linha), na ordem em que devem ser tentados:
  1) jsDelivr fixado no commit atual (CDN rápida; o commit fixo evita arte velha em cache)
  2) raw.githubusercontent (reserva)
Antes de imprimir, baixa cada link e confere que responde 200, image/jpeg e o tamanho do arquivo local.
Rode DEPOIS do git push (o commit precisa estar no GitHub)."""
import json,os,subprocess,sys,time,requests
B=os.path.dirname(os.path.abspath(__file__));R=os.path.dirname(B)
i=sys.argv[1]
n=next(o for o in json.load(open(f'{R}/dados/lote.json')) if o['id']==i)
arte=n['arte'];tam=os.path.getsize(f'{R}/{arte}')
sha=subprocess.check_output(['git','-C',R,'rev-parse','HEAD'],text=True).strip()
links=[f'https://cdn.jsdelivr.net/gh/estudioeleve/portal-artes-feed@{sha}/{arte}',
       f'https://raw.githubusercontent.com/estudioeleve/portal-artes-feed/{sha}/{arte}']
for u in links:
  ok=False
  for t in range(4):
    try:
      r=requests.get(u,timeout=30)
      ok=r.status_code==200 and r.headers.get('content-type','').startswith('image/jpeg') and len(r.content)==tam
    except Exception: ok=False
    if ok: break
    time.sleep(10)
  print(u if ok else 'FALHOU '+u)
