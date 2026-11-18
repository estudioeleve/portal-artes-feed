import {bundle} from '@remotion/bundler';import {renderStill,selectComposition} from '@remotion/renderer';
const b=await bundle({entryPoint:'/tmp/portal/src/index.ts'});
const bx='/opt/pw-browsers/chromium_headless_shell-1243/chrome-headless-shell-linux64/chrome-headless-shell';
const c=await selectComposition({serveUrl:b,id:process.env.C||'TradSL',browserExecutable:bx});
for(const s of process.argv.slice(2)){await renderStill({composition:c,serveUrl:b,output:(process.env.OUT_DIR||'/tmp')+`/st_${s}.png`,frame:Math.round(+s*30),browserExecutable:bx});}
