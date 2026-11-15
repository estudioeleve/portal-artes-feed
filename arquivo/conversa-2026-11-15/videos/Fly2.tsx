// Destaques do dia — voo 3D pela página do Portal: cada matéria sai da página, vem pra frente da tela e volta; câmera corre pra próxima.
import React from 'react';
import {AbsoluteFill, Img, staticFile, useCurrentFrame, useVideoConfig, delayRender, continueRender} from 'remotion';
import data from './flydata.json';

{
  const h = delayRender('fontes-fly2');
  Promise.all([
    new FontFace('Geist', `url(${staticFile('fonts/geist-latin-500-normal.woff2')})`, {weight: '500'}),
    new FontFace('Instrument Serif', `url(${staticFile('fonts/instrument-serif-latin-400-italic.woff2')})`, {style: 'italic'}),
  ].map((f) => f.load().then((x) => (document as any).fonts.add(x)))).then(() => continueRender(h)).catch(() => continueRender(h));
}

const W = 1080, HH = 1920, CX = 540, CY = 960;
const K = 2.6, PW = 430 * K, PH = data.H * K, P = 2200;
const TH = (42 * Math.PI) / 180;
const KB = 1878 - CY;
const clamp = (x: number, a = 0, b = 1) => Math.min(b, Math.max(a, x));
const sst = (a: number, b: number, x: number) => { const u = clamp((x - a) / (b - a)); return u * u * (3 - 2 * u); };
const smt = (u: number) => { u = clamp(u); return u * u * u * (u * (u * 6 - 15) + 10); };
const lerp = (a: number, b: number, u: number) => a + (b - a) * u;
const outBack = (u: number, s = 1.4) => (u <= 0 ? 0 : u >= 1 ? 1 : 1 + (s + 1) * Math.pow(u - 1, 3) + s * Math.pow(u - 1, 2));

const PC = Object.fromEntries(data.pieces.map((p) => [p.n, p]));
const STORIES = ['hero', 'vacina', 'enem', 'papa', 'rosa', 'granizo', 'menino'];
const A = [0.75, 2.05, 3.3, 4.55, 5.8, 7.05, 8.3]; // câmera chega e o cartão sobe
const LEAVE = (i: number) => (i < A.length - 1 ? A[i + 1] - 0.45 : 9.15); // cartão volta pra página
const RISE0 = 9.3, RISE1 = 10.3, OV1 = 10.45, DIVE1 = 10.95;
const yc = (n: string) => (PC[n].y + PC[n].h / 2) * K;

