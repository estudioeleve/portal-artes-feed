// Voo 3D sobre a página inicial do Portal ONE News — vertical 1080x1920. Tudo calculado a partir do tempo (sem timers).
import React from 'react';
import {AbsoluteFill, Img, staticFile, useCurrentFrame, useVideoConfig, delayRender, continueRender} from 'remotion';
import data from './flydata.json';

{
  const h = delayRender('fontes-fly');
  Promise.all([
    new FontFace('Geist', `url(${staticFile('fonts/geist-latin-500-normal.woff2')})`, {weight: '500'}),
    new FontFace('Instrument Serif', `url(${staticFile('fonts/instrument-serif-latin-400-italic.woff2')})`, {style: 'italic'}),
  ].map((f) => f.load().then((x) => (document as any).fonts.add(x)))).then(() => continueRender(h)).catch(() => continueRender(h));
}

const W = 1080, HH = 1920, CX = 540, CY = 960;
const K = 2.6; // unidades do plano por px da página
const PW = 430 * K; // largura da página no plano
const PH = data.H * K;
const P = 2200; // distância focal (perspectiva CSS)
const TH = (56 * Math.PI) / 180;
const S0 = 540, D0 = 2100, D1 = 2625;
const KB = 1878 - CY; // borda de baixo da página fica 42px acima do fim do quadro

const clamp = (x: number, a = 0, b = 1) => Math.min(b, Math.max(a, x));
const sst = (a: number, b: number, x: number) => { const u = clamp((x - a) / (b - a)); return u * u * (3 - 2 * u); };
const lerp = (a: number, b: number, u: number) => a + (b - a) * u;

// ---- curva de velocidade (fração do cruzeiro) e integral ----
const vel = (t: number) => {
  if (t < 0.6) return 0.5 * sst(0, 0.6, t);
  if (t < 1.25) return 0.5;
  if (t < 1.6) return lerp(0.5, 1, sst(1.25, 1.6, t));
  if (t < 7.3) return 1;
  if (t < 8.3) return lerp(1, 0.12, sst(7.3, 8.3, t));
  return 0.12;
};
const DT = 1 / 600;
const CUM: number[] = [0];
for (let i = 1; i <= 15 * 600; i++) CUM.push(CUM[i - 1] + vel((i - 0.5) * DT) * DT);
const cum = (t: number) => { const x = clamp(t, 0, 15) / DT, i = Math.floor(x); return CUM[i] + (CUM[Math.min(i + 1, CUM.length - 1)] - CUM[i]) * (x - i); };
const CRUISE = (data.footerTitle + 20 - S0) / cum(8.0); // px da página por segundo
const sGlide = (t: number) => (S0 + CRUISE * cum(t)) * K;

// ---- zoom de saída (log) com velocidade em trapézio ----
const trapW = (() => { const a = 7.75, b = 8.35, c = 8.95, d = 9.25; const f = (t: number) => (t < a ? 0 : t < b ? 0.5 * (t - a) ** 2 / (b - a) : t < c ? 0.5 * (b - a) + (t - b) : t < d ? 0.5 * (b - a) + (c - b) + ((d - c) - (d - t) ** 2 / (d - c)) * 0.5 : 0.5 * (b - a) + (c - b) + 0.5 * (d - c)); const tot = f(99); return (t: number) => f(t) / tot; })();
const Dflight = (t: number) => lerp(D0, D1, 1 - Math.pow(1 - clamp(t / 8.2), 3));
const SC_O = 1818 / PH; // escala da vista de cima (página inteira)
const D_O = P / SC_O;

type Cam = {s: number; D: number; th: number};
const camAt = (t: number): Cam => {
  let D = Dflight(t);
  if (t > 7.75) D = Math.exp(lerp(Math.log(Dflight(7.75)), Math.log(D_O), trapW(t)));
  const th = TH * (1 - sst(8.15, 8.95, t));
  const sg = sGlide(Math.min(t, 9.25));
  const db = (KB * D) / (P * Math.cos(th) + KB * Math.sin(th));
  const sPin = PH - db;
  const b = sst(8.3, 8.65, t);
  return {s: lerp(sg, Math.max(sg, sPin), b), D, th};
};

// ---- profundidade de campo ----
const blurAt = (depth: number, focus: number) => {
  const scr = depth < focus ? Math.min(120, (60 * (focus - depth)) / depth) : (10 * (depth - focus)) / depth;
  return Math.min(40, scr / (P / depth)); // raio no espaço local do elemento
};
const depthOf = (c: Cam, yUnits: number, z = 0) => c.D - (yUnits - c.s) * Math.sin(c.th) - z * Math.cos(c.th);

