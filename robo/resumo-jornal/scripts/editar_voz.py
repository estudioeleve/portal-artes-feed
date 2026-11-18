"""Edita a narração do Israel para o Resumo do Dia.

Uso:
  python3 editar_voz.py analisar narracao.MOV pasta/
      -> extrai o áudio, transcreve palavra por palavra (pasta/palavras.txt),
         marca os silêncios (pasta/sil.txt) e lista os "buracos suspeitos":
         trechos com som alto onde a transcrição não achou palavras
         (quase sempre são tomadas repetidas ou barulho de fundo).
  python3 editar_voz.py montar pasta/ trechos.json
      -> trechos.json = [[ini, fim], ...] em segundos do áudio ORIGINAL,
         um por frase, sempre a ÚLTIMA tomada certa. Gera pasta/voz_ed.wav
         com 0,6 s de silêncio limpo entre as frases e pasta/mapa.json
         (para converter tempo original -> tempo editado).
  python3 editar_voz.py conferir pasta/
      -> transcreve o voz_ed.wav final para conferir que não sobrou
         tomada errada, palavra repetida nem barulho.

Regras (aprendidas em 17/11/2026):
- O início de cada trecho é a hora EXATA da primeira palavra da tomada certa.
  Nunca "puxar para trás" o início: foi assim que entrou o "E" da tomada errada.
- O fim de cada trecho pode ir até o som acabar (máx. +0,8 s), mas nunca
  encostar no próximo trecho.
- Entre frases: silêncio digital de 0,6 s (não deixar tudo grudado, nem
  manter o ruído do ambiente entre as frases).
- Silêncio real com mais de 0,9 s DENTRO de uma frase vira 0,6 s.
"""
import sys, json, wave, subprocess, os
import numpy as np


def _audio(src, pasta):
    os.makedirs(pasta, exist_ok=True)
    subprocess.run(['ffmpeg', '-v', 'error', '-y', '-i', src, '-vn', '-ac', '1', '-ar', '16000', f'{pasta}/v16.wav'], check=True)
    subprocess.run(['ffmpeg', '-v', 'error', '-y', '-i', src, '-vn', '-ac', '2', '-ar', '48000', f'{pasta}/v48.wav'], check=True)


def _ler(p):
    w = wave.open(p)
    return np.frombuffer(w.readframes(w.getnframes()), dtype=np.int16).astype(np.float32) / 32768


def _db(a, t0, t1):
    x = a[int(t0 * 16000):int(t1 * 16000)]
    return 20 * np.log10(np.sqrt((x.astype(float) ** 2).mean()) + 1e-9) + 90 if len(x) else 0


def _transcrever(a, desloc=0.0):
    from faster_whisper import WhisperModel
    m = WhisperModel('small', device='cpu', compute_type='int8')
    segs, _ = m.transcribe(a, language='pt', word_timestamps=True, condition_on_previous_text=False,
                           vad_filter=True, vad_parameters=dict(min_silence_duration_ms=300))
    return [(round(desloc + x.start, 2), round(desloc + x.end, 2), x.word.strip()) for s in segs for x in s.words]


def analisar(src, pasta):
    _audio(src, pasta)
    a = _ler(f'{pasta}/v16.wav')
    W = _transcrever(a)
    with open(f'{pasta}/palavras.txt', 'w') as f:
        f.write(' '.join(f'{s}:{t}' for s, e, t in W))
    json.dump(W, open(f'{pasta}/palavras.json', 'w'), ensure_ascii=False)
    out = subprocess.run(['ffmpeg', '-i', f'{pasta}/v48.wav', '-af', 'silencedetect=noise=-38dB:d=0.45', '-f', 'null', '-'],
                         capture_output=True, text=True).stderr
    import re
    st = re.findall(r'silence_start: ([\d.]+)', out); en = re.findall(r'silence_end: ([\d.]+)', out)
    with open(f'{pasta}/sil.txt', 'w') as f:
        for s, e in zip(st, en): f.write(f'{s} {e}\n')
    print(open(f'{pasta}/palavras.txt').read())
    print('\nBURACOS SUSPEITOS (som alto sem palavra transcrita) — ouvir/transcrever esses trechos sozinhos:')
    for p, n in zip(W, W[1:]):
        if n[0] - p[1] > 1.0:
            altos = sum(1 for t in np.arange(p[1], n[0], 0.1) if _db(a, t, t + 0.1) > 45)
            if altos >= 4:
                print(f'  {p[1]:.2f}-{n[0]:.2f} ({p[2]} | {n[2]}) {altos} pedaços altos')
    print('\nPara transcrever um buraco sozinho: python3 editar_voz.py trecho pasta/ INI FIM')


