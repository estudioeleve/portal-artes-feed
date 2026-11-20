import React from 'react';
import {AbsoluteFill, Img, OffthreadVideo, Sequence, staticFile, useCurrentFrame} from 'remotion';

// Matéria de TV de colaborador, genérica: dados vêm do JSON (props.d).
const FPS = 30;
const AZ = '#022078', AZ2 = '#0B2FA8', CL = '#7FB0FF';
const clamp = (x: number) => Math.max(0, Math.min(1, x));
const seg = (t: number, a: number, b: number) => clamp((t - a) / (b - a));
const ease = (x: number) => 1 - Math.pow(1 - x, 3);
const ML = 100, MR = 170;


const Fontes = () => <style>{`
@font-face{font-family:M;src:url(${staticFile('fonts/montserrat-latin-900-normal.woff2')});font-weight:900}
@font-face{font-family:M;src:url(${staticFile('fonts/montserrat-latin-800-normal.woff2')});font-weight:800}`}</style>;

type T = {a: number; chip: string; tit: string};

type N = {a: number; b: number; nome: string; cargo: string};

type D = {a: number; b: number; big: string; txt: string};

type R = {a: number; b: number; txt: string};


const Tarja: React.FC<{c: T; lt: number}> = ({c, lt}) => {
  const w = ease(seg(lt, 0.1, 0.45)), w2 = ease(seg(lt, 0.25, 0.65));
  const n = Math.floor(Math.max(0, lt - 0.3) * 40);
  return <div style={{position: 'absolute', left: ML, right: MR, top: 1270}}>
    <div style={{display: 'flex', clipPath: `inset(0 ${100 - w * 100}% 0 0)`}}>
      <div style={{background: CL, color: AZ, fontFamily: 'M', fontWeight: 900, fontSize: 28, letterSpacing: '0.08em', padding: '8px 18px'}}>{c.chip}</div>
      <div style={{background: AZ, color: '#fff', fontFamily: 'M', fontWeight: 900, fontSize: 28, letterSpacing: '-0.01em', padding: '8px 16px'}}>Portal <span style={{color: CL, fontWeight: 800}}>luziense</span></div>
    </div>
    <div style={{background: '#fff', padding: '20px 26px 22px', borderLeft: `12px solid ${AZ2}`, boxShadow: '0 18px 40px rgba(0,0,0,.35)', clipPath: `inset(0 ${100 - w2 * 100}% 0 0)`}}>
      <div style={{fontFamily: 'M', fontWeight: 800, fontSize: 40, lineHeight: 1.15, color: AZ, letterSpacing: '-0.015em', minHeight: 92}}>{c.tit.slice(0, n)}</div>
    </div>
  </div>;
};

const Nome: React.FC<{c: N; t: number}> = ({c, t}) => {
  const w = ease(seg(t, c.a, c.a + 0.4)) * (1 - ease(seg(t, c.b - 0.4, c.b)));
  if (w <= 0) return null;
  return <div style={{position: 'absolute', left: ML, top: 1085, clipPath: `inset(0 ${100 - w * 100}% 0 0)`}}>
    <div style={{background: AZ, color: '#fff', fontFamily: 'M', fontWeight: 900, fontSize: 38, padding: '10px 22px 8px', letterSpacing: '-0.01em'}}>{c.nome}</div>
    <div style={{display: 'inline-block', background: '#fff', color: AZ, fontFamily: 'M', fontWeight: 800, fontSize: 26, padding: '6px 22px'}}>{c.cargo}</div>
  </div>;
};

