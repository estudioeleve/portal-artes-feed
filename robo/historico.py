"""Registra um item do lote como publicado.
uso: python3 robo/historico.py <id> <media_id>
     python3 robo/historico.py --resumo   (conta os posts de hoje por bloco)"""
import json,sys,os,time
B=os.path.dirname(os.path.abspath(__file__));R=os.path.dirname(B);HF=f'{R}/dados/historico.json'
H=json.load(open(HF)) if os.path.exists(HF) else []
hoje=time.strftime('%Y-%m-%d',time.gmtime(time.time()-3*3600))
if sys.argv[1]=='--resumo':
  d=[h for h in H if h['data']==hoje and h['media_id']!='NAO_PUBLICADO_leva_teste'];from collections import Counter
  c=Counter(h['bloco'] for h in d);print('Hoje',hoje,':',len(d),'posts',dict(c),'| RMBH',f"{(c['rmbh']/len(d)*100 if d else 0):.0f}%");sys.exit()
L=json.load(open(f'{R}/dados/lote.json'));o=[x for x in L if x['id']==sys.argv[1]][0]
H.append(dict(data=hoje,hora=time.strftime('%H:%M',time.gmtime(time.time()-3*3600)),id=o['id'],bloco=o['bloco'],portal=o['portal'],ed=o['ed'],titulo=o['titulo'],titulo_orig=o.get('titulo_orig',''),link=o['link'],fonte=o.get('fonte',''),arte=o.get('arte',''),media_id=sys.argv[2]))
json.dump(H,open(HF,'w'),ensure_ascii=False,indent=1);print('registrado',o['id'],'| total hoje:',sum(1 for h in H if h['data']==hoje and h['media_id']!='NAO_PUBLICADO_leva_teste'))
