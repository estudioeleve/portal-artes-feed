# corta trechos de vários vídeos, tira pausas longas, ajusta inicio pela energia
import subprocess,json,os,sys,wave,numpy as np
cfg=json.load(open(sys.argv[1]))
SRC=cfg['src']; OUT=cfg['out']; PC=OUT+'_pc'; os.makedirs(PC,exist_ok=True)
words={k:json.load(open(v)) for k,v in cfg['words'].items()}
aud={}
for k,f in SRC.items():
    subprocess.run(['ffmpeg','-v','error','-y','-i',f,'-vn','-ac','1','-ar','16000',f'{PC}/{k}.wav'],check=True)
    w=wave.open(f'{PC}/{k}.wav');aud[k]=np.frombuffer(w.readframes(w.getnframes()),dtype=np.int16).astype(float)
def db(k,t):
    a=aud[k];i=int(t*16000);x=a[max(0,i):i+320]
    return 20*np.log10(np.sqrt((x**2).mean())+1) if len(x) else 0
def ini(k,t): return max(0,t-0.22)
def fim(k,t): return t+0.3
pieces=[]
for item in cfg['seq']:
    k=item['k']
    if item.get('mudo'):
        pieces.append(dict(k=k,a=item['a'],b=item['b'],mudo=True,g=0));continue
    a,b=item['a'],item['b']
    W=[w for w in words[k] if a-0.3<=w[0]<b]
    # separa onde ha pausa > 0.8
    grupos=[[W[0]]]
    for w in W[1:]:
        if w[0]-grupos[-1][-1][1]>0.8: grupos.append([w])
        else: grupos[-1].append(w)
    for gi,g in enumerate(grupos):
        s=ini(k,max(a,g[0][0])) if gi==0 else ini(k,g[0][0])
        e=fim(k,min(b,g[-1][1])) if gi==len(grupos)-1 else fim(k,g[-1][1])
        if gi==0: s=min(s,a) if item.get('exato') else s
        if pieces and pieces[-1]['k']==k and not pieces[-1].get('mudo') and s<pieces[-1]['b']: s=pieces[-1]['b']
        pieces.append(dict(k=k,a=round(s,2),b=round(e,2),g=item.get('g',0)))
t=0;lst=[];mapa=[]
for n,p in enumerate(pieces):
    d=p['b']-p['a'];o=f'{PC}/p{n:03d}.mkv'
    if p.get('mudo'):
        inp=['-f','lavfi','-i',f'anullsrc=r=48000:cl=mono'];amap=['-map','1:a'];af=[]
    else:
        inp=[];amap=['-map','0:a'];af=['-af',f"pan=mono|c0=c0,aresample=48000,volume={p['g']}dB,afade=t=in:d=0.03,afade=t=out:st={max(0,d-0.05):.3f}:d=0.05"]
    subprocess.run(['ffmpeg','-v','error','-y','-ss',str(p['a']),'-t',f'{d:.3f}','-i',SRC[p['k']],*inp,'-map','0:v',*amap,'-vf','fps=30,format=yuv420p,scale=1080:1920','-c:v','libx264','-preset','veryfast','-crf','17',*af,'-ac','1','-ar','48000','-c:a','pcm_s16le','-t',f'{d:.3f}',o],check=True)
    lst.append(f"file '{o}'");mapa.append([p['k'],round(t,3),p['a'],p['b']]);t+=d
open(f'{PC}/l.txt','w').write('\n'.join(lst))
subprocess.run(['ffmpeg','-v','error','-y','-f','concat','-safe','0','-i',f'{PC}/l.txt','-map','0:v','-c:v','copy',OUT+'.mp4','-map','0:a','-af','alimiter=limit=0.9',OUT+'_voz.wav'],check=True)
json.dump({'mapa':mapa,'dur':t},open(OUT+'_mapa.json','w'))
print(round(t,2),len(pieces))