const Dado: React.FC<{c: D; t: number}> = ({c, t}) => {
  const w = ease(seg(t, c.a, c.a + 0.45)) * (1 - ease(seg(t, c.b - 0.35, c.b)));
  if (w <= 0) return null;
  return <div style={{position: 'absolute', left: ML, top: 1040, clipPath: `inset(0 ${100 - w * 100}% 0 0)`, display: 'flex', alignItems: 'stretch', boxShadow: '0 18px 40px rgba(0,0,0,.35)'}}>
    <div style={{background: AZ, color: '#fff', fontFamily: 'M', fontWeight: 900, fontSize: 64, padding: '14px 24px 10px', letterSpacing: '-0.03em', display: 'flex', alignItems: 'center'}}>{c.big}</div>
    <div style={{background: '#fff', color: AZ, fontFamily: 'M', fontWeight: 800, fontSize: 28, padding: '14px 22px', width: 330, lineHeight: 1.2, display: 'flex', alignItems: 'center'}}>{c.txt}</div>
  </div>;
};

const Rotulo: React.FC<{c: R; t: number}> = ({c, t}) => {
  const w = ease(seg(t, c.a, c.a + 0.3)) * (1 - ease(seg(t, c.b - 0.25, c.b)));
  if (w <= 0) return null;
  const pis = Math.floor(t * 2) % 2 === 0;
  return <div style={{position: 'absolute', left: ML, top: 205, clipPath: `inset(0 ${100 - w * 100}% 0 0)`, display: 'flex', alignItems: 'center', gap: 12, background: 'rgba(2,12,48,.78)', padding: '10px 18px', fontFamily: 'M', fontWeight: 800, fontSize: 26, color: '#fff', letterSpacing: '0.06em'}}>
    {c.txt.startsWith('CÂMERA') || (c as any).rec ? <div style={{width: 16, height: 16, borderRadius: 8, background: '#ff3b3b', opacity: pis ? 1 : 0.35}} /> : null}{c.txt}
  </div>;
};

// vinheta de abertura sobre as imagens mais fortes
const Abertura: React.FC<{t: number; d: any}> = ({t, d}) => {
  const I = d.abre.dur;
  const a = ease(seg(t, 0.05, 0.45)), b = ease(seg(t, 0.3, 0.75)), c = ease(seg(t, 0.9, 1.3));
  const sai = 1 - ease(seg(t, I - 0.3, I));
  const n = Math.floor(Math.max(0, t - 0.45) * 34);
  const tit = d.abre.tit;
  return <AbsoluteFill style={{opacity: sai}}>
    <div style={{position: 'absolute', left: 0, right: 0, top: 205, display: 'flex', justifyContent: 'center', opacity: a}}><Img src={staticFile('luz_logo_c.png')} style={{width: 330, filter: 'drop-shadow(0 6px 18px rgba(0,0,0,.6))'}} /></div>
    <div style={{position: 'absolute', left: ML, right: MR, top: 1120}}>
      <div style={{display: 'inline-block', background: '#E01B1B', color: '#fff', fontFamily: 'M', fontWeight: 900, fontSize: 30, letterSpacing: '0.1em', padding: '8px 18px', clipPath: `inset(0 ${100 - a * 100}% 0 0)`}}>DENÚNCIA</div>
      <div style={{background: '#fff', padding: '22px 26px 24px', borderLeft: `12px solid ${AZ2}`, clipPath: `inset(0 ${100 - b * 100}% 0 0)`, boxShadow: '0 18px 40px rgba(0,0,0,.4)'}}>
        <div style={{fontFamily: 'M', fontWeight: 900, fontSize: 56, lineHeight: 1.08, color: AZ, letterSpacing: '-0.025em', minHeight: 182}}>{tit.slice(0, n)}</div>
      </div>
      <div style={{marginTop: 14, display: 'inline-block', background: AZ, color: '#fff', fontFamily: 'M', fontWeight: 800, fontSize: 27, padding: '9px 18px', clipPath: `inset(0 ${100 - c * 100}% 0 0)`}}>{d.abre.local}</div>
      <div style={{marginTop: 12, fontFamily: 'M', fontWeight: 800, fontSize: 24, color: '#fff', letterSpacing: '0.06em', opacity: c, textShadow: '0 2px 10px rgba(0,0,0,.8)'}}>{d.abre.rep}</div>
    </div>
  </AbsoluteFill>;
};

