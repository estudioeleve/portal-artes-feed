#!/bin/sh
# Monta o projeto do Resumo do Dia em /tmp/rj (Remotion) a partir desta pasta do repositório.
set -e
R=$(cd "$(dirname "$0")" && pwd)
mkdir -p /tmp/rj/src /tmp/rj/public/fonts
cp "$R/package.json" /tmp/rj/
cp "$R/Jornal.tsx" /tmp/rj/src/
cp "$R/public/drone_sl.mp4" "$R/public/luz_logo_c.png" /tmp/rj/public/
cp "$R"/public/montserrat-*.woff2 /tmp/rj/public/fonts/
cp "$R/../encerramentos/fim-luziense.mp4" /tmp/rj/public/
ffmpeg -v error -y -i "$R/../trilhas/resumo-do-dia.m4a" -ar 48000 -ac 2 /tmp/rj/public/trilha_resumo.wav
cat > /tmp/rj/src/index.ts <<'X'
import {registerRoot} from 'remotion';
import {Root} from './Root';
registerRoot(Root);
X
cat > /tmp/rj/src/Root.tsx <<'X'
import React from 'react';
import {Composition} from 'remotion';
import {Jornal, FIMJ} from './Jornal';
import D from './cenas.json';
export const Root: React.FC = () => (<>
  <Composition id="Jornal" component={() => <Jornal d={D as any} />} durationInFrames={Math.round((D.end + 0.3 + FIMJ) * 30)} fps={30} width={1080} height={1920} />
</>);
X
[ -f /tmp/rj/src/cenas.json ] || cp "$R/exemplo-17-11.json" /tmp/rj/src/cenas.json
cp "$R"/scripts/*.mjs /tmp/rj/
sed -i "s#/tmp/portal#/tmp/rj#g; s#id:'JornalSL'#id:'Jornal'#; s#id:process.env.C||'TradSL'#id:'Jornal'#" /tmp/rj/*.mjs
cd /tmp/rj && [ -d node_modules ] || npm install --silent --no-audit --no-fund
echo "pronto: /tmp/rj"
