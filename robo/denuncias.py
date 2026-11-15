"""Denúncias do leitor ("Faça sua denúncia") aprovadas no site -> Instagram do @portalonenews.
Pedido do Israel, 15/11/2026. Ver a seção "DENÚNCIAS DO LEITOR" no REGRAS.md.

A chave do site NUNCA vai para o repositório (ele é público): o endereço com a chave fica só
no prompt da tarefa agendada e entra aqui por argumento.

uso:
  python3 robo/denuncias.py preparar '<ENDEREÇO-DA-LISTA-COM-CHAVE>'
      baixa a lista, prepara a mídia de cada denúncia (instagram_formato feed: arte_feed em JPEG, sem editar;
      reels: video_url 1080x1920 com recorte centralizado + arte_reels por cima do começo ao fim, áudio
      original, sem música) e grava dados/denuncias_fila.json (sem a chave).
  python3 robo/denuncias.py postada <id> '<ENDEREÇO-DA-LISTA-COM-CHAVE>' <media_id>
      avisa o site (POST vazio em /denuncias/<id>/postada?chave=...) e registra no histórico.
"""
import json,os,subprocess,sys,datetime,io
from urllib.parse import urlparse,parse_qs
import requests
B=os.path.dirname(os.path.abspath(__file__));R=os.path.dirname(B)
FILA=f'{R}/dados/denuncias_fila.json'
FEITAS=f'{R}/dados/denuncias_postadas.json'
H={'User-Agent':'Mozilla/5.0 (robo Portal ONE News)'}

def chave(url):
  q=parse_qs(urlparse(url).query)
  for k in ('chave','key','token'):
    if k in q: return q[k][0]
  sys.exit('ERRO: não achei a chave (parâmetro chave=) no endereço.')

def lista(url):
  r=requests.get(url,headers=H,timeout=60);r.raise_for_status();d=r.json()
  if isinstance(d,dict):
    for k in ('denuncias','itens','items','data','lista'):
      if isinstance(d.get(k),list): return d[k]
    return []
  return d

def baixar(u,dest):
  r=requests.get(u,headers=H,timeout=300,stream=True);r.raise_for_status()
  with open(dest,'wb') as f:
    for c in r.iter_content(1<<20): f.write(c)
  return dest

def load(p,d):
  try: return json.load(open(p))
  except Exception: return d

