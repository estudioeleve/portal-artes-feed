// Prévia v2: divulgação da página "Faça sua denúncia" — tema claro, celular 3D, cursor tocando, zoom de câmera.
import React from 'react';
import {AbsoluteFill, OffthreadVideo, Audio, Img, staticFile, useCurrentFrame, useVideoConfig} from 'remotion';
import {K, MONT, SORA, MONO, seg, outExpo, outBack, clamp, LogoEnd} from './base';

const VOFF = 0; // segundos do vídeo pulados no início (tela parada)
const SC = 410 / 420; // escala da tela gravada (420x900) dentro do celular
const SW = 420 * SC, SH = 900 * SC;
const PCX = 540, PCY = 1000; // centro do celular no quadro

// toques gravados (tempo do vídeo e posição na tela 420x900)
const TAPS = [
  {t: 10.505, x: 210, y: 351, n: 'texto'},
  {t: 18.603, x: 36, y: 480, n: 'mic'},
  {t: 24.4, x: 210, y: 455, n: 'local'},
  {t: 30.203, x: 210, y: 543, n: 'foto'},
  {t: 32.901, x: 254, y: 666, n: 'identificar'},
  {t: 37.202, x: 93, y: 666, n: 'anonimo'},
  {t: 39.501, x: 210, y: 728, n: 'enviar'},
].map((p) => ({...p, t: p.t - VOFF}));

// câmera: [tempo, zoom, foco x, foco y] (foco na tela 420x900)
const CAM: [number, number, number, number][] = [
  [0, 1, 210, 450], [1.2, 1, 210, 450], [2.3, 1.35, 210, 170], [6.3, 1.35, 210, 170], [7.4, 1, 210, 450], [9.8, 1, 210, 450],
  [10.6, 1.5, 210, 330], [16.6, 1.5, 210, 330], [17.6, 1.12, 150, 420], [21.6, 1.12, 150, 420], [23.6, 1.15, 210, 450],
  [24.5, 1.5, 210, 450], [28.2, 1.5, 210, 450], [29.2, 1.15, 210, 560], [31.0, 1.15, 210, 560], [32.4, 1.35, 210, 690],
  [38.0, 1.35, 210, 690], [38.9, 1.3, 210, 720], [39.7, 1.3, 210, 720], [40.5, 1.22, 210, 470], [42.4, 1.22, 210, 470],
  [43.4, 1, 210, 450], [48.9, 1, 210, 450],
];
const ease = (u: number) => (u < 0.5 ? 4 * u * u * u : 1 - Math.pow(-2 * u + 2, 3) / 2);
const camAt = (t: number) => {
  for (let i = 0; i < CAM.length - 1; i++) {
    const a = CAM[i], b = CAM[i + 1];
    if (t <= b[0]) { const u = ease(seg(t, a[0], b[0])); return [a[1] + (b[1] - a[1]) * u, a[2] + (b[2] - a[2]) * u, a[3] + (b[3] - a[3]) * u]; }
  }
  const l = CAM[CAM.length - 1]; return [l[1], l[2], l[3]];
};

const PASSOS: [number, string][] = [
  [0, 'Mande sua denúncia direto pro Portal'],
  [6.8, 'Canal oficial: aqui você tem voz'],
  [10.3, '1 · Digite com todos os detalhes'],
  [17.6, '2 · Ou aperte o microfone e fale'],
  [21.8, '3 · Coloque o seu bairro'],
  [28.8, '4 · Envie foto ou vídeo'],
  [32.2, '5 · Identifique-se… ou não'],
  [35.2, 'Pode ser anônima, sim'],
  [38.1, '6 · Clique em Enviar'],
  [40.3, 'Pronto! Você recebe o protocolo'],
  [42.4, 'A redação avalia e publica nas redes'],
];
const VERDE_DESDE = 9;

const FundoClaro: React.FC<{t: number}> = ({t}) => (
  <AbsoluteFill style={{background: 'radial-gradient(ellipse 110% 70% at 50% 8%, #E3ECFF 0%, #F4F8FF 45%, #FFFFFF 100%)', overflow: 'hidden'}}>
    <div style={{position: 'absolute', left: 540 - 520, top: 560, width: 1040, height: 1040, borderRadius: '50%', background: 'radial-gradient(circle, rgba(26,68,157,0.13), transparent 65%)', transform: `scale(${1 + Math.sin(t * 0.8) * 0.04})`}} />
    {Array.from({length: 22}).map((_, i) => {
      const r = (n: number) => { const x = Math.sin(n * 127.1 + 311.7) * 43758.5453; return x - Math.floor(x); };
      const y = (r(i + 5) * 1920 - t * (16 + r(i) * 26) + 1920 * 8) % 1920;
      return <div key={i} style={{position: 'absolute', left: r(i) * 1080, top: y, width: 6 + r(i + 2) * 8, height: 6 + r(i + 2) * 8, borderRadius: 99, background: K.acc, opacity: 0.16 + 0.2 * r(i + 8)}} />;
    })}
  </AbsoluteFill>
);

