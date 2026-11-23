import re,sys
s=open(sys.argv[1]).read()
r=lambda f:open(f).read().rstrip('\n')
def troca(s,a,b,n=1):
    c=s.count(a); assert c==n,(a[:60],c); return s.replace(a,b)
# 1 css antes do primeiro </style>
i=s.index('</style>'); s=s[:i]+r('css.txt')+'\n'+s[i:]
# 2 hero
a=s.index('<section class="dc-hero">'); b=s.index('</section>',a)+len('</section>')
s=s[:a]+r('hero.txt')+s[b:]
# 3 aviso
s=troca(s,'<p class="erro-envio" id="erro-envio" hidden></p>',r('aviso.txt'))
# 4 midia
a=s.index('<legend>Foto ou vídeo'); b=s.index('<div class="files" id="file-list"></div>',a)+len('<div class="files" id="file-list"></div>')
s=s[:a]+r('midia.txt')+s[b:]
# 5 botao
s=troca(s,'<button type="submit" class="submit">Enviar</button>','<button type="submit" class="submit">Enviar denúncia</button>')
# 6 js
a=s.index("    var drop = document.getElementById('drop')"); b=s.index("    syncId();\n  }catch(err)",a)+len("    syncId();\n")
s=s[:a]+r('js.txt')+'\n'+s[b:]
open(sys.argv[2],'w').write(s)
