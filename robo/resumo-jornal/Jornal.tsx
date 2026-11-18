import React from 'react';
import {AbsoluteFill, Img, OffthreadVideo, Sequence, staticFile, useCurrentFrame} from 'remotion';

// Resumo do dia em formato de telejornal (Israel, 17/11/2026): imagem grande, tarjas de TV, sem efeitos de "IA".
const FPS = 30;
const AZ = '#022078', AZ2 = '#0B2FA8', CL = '#7FB0FF';
const clamp = (x: number) => Math.max(0, Math.min(1, x));
const seg = (t: number, a: number, b: number) => clamp((t - a) / (b - a));
const ease = (x: number) => 1 - Math.pow(1 - x, 3);

type Midia = {v?: string; de?: number; i?: string; fx?: number; fy?: number; vert?: boolean};
type Cena = {a: number; m?: Midia; chip?: string; tit?: string; curto?: string; nome?: string; cargo?: string; tipo?: 'abre' | 'fim'};
export type DadosJ = {cenas: Cena[]; end: number; data: string};
export const FIMJ = 4.6;

const Fontes = () => <style>{`
@font-face{font-family:M;src:url(${staticFile('fonts/montserrat-latin-900-normal.woff2')});font-weight:900}
@font-face{font-family:M;src:url(${staticFile('fonts/montserrat-latin-800-normal.woff2')});font-weight:800}`}</style>;

const PY = 290, PH = 1000; const ML = 100, MR = 170; // margens seguras do Reels // painel da imagem: largura toda, do topo (250) até 1390

const Drone: React.FC<{escuro?: number}> = ({escuro = 0.55}) => <AbsoluteFill>
  <OffthreadVideo src={staticFile('drone_sl.mp4')} muted style={{width: 1080, height: 1920, objectFit: 'cover', filter: 'saturate(0.8) contrast(1.05)'}} />
  <AbsoluteFill style={{background: `linear-gradient(180deg, rgba(1,12,48,${escuro}) 0%, rgba(1,12,48,${escuro * 0.5}) 40%, rgba(1,12,48,${escuro + 0.2}) 100%)`}} />
</AbsoluteFill>;

const Painel: React.FC<{m: Midia; lt: number}> = ({m, lt}) => {
  const z = 1 + lt * 0.006; // aproximação lenta, quase imperceptível
  const box: React.CSSProperties = {position: 'absolute', left: 0, top: PY, width: 1080, height: PH, overflow: 'hidden', background: '#000'};
  if (m.v) return <div style={box}><OffthreadVideo src={staticFile(m.v)} muted startFrom={Math.round((m.de || 0) * FPS)} style={{width: '100%', height: '100%', objectFit: 'cover', transform: `scale(${z})`}} /></div>;
  return <div style={box}><Img src={staticFile(m.i!)} style={{width: '100%', height: '100%', objectFit: 'cover', objectPosition: `${(m.fx ?? 0.5) * 100}% ${(m.fy ?? 0.5) * 100}%`, transform: `scale(${z})`}} /></div>;
};

// cabeçalho fixo: marca à esquerda, "RESUMO DO DIA | data" à direita
const Topo: React.FC<{data: string}> = ({data}) => <div style={{position: 'absolute', left: ML, right: MR, top: 196, height: 70, display: 'flex', alignItems: 'center', justifyContent: 'space-between'}}>
  <div style={{display: 'flex', alignItems: 'baseline', gap: 10, color: '#fff'}}>
    <span style={{fontFamily: 'M', fontWeight: 900, fontSize: 40, letterSpacing: '-0.02em'}}>Portal</span>
    <span style={{fontFamily: 'M', fontWeight: 800, fontSize: 34, color: CL, letterSpacing: '-0.02em'}}>luziense</span>
  </div>
  <div style={{display: 'flex', alignItems: 'stretch', fontFamily: 'M', fontWeight: 800, fontSize: 22, letterSpacing: '0.06em'}}>
    <div style={{background: '#fff', color: AZ, padding: '9px 14px'}}>RESUMO DO DIA</div>
    <div style={{background: AZ2, color: '#fff', padding: '9px 14px'}}>{data}</div>
  </div>
</div>;

// tarja inferior de telejornal: editoria + título
const Tarja: React.FC<{c: Cena; lt: number; digitar: boolean}> = ({c, lt, digitar}) => {
  const w = ease(seg(lt, 0.15, 0.5)), w2 = ease(seg(lt, 0.3, 0.7));
  const txt = c.tit || c.curto || '';
  const n = digitar ? Math.floor(Math.max(0, lt - 0.3) * 38) : txt.length;
  return <div style={{position: 'absolute', left: ML, right: MR, top: PY + PH - 110}}>
    <div style={{display: 'inline-block', background: CL, color: AZ, fontFamily: 'M', fontWeight: 900, fontSize: 30, letterSpacing: '0.08em', padding: '8px 18px', clipPath: `inset(0 ${100 - w * 100}% 0 0)`}}>{c.chip}</div>
    <div style={{background: '#fff', padding: '22px 28px 24px', borderLeft: `12px solid ${AZ2}`, boxShadow: '0 18px 40px rgba(0,0,0,.35)', clipPath: `inset(0 ${100 - w2 * 100}% 0 0)`}}>
      <div style={{fontFamily: 'M', fontWeight: 800, fontSize: c.tit ? 40 : 48, lineHeight: 1.15, color: AZ, letterSpacing: '-0.015em', minHeight: c.tit ? 92 : 0}}>{txt.slice(0, n)}</div>
    </div>
  </div>;
};