const Cursor: React.FC<{t: number; z: number}> = ({t, z}) => {
  const show = clamp(seg(t, 9.4, 9.8)) * (1 - seg(t, 39.7, 40.05));
  if (show <= 0) return null;
  // posição: parte de baixo da tela e vai de toque em toque
  let x = 300, y = 820, press = 0;
  const pts = [{t: 9.4, x: 300, y: 820}, ...TAPS];
  for (let i = 0; i < pts.length; i++) {
    const p = pts[i], nx = pts[i + 1];
    if (!nx || t < p.t) { if (t < p.t && i === 0) { x = p.x; y = p.y; } break; }
    const u = ease(seg(t, Math.max(p.t + 0.25, nx.t - 0.7), nx.t - 0.05));
    x = p.x + (nx.x - p.x) * u; y = p.y + (nx.y - p.y) * u;
  }
  for (const p of TAPS) { const d = t - p.t; if (d > -0.12 && d < 0.18) press = 1 - Math.abs(d - 0.03) / 0.15; }
  const s = 1 / z;
  return <>
    {TAPS.map((p, i) => { const d = t - p.t; if (d < 0 || d > 0.6) return null; const u = outExpo(d / 0.6); return <div key={i} style={{position: 'absolute', left: p.x * SC - 40 * s, top: p.y * SC - 40 * s, width: 80 * s, height: 80 * s, borderRadius: 99, border: `${4 * s}px solid ${K.blue}`, opacity: (1 - u) * 0.8, transform: `scale(${0.3 + u * 1.2})`}} />; })}
    <div style={{position: 'absolute', left: x * SC, top: y * SC, opacity: show, transform: `translate(-6px,-4px) scale(${s * (1 - press * 0.18)})`, transformOrigin: '6px 4px'}}>
      <svg width="54" height="62" viewBox="0 0 27 31" style={{filter: 'drop-shadow(0 6px 10px rgba(12,30,74,0.35))'}}>
        <path d="M3 2 L3 24 L9 18.5 L13 28 L17 26.3 L13.1 17 L21 17 Z" fill="#0E1117" stroke="#fff" strokeWidth="2" strokeLinejoin="round" />
      </svg>
    </div>
  </>;
};

