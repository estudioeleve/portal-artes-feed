import subprocess,json,os
INTRO=[(10.3,11.9),(5.95,7.55),(108.0,109.6)]
BODY=[('F0',52.6,66.3),('M',0.0,8.25),('M',8.95,52.25),('F',67.6,88.3),('F',89.1,96.7),('F',97.35,102.4),('F',103.05,108.4),('F',108.85,115.15),('F',116.15,119.9)]
os.makedirs('/tmp/fab/pc',exist_ok=True)
L=[('I',a,b) for a,b in INTRO]+BODY;t=0;mapa=[];lst=[]
for k,(w,a,b) in enumerate(L):
    d=b-a;o=f'/tmp/fab/pc/p{k:02d}.mkv'
    vf='fps=30,format=yuv420p'; ss=a
    if w=='F0':
        vf='trim=start=0.4,setpts=PTS-STARTPTS,tpad=start_duration=0.4:start_mode=clone,fps=30,format=yuv420p'
    if w=='I': af=f"aevalsrc=0:s=48000:d={d}"; inp=['-f','lavfi','-i',af]; amap=['-map','1:a']; afl=[]
    else:
        g={'M':'volume=7.5dB','F':'volume=-1.5dB','F0':'volume=-1.5dB'}[w]; inp=[]; amap=['-map','0:a']
        afl=['-af',f"pan=mono|c0=c0,aresample=48000,{g},afade=t=in:d=0.03,afade=t=out:st={d-0.04:.3f}:d=0.04"]
    subprocess.run(['ffmpeg','-v','error','-y','-ss',str(a),'-t',f'{d:.3f}','-i','/tmp/fab/bruto.mov',*inp,'-map','0:v',*amap,'-vf',vf,'-c:v','libx264','-preset','veryfast','-crf','16',*afl,'-ac','1','-ar','48000','-c:a','pcm_s16le','-shortest',o],check=True)
    lst.append(f"file '{o}'");mapa.append([w,round(t,3),a,b]);t+=d
open('/tmp/fab/pc/l.txt','w').write('\n'.join(lst))
subprocess.run(['ffmpeg','-v','error','-y','-f','concat','-safe','0','-i','/tmp/fab/pc/l.txt','-map','0:v','-c:v','copy','/tmp/portal/public/fab_base.mp4','-map','0:a','-af','alimiter=limit=0.9','/tmp/fab/voz.wav'],check=True)
json.dump({'mapa':mapa,'dur':t},open('/tmp/fab/mapa.json','w'))
print(round(t,2));[print(m) for m in mapa]
