#!/usr/bin/env python3
"""Emenda o encerramento do Portal no fim de um Reels (Israel, 16/11/2026: todo Reels, notícia ou denúncia).

Uso: python3 robo/encerramento.py <entrada.mp4> <saida.mp4> --portal <one|luziense|politica|comercio>
- one e luziense: encerramento do Portal ONE News (robo/encerramentos/fim-one.mp4)
- politica e comercio: só o encerramento próprio (fim-politica.mp4 / fim-comercio.mp4); se ainda não existir, o vídeo sai sem encerramento.
O som do Reels termina junto com ele; o encerramento passa em silêncio.
"""
import argparse, os, shutil, subprocess, sys

AQUI = os.path.dirname(os.path.abspath(__file__))
ARQ = {'one': 'fim-one.mp4', 'luziense': 'fim-one.mp4', 'luz': 'fim-one.mp4',
       'politica': 'fim-politica.mp4', 'comercio': 'fim-comercio.mp4'}


def tem_audio(p):
    r = subprocess.run(['ffprobe', '-v', 'error', '-select_streams', 'a', '-show_entries', 'stream=index', '-of', 'csv=p=0', p],
                       capture_output=True, text=True)
    return bool(r.stdout.strip())


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('entrada'); ap.add_argument('saida'); ap.add_argument('--portal', default='one')
    a = ap.parse_args()
    fim = os.path.join(AQUI, 'encerramentos', ARQ.get(a.portal, 'fim-one.mp4'))
    if not os.path.exists(fim):
        if os.path.abspath(a.entrada) != os.path.abspath(a.saida):
            shutil.copy(a.entrada, a.saida)
        print('sem encerramento para', a.portal); return
    v0 = '[0:v]scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920,fps=30,setsar=1,format=yuv420p[v0]'
    v1 = '[1:v]scale=1080:1920,fps=30,setsar=1,format=yuv420p[v1]'
    a0 = '[0:a]aresample=44100,aformat=channel_layouts=stereo[a0]' if tem_audio(a.entrada) else 'anullsrc=r=44100:cl=stereo[a0x];[a0x][0:v]atrim=0:1[a0d];[a0d]anull[a0]'
    if tem_audio(a.entrada):
        filtro = f'{v0};{v1};{a0};anullsrc=r=44100:cl=stereo,atrim=0:4.6[a1];[v0][a0][v1][a1]concat=n=2:v=1:a=1[v][a]'
        mapa = ['-map', '[v]', '-map', '[a]', '-c:a', 'aac', '-b:a', '160k']
    else:
        filtro = f'{v0};{v1};[v0][v1]concat=n=2:v=1:a=0[v]'
        mapa = ['-map', '[v]']
    tmp = a.saida + '.tmp.mp4'
    cmd = ['ffmpeg', '-y', '-v', 'error', '-i', a.entrada, '-i', fim, '-filter_complex', filtro, *mapa,
           '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '20', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', tmp]
    r = subprocess.run(cmd)
    if r.returncode != 0:
        print('ERRO ao emendar o encerramento; o vídeo segue sem ele'); 
        if os.path.abspath(a.entrada) != os.path.abspath(a.saida): shutil.copy(a.entrada, a.saida)
        sys.exit(0)
    os.replace(tmp, a.saida)
    print('encerramento emendado:', os.path.basename(fim))


if __name__ == '__main__':
    main()