// crédito de quem aparece (nome e cargo), canto superior do painel
const Nome: React.FC<{c: Cena; lt: number}> = ({c, lt}) => {
  const w = ease(seg(lt, 0.4, 0.8)) * (1 - ease(seg(lt, 4.6, 5.0)));
  if (!c.nome || w <= 0) return null;
  return <div style={{position: 'absolute', left: ML, top: PY + PH - 290, clipPath: `inset(0 ${100 - w * 100}% 0 0)`}}>
    <div style={{background: AZ, color: '#fff', fontFamily: 'M', fontWeight: 900, fontSize: 38, padding: '10px 22px 8px', letterSpacing: '-0.01em'}}>{c.nome}</div>
    <div style={{display: 'inline-block', background: '#fff', color: AZ, fontFamily: 'M', fontWeight: 800, fontSize: 26, padding: '6px 22px'}}>{c.cargo}</div>
  </div>;
};

const Abre: React.FC<{lt: number; data: string}> = ({lt, data}) => {
  const a = ease(seg(lt, 0.1, 0.5)), b = ease(seg(lt, 0.35, 0.8));
  return <AbsoluteFill style={{alignItems: 'center', justifyContent: 'center'}}>
    <Img src={staticFile('luz_logo_c.png')} style={{width: 620, opacity: a, transform: `translateY(${(1 - a) * 30}px)`}} />
    <div style={{marginTop: 46, display: 'flex', fontFamily: 'M', fontWeight: 800, fontSize: 40, letterSpacing: '0.08em', clipPath: `inset(0 ${100 - b * 100}% 0 0)`}}>
      <div style={{background: '#fff', color: AZ, padding: '12px 22px'}}>RESUMO DO DIA</div>
      <div style={{background: AZ2, color: '#fff', padding: '12px 22px'}}>{data}</div>
    </div>
  </AbsoluteFill>;
};

const Fim: React.FC<{lt: number}> = ({lt}) => {
  const a = ease(seg(lt, 0.1, 0.5)), b = ease(seg(lt, 0.4, 0.9)), c = ease(seg(lt, 6.0, 6.4));
  return <AbsoluteFill style={{alignItems: 'center', justifyContent: 'center'}}>
    <Img src={staticFile('luz_logo_c.png')} style={{width: 620, opacity: a}} />
    <div style={{marginTop: 50, fontFamily: 'M', fontWeight: 800, fontSize: 40, color: '#fff', letterSpacing: '0.04em', opacity: b}}>portalonenews.com.br</div>
    <div style={{marginTop: 34, background: '#fff', color: AZ, fontFamily: 'M', fontWeight: 900, fontSize: 40, padding: '14px 30px', clipPath: `inset(0 ${100 - c * 100}% 0 0)`}}>SIGA E ATIVE AS NOTIFICAÇÕES</div>
  </AbsoluteFill>;
};

export const Jornal: React.FC<{d: DadosJ}> = ({d}) => {
  const f = useCurrentFrame(); const t = f / FPS; const C = d.cenas;
  const tFim = d.end + 0.3;
  let i = 0; for (let k = 0; k < C.length; k++) if (t >= C[k].a) i = k;
  const c = C[i]; const lt = t - c.a;
  // corte seco com leve deslize da imagem nova (8 quadros), como troca de VT
  const push = ease(seg(lt, 0, 0.27));
  const cheia = c.tipo === 'abre' || c.tipo === 'fim';
  return <AbsoluteFill style={{background: '#010c30'}}>
    <Fontes />
    <Sequence durationInFrames={Math.round(tFim * FPS)}>
      <Drone escuro={cheia ? 0.45 : 0.7} />
      {C.map((x, k) => x.m ? <Sequence key={k} from={Math.round(x.a * FPS)} durationInFrames={Math.max(1, Math.round(((C[k + 1]?.a ?? d.end) - x.a) * FPS))}>
        <div style={{position: 'absolute', inset: 0, transform: `translateX(${(1 - (k === i ? push : 1)) * 70}px)`, opacity: k === i ? 0.3 + 0.7 * push : 1}}><Painel m={x.m} lt={k === i ? lt : 0} /></div>
      </Sequence> : null)}
      {!cheia ? <Topo data={d.data} /> : null}
      {c.m ? <><Nome c={c} lt={lt} /><Tarja c={c} lt={lt} digitar={!!c.tit} /></> : null}
      {c.tipo === 'abre' ? <Abre lt={lt} data={d.data} /> : null}
      {c.tipo === 'fim' ? <Fim lt={lt} /> : null}
      {!cheia ? <div style={{position: 'absolute', left: ML, right: MR, top: PY + PH + 165, fontFamily: 'M', fontWeight: 800, fontSize: 26, color: 'rgba(255,255,255,.75)', letterSpacing: '0.06em'}}>PORTALONENEWS.COM.BR</div> : null}
    </Sequence>
    <Sequence from={Math.round(tFim * FPS)}><OffthreadVideo src={staticFile('fim-luziense.mp4')} muted /></Sequence>
  </AbsoluteFill>;
};