def preparar(url):
  from PIL import Image
  hoje=datetime.datetime.now(datetime.timezone(datetime.timedelta(hours=-3))).strftime('%Y-%m-%d')
  feitas={str(x) for x in load(FEITAS,[])}
  fila=[];itens=lista(url)
  tmp=f'{R}/fotos/denuncias';os.makedirs(tmp,exist_ok=True)
  for d in itens:
    i=str(d.get('id'))
    if i in feitas: print(i,'já postada (denuncias_postadas.json): pulando');continue
    # instagram_formato: 'feed' (só foto) ou 'reels' (tem vídeo). midia_tipo fica como reserva.
    tipo=(d.get('instagram_formato') or d.get('midia_tipo') or '').lower()
    tipo={'feed':'foto','reels':'video'}.get(tipo,tipo)
    it={k:d.get(k) for k in ('id','protocolo','instagram_formato','midia_tipo','relato','anonima','nome','cidade','bairro','moldura','titulo','data')}
    it['extra']={k:v for k,v in d.items() if k not in it and k not in ('midia_url','video_url','arte_feed','arte_reels')}
    try:
      if tipo=='foto':
        os.makedirs(f'{R}/artes/denuncias/{hoje}',exist_ok=True)
        src=baixar(d['arte_feed'],f'{tmp}/{i}_feed')
        im=Image.open(src)
        out=f'artes/denuncias/{hoje}/denuncia_{i}.jpg'
        # Só converte para JPEG (o Instagram só aceita JPEG); não recorta nem edita a arte.
        if im.mode in ('RGBA','LA','P'):
          im=im.convert('RGBA');fundo=Image.new('RGB',im.size,'white');fundo.paste(im,mask=im.split()[-1]);im=fundo
        im.convert('RGB').save(f'{R}/{out}','JPEG',quality=92)
        w,h=im.size;rz=w/h
        if not (0.8-0.005<=rz<=1.91): print(i,'ATENÇÃO: proporção',w,'x',h,'fora de 4:5 a 1.91:1, o Instagram recusa')
        it['arquivo']=out
      elif tipo in ('video','vídeo'):
        os.makedirs(f'{R}/videos/{hoje}',exist_ok=True)
        v=baixar(d.get('video_url') or d['midia_url'],f'{tmp}/{i}_video')
        a=baixar(d['arte_reels'],f'{tmp}/{i}_arte.png')
        out=f'videos/{hoje}/denuncia_{i}.mp4'
        # Recorte centralizado para 1080x1920 (cobre a tela inteira, sem faixa preta) e arte por cima do começo ao fim.
        # Áudio: só o som original do vídeo (sem música, sem efeito). O ffmpeg já aplica a rotação do celular.
        vf=("[0:v]scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920,setsar=1,fps=30[b];"
            "[1:v]scale=1080:1920,format=rgba[o];[b][o]overlay=0:0:format=auto,format=yuv420p[v]")
        cmd=['ffmpeg','-y','-loglevel','error','-i',v,'-loop','1','-i',a,'-filter_complex',vf,
             '-map','[v]','-map','0:a?','-shortest','-c:v','libx264','-preset','medium','-crf','20','-r','30',
             '-c:a','aac','-b:a','128k','-ar','44100','-movflags','+faststart',f'{R}/{out}']
        subprocess.run(cmd,check=True)
        it['arquivo']=out
        # quadro do meio para conferir (Read) antes de postar
        dur=float(subprocess.check_output(['ffprobe','-v','error','-show_entries','format=duration','-of','csv=p=0',f'{R}/{out}'],text=True).strip() or 0)
        it['duracao']=round(dur,1)
        subprocess.run(['ffmpeg','-y','-loglevel','error','-ss',str(dur/2),'-i',f'{R}/{out}','-frames:v','1',f'{tmp}/{i}_quadro.jpg'],check=True)
        it['quadro']=f'fotos/denuncias/{i}_quadro.jpg'
        if dur<3: print(i,'ATENÇÃO: vídeo com menos de 3 s, o Instagram recusa')
      else:
        print(i,'instagram_formato desconhecido:',tipo,'-> pulando');continue
    except Exception as e:
      print(i,'ERRO ao preparar:',e);continue
    it['acao']='create_image_post' if tipo=='foto' else 'create_video_post'
    fila.append(it);print(i,tipo,'->',it['arquivo'],'|',it['acao'])
  json.dump(fila,open(FILA,'w'),ensure_ascii=False,indent=1)
  print(len(fila),'denúncia(s) na fila. Escreva a legenda de cada uma (regras no REGRAS.md), faça commit + push e poste uma por vez, espaçada dos outros posts.')

def postada(i,url,media):
  k=chave(url);host=urlparse(url)
  base=f'{host.scheme}://{host.netloc}/wp-json/portalone/v1/denuncias/{i}/postada'
  r=requests.post(base,params={'chave':k},headers=H,timeout=60)
  print('site respondeu',r.status_code,r.text[:200])
  feitas=load(FEITAS,[])
  if str(i) not in [str(x) for x in feitas]: feitas.append(str(i))
  json.dump(feitas,open(FEITAS,'w'),ensure_ascii=False,indent=1)
  # registra no historico.json como post do Instagram (conta nos 100 do dia)
  fila=load(FILA,[]);it=next((x for x in fila if str(x.get('id'))==str(i)),{})
  h=load(f'{R}/dados/historico.json',[])
  agora=datetime.datetime.now(datetime.timezone(datetime.timedelta(hours=-3)))
  h.append({'id':f'denuncia_{i}','data':agora.strftime('%Y-%m-%d'),'hora':agora.strftime('%H:%M'),'media_id':media,
            'titulo':'Denúncia do leitor '+str(it.get('protocolo') or i),'titulo_orig':'','bloco':'rmbh','portal':str(it.get('moldura') or ''),'ed':'DENÚNCIA','fonte':'Faça sua denúncia','link':'denuncia:'+str(i),'arte':it.get('arquivo') or ''})
  json.dump(h,open(f'{R}/dados/historico.json','w'),ensure_ascii=False,indent=1)
  if not r.ok: sys.exit('ATENÇÃO: o site não confirmou. Anote em dados/erros.txt; a denúncia está em denuncias_postadas.json e não sai de novo.')

if __name__=='__main__':
  a=sys.argv[1:]
  if a and a[0]=='preparar' and len(a)==2: preparar(a[1])
  elif a and a[0]=='postada' and len(a)==4: postada(a[1],a[2],a[3])
  else: print(__doc__)