export const DenunciaDemo: React.FC = () => {
  const f = useCurrentFrame();
  const {fps} = useVideoConfig();
  const t = f / fps;
  const outroStart = 48.9;
  const vf = Math.round(outroStart * fps);
  if (f >= vf) return <LogoEnd d={(f - vf) / fps} />;

  const inP = seg(t, 0.05, 1.0);
  const head = outExpo(seg(t, 0.2, 0.8));
  const [z, fx, fy] = camAt(t);
  const zp = clamp((z - 1) / 0.5);
  // ponto focado vai em direção ao centro do quadro conforme o zoom
  const fxF = PCX - SW / 2 + fx * SC, fyF = PCY - SH / 2 + fy * SC;
  const tx = (PCX - (PCX + (fxF - PCX) * z)) * zp, ty = (1020 - (PCY + (fyF - PCY) * z)) * zp;
  const sway = Math.sin(t * 0.7) * 9 * (1 - zp * 0.8);
  const rotIn = (1 - outBack(clamp(inP), 1.2)) * 70;
  const yIn = (1 - outExpo(clamp(inP))) * 700;
  const fade = 1 - seg(t, outroStart - 0.35, outroStart);

  let pi = 0; PASSOS.forEach(([pt], i) => { if (t >= pt) pi = i; });
  const pu = outBack(seg(t, PASSOS[pi][0], PASSOS[pi][0] + 0.4), 1.4);

  return (
    <AbsoluteFill style={{opacity: fade}}>
      <Audio src={staticFile('narracao-denuncia.m4a')} />
      <FundoClaro t={t} />

      {/* celular com câmera */}
      <div style={{position: 'absolute', inset: 0, transform: `translate(${tx}px, ${ty + yIn}px)`}}>
        <div style={{position: 'absolute', left: PCX, top: PCY, width: 0, height: 0, perspective: 2400}}>
          <div style={{position: 'absolute', left: -SW / 2, top: -SH / 2, width: SW, height: SH, transformStyle: 'preserve-3d', transform: `scale(${z}) rotateY(${sway + rotIn}deg) rotateX(${3 + Math.cos(t * 0.5) * 2}deg)`}}>
            <div style={{position: 'absolute', inset: -13, borderRadius: 52, background: 'linear-gradient(145deg, #2A3142, #0B0E15 40%, #1A1F2B)', boxShadow: '0 60px 110px rgba(13,30,80,0.35), 0 0 0 2px rgba(127,176,255,0.35)'}} />
            <div style={{position: 'absolute', inset: 0, borderRadius: 40, overflow: 'hidden', background: '#fff'}}>
              <OffthreadVideo src={staticFile('denuncia-uso-hd.mp4')} startFrom={Math.round(VOFF * 30)} muted style={{width: SW, height: SH, objectFit: 'cover'}} />
              <Cursor t={t} z={z} />
              <div style={{position: 'absolute', top: 0, bottom: 0, left: `${-60 + ((t * 22) % 220)}%`, width: '30%', background: 'linear-gradient(100deg, transparent, rgba(255,255,255,0.28), transparent)', transform: 'skewX(-18deg)'}} />
            </div>
            <div style={{position: 'absolute', top: 10, left: '50%', marginLeft: -60, width: 120, height: 30, borderRadius: 16, background: '#05070B'}} />
          </div>
        </div>
      </div>

      <div style={{position: 'absolute', left: 0, right: 0, top: 0, height: 600, opacity: clamp(zp * 1.6), background: 'linear-gradient(180deg, #E9F0FF 0%, #F1F6FF 78%, rgba(241,246,255,0) 100%)'}} />
      {/* cabeçalho */}
      <div style={{position: 'absolute', left: 120, right: 120, top: 250, display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 16, opacity: head, transform: `translateY(${(1 - head) * -30}px)`}}>
        <div style={{display: 'flex', alignItems: 'center', gap: 16}}>
          <div style={{background: K.navy, borderRadius: 14, padding: '10px 20px'}}><Img src={staticFile('logo/full.png')} style={{height: 46, display: 'block'}} /></div>
          <div style={{fontFamily: MONO, fontWeight: 700, fontSize: 22, letterSpacing: '0.16em', color: K.blue}}>NOVO NO SITE</div>
        </div>
        <div style={{fontFamily: MONT, fontWeight: 900, fontSize: 72, color: '#0E2A66', lineHeight: 1, letterSpacing: '-0.01em'}}>FAÇA SUA DENÚNCIA</div>
        <div key={pi} style={{marginTop: 4, fontFamily: SORA, fontWeight: 700, fontSize: 32, color: '#fff', background: pi >= VERDE_DESDE ? '#1F7A4C' : K.blue, padding: '14px 30px', borderRadius: 999, boxShadow: '0 12px 30px rgba(26,68,157,0.3)', opacity: clamp(pu * 1.5), transform: `translateY(${(1 - clamp(pu)) * 24}px) scale(${0.85 + 0.15 * pu})`}}>{PASSOS[pi][1]}</div>
      </div>

      {(() => { const c = outBack(seg(t, 41.3, 41.85), 1.6); if (c <= 0) return null; return (
        <div style={{position: 'absolute', left: 540 - 300, top: 1240, width: 600, display: 'flex', alignItems: 'center', gap: 20, padding: '24px 30px', borderRadius: 26, background: 'rgba(255,255,255,0.92)', border: '2px solid rgba(127,176,255,0.6)', boxShadow: '0 30px 70px rgba(12,30,74,0.28)', opacity: clamp(c * 1.4), transform: `perspective(1200px) translateY(${(1 - c) * 120}px) rotateX(${(1 - c) * 40}deg) scale(${0.6 + 0.4 * c})`}}>
          <div style={{width: 64, height: 64, borderRadius: 99, background: '#1F7A4C', display: 'grid', placeItems: 'center', flex: 'none'}}><svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg></div>
          <div style={{display: 'flex', flexDirection: 'column', gap: 4}}>
            <div style={{fontFamily: MONT, fontWeight: 900, fontSize: 30, color: '#0E2A66'}}>Denúncia recebida</div>
            <div style={{fontFamily: MONO, fontWeight: 700, fontSize: 24, color: K.blue, letterSpacing: '0.06em'}}>PROTOCOLO OC-2026-0142</div>
          </div>
        </div>); })()}
      <div style={{position: 'absolute', left: 120, right: 180, top: 1375, display: 'flex', justifyContent: 'center', gap: 14, transform: 'translateX(30px)'}}>
        {['Site', 'Instagram', 'Facebook'].map((n, i) => { const c = outBack(seg(t, 46.6 + i * 0.18, 47.0 + i * 0.18), 1.8); if (c <= 0) return null; return <div key={n} style={{fontFamily: SORA, fontWeight: 700, fontSize: 26, color: K.blue, background: '#fff', border: `2px solid ${K.acc}`, borderRadius: 999, padding: '10px 24px', boxShadow: '0 10px 24px rgba(26,68,157,0.18)', opacity: clamp(c * 1.4), transform: `translateY(${(1 - c) * 40}px) scale(${0.7 + 0.3 * c})`}}>{n}</div>; })}
      </div>
      <div style={{position: 'absolute', left: 120, right: 180, top: 1500, textAlign: 'center', fontFamily: MONO, fontWeight: 700, fontSize: 24, letterSpacing: '0.12em', color: K.blue, opacity: head * (1 - clamp(zp * 3))}}>PORTALONENEWS.COM.BR</div>
    </AbsoluteFill>
  );
};

export const DENUNCIA_TOTAL = 48.9 + 3.0;
