"""Vídeo longo (mais de 3 minutos) -> Reels de até 3 minutos, enquadrado no personagem principal.
Pedido do Israel, 18/11/2026: "o Instagram não entrega acima de 3 minutos"; o robô enquadra quem fala,
pega as partes principais e NUNCA corta fala importante. Ver a seção "VÍDEO LONGO" no REGRAS.md.

Duas etapas (no meio, o robô LÊ a transcrição e escolhe os trechos):

  python3 robo/video_longo.py analisar VIDEO.mp4 PASTA
      -> PASTA/falas.json   frases com início/fim e texto (transcrição palavra por palavra)
         PASTA/falas.txt    a mesma coisa em texto, numerada, para o robô ler
         PASTA/rosto.json   onde está o rosto principal ao longo do vídeo (para enquadrar)
  (o robô escreve PASTA/escolha.json = {"frases": [n, n, ...]}  -> números das frases a manter, em ordem)
  python3 robo/video_longo.py montar VIDEO.mp4 PASTA SAIDA.mp4
      -> corta nas fronteiras das frases escolhidas, enquadra em 1080x1920 seguindo o rosto,
         e se ainda passar de 3 min acelera de leve (no máximo 1,15x, voz continua natural).
         Se nem acelerando couber, para com erro: o robô escolhe menos frases e roda de novo.
"""
import sys, os, json, subprocess, math
import numpy as np

LIMITE = 178.0          # segundos (margem abaixo de 3:00)
ACEL_MAX = 1.15


def _dur(f):
    return float(subprocess.check_output(['ffprobe', '-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', f], text=True).strip())


def _tam(f):
    o = subprocess.check_output(['ffprobe', '-v', 'error', '-select_streams', 'v:0', '-show_entries', 'stream=width,height:stream_side_data=rotation',
                                 '-of', 'json', f], text=True)
    s = json.loads(o)['streams'][0]
    w, h = s['width'], s['height']
    rot = 0
    for sd in s.get('side_data_list', []) or []:
        rot = int(sd.get('rotation', 0) or 0)
    if abs(rot) in (90, 270): w, h = h, w
    return w, h


def _transcrever(video, pasta):
    import wave
    from faster_whisper import WhisperModel
    wav = f'{pasta}/a16.wav'
    subprocess.run(['ffmpeg', '-v', 'error', '-y', '-i', video, '-vn', '-ac', '1', '-ar', '16000', wav], check=True)
    w = wave.open(wav); a = np.frombuffer(w.readframes(w.getnframes()), dtype=np.int16).astype(np.float32) / 32768
    m = WhisperModel('small', device='cpu', compute_type='int8')
    segs, _ = m.transcribe(a, language='pt', word_timestamps=True, condition_on_previous_text=False,
                           vad_filter=True, vad_parameters=dict(min_silence_duration_ms=400))
    palavras = [(x.start, x.end, x.word.strip()) for s in segs for x in s.words]
    # junta em frases: quebra em ponto final/interrogação/exclamação ou pausa > 0,8 s
    frases, cur = [], []
    for i, p in enumerate(palavras):
        cur.append(p)
        prox = palavras[i + 1] if i + 1 < len(palavras) else None
        fim = p[2].endswith(('.', '?', '!')) or (prox and prox[0] - p[1] > 0.8) or prox is None
        if fim and (cur[-1][1] - cur[0][0] > 1.5 or prox is None):
            frases.append({'n': len(frases) + 1, 'ini': round(cur[0][0], 2), 'fim': round(cur[-1][1], 2), 'texto': ' '.join(x[2] for x in cur)})
            cur = []
    return frases


