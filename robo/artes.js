// Gera as artes de dados/lote.json (itens com 'titulo', 'portal', 'ed', 'foto').
// uso: node robo/artes.js   -> artes/AAAA-MM-DD/<id>.jpg e grava 'linhas' (tem que ser 3)
const {chromium}=require('playwright');const path=require('path');const fs=require('fs');
const R=path.resolve(__dirname,'..');
(async()=>{const L=JSON.parse(fs.readFileSync(R+'/dados/lote.json'));
const dia=new Date(Date.now()-3*3600e3).toISOString().slice(0,10);fs.mkdirSync(R+'/artes/'+dia,{recursive:true});
const b=await chromium.launch();const p=await b.newPage({viewport:{width:1080,height:1350}});
for(const n of L){ if(!n.titulo||!n.portal||!n.ed) continue;
 const u=new URL('file://'+path.resolve(__dirname,'modelo/moldura.html'));
 u.searchParams.set('portal',n.portal);u.searchParams.set('ed',n.ed);u.searchParams.set('ti',n.titulo);
 if(n.foto)u.searchParams.set('foto','file://'+path.resolve(R,n.foto));
 await p.goto(u.href);await p.evaluate(()=>document.fonts.ready);await p.waitForTimeout(250);
 n.linhas=+await p.evaluate(()=>document.body.dataset.linhas);
 n.arte='artes/'+dia+'/'+n.id+'.jpg';
 await p.locator('#arte').screenshot({path:R+'/'+n.arte,type:'jpeg',quality:90});
 n.url_arte='https://raw.githubusercontent.com/estudioeleve/portal-artes-feed/main/'+n.arte;
 console.log(n.id,'linhas:',n.linhas,n.linhas==3?'OK':'<<< REESCREVER O TÍTULO',n.arte)}
fs.writeFileSync(R+'/dados/lote.json',JSON.stringify(L,null,1));await b.close()})();