// ---- peças que levantam ----
type Pc = (typeof data.pieces)[number];
const PEC: Record<string, {z: number; a: number}> = {
  hero: {z: 22, a: 0}, chip: {z: 70, a: 30}, emph: {z: 120, a: 46}, emphL: {z: 200, a: 54}, emphR: {z: 200, a: 54},
  row: {z: 30, a: 12}, row2: {z: 40, a: 18}, depo: {z: 112, a: 30}, avatar: {z: 30, a: 20},
};
const PX = 1440 / 430 / K; // "px de página" do roteiro -> unidades
const liftOf = (p: Pc, t: number, c: Cam) => {
  const cfg = PEC[p.k];
  let u: number;
  if (p.y + p.h < 650) u = sst(0.1, 1.35, t); // peças do destaque sobem logo no início
  else { const dl = p.y + p.h / 2 - c.s / K; u = 1 - sst(60, 420, dl); }
  if (p.k === 'depo') u = sst(0.0, 1, u);
  const settle = 1 - sst(7.9, 9.0, t);
  return {z: (p.k === 'depo' ? lerp(22, cfg.z, u) * (u > 0 ? 1 : 0) : cfg.z * u) * settle / PX, a: cfg.a * u * settle};
};

const Piece: React.FC<{p: Pc; t: number; c: Cam}> = ({p, t, c}) => {
  const {z, a} = liftOf(p, t, c);
  const x = p.x * K, y = p.y * K, w = p.w * K, h = p.h * K, r = p.r * K;
  const zp = z * PX; // em px do roteiro
  const sb = blurAt(depthOf(c, y + h / 2, 0), c.D);
  const pb = blurAt(depthOf(c, y + h / 2, z + Math.sin((a * Math.PI) / 180) * h * 0.25), c.D);
  const isDepo = p.k === 'depo';
  return <>
    {z > 0.5 ? <div style={{position: 'absolute', left: x + 0.1 * zp / PX, top: y + (4 + 0.36 * zp) / PX, width: w, height: h, borderRadius: r, background: '#1a2133', opacity: 0.26 * clamp(zp / 10), filter: `blur(${(7 + 0.5 * zp) / PX + sb}px)`, transform: 'translateZ(0.5px)'}} /> : null}
    <div style={{position: 'absolute', left: x, top: y, width: w, height: h, transformOrigin: '50% 50%', transform: `translateZ(${z + 1}px) rotateX(${-a}deg)`, borderRadius: r, overflow: 'hidden', filter: pb > 0.3 ? `blur(${pb}px)` : undefined, background: '#fff', boxShadow: isDepo ? `0 ${34 * clamp(zp / 112)}px 0 -2px #ffffff` : undefined}}>
      <Img src={staticFile(`fly/p_${p.n}.png`)} style={{width: '100%', height: '100%', display: 'block'}} />
    </div>
  </>;
};

// ---- tiles do logo ----
const TILE = staticFile('fly/tile.png');
const tiles3d = (t: number) => {
  const base = [
    {x0: 318, y0: 600, x1: -52, y1: 40, z1: 260, s1: 64},
    {x0: 352, y0: 600, x1: 470, y1: -10, z1: 330, s1: 56},
    {x0: 318, y0: 634, x1: -30, y1: 140, z1: 190, s1: 50},
    {x0: 352, y0: 634, x1: 452, y1: 110, z1: 230, s1: 60},
  ];
  const u = sst(0.1, 1.35, t), tr = sst(1.2, 2.4, t);
  return base.map((b) => ({x: lerp(b.x0, b.x1, u) * K, y: (lerp(b.y0, b.y1, u) + 300 * tr) * K, z: lerp(2, b.z1, u) * K, s: lerp(12, b.s1, u) * K}));
};

const Palavras: React.FC<{txt: string; t: number; t0: number; step: number; style: React.CSSProperties}> = ({txt, t, t0, step, style}) => (
  <div style={{display: 'flex', flexWrap: 'wrap', justifyContent: 'center', columnGap: '0.24em', ...style}}>
    {txt.split(' ').map((w, i) => { const u = sst(t0 + i * step, t0 + i * step + 0.34, t); return <span key={i} style={{display: 'inline-block', opacity: u, transform: `translateY(${(1 - u) * 16}px)`, filter: `blur(${(1 - u) * 12}px)`}}>{w}</span>; })}
  </div>
);

