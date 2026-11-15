const { chromium } = require('/opt/npm-tools/node_modules/playwright');
(async () => { const b = await chromium.launch({ args: ['--allow-file-access-from-files'] });
const p = await b.newPage({ viewport: { width: 1080, height: 1350 } });
await p.goto('file:///tmp/conv/convite.html'); await p.evaluate(() => document.fonts.ready); await p.waitForTimeout(400);
await p.screenshot({ path: '/home/claude/saida/convite-denuncia.png' }); await b.close(); })();