// posição da câmera na página (unidades)
const sStory = (t: number) => {
  if (t <= A[0]) return yc('hero') - 60 * K * (1 - smt(t / A[0]));
  for (let i = 0; i < A.length; i++) {
    const y = yc(STORIES[i]); const L = LEAVE(i) + 0.05;
    const next = i < A.length - 1 ? A[i + 1] : RISE0 + 0.6;
    const yn = i < A.length - 1 ? yc(STORIES[i + 1]) : y + 260 * K;
    if (t <= L) return lerp(y, y + 18 * K, (t - A[i]) / (L - A[i]));
    if (t <= next) return lerp(y + 18 * K, yn, smt((t - L) / (next - L)));
  }
  return yc(STORIES[A.length - 1]) + 260 * K;
};
const Dbase = (t: number) => (t < A[0] ? Math.exp(lerp(Math.log(6400), Math.log(2350), smt(t / A[0]))) : 2350 + Math.sin(t * 1.3) * 40);
const trapW = (t: number) => { const a = RISE0, b = RISE0 + 0.3, c = RISE1 - 0.25, d = RISE1; const f = (x: number) => (x < a ? 0 : x < b ? 0.5 * (x - a) ** 2 / (b - a) : x < c ? 0.5 * (b - a) + (x - b) : x < d ? 0.5 * (b - a) + (c - b) + 0.5 * ((d - c) - (d - x) ** 2 / (d - c)) : 0.5 * (b - a) + (c - b) + 0.5 * (d - c)); return f(t) / f(99); };
const SC_O = 1818 / PH, D_O = P / SC_O;
type Cam = {s: number; D: number; th: number};
const camAt = (t: number): Cam => {
  let D = Dbase(t);
  if (t > RISE0) D = Math.exp(lerp(Math.log(Dbase(RISE0)), Math.log(D_O), trapW(t)));
  const th = t < A[0] ? lerp((22 * Math.PI) / 180, TH, smt(t / A[0])) : TH * (1 - sst(RISE0 + 0.15, RISE1 - 0.15, t));
  const sg = sStory(Math.min(t, RISE1));
  const db = (KB * D) / (P * Math.cos(th) + KB * Math.sin(th));
  return {s: lerp(sg, Math.max(sg, PH - db), sst(RISE0 + 0.2, RISE0 + 0.55, t)), D, th};
};
const blurAt = (depth: number, focus: number) => {
  const scr = depth < focus ? Math.min(7, (8 * (focus - depth)) / depth) : (2 * (depth - focus)) / depth;
  return Math.min(6, scr / (P / depth));
};
const project = (c: Cam, X: number, Y: number) => { const dl = Y - c.s; const dep = c.D - dl * Math.sin(c.th); const k = P / dep; return {x: CX + (X - PW / 2) * k, y: CY + dl * Math.cos(c.th) * k, k, dep}; };

// quanto cada cartão de destaque está fora da página (0 = na página, 1 = na frente da tela)
const popOf = (i: number, t: number) => { const up = outBack(clamp((t - A[i]) / 0.38)); const down = smt((t - LEAVE(i)) / 0.32); return t < A[i] ? 0 : t < LEAVE(i) ? up : 1 - down; };

const SMALL = new Set(['chip', 'avatar', 'row', 'row2', 'depo', 'emph']);
const TILE = staticFile('fly/tile.png');

const Palavras: React.FC<{txt: string; t: number; t0: number; step: number; style: React.CSSProperties}> = ({txt, t, t0, step, style}) => (
  <div style={{display: 'flex', flexWrap: 'wrap', justifyContent: 'center', columnGap: '0.24em', ...style}}>
    {txt.split(' ').map((w, i) => { const u = sst(t0 + i * step, t0 + i * step + 0.34, t); return <span key={i} style={{display: 'inline-block', opacity: u, transform: `translateY(${(1 - u) * 16}px)`, filter: `blur(${(1 - u) * 12}px)`}}>{w}</span>; })}
  </div>
);