const Fim: React.FC<{lt: number}> = ({lt}) => {
  const a = ease(seg(lt, 0.1, 0.5)), b = ease(seg(lt, 0.4, 0.9)), c = ease(seg(lt, 0.9, 1.3));
  return <AbsoluteFill style={{alignItems: 'center', justifyContent: 'center'}}>
    <Img src={staticFile('luz_logo_c.png')} style={{width: 620, opacity: a}} />
    <div style={{marginTop: 50, fontFamily: 'M', fontWeight: 800, fontSize: 40, color: '#fff', letterSpacing: '0.04em', opacity: b}}>portalonenews.com.br</div>
    <div style={{marginTop: 34, background: '#fff', color: AZ, fontFamily: 'M', fontWeight: 900, fontSize: 40, padding: '14px 30px', clipPath: `inset(0 ${100 - c * 100}% 0 0)`}}>SIGA E ATIVE AS NOTIFICAÇÕES</div>
  </AbsoluteFill>;
};

export const matDur = (d: any) => d.base + 3.2 + 4.6;
export const MateriaG: React.FC<{d: any}> = ({d}) => {
  const f = useCurrentFrame(); const t = f / FPS;
  const BASE = d.base, CARD = 3.2, I = d.abre.dur;
  const TARJAS: T[] = d.tarjas, NOMES: N[] = d.nomes, DADOS: D[] = d.dados || [], ROTULOS: R[] = d.rotulos || [];
  let ti = -1; for (let k = 0; k < TARJAS.length; k++) if (t >= TARJAS[k].a) ti = k;
  const corpo = t >= I && t < BASE;
  return <AbsoluteFill style={{background: '#010c30'}}>
    <Fontes />
    <Sequence durationInFrames={Math.round(BASE * FPS)}>
      <OffthreadVideo src={staticFile(d.video)} muted />
      <div style={{position: 'absolute', left: 0, right: 0, top: 0, height: 420, background: 'linear-gradient(rgba(0,0,0,.45),rgba(0,0,0,0))'}} />
      <div style={{position: 'absolute', left: 0, right: 0, top: 980, bottom: 0, background: 'linear-gradient(rgba(0,0,0,0),rgba(0,0,0,.6) 45%,rgba(0,0,0,.6))'}} />
      {t < I ? <Abertura t={t} d={d} /> : null}
      {corpo ? <div style={{position: 'absolute', right: MR - 40, top: 200}}><Img src={staticFile('luz_logo_c.png')} style={{width: 190, opacity: 0.92, filter: 'drop-shadow(0 4px 12px rgba(0,0,0,.6))'}} /></div> : null}
      {corpo ? ROTULOS.map((r, k) => <Rotulo key={k} c={r} t={t} />) : null}
      {corpo ? NOMES.map((r, k) => <Nome key={k} c={r} t={t} />) : null}
      {corpo ? DADOS.map((r, k) => <Dado key={k} c={r} t={t} />) : null}
      {corpo && ti >= 0 ? <Tarja c={TARJAS[ti]} lt={t - TARJAS[ti].a} /> : null}
      {corpo ? <div style={{position: 'absolute', left: ML, top: 1500, fontFamily: 'M', fontWeight: 800, fontSize: 24, color: 'rgba(255,255,255,.8)', letterSpacing: '0.06em'}}>PORTALONENEWS.COM.BR</div> : null}
    </Sequence>
    <Sequence from={Math.round(BASE * FPS)} durationInFrames={Math.round(CARD * FPS)}>
      <OffthreadVideo src={staticFile('drone_sl.mp4')} muted style={{position: 'absolute', width: '100%', height: '100%', objectFit: 'cover'}} />
      <AbsoluteFill style={{background: 'rgba(2,20,90,.62)'}} />
      <Fim lt={t - BASE} />
    </Sequence>
    <Sequence from={Math.round((BASE + CARD) * FPS)}><OffthreadVideo src={staticFile('fim-luziense.mp4')} muted /></Sequence>
  </AbsoluteFill>;
};
