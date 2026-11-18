import numpy as np,wave,json
from faster_whisper import WhisperModel
m=WhisperModel('small',device='cpu',compute_type='int8')
w=wave.open('nar/voz16.wav');a=np.frombuffer(w.readframes(w.getnframes()),dtype=np.int16).astype(np.float32)/32768
segs,_=m.transcribe(a,language='pt',word_timestamps=True,condition_on_previous_text=False,vad_filter=True,vad_parameters=dict(min_silence_duration_ms=300))
W=[]
for s in segs:
    for x in s.words: W.append((round(x.start,2),round(x.end,2),x.word.strip()))
json.dump(W,open('nar/words_all.json','w'),ensure_ascii=False)
print(' '.join(f'{s}:{t}' for s,e,t in W))