def _rostos(video, pasta, passo=0.5):
    import cv2
    cas = cv2.CascadeClassifier(os.path.join(os.path.dirname(os.path.abspath(__file__)), 'modelos', 'haarcascade_frontalface_default.xml'))
    cap = cv2.VideoCapture(video)
    fps = cap.get(cv2.CAP_PROP_FPS) or 30
    n = int(cap.get(cv2.CAP_PROP_FRAME_COUNT) or 0)
    pts = []
    for fr in range(0, n, max(1, int(fps * passo))):
        cap.set(cv2.CAP_PROP_POS_FRAMES, fr)
        ok, img = cap.read()
        if not ok: break
        H, W = img.shape[:2]
        esc = 640 / max(H, W)
        g = cv2.cvtColor(cv2.resize(img, None, fx=esc, fy=esc), cv2.COLOR_BGR2GRAY)
        fs = cas.detectMultiScale(g, 1.1, 5, minSize=(24, 24))
        if len(fs):
            x, y, w, h = max(fs, key=lambda r: r[2] * r[3])   # o maior rosto = personagem principal
            pts.append({'t': round(fr / fps, 2), 'x': (x + w / 2) / esc / W, 'y': (y + h / 2) / esc / H, 'h': h / esc / H})
    cap.release()
    return pts


def analisar(video, pasta):
    os.makedirs(pasta, exist_ok=True)
    frases = _transcrever(video, pasta)
    json.dump(frases, open(f'{pasta}/falas.json', 'w'), ensure_ascii=False, indent=1)
    with open(f'{pasta}/falas.txt', 'w') as f:
        for x in frases:
            f.write(f"[{x['n']}] {x['ini']:.1f}-{x['fim']:.1f}s ({x['fim'] - x['ini']:.1f}s): {x['texto']}\n")
    ros = _rostos(video, pasta)
    json.dump(ros, open(f'{pasta}/rosto.json', 'w'))
    tot = sum(x['fim'] - x['ini'] for x in frases)
    print(f'duração do vídeo: {_dur(video):.0f} s | fala somada: {tot:.0f} s | {len(frases)} frases | rosto achado em {len(ros)} pontos')
    print(f'meta: escolher frases que somem até {LIMITE:.0f} s (ou até {LIMITE * ACEL_MAX:.0f} s, que o script acelera de leve)')
    print(open(f'{pasta}/falas.txt').read())


