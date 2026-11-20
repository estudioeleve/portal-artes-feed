import React from 'react';
import {AbsoluteFill, Img, OffthreadVideo, Sequence, staticFile, useCurrentFrame} from 'remotion';

// Resumo do dia em formato de telejornal (Israel, 17/11/2026): imagem grande, tarjas de TV, sem efeitos de "IA".
const FPS = 30;
const AZ = '#022078', AZ2 = '#0B2FA8', CL = '#7FB0FF';
const clamp = (x: number) => Math.max(0, Math.min(1, x));
const seg = (t: number, a: number, b: number) => clamp((t - a) / (b - a));
const ease = (x: number) => 1 - Math.pow(1 - x, 3);

type Midia = {v?: string; de?: number; i?: string; fx?: number; fy?: number; vert?: boolean; z?: number; cred?: string; inteira?: boolean; zi?: number; dolar?: boolean; t2?: number};
type Cena = {a: number; m?: Midia; chip?: string; tit?: string; curto?: string; nome?: string; cargo?: string; tipo?: 'abre' | 'fim'; lado?: 'd'; t2?: number; tit2?: string};
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

const Cred: React.FC<{t?: string}> = ({t}) => t ? <div style={{position: 'absolute', right: MR - 60, top: 22, fontFamily: 'M', fontWeight: 800, fontSize: 19, color: 'rgba(255,255,255,.85)', letterSpacing: '0.04em', textShadow: '0 2px 8px rgba(0,0,0,.8)'}}>{t}</div> : null;

// zoom na pessoa sem perder a foto: leva o rosto para o centro, sem passar das bordas da imagem
const zoomT = (m: Midia, lt: number) => {
  const W = 1080, H = PH - 170, ih = W * 720 / 1280; // fotos 16:9
  const k = ease(seg(lt, 0.05, 0.9)); const sc = 1 + ((m.zi ?? 1) - 1) * k + lt * 0.004;
  const px = ((m.fx ?? 0.5) - 0.5) * W, py = ((m.fy ?? 0.5) - 0.5) * H;
  const mx = (W * sc - W) / 2, my = Math.max(0, (ih * sc - H) / 2);
  const tx = Math.max(-mx, Math.min(mx, -px * sc)) * k, ty = Math.max(-my, Math.min(my, -py * sc)) * k;
  return `translate(${tx}px, ${ty}px) scale(${sc})`;
};

const Painel: React.FC<{m: Midia; lt: number}> = ({m, lt}) => {
  const z = (m.z ?? 1) * (1 + lt * 0.006); // aproximação lenta, quase imperceptível
  const box: React.CSSProperties = {position: 'absolute', left: 0, top: PY, width: 1080, height: PH, overflow: 'hidden', background: '#000'};
  const org = `${(m.fx ?? 0.5) * 100}% ${(m.fy ?? 0.5) * 100}%`;
  if (m.dolar) return <div style={box}><Dolar t2={m.t2} /></div>;
  if (m.v) return <div style={box}><OffthreadVideo src={staticFile(m.v)} muted startFrom={Math.round((m.de || 0) * FPS)} style={{width: '100%', height: '100%', objectFit: 'cover', objectPosition: org, transform: `scale(${z})`, transformOrigin: org}} /><Cred t={m.cred} /></div>;
  if (m.inteira && !m.zi) { const pan = `${Math.min(1, lt / 5) * 100}% ${(m.fy ?? 0.35) * 100}%`; return <div style={box}><Img src={staticFile(m.i!)} style={{width: '100%', height: '100%', objectFit: 'cover', objectPosition: pan, transform: `scale(${1 + lt * 0.004})`}} /><Cred t={m.cred} /></div>; }
  return <div style={box}><Img src={staticFile(m.i!)} style={{width: '100%', height: '100%', objectFit: 'cover', objectPosition: org, transform: `scale(${z})`, transformOrigin: org}} /><Cred t={m.cred} /></div>;
};

