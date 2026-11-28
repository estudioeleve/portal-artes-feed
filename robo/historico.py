"""Registra um item do lote como publicado.
uso: python3 robo/historico.py <id> <media_id>
     python3 robo/historico.py --resumo   (conta os posts de hoje por bloco)
     python3 robo/historico.py --cota     (limite real: posts nas últimas 24h e quantos pode postar agora)"""
import json,sys,os,time
B=os.path.dirname(os.path.abspath(__file__));R=os.path.dirname(B);HF=f'{R}/dados/historico.json'
H=json.load(open(HF)) if os.path.exists(HF) else []
hoje=time.strftime('%Y-%m-%d',time.gmtime(time.time()-3*3600))
if sys.argv[1]=='--cota':
  # Israel, 27/11/2026: o limite do Instagram é 100 posts em 24 HORAS CORRIDAS (não "por dia"). Conta as últimas 24h e a última hora.
  import calendar
  agora=time.time()
  def ts(h):
    try: return calendar.timegm(time.strptime(h['data']+' '+h['hora'],'%Y-%m-%d %H:%M'))+3*3600
    except Exception: return 0
  ig=[h for h in H if h['media_id'] not in ('NAO_PUBLICADO_leva_teste','SITE')]
  d24=sum(1 for h in ig if agora-ts(h)<24*3600); d1=sum(1 for h in ig if agora-ts(h)<3600)
  livre=max(0,100-d24); noticias=max(0,min(4-d1,livre-10)); reserva=min(10,livre)
  print(f'Ultimas 24h: {d24} posts no Instagram | ultima hora: {d1} | vagas livres: {livre}')
  print(f'PODE POSTAR NESTA RODADA: {noticias} noticia(s) (teto 4 por hora) | reserva para denuncias e pedidos do Israel: {reserva}')
  sys.exit()
if sys.argv[1]=='--resumo':
  d=[h for h in H if h['data']==hoje and h['media_id'] not in ('NAO_PUBLICADO_leva_teste','SITE')];from collections import Counter
  so=[h for h in H if h['data']==hoje and h['media_id']=='SITE']
  c=Counter(h['bloco'] for h in d);print('Hoje',hoje,':',len(d),'posts no Instagram',dict(c),'| RMBH',f"{(c['rmbh']/len(d)*100 if d else 0):.0f}%",'| só site:',len(so),dict(Counter(h['bloco'] for h in so)));sys.exit()
L=json.load(open(f'{R}/dados/lote.json'));o=[x for x in L if x['id']==sys.argv[1]][0]
H.append(dict(data=hoje,hora=time.strftime('%H:%M',time.gmtime(time.time()-3*3600)),id=o['id'],bloco=o['bloco'],portal=o['portal'],ed=o['ed'],titulo=o['titulo'],titulo_orig=o.get('titulo_orig',''),link=o['link'],fonte=o.get('fonte',''),arte=o.get('arte',''),media_id=sys.argv[2]))
json.dump(H,open(HF,'w'),ensure_ascii=False,indent=1);print('registrado',o['id'],'| Instagram hoje:',sum(1 for h in H if h['data']==hoje and h['media_id'] not in ('NAO_PUBLICADO_leva_teste','SITE')),'| só site hoje:',sum(1 for h in H if h['data']==hoje and h['media_id']=='SITE'))
