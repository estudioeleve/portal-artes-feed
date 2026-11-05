"""Links da arte para publicar no Instagram, já testados.
uso: python3 robo/links.py <id>
Imprime dois links (um por linha), na ordem em que devem ser tentados:
  1) jsDelivr fixado no commit atual (CDN rápida; o commit fixo evita arte velha em cache)
  2) raw.githubusercontent (reserva)
Antes de imprimir, baixa cada link e confere que responde 200, image/jpeg e o tamanho do arquivo local.
Se a notícia já saiu (mesmo link ou título parecido no historico.json), imprime REPETIDO e não dá link.
Rode DEPOIS do git push (o commit precisa estar no GitHub)."""
import json,os,subprocess,sys,time,requests
B=os.path.dirname(os.path.abspath(__file__));R=os.path.dirname(B)
i=sys.argv[1]
n=next(o for o in json.load(open(f'{R}/dados/lote.json')) if o['id']==i)
# Traz o histórico mais novo do GitHub: outra rodada pode estar rodando ao mesmo tempo (Betim saiu 2 vezes em 02/11).
subprocess.run(['git','-C',R,'pull','-q','--rebase','--autostash','origin','main'],check=True)
sys.path.insert(0,B);from repetido import achar
h=achar(n)
if h:
  print('REPETIDO: NÃO PUBLIQUE.',i,'já saiu em',h['data'],h.get('hora',''),'(',h.get('media_id'),')',h.get('titulo','')[:90]);sys.exit(1)
from foto_repetida import achar_foto
f=achar_foto(n)
if f:
  print('FOTO REPETIDA: NÃO PUBLIQUE, troque a foto.',i,'usa a mesma foto de',f['data'],f.get('hora',''),f.get('titulo','')[:90]);sys.exit(1)
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
