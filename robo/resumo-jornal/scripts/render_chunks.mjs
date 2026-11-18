import {bundle} from '@remotion/bundler';import {renderMedia,selectComposition} from '@remotion/renderer';
const b=await bundle({entryPoint:'/tmp/portal/src/index.ts'});
const bx='/opt/pw-browsers/chromium_headless_shell-1243/chrome-headless-shell-linux64/chrome-headless-shell';
const c=await selectComposition({serveUrl:b,id:'JornalSL',browserExecutable:bx});
const N=c.durationInFrames, P=700;
const PAR=+(process.env.PAR||0),NP=+(process.env.NP||1);for(let s=0,ix=0;s<N;s+=P,ix++){if(ix%NP!==PAR)continue;const e=Math.min(N-1,s+P-1);const out=process.env.OUT_DIR+`/c${String(s).padStart(5,'0')}.mp4`;
 if((await import('fs')).existsSync(out)){continue}
 await renderMedia({composition:c,serveUrl:b,codec:'h264',outputLocation:out+'.tmp.mp4',browserExecutable:bx,concurrency:1,crf:22,x264Preset:'veryfast',muted:true,frameRange:[s,e],offthreadVideoCacheSizeInBytes:250*1024*1024});
 (await import('fs')).renameSync(out+'.tmp.mp4',out);console.log('chunk',s,e,N);}
console.log('ok');