def _trilha_rosto(ros, dur):
    """centro (x, y) suavizado do rosto ao longo do tempo; sem rosto, fica no centro."""
    if not ros: return lambda t: (0.5, 0.42)
    ts = np.array([r['t'] for r in ros]); xs = np.array([r['x'] for r in ros]); ys = np.array([r['y'] for r in ros])
    def med(a, k=7):
        out = a.copy()
        for i in range(len(a)):
            out[i] = np.median(a[max(0, i - k // 2): i + k // 2 + 1])
        return out
    xs, ys = med(xs), med(ys)
    def f(t):
        x = float(np.interp(t, ts, xs)); y = float(np.interp(t, ts, ys))
        return x, y
    return f


def montar(video, pasta, saida):
    import cv2
    frases = {x['n']: x for x in json.load(open(f'{pasta}/falas.json'))}
    esc = json.load(open(f'{pasta}/escolha.json'))['frases']
    # trechos = frases escolhidas, com folga de 0,15 s; frases seguidas viram um trecho só
    tre = []
    for n in esc:
        a, b = frases[n]['ini'] - 0.15, frases[n]['fim'] + 0.25
        if tre and a - tre[-1][1] < 0.6: tre[-1][1] = b
        else: tre.append([max(0, a), b])
    tot = sum(b - a for a, b in tre)
    vel = 1.0
    if tot > LIMITE:
        vel = tot / LIMITE
        if vel > ACEL_MAX:
            sys.exit(f'ERRO: as frases escolhidas somam {tot:.0f} s; nem acelerando {ACEL_MAX}x cabe em 3 min. Escolha menos frases.')
    W, H = _tam(video)
    ros = json.load(open(f'{pasta}/rosto.json'))
    trilha = _trilha_rosto(ros, _dur(video))
    # janela 9:16: vídeo deitado -> altura toda; vídeo em pé desenquadrado -> aproxima 20 % para centrar a pessoa
    if W / H > 9 / 16 + 0.01: ch = H; cw = int(H * 9 / 16)
    else: cw = int(W * 0.8); ch = int(cw * 16 / 9)
    if ch > H: ch = H; cw = int(H * 9 / 16)
    tmp = f'{pasta}/mudo.mp4'
    enc = subprocess.Popen(['ffmpeg', '-v', 'error', '-y', '-f', 'rawvideo', '-pix_fmt', 'bgr24', '-s', '1080x1920', '-r', '30', '-i', '-',
                            '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '19', '-pix_fmt', 'yuv420p', tmp], stdin=subprocess.PIPE)
    cap = cv2.VideoCapture(video)
    fps = cap.get(cv2.CAP_PROP_FPS) or 30
    cx_prev = None
    for a, b in tre:
        cap.set(cv2.CAP_PROP_POS_MSEC, a * 1000)   # pula para o começo do trecho uma vez só e lê em sequência
        ok, img = cap.read(); tv = a
        t = a
        while t < b and ok:
            while tv + 1 / fps <= t and ok:            # avança os quadros da fonte até o tempo de saída (30 fps)
                ok, nxt = cap.read()
                if ok: img = nxt; tv += 1 / fps
            if not ok: break
            if img.shape[1] != W: img = cv2.resize(img, (W, H))
            fx, fy = trilha(t)
            cx = fx * W; cy = fy * H + ch * 0.15      # rosto a ~35 % do topo (cabeça no terço de cima, sem cortar)
            if cx_prev is not None: cx = cx_prev + (cx - cx_prev) * 0.08   # câmera anda devagar, sem tremer
            cx_prev = cx
            x0 = int(min(max(cx - cw / 2, 0), W - cw)); y0 = int(min(max(cy - ch / 2, 0), H - ch))
            enc.stdin.write(cv2.resize(img[y0:y0 + ch, x0:x0 + cw], (1080, 1920), interpolation=cv2.INTER_LANCZOS4).tobytes())
            t += 1 / 30
    enc.stdin.close(); enc.wait(); cap.release()
    # áudio dos mesmos trechos, com emenda suave
    fc = ';'.join(f'[0:a]atrim={a}:{b},asetpts=PTS-STARTPTS,afade=t=in:d=0.03,afade=t=out:st={b - a - 0.05}:d=0.05[s{i}]' for i, (a, b) in enumerate(tre))
    fc += ';' + ''.join(f'[s{i}]' for i in range(len(tre))) + f'concat=n={len(tre)}:v=0:a=1[a]'
    subprocess.run(['ffmpeg', '-v', 'error', '-y', '-i', video, '-filter_complex', fc, '-map', '[a]', f'{pasta}/audio.wav'], check=True)
    vf = f'setpts=PTS/{vel:.4f}' if vel > 1 else 'null'
    af = f'atempo={vel:.4f},loudnorm=I=-15:TP=-1.5' if vel > 1 else 'loudnorm=I=-15:TP=-1.5'
    subprocess.run(['ffmpeg', '-v', 'error', '-y', '-i', tmp, '-i', f'{pasta}/audio.wav', '-vf', vf, '-af', af, '-map', '0:v', '-map', '1:a',
                    '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '19', '-c:a', 'aac', '-b:a', '160k', '-shortest', '-movflags', '+faststart', saida], check=True)
    print(f'ok: {saida} | {len(tre)} trechos | {tot:.0f} s de fala | velocidade {vel:.2f}x | final {_dur(saida):.0f} s')


if __name__ == '__main__':
    c = sys.argv[1]
    if c == 'analisar': analisar(sys.argv[2], sys.argv[3])
    elif c == 'montar': montar(sys.argv[2], sys.argv[3], sys.argv[4])
