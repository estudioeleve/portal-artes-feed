// Encerramento padrão dos Reels do Portal ONE News (~4 s, sem som): pedaços da logo voam em 3D e se encaixam,
// pisca branco, a logo volta no azul com o slogan e o @.
import React from 'react';
import {AbsoluteFill, Img, staticFile, useCurrentFrame, useVideoConfig} from 'remotion';
import {MONT, SORA} from './base';

const clamp = (x: number, a = 0, b = 1) => Math.min(b, Math.max(a, x));
const seg = (t: number, a: number, b: number) => clamp((t - a) / (b - a));
const outCubic = (u: number) => 1 - Math.pow(1 - u, 3);
const outBack = (u: number, s = 1.6) => (u <= 0 ? 0 : u >= 1 ? 1 : 1 + (s + 1) * Math.pow(u - 1, 3) + s * Math.pow(u - 1, 2));
const rnd = (i: number) => { const x = Math.sin(i * 127.1 + 311.7) * 43758.5453; return x - Math.floor(x); };

const LW = 780, LH = Math.round((780 * 463) / 995); // logo na tela
const COLS = 7, ROWS = 3;

const Pecas: React.FC<{t: number; src: string}> = ({t, src}) => {
  const pw = LW / COLS, ph = LH / ROWS;
  const pcs = [];
  for (let r = 0; r < ROWS; r++) for (let c = 0; c < COLS; c++) {
    const i = r * COLS + c;
    const t0 = 0.12 + rnd(i) * 0.55, t1 = t0 + 0.55 + rnd(i + 9) * 0.2;
    const u = outCubic(seg(t, t0, t1));
    const fx = (rnd(i + 3) - 0.5) * 1700, fy = (rnd(i + 5) - 0.5) * 2200, fz = 900 + rnd(i + 7) * 1400;
    const rx = (rnd(i + 11) - 0.5) * 540, ry = (rnd(i + 13) - 0.5) * 540, rz = (rnd(i + 17) - 0.5) * 360;
    const k = 1 - u;
    pcs.push(
      <div key={i} style={{position: 'absolute', left: c * pw, top: r * ph, width: pw + 0.6, height: ph + 0.6, backgroundImage: `url(${src})`, backgroundSize: `${LW}px ${LH}px`, backgroundPosition: `${-c * pw}px ${-r * ph}px`,
        opacity: clamp(seg(t, t0, t0 + 0.15) * 1.2), filter: k > 0.02 ? `blur(${k * 10}px)` : undefined,
        transform: `translate3d(${fx * k}px, ${fy * k}px, ${fz * k}px) rotateX(${rx * k}deg) rotateY(${ry * k}deg) rotateZ(${rz * k}deg)`}} />,
    );
  }
  return <>{pcs}</>;
};

export const FimReels: React.FC = () => {
  const f = useCurrentFrame();
  const {fps} = useVideoConfig();
  const t = f / fps;
  const flash = seg(t, 1.85, 1.98) * (1 - seg(t, 2.12, 2.4)); // pisca branco
  const branco = t >= 1.95 && t < 2.2;
  const montado = t > 1.5;
  // depois do flash a logo sobe um pouco e dá lugar ao slogan
  const sobe = outCubic(seg(t, 2.2, 2.75));
  const esc = (1 + 0.04 * outBack(seg(t, 1.35, 1.75)) - 0.04 * seg(t, 1.75, 1.95)) * (1 - 0.18 * sobe) * (1 + 0.025 * seg(t, 2.75, 4.2));
  const brilho = seg(t, 1.25, 1.75);
  const sl = outBack(seg(t, 2.5, 2.95), 1.3), ar = outBack(seg(t, 2.75, 3.2), 1.5);
  return (
    <AbsoluteFill style={{overflow: 'hidden', background: branco ? '#ffffff' : 'radial-gradient(ellipse 90% 70% at 50% 45%, #2452B8 0%, #1A449D 45%, #0C1E4A 100%)'}}>
      {/* brilho de fundo */}
      {!branco ? <div style={{position: 'absolute', left: 540 - 600, top: 960 - 600, width: 1200, height: 1200, borderRadius: '50%', background: 'radial-gradient(circle, rgba(127,176,255,0.28), transparent 65%)', transform: `scale(${0.6 + 0.5 * seg(t, 0, 1.6)})`}} /> : null}
      <div style={{position: 'absolute', left: 540, top: 900 - 140 * sobe, width: 0, height: 0, perspective: 1400}}>
        <div style={{position: 'absolute', left: -LW / 2, top: -LH / 2, width: LW, height: LH, transformStyle: 'preserve-3d', transform: `scale(${esc})`}}>
          {montado ? <Img src={staticFile(branco ? 'logo/full_navy.png' : 'logo/full.png')} style={{width: LW, height: LH, display: 'block'}} /> : <Pecas t={t} src={staticFile('logo/full.png')} />}
          {/* luz passando pela logo depois de montada */}
          {brilho > 0 && brilho < 1 && !branco ? <div style={{position: 'absolute', inset: 0, WebkitMaskImage: `url(${staticFile('logo/full.png')})`, WebkitMaskSize: '100% 100%', background: `linear-gradient(100deg, transparent ${brilho * 140 - 40}%, rgba(255,255,255,0.95) ${brilho * 140 - 20}%, transparent ${brilho * 140}%)`}} /> : null}
        </div>
      </div>
      {/* slogan + @ */}
      {t > 2.4 ? <>
        <div style={{position: 'absolute', left: 140, right: 140, top: 1060, textAlign: 'center', fontFamily: MONT, fontWeight: 800, fontSize: 40, lineHeight: 1.25, color: '#fff', letterSpacing: '-0.01em', opacity: clamp(sl * 1.4), transform: `translateY(${(1 - clamp(sl)) * 30}px)`}}>O maior ecossistema de notícias<br />da Região Metropolitana de BH</div>
        <div style={{position: 'absolute', left: 0, right: 0, top: 1200, display: 'flex', justifyContent: 'center', opacity: clamp(ar * 1.4), transform: `scale(${0.85 + 0.15 * ar})`}}>
          <div style={{fontFamily: SORA, fontWeight: 700, fontSize: 40, color: '#0C1E4A', background: '#fff', borderRadius: 999, padding: '16px 38px', letterSpacing: '-0.02em'}}>portalonenews.com.br</div>
        </div>
        {(() => { const d = outBack(seg(t, 3.0, 3.45), 1.5); return <div style={{position: 'absolute', left: 0, right: 0, top: 1310, display: 'flex', justifyContent: 'center', alignItems: 'center', gap: 14, opacity: clamp(d * 1.4), transform: `translateY(${(1 - clamp(d)) * 24}px)`, fontFamily: SORA, fontWeight: 700, fontSize: 34, color: '#fff', letterSpacing: '-0.02em'}}>
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 11l18-5v12L3 14v-3z" /><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6" /></svg>
          Faça sua denúncia: link na bio
        </div>; })()}
        {(() => { const d = seg(t, 3.25, 3.6); return <div style={{position: 'absolute', left: 0, right: 0, top: 1380, textAlign: 'center', opacity: d, fontFamily: SORA, fontWeight: 600, fontSize: 30, color: 'rgba(255,255,255,0.8)'}}>@portalonenews</div>; })()}
      </> : null}
      <AbsoluteFill style={{background: '#fff', opacity: flash * 0.9, pointerEvents: 'none'}} />
    </AbsoluteFill>
  );
};

export const FIM_REELS_TOTAL = 4.6;