// gráfico do dólar (dados reais: fechamento do dólar comercial)
const PTS: [number, number, string][] = [[0, 5.45, '30/10'], [14, 5.67, '13/11'], [17, 5.79, ''], [18, 5.78, ''], [19, 5.87, ''], [20, 5.93, '19/11']];
const Dolar: React.FC<{t2?: number}> = ({t2}) => {
  const lt = useCurrentFrame() / FPS;
  const X0 = ML, X1 = 800, Y0 = 820, Y1 = 380, V0 = 5.40, V1 = 5.95;
  const px = (d: number) => X0 + (d / 20) * (X1 - X0), py = (v: number) => Y0 - ((v - V0) / (V1 - V0)) * (Y0 - Y1);
  const p = (x: number) => x < 0.5 ? 4 * x * x * x : 1 - Math.pow(-2 * x + 2, 3) / 2;
  const prog = p(seg(lt, 0.5, 3.4)); const head = prog * 20;
  const vis: [number, number][] = [];
  for (let k = 0; k < PTS.length; k++) { if (PTS[k][0] <= head) vis.push([PTS[k][0], PTS[k][1]]); else { const a = PTS[k - 1]; const f = (head - a[0]) / (PTS[k][0] - a[0]); vis.push([head, a[1] + (PTS[k][1] - a[1]) * f]); break; } }
  const hv = vis[vis.length - 1];
  const d = vis.map((q, k) => `${k ? 'L' : 'M'}${px(q[0]).toFixed(1)},${py(q[1]).toFixed(1)}`).join(' ');
  const area = d + ` L${px(hv[0]).toFixed(1)},${Y0} L${X0},${Y0} Z`;
  const valor = hv[1].toFixed(2).replace('.', ',');
  const fimL = ease(seg(lt, 3.3, 3.8)); const pulso = 0.5 + 0.5 * Math.sin(lt * 5);
  const intro = ease(seg(lt, 0, 0.5)); const nov = t2 ? ease(seg(lt, t2, t2 + 0.4)) : 0;
  return <AbsoluteFill style={{background: 'radial-gradient(120% 90% at 70% 20%, #0a2a8a 0%, #031247 55%, #010a2c 100%)'}}>
    <Img src={staticFile('r18/usd1.jpg')} style={{position: 'absolute', width: 1500, left: -180 + lt * 6, top: 230, transform: 'rotate(-9deg)', opacity: 0.16 * intro, filter: 'grayscale(0.4) blur(1.5px)'}} />
    <AbsoluteFill style={{background: 'linear-gradient(180deg, rgba(1,10,44,.55) 0%, rgba(1,10,44,0) 40%, rgba(1,10,44,.6) 100%)'}} />
    <div style={{position: 'absolute', left: ML, top: 58, fontFamily: 'M', fontWeight: 800, fontSize: 26, letterSpacing: '0.12em', color: CL, opacity: intro}}>DÓLAR COMERCIAL · FECHAMENTO DA SEMANA</div>
    <div style={{position: 'absolute', left: ML, top: 96, display: 'flex', alignItems: 'center', gap: 26, opacity: intro}}>
      <div style={{fontFamily: 'M', fontWeight: 900, fontSize: 150, color: '#fff', letterSpacing: '-0.04em', fontVariantNumeric: 'tabular-nums', lineHeight: 1}}><span style={{fontSize: 70, verticalAlign: 'top', marginRight: 8}}>R$</span>{valor}</div>
      <div style={{background: CL, color: AZ, fontFamily: 'M', fontWeight: 900, fontSize: 34, padding: '10px 16px', borderRadius: 6, clipPath: `inset(0 ${100 - fimL * 100}% 0 0)`}}>▲ 1,1%</div>
    </div>
    <div style={{position: 'absolute', left: ML, top: 268, fontFamily: 'M', fontWeight: 800, fontSize: 26, color: '#fff', background: AZ2, padding: '8px 16px', letterSpacing: '0.04em', clipPath: `inset(0 ${100 - nov * 100}% 0 0)`}}>MAIOR VALOR DESDE ABRIL DE 2025</div>
    <svg width={1080} height={1000} style={{position: 'absolute', left: 0, top: 0}}>
      <defs>
        <linearGradient id="ar" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stopColor={CL} stopOpacity="0.42" /><stop offset="1" stopColor={CL} stopOpacity="0" /></linearGradient>
        <filter id="gl"><feGaussianBlur stdDeviation="7" /></filter>
      </defs>
      {[5.4, 5.5, 5.6, 5.7, 5.8, 5.9].map((v, k) => <g key={k} opacity={intro}>
        <line x1={X0} x2={X1 + 20} y1={py(v)} y2={py(v)} stroke="rgba(255,255,255,.14)" strokeWidth={2} strokeDasharray={v === 5.4 ? '' : '6 8'} />
        <text x={X1 + 34} y={py(v) + 9} fill="rgba(255,255,255,.6)" fontFamily="M" fontWeight={800} fontSize={24}>{v.toFixed(2).replace('.', ',')}</text>
      </g>)}
      {PTS.filter(q => q[2]).map((q, k) => <text key={k} x={px(q[0])} y={Y0 + 46} textAnchor={k === 0 ? 'start' : k === 2 ? 'end' : 'middle'} fill="rgba(255,255,255,.7)" fontFamily="M" fontWeight={800} fontSize={24} opacity={intro}>{q[2]}</text>)}
      <path d={area} fill="url(#ar)" />
      <path d={d} fill="none" stroke={CL} strokeWidth={14} opacity={0.35} filter="url(#gl)" strokeLinejoin="round" strokeLinecap="round" />
      <path d={d} fill="none" stroke="#fff" strokeWidth={6} strokeLinejoin="round" strokeLinecap="round" />
      {prog > 0.01 ? <g>
        <circle cx={px(hv[0])} cy={py(hv[1])} r={26 + 10 * pulso * fimL} fill={CL} opacity={0.25} />
        <circle cx={px(hv[0])} cy={py(hv[1])} r={13} fill="#fff" stroke={CL} strokeWidth={5} />
      </g> : null}
    </svg>
    <div style={{position: 'absolute', left: px(20) - 150, top: py(5.93) - 92, width: 150, textAlign: 'center', fontFamily: 'M', fontWeight: 900, fontSize: 36, color: AZ, background: '#fff', borderRadius: 6, padding: '6px 0', opacity: fimL, transform: `translateY(${(1 - fimL) * 12}px)`}}>5,93</div>
    <div style={{position: 'absolute', right: MR - 60, top: 22, fontFamily: 'M', fontWeight: 800, fontSize: 19, color: 'rgba(255,255,255,.6)', letterSpacing: '0.04em'}}>Fonte: Banco Central / mercado</div>
  </AbsoluteFill>;
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
  const seg2 = !!(c.t2 && c.tit2 && lt >= c.t2);
  const w = ease(seg(lt, 0.15, 0.5)), w2 = ease(seg(lt, 0.3, 0.7));
  const txt = seg2 ? c.tit2! : (c.tit || c.curto || '');
  const n = seg2 ? Math.floor(Math.max(0, lt - c.t2! - 0.1) * 38) : digitar ? Math.floor(Math.max(0, lt - 0.3) * 38) : txt.length;
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
  return <div style={{position: 'absolute', ...(c.lado === 'd' ? {right: MR - 70, textAlign: 'right' as const} : {left: ML}), top: PY + PH - 290, clipPath: `inset(0 ${100 - w * 100}% 0 0)`}}>
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
  let j = i; while (j > 0 && C[j - 1].m && C[j - 1].tit === c.tit && C[j - 1].chip === c.chip && c.tit) j--;
  const lt2 = t - C[j].a;
  let jn = i; while (jn > 0 && C[jn - 1].m && C[jn - 1].nome === c.nome && c.nome) jn--;
  const ltn = t - C[jn].a; // tarja e crédito continuam quando só a imagem muda
  // corte seco com leve deslize da imagem nova (8 quadros), como troca de VT
  const push = ease(seg(lt, 0, 0.27));
  const cheia = c.tipo === 'abre' || c.tipo === 'fim';
  return <AbsoluteFill style={{background: '#010c30'}}>
    <Fontes />
    <Sequence durationInFrames={Math.round(tFim * FPS)}>
      <Drone escuro={cheia ? 0.45 : 0.7} />
      {C.map((x, k) => x.m ? <Sequence key={k} from={Math.round(x.a * FPS)} durationInFrames={Math.max(1, Math.round(((C[k + 1]?.a ?? d.end) - x.a) * FPS))}>
        <div style={{position: 'absolute', inset: 0, transform: 'none', opacity: 1}}><Painel m={x.m} lt={k === i ? lt : 0} /></div>
      </Sequence> : null)}
      {!cheia ? <Topo data={d.data} /> : null}
      {c.m ? <><Nome c={c} lt={ltn} /><Tarja c={c} lt={lt2} digitar={!!c.tit} /></> : null}
      {c.tipo === 'abre' ? <Abre lt={lt} data={d.data} /> : null}
      {c.tipo === 'fim' ? <Fim lt={lt} /> : null}
      {!cheia ? <div style={{position: 'absolute', left: ML, right: MR, top: PY + PH + 165, fontFamily: 'M', fontWeight: 800, fontSize: 26, color: 'rgba(255,255,255,.75)', letterSpacing: '0.06em'}}>PORTALONENEWS.COM.BR</div> : null}
    </Sequence>
    <Sequence from={Math.round(tFim * FPS)}><OffthreadVideo src={staticFile('fim-luziense.mp4')} muted /></Sequence>
  </AbsoluteFill>;
};