def trecho(pasta, t0, t1):
    a = _ler(f'{pasta}/v16.wav')
    W = _transcrever(a[int(t0 * 16000):int(t1 * 16000)], t0)
    print(' '.join(f'{s}:{t}' for s, e, t in W))


def montar(pasta, arq):
    R = json.load(open(arq))
    a = _ler(f'{pasta}/v16.wav')
    S = [tuple(map(float, l.split())) for l in open(f'{pasta}/sil.txt')]
    out = []
    for j, (s, e) in enumerate(R):
        hi = R[j + 1][0] - 0.12 if j + 1 < len(R) else len(a) / 16000
        k = 0
        while k < 16 and _db(a, e, e + 0.05) > 36 and e + 0.05 < hi: e += 0.05; k += 1
        out.append((s, min(e + 0.08, hi)))
    segs, inicios = [], set()
    for s, e in out:
        cur = s; inicios.add(s)
        for a0, b0 in S:
            if a0 > s and b0 < e and b0 - a0 > 0.9:
                segs.append((cur, a0 + 0.3)); cur = b0 - 0.3
        segs.append((cur, e))
    fc, t, mp = [], 0, []
    for i, (s, e) in enumerate(segs):
        g = 0.6 if (i + 1 < len(segs) and segs[i + 1][0] in inicios) else 0
        fc.append(f'[0:a]atrim={s}:{e},asetpts=PTS-STARTPTS,afade=t=in:d=0.015,afade=t=out:st={e - s - 0.03}:d=0.03,apad=pad_dur={g}[s{i}]')
        mp.append((s, e, t)); t += e - s + g
    fc.append(''.join(f'[s{i}]' for i in range(len(segs))) + f'concat=n={len(segs)}:v=0:a=1,loudnorm=I=-15:TP=-1.5:LRA=9[o]')
    subprocess.run(['ffmpeg', '-v', 'error', '-y', '-i', f'{pasta}/v48.wav', '-filter_complex', ';'.join(fc), '-map', '[o]', '-ar', '48000', f'{pasta}/voz_ed.wav'], check=True)
    json.dump({'mapa': mp, 'dur': t}, open(f'{pasta}/mapa.json', 'w'))
    print('duração', round(t, 2), 's')
    print('início de cada frase no tempo editado:', [round(o, 2) for s, e, o in mp if s in inicios])


def conferir(pasta):
    subprocess.run(['ffmpeg', '-v', 'error', '-y', '-i', f'{pasta}/voz_ed.wav', '-ac', '1', '-ar', '16000', f'{pasta}/ed16.wav'], check=True)
    W = _transcrever(_ler(f'{pasta}/ed16.wav'))
    print(' '.join(f'{s}:{t}' for s, e, t in W))
    rep = [(W[i][0], W[i][2]) for i in range(1, len(W)) if W[i][2].lower().strip('.,') == W[i - 1][2].lower().strip('.,')]
    print('\nPALAVRAS REPETIDAS EM SEGUIDA (checar se é tomada errada):', rep or 'nenhuma')


if __name__ == '__main__':
    c = sys.argv[1]
    if c == 'analisar': analisar(sys.argv[2], sys.argv[3])
    elif c == 'trecho': trecho(sys.argv[2], float(sys.argv[3]), float(sys.argv[4]))
    elif c == 'montar': montar(sys.argv[2], sys.argv[3])
    elif c == 'conferir': conferir(sys.argv[2])
