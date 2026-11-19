#!/bin/sh
# Mistura a voz editada com a trilha fixa do Resumo do Dia e junta com o vídeo mudo.
# Uso: sh mixar.sh voz_ed.wav video_mudo.mp4 saida.mp4 DURACAO_TOTAL_SEGUNDOS
# DURACAO_TOTAL = end do cenas.json + 0.3 + 4.6 (logo final). A música vai até o fim da logo.
set -e
VOZ=$1; VID=$2; OUT=$3; TOT=$4
T=/tmp/portal/public/trilha_resumo.wav
FADE=$(python3 -c "print($TOT-2.5)")
# trilha emendada nela mesma (cruzamento de 3 s) até cobrir o vídeo inteiro
ffmpeg -v error -y -i $T -i $T -i $T -filter_complex "[0][1]acrossfade=d=3:c1=tri:c2=tri[a];[a][2]acrossfade=d=3:c1=tri:c2=tri,atrim=0:$TOT,afade=t=out:st=$FADE:d=2.5[o]" -map "[o]" /tmp/trilha_longa.wav
# música baixa embaixo da voz (abaixa sozinha quando ele fala: ~17 dB abaixo da voz)
ffmpeg -v error -y -i $VOZ -i /tmp/trilha_longa.wav -filter_complex "[0]apad=whole_dur=$TOT,asplit=2[v][sc];[1]volume=0.32[m];[m][sc]sidechaincompress=threshold=0.03:ratio=6:attack=40:release=500[md];[v][md]amix=inputs=2:normalize=0:duration=longest,alimiter=limit=0.95[o]" -map "[o]" -ar 48000 /tmp/mix.wav
ffmpeg -v error -y -i $VID -i /tmp/mix.wav -map 0:v -map 1:a -c:v copy -c:a aac -b:a 192k -shortest -movflags +faststart $OUT
echo "ok: $OUT"