export const Fly: React.FC = () => {
  const f = useCurrentFrame();
  const {fps} = useVideoConfig();
  const t = f / fps;
  const floor = <AbsoluteFill style={{background: 'radial-gradient(ellipse 75% 60% at 50% 50%, #f9fbfb 0%, #f6f7f8 55%, #f2f3f6 100%)'}} />;

  // ===== fase 3D: voo e subida =====
  if (t < 9.25) {
    const c = camAt(t);
    const sinT = Math.sin(c.th), cosT = Math.cos(c.th);
    // faixas visíveis
    const strips: number[] = [];
    for (let i = 0; i < data.ns; i++) {
      const yc = (i + 0.5) * data.STRIP * K; const dl = yc - c.s;
      const dep = c.D - dl * sinT;
      if (dep < 250) continue;
      const ys = CY + (dl * cosT * P) / dep;
      if (ys < -400 || ys > HH + 600) continue;
      strips.push(i);
    }
    const tl = tiles3d(t);
    const late = t > 2.6 ? 0 : 1;
    return (
      <AbsoluteFill style={{overflow: 'hidden'}}>
        {floor}
        <div style={{position: 'absolute', inset: 0, perspective: P, perspectiveOrigin: `${CX}px ${CY}px`}}>
          <div style={{position: 'absolute', left: CX, top: CY, width: 0, height: 0, transformStyle: 'preserve-3d', transform: `translateZ(${P - c.D}px) rotateX(${(c.th * 180) / Math.PI}deg) translate(${-PW / 2}px, ${-c.s}px)`}}>
            <div style={{position: 'absolute', left: 0, top: 0, width: PW, height: PH, borderRadius: 24 * K, background: '#fff', boxShadow: `0 ${30 * K}px ${90 * K}px rgba(26,33,51,0.16)`}} />
            {strips.map((i) => {
              const y0 = i * data.STRIP * K, hh = Math.min(data.STRIP * K, PH - y0) + 0.6;
              const bl = blurAt(depthOf(c, y0 + hh / 2), c.D);
              const first = i === 0, last = i === data.ns - 1;
              const m = data.M * K, R = 24 * K, hs = data.STRIP * K;
              const rr = first ? `round ${R}px ${R}px 0 0` : last ? `round 0 0 ${R}px ${R}px` : '';
              return <Img key={i} src={staticFile(`fly/s_${String(i).padStart(2, '0')}.jpg`)} style={{position: 'absolute', left: 0, top: y0 - m, width: PW, height: hs + 2 * m, transform: 'translateZ(0.2px)', filter: bl > 0.3 ? `blur(${bl}px)` : undefined, clipPath: `inset(${m}px 0 ${m + hs - hh + 0.6}px 0 ${rr})`}} />;
            })}
            {data.pieces.map((p) => { const dl = p.y * K - c.s; const dep = c.D - dl * sinT; if (dep < 300) return null; const ys = CY + (dl * cosT * P) / dep; if (ys < -600 || ys > HH + 900) return null; return <Piece key={p.n} p={p} t={t} c={c} />; })}
            {late ? tl.map((q, i) => { const bl = blurAt(depthOf(c, q.y + q.s / 2, q.z), c.D); return <Img key={i} src={TILE} style={{position: 'absolute', left: q.x - q.s / 2, top: q.y - q.s / 2, width: q.s, height: q.s, transform: `translateZ(${q.z}px) rotateX(${-30 * sst(0.1, 1.35, t)}deg)`, filter: `blur(${Math.min(bl, 40)}px) drop-shadow(0 ${q.z * 0.12}px ${q.z * 0.08}px rgba(26,33,51,0.25))`}} />; }) : null}
          </div>
        </div>
        {/* tile que passa perto da lente, pela esquerda */}
        {t > 4.9 && t < 6.0 ? (() => { const u = sst(4.9, 6.0, t); return <Img src={TILE} style={{position: 'absolute', left: lerp(-420, 120, u), top: lerp(1650, 1250, u), width: 380, height: 380, filter: 'blur(22px)', opacity: Math.sin(u * Math.PI)}} />; })() : null}
      </AbsoluteFill>
    );
  }

  // ===== fase 2D: vista de cima, mergulho e cartão =====
  const c9 = camAt(9.25);
  const sc0 = P / c9.D; // px de tela por unidade
  const top0 = CY - c9.s * sc0;
  let sc = sc0, top = top0, cut = PH, rad = 24, fade = 0;
  const SCC = 660 / PW, TOPC = 640, HEROH = 650 * K;
  if (t < 9.47) {
    const u = sst(9.25, 9.47, t); const k = 1 + 0.035 * u;
    sc = sc0 * k; top = CY - (CY - top0) * k;
  } else if (t < 9.97) {
    const u = clamp((t - 9.47) / 0.5); const e = u < 0.5 ? 2 * u * u : 1 - Math.pow(-2 * u + 2, 2) / 2;
    const scA = sc0 * 1.035, topA = CY - (CY - top0) * 1.035;
    sc = Math.exp(lerp(Math.log(scA), Math.log(SCC), e));
    top = lerp(topA, TOPC, sst(0.62, 1, u));
    cut = lerp(PH, HEROH, sst(0.1, 0.85, u)); rad = lerp(24, 40, e); fade = sst(0.05, 0.6, u);
  } else {
    const u = sst(10.6, 14.5, t); const k = 1 + 0.02 * u;
    sc = SCC * k; const hc = HEROH * SCC; top = TOPC + hc / 2 - (hc / 2) * k; cut = HEROH; rad = 40; fade = 1;
  }
  const w = PW * sc, hVis = cut * sc, left = CX - w / 2;
  const heroPx = HEROH * sc;
  const tc = sst(9.6, 10.05, t);
  const drift = (i: number) => Math.sin(t * 0.7 + i * 1.7) * 4;
  return (
    <AbsoluteFill style={{overflow: 'hidden'}}>
      {floor}
      <div style={{position: 'absolute', left, top, width: w, height: hVis, borderRadius: rad, overflow: 'hidden', background: '#fff', boxShadow: `0 ${18 + 22 * fade}px ${50 + 50 * fade}px rgba(26,33,51,${0.16 + 0.06 * fade})`,
        WebkitMaskImage: fade > 0 ? `linear-gradient(to bottom, #000 0px, #000 ${heroPx}px, rgba(0,0,0,${1 - fade}) ${heroPx + 1}px)` : undefined}}>
        <Img src={staticFile('fly/clean_mid.jpg')} style={{position: 'absolute', left: 0, top: 0, width: w, height: PH * sc, display: 'block'}} />
      </div>
      {/* tiles do logo no cartão */}
      {tc > 0 ? <>
        <Img src={TILE} style={{position: 'absolute', left: left - 54 + drift(0), top: top - 58 + drift(1), width: 120 * tc, height: 120 * tc, transform: 'rotate(-8deg)', filter: 'drop-shadow(0 14px 18px rgba(26,33,51,0.25))'}} />
        <Img src={TILE} style={{position: 'absolute', left: left + w - 66 + drift(2), top: top - 62 + drift(3), width: 120 * tc, height: 120 * tc, transform: 'rotate(7deg)', filter: 'drop-shadow(0 14px 18px rgba(26,33,51,0.25))'}} />
        <Img src={TILE} style={{position: 'absolute', left: W - 96 + drift(4), top: 1130 + drift(5), width: 170 * tc, height: 170 * tc, transform: 'rotate(-10deg)', filter: 'drop-shadow(0 18px 24px rgba(26,33,51,0.28))'}} />
        <Img src={TILE} style={{position: 'absolute', left: lerp(-420, -150, tc) + drift(6), top: lerp(1900, 1560, tc), width: 440, height: 440, filter: 'blur(30px)', opacity: 0.85 * tc}} />
        <Img src={TILE} style={{position: 'absolute', left: lerp(1260, 840, tc) + drift(7), top: lerp(2000, 1640, tc), width: 480, height: 480, filter: 'blur(34px)', opacity: 0.85 * tc}} />
      </> : null}
      {/* selo, título e cauda em itálico */}
      {t > 10.0 ? <>
        {(() => { const u = sst(10.02, 10.4, t); return <div style={{position: 'absolute', left: 0, right: 0, top: 300, display: 'flex', justifyContent: 'center', opacity: u}}>
          <div style={{display: 'flex', alignItems: 'center', gap: 12, background: '#fff', borderRadius: 999, padding: '9px 22px 9px 9px', boxShadow: '0 8px 24px rgba(26,33,51,0.12)'}}>
            <Img src={TILE} style={{width: 40, height: 40}} />
            <span style={{fontFamily: 'Geist', fontWeight: 500, fontSize: 28, color: '#111318'}}>Portal ONE News</span>
          </div></div>; })()}
        <Palavras txt="Os destaques de hoje" t={t} t0={10.12} step={0.06} style={{position: 'absolute', left: 80, right: 80, top: 382, fontFamily: 'Geist', fontWeight: 500, fontSize: 92, letterSpacing: '-0.035em', color: '#111318', lineHeight: 1}} />
        <Palavras txt="estão no Portal ONE News." t={t} t0={10.36} step={0.05} style={{position: 'absolute', left: 80, right: 80, top: 488, fontFamily: 'Instrument Serif', fontStyle: 'italic', fontSize: 92, color: '#8b8b92', lineHeight: 1, letterSpacing: '-0.01em'}} />
      </> : null}
    </AbsoluteFill>
  );
};

export const FLY_TOTAL = 14.5;