export const Fly2: React.FC = () => {
  const f = useCurrentFrame();
  const {fps} = useVideoConfig();
  const t = f / fps;
  const floor = <AbsoluteFill style={{background: 'radial-gradient(ellipse 75% 60% at 50% 50%, #f9fbfb 0%, #f6f7f8 55%, #f2f3f6 100%)'}} />;

  if (t < RISE1) {
    const c = camAt(t);
    const sinT = Math.sin(c.th), cosT = Math.cos(c.th);
    const strips: number[] = [];
    for (let i = 0; i < data.ns; i++) { const y = (i + 0.5) * data.STRIP * K; const pr = project(c, PW / 2, y); if (pr.dep < 250 || pr.y < -500 || pr.y > HH + 700) continue; strips.push(i); }
    const settle = 1 - sst(RISE0, RISE0 + 0.8, t);
    // cartão ativo
    let act = -1, e = 0;
    STORIES.forEach((n, i) => { const v = popOf(i, t); if (v > 0.001) { act = i; e = v; } });
    const tilesU = sst(0.2, 1.3, t) * (1 - sst(1.6, 2.3, t));
    return (
      <AbsoluteFill style={{overflow: 'hidden'}}>
        {floor}
        <div style={{position: 'absolute', inset: 0, perspective: P, perspectiveOrigin: `${CX}px ${CY}px`}}>
          <div style={{position: 'absolute', left: CX, top: CY, width: 0, height: 0, transformStyle: 'preserve-3d', transform: `translateZ(${P - c.D}px) rotateX(${(c.th * 180) / Math.PI}deg) translate(${-PW / 2}px, ${-c.s}px)`}}>
            <div style={{position: 'absolute', left: 0, top: 0, width: PW, height: PH, borderRadius: 24 * K, background: '#fff', boxShadow: `0 ${30 * K}px ${90 * K}px rgba(26,33,51,0.16)`}} />
            {strips.map((i) => {
              const y0 = i * data.STRIP * K, hs = data.STRIP * K, hh = Math.min(hs, PH - y0) + 0.6, m = data.M * K, R = 24 * K;
              const bl = blurAt(c.D - (y0 + hh / 2 - c.s) * sinT, c.D);
              const rr = i === 0 ? `round ${R}px ${R}px 0 0` : i === data.ns - 1 ? `round 0 0 ${R}px ${R}px` : '';
              return <Img key={i} src={staticFile(`fly/s_${String(i).padStart(2, '0')}.jpg`)} style={{position: 'absolute', left: 0, top: y0 - m, width: PW, height: hs + 2 * m, transform: 'translateZ(0.2px)', filter: bl > 0.3 ? `blur(${bl}px)` : undefined, clipPath: `inset(${m}px 0 ${m + hs - hh + 0.6}px 0 ${rr})`}} />;
            })}
            {data.pieces.map((p) => {
              const si = STORIES.indexOf(p.n);
              if (si >= 0 && popOf(si, t) > 0.001) return null;
              const pr = project(c, p.x * K, p.y * K); if (pr.dep < 300 || pr.y < -600 || pr.y > HH + 900) return null;
              const dl = p.y + p.h / 2 - c.s / K;
              const u = (si >= 0 ? 0 : SMALL.has(p.k) || p.k.startsWith('emph') ? 1 - sst(40, 380, dl) : 0) * settle;
              const z = (p.k === 'chip' ? 26 : p.k === 'depo' ? 34 : 10) * K * u, a = (p.k === 'chip' ? 30 : p.k === 'depo' ? 24 : 8) * u;
              const x = p.x * K, y = p.y * K, w = p.w * K, h = p.h * K, r = p.r * K;
              const bl = blurAt(c.D - (y + h / 2 - c.s) * sinT - z * cosT, c.D);
              return <React.Fragment key={p.n}>
                {z > 1 ? <div style={{position: 'absolute', left: x + z * 0.1, top: y + 4 + z * 0.36, width: w, height: h, borderRadius: r, background: '#1a2133', opacity: 0.22, filter: `blur(${7 + z * 0.5}px)`, transform: 'translateZ(0.5px)'}} /> : null}
                <div style={{position: 'absolute', left: x, top: y, width: w, height: h, borderRadius: r, overflow: 'hidden', background: '#fff', transform: `translateZ(${z + 1}px) rotateX(${-a}deg)`, filter: bl > 0.3 ? `blur(${bl}px)` : undefined}}>
                  <Img src={staticFile(`fly/p_${p.n}.png`)} style={{width: '100%', height: '100%', display: 'block'}} />
                </div>
              </React.Fragment>;
            })}
            {tilesU > 0.01 ? [[-48, 30, 300, 60], [478, -10, 360, 54], [-28, 150, 220, 48], [458, 120, 260, 58]].map(([x1, y1, z1, s1], i) => { const s = lerp(10, s1, tilesU) * K; return <Img key={i} src={TILE} style={{position: 'absolute', left: lerp(330, x1, tilesU) * K - s / 2, top: lerp(600, y1, tilesU) * K - s / 2, width: s, height: s, opacity: clamp(tilesU * 3), transform: `translateZ(${z1 * K * tilesU}px) rotateX(-30deg)`, filter: 'drop-shadow(0 20px 18px rgba(26,33,51,0.22))'}} />; }) : null}
          </div>
        </div>
        {/* véu leve atrás do cartão em destaque */}
        {act >= 0 ? <AbsoluteFill style={{background: 'rgba(242,243,246,0.55)', opacity: clamp(e)}} /> : null}
        {act >= 0 ? (() => {
          const p = PC[STORIES[act]];
          const pr = project(c, p.x * K, p.y * K), pr2 = project(c, (p.x + p.w) * K, (p.y + p.h) * K);
          const r0 = {x: pr.x, y: pr.y, w: pr2.x - pr.x, h: pr2.y - pr.y};
          const tw = 960, th = (tw * p.h) / p.w;
          const sway = Math.sin(t * 2.2) * 3 * clamp(e);
          const r1 = {x: CX - tw / 2, y: 900 - th / 2, w: tw, h: th};
          const u = clamp(e, 0, 1.15);
          const R = {x: lerp(r0.x, r1.x, u), y: lerp(r0.y, r1.y, u), w: lerp(r0.w, r1.w, u), h: lerp(r0.h, r1.h, u)};
          return <div style={{position: 'absolute', left: R.x, top: R.y, width: R.w, height: R.h, borderRadius: lerp(p.r * pr.k * K, 26, clamp(u)), overflow: 'hidden', background: '#fff', boxShadow: `0 ${40 * u}px ${80 * u}px rgba(26,33,51,${0.28 * clamp(u)})`, transform: `perspective(1600px) rotateY(${sway}deg) rotateX(${(1 - clamp(u)) * 8}deg)`}}>
            <Img src={staticFile(`fly/p_${p.n}.png`)} style={{width: '100%', height: '100%', display: 'block'}} />
          </div>;
        })() : null}
      </AbsoluteFill>
    );
  }

  // ===== vista de cima, mergulho e cartão final =====
  const c9 = camAt(RISE1 - 0.0001);
  const sc0 = P / c9.D, top0 = CY - c9.s * sc0;
  let sc = sc0, top = top0, cut = PH, rad = 24, fade = 0;
  const SCC = 600 / PW, TOPC = 760, HEROH = 650 * K;
  if (t < OV1) { const k = 1 + 0.035 * sst(RISE1, OV1, t); sc = sc0 * k; top = CY - (CY - top0) * k; }
  else if (t < DIVE1) {
    const u = clamp((t - OV1) / (DIVE1 - OV1)); const e = u < 0.5 ? 2 * u * u : 1 - Math.pow(-2 * u + 2, 2) / 2;
    sc = Math.exp(lerp(Math.log(sc0 * 1.035), Math.log(SCC), e)); top = lerp(CY - (CY - top0) * 1.035, TOPC, sst(0.62, 1, u));
    cut = lerp(PH, HEROH, sst(0.1, 0.85, u)); rad = lerp(24, 40, e); fade = sst(0.05, 0.6, u);
  } else { const k = 1 + 0.02 * sst(DIVE1 + 0.6, 15, t); sc = SCC * k; const hc = HEROH * SCC; top = TOPC + hc / 2 - (hc / 2) * k; cut = HEROH; rad = 40; fade = 1; }
  const w = PW * sc, hVis = cut * sc, left = CX - w / 2, heroPx = HEROH * sc;
  const tc = sst(DIVE1 - 0.35, DIVE1 + 0.1, t);
  const dr = (i: number) => Math.sin(t * 0.7 + i * 1.7) * 4;
  return (
    <AbsoluteFill style={{overflow: 'hidden'}}>
      {floor}
      <div style={{position: 'absolute', left, top, width: w, height: hVis, borderRadius: rad, overflow: 'hidden', background: '#fff', boxShadow: `0 ${18 + 22 * fade}px ${50 + 50 * fade}px rgba(26,33,51,${0.16 + 0.06 * fade})`, WebkitMaskImage: fade > 0 ? `linear-gradient(to bottom, #000 0px, #000 ${heroPx}px, rgba(0,0,0,${1 - fade}) ${heroPx + 1}px)` : undefined}}>
        <Img src={staticFile('fly/clean_mid.jpg')} style={{position: 'absolute', left: 0, top: 0, width: w, height: PH * sc, display: 'block'}} />
      </div>
      {tc > 0 ? <>
        <Img src={TILE} style={{position: 'absolute', left: left - 54 + dr(0), top: top - 58 + dr(1), width: 120 * tc, height: 120 * tc, transform: 'rotate(-8deg)', filter: 'drop-shadow(0 14px 18px rgba(26,33,51,0.25))'}} />
        <Img src={TILE} style={{position: 'absolute', left: left + w - 66 + dr(2), top: top - 62 + dr(3), width: 120 * tc, height: 120 * tc, transform: 'rotate(7deg)', filter: 'drop-shadow(0 14px 18px rgba(26,33,51,0.25))'}} />
        <Img src={TILE} style={{position: 'absolute', left: W - 96 + dr(4), top: 1160 + dr(5), width: 170 * tc, height: 170 * tc, transform: 'rotate(-10deg)', filter: 'drop-shadow(0 18px 24px rgba(26,33,51,0.28))'}} />
        <Img src={TILE} style={{position: 'absolute', left: lerp(-420, -150, tc) + dr(6), top: lerp(1900, 1600, tc), width: 440, height: 440, filter: 'blur(30px)', opacity: 0.85 * tc}} />
        <Img src={TILE} style={{position: 'absolute', left: lerp(1260, 840, tc) + dr(7), top: lerp(2000, 1680, tc), width: 480, height: 480, filter: 'blur(34px)', opacity: 0.85 * tc}} />
      </> : null}
      {t > DIVE1 ? <>
        {(() => { const u = sst(DIVE1 + 0.05, DIVE1 + 0.4, t); return <div style={{position: 'absolute', left: 0, right: 0, top: 300, display: 'flex', justifyContent: 'center', opacity: u}}>
          <div style={{display: 'flex', alignItems: 'center', gap: 12, background: '#fff', borderRadius: 999, padding: '9px 22px 9px 9px', boxShadow: '0 8px 24px rgba(26,33,51,0.12)'}}>
            <Img src={TILE} style={{width: 40, height: 40}} /><span style={{fontFamily: 'Geist', fontWeight: 500, fontSize: 28, color: '#111318'}}>Portal ONE News</span>
          </div></div>; })()}
        <Palavras txt="Os destaques de hoje" t={t} t0={DIVE1 + 0.15} step={0.06} style={{position: 'absolute', left: 80, right: 80, top: 382, fontFamily: 'Geist', fontWeight: 500, fontSize: 92, letterSpacing: '-0.035em', color: '#111318', lineHeight: 1}} />
        <Palavras txt="estão no Portal ONE News." t={t} t0={DIVE1 + 0.39} step={0.05} style={{position: 'absolute', left: 80, right: 80, top: 488, fontFamily: 'Instrument Serif', fontStyle: 'italic', fontSize: 92, color: '#8b8b92', lineHeight: 1, letterSpacing: '-0.01em'}} />
        {(() => { const u = outBack(clamp((t - (DIVE1 + 0.95)) / 0.45)); return <div style={{position: 'absolute', left: 0, right: 0, top: 618, display: 'flex', justifyContent: 'center', opacity: clamp(u * 2), transform: `translateY(${(1 - clamp(u)) * 20}px) scale(${0.9 + 0.1 * u})`}}>
          <div style={{fontFamily: 'Geist', fontWeight: 500, fontSize: 34, color: '#fff', background: '#1A449D', borderRadius: 999, padding: '14px 30px', letterSpacing: '-0.01em'}}>Acesse portalonenews.com.br</div>
        </div>; })()}
      </> : null}
    </AbsoluteFill>
  );
};

export const FLY2_TOTAL = 15;
