# Robô de notícias do Portal ONE News: regras de cada rodada

## ⛔ COMANDOS QUE NÃO PEDEM PERMISSÃO (Israel, 03/11/2026: "toda hora esse pedido de permissão... para com os atrasos nas postagens")
- **Nunca usar mais de um `cd` no mesmo comando Bash**: isso abre um pedido de permissão e atrasa os posts. Use `git -C <pasta>` e caminhos absolutos, ou entre na pasta uma vez só, em um comando separado.
- **Testado em 03/11/2026 13h30**: o comando único de abertura (`if ... git clone ... else ... fi; git config ...`), mesmo sem `cd`, abriu pedido de permissão nas 2 vezes em que rodou (21 minutos perdidos). `fetch`, `checkout -B` e `config` em comandos simples e separados não abriram. Sincronize sempre com comandos simples, um por chamada.
- Rodada sem demora: pautas, artes e primeiro post nos primeiros minutos da rodada.

## 🔍 IMAGEM NÍTIDA E NADA DE "VÍDEO" EM POST DE FOTO (Israel, 10/11/2026 ~08h30, bronca: "essa imagem não dá para ver nada... no título você colocou vídeo, sendo que é uma imagem estática... nós somos um portal de notícias sério")
- O post do Barreiro (10/11 07:52) saiu com um print escuro e borrado de vídeo, onde não dava para entender nada, e com o título começando por "Vídeo:". **Não pode mais.**
- **Imagem nítida**: no Instagram só vai foto em que dá para entender o assunto de primeira (boa luz, foco, boa resolução). Print de vídeo, frame escuro, noturno, tremido ou borrado: **não use**. Busque outra foto do fato (`coletar.py <URL> --anexar`); se não houver, a notícia fica **só no site**.
- **Nunca escrever "Vídeo", "Veja o vídeo", "Assista" ou "Imagens mostram" no título da arte** (nem chamar o leitor para um vídeo na legenda): o post é uma foto estática. Diga o fato, não o formato da fonte.
- Ao olhar cada arte (Read) antes de publicar, pergunte também: **a imagem está nítida e dá para entender o que é?** E: **o título fala de vídeo?** Se sim em qualquer um dos problemas, não publique.

## 🖋️ COLUNAS EM ZIP: PUBLICAR NA HORA (Israel, 09/11/2026: "sempre que eu te enviar, você, na hora, posta nas colunas de cada um")
- O Israel manda na conversa um zip (feito pelo ChatGPT) com `manifesto.json`, `materias/*.txt` e `imagens/*.png`, para as colunas de **Israel Fernandes**, **Jota Bertoldo (J. Bertoldo)** e **Paulo Alexandre**.
- Na hora: `python3 robo/colunas.py <arquivo.zip>` e depois `git add -A && git commit && git push`. Vai para `dados/colunas.json` (não para o site.json, nem para o Instagram) e o site publica no nome do colunista em até 15 minutos.
- Precisa do tema **2.4.4** instalado no WordPress (`tema/portal-one-2.4.4.zip`). Se faltar o usuário do colunista, o painel "Robô de notícias" avisa e a coluna espera.
- Não mexer no texto do colunista; manter o crédito da imagem.
- **Por enquanto só no site** (Israel, 09/11/2026 14h50). Instagram das colunas só depois que ele mandar a moldura específica de colunas; até lá, nada de coluna no Instagram.
- Zip repetido (mesmo conteúdo já enviado): o `colunas.py` avisa "já está no colunas.json" e não duplica.

## ⚽ JOGO GRANDE DE ATLÉTICO OU CRUZEIRO: COBERTURA ESPECIAL, PRINCIPALMENTE NO SITE (Israel, 07/11/2026 ~19h40, bronca: "O Atlético jogando um jogo muito importante e você não deu uma atenção... deveria fazer uma cobertura especial... colocar um placar online")
- Jogo decisivo (semifinal, final, clássico, jogo que vale título/vaga/rebaixamento) do time principal de Atlético ou Cruzeiro: **a rodada que pegar o jogo faz cobertura especial**, não deixa "para a próxima rodada".
- **Site**: matéria **"Placar ao vivo"** assim que a rodada começar com o jogo em andamento (placar no topo, o que o time precisa, gols, cartões, mudanças, números), buscando nos sites que transmitem lance a lance (Lance!, VAVEL, ge, Itatiaia, O Tempo/No Ataque, Terra). A cada espera entre posts, **publique uma atualização nova** (o site não atualiza matéria antiga: cada atualização é um item novo, com título diferente para a trava REPETIDO não barrar). No fim do jogo: matéria do resultado e, depois, repercussão (técnico, jogadores, próximo jogo).
- **Instagram**: gol importante (se a rodada estiver no ar) e **resultado logo após o apito final**, com arte e foto do jogo.
- Antes do jogo, no dia: prévia com horário, onde assistir e o que cada time precisa.

## 📸 FOTO ATUAL E EXATA (Israel, 09/11/2026 ~10h40, bronca: "Foto antiga de um jogador que não está mais no clube... presta atenção nas imagens")
- A matéria do Galo "seis jogos sem vencer" saiu no site com foto antiga do Hulk, que não está mais no Atlético. **Não pode.**
- Antes de usar qualquer foto (Instagram **e** site), olhe a imagem (Read) e confira: é do fato/jogo atual? A pessoa ainda está no clube/cargo? Foto de arquivo de banco de agência costuma ser antiga: desconfie.
- Esporte: use foto **do jogo ou do treino da semana** (Agência Brasil, site do clube, ge, Itatiaia). Se não achar foto atual, busque outra matéria do mesmo fato (`coletar.py <URL> --anexar`) ou não publique.

## ⏱️ NOTÍCIA EM TEMPO REAL (Israel, 08/11/2026 ~23h: "você está fazendo matéria hoje que aconteceu há 2 dias") — manda mais que as outras regras de escolha de pauta
- **Olhe o horário de cada matéria** (campo `hora` do radar e a data dentro do texto). Pegue o que **acabou de acontecer**: aconteceu, posta. Fato de 2 dias atrás **não entra** (nem no Instagram, nem no site), mesmo que a matéria tenha sido publicada hoje. Exceção só se houver fato novo de verdade (ex.: prisão, resultado), e o título deixa claro o que é novo.
- **Urgente posta na hora**, sem esperar o intervalo.
- **Chuva**: se estão saindo muitas matérias de chuva, é sinal de que o assunto está quente: **poste** (BH, Grande BH e principalmente Santa Luzia), sempre com fonte.
- **Atlético e Cruzeiro**: mantenha abastecidas as abas `atletico` e `cruzeiro` do site (categoria `atletico`/`cruzeiro`), só time principal, notícia do dia.
- **Imagens do Instagram**: olhe cada arte com atenção antes de publicar (assunto no centro, sem borda, sem zoom estranho, título em 3 linhas de verdade; confira a olho, porque o `artes.js` às vezes diz 3 e a arte mostra 4).
- **O que o Israel mandar na conversa sai na hora** (ver regra de 05/11). E se o radar trouxer matéria sobre o mesmo fato que ele mandou, **não poste de novo**.
  - 10/11/2026 12h: laudo do caso Beatriz Vitória (Jaboticatubas, "enterrada viva") já saiu no Instagram e no site a pedido dele. **Não repetir** se aparecer em outro veículo.

## 🎯 PONTO PRINCIPAL SEMPRE NO CENTRO DA ARTE (Israel, 07/11/2026 10h54, bronca: "Quantas vezes eu vou ter que pedir... o ponto principal centralizado?")
- Saiu o post do Prospera MEI (09h35) com a pilha de moedas colada na borda esquerda e o resto da arte vazio. **Não pode mais.**
- Vale para **toda** arte do Instagram, com ou sem pessoa: o **assunto principal** (pessoa, objeto, veículo, prédio, placa, animal) fica **no meio da moldura**, inteiro e visível acima do título.
- O `robo/colagem.py` agora acha o assunto também **sem rosto** e grava o `foco` para centralizá-lo. Se o assunto estiver colado na borda da foto, ele recorta a foto em volta do assunto; se nem assim der (zoom alto), marca `assunto_borda` e o **`links.py` bloqueia** ("ASSUNTO NA BORDA: NÃO PUBLIQUE"): troque a foto ou deixe a notícia só no site.
- Depois do artes.js, **olhe cada arte (Read) e pergunte: o ponto principal está no centro?** Se não estiver, ajuste `foco`/`zoom` à mão ou troque a foto. Foto de banco de imagens com objeto num canto e fundo vazio (moedas, calculadora, carrinho): troque.

## 🖼️ AJUSTE DA IMAGEM NA MOLDURA: SEM BORDA E PESSOA NO CENTRO (Israel, 06/11/2026 17h40: "mais uma vez... coloca aí como regra... tá ficando borda... se tiver uma pessoa na imagem, coloca a pessoa no centro")
- Ele mandou print do post da Lukinha Automóveis: a foto era uma montagem de duas e a **faixa da segunda foto aparecia na lateral direita** da arte. Isso não pode mais acontecer.
- **Obrigatório em todo item do Instagram**, depois do `coletar.py` e antes do `artes.js`: `python3 robo/colagem.py <id> <id> ...`. Ele:
  1. detecta **montagem de duas fotos** (emenda vertical ou horizontal) e **fica só com uma parte** (a que tem rosto; senão a maior). Para forçar: `--lado esq|dir|cima|baixo`;
  2. tira **faixa lisa de qualquer cor** na borda (tarja azul, branca, preta);
  3. acha o **rosto** e grava o `foco` para deixar **a pessoa no centro** da moldura.
- Depois, **olhe a arte (Read)**: nenhuma faixa de outra foto na lateral, nenhuma borda, pessoa no meio, rosto inteiro. Se o detector errar, ajuste `foco`/`zoom` à mão ou recorte com `--lado`. Na dúvida, troque a foto.

## 🔴 TODA MATÉRIA DE SANTA LUZIA NO SITE VAI TAMBÉM PARA O INSTAGRAM (Israel, 06/11/2026: "é obrigatório... com urgência... a nossa grande prioridade é Santa Luzia")
- **Santa Luzia não pode ser "só no site".** Toda matéria de Santa Luzia que for para o site vai para o Instagram, a mesma matéria, com moldura `luziense`, **na frente de todos os outros posts da rodada**. Nunca marque `so_site` em matéria de Santa Luzia.
- **No começo de cada rodada**, procure em `dados/site.json` as matérias de Santa Luzia (categoria `santa-luzia` ou título com o nome da cidade) dos últimos 2 dias que não têm registro de Instagram em `dados/historico.json` e publique primeiro.
- Continuam valendo as travas (REPETIDO, FOTO REPETIDA), a moldura e o intervalo entre posts. Se a trava barrar uma matéria de Santa Luzia, anote em `dados/erros.txt` para o Israel decidir.

## ⚡ NOTÍCIA QUE O ISRAEL MANDA NA CONVERSA SAI NA HORA (Israel, 05/11/2026)
- **"Portal Luziense" (ou Política, Comércio) quer dizer a moldura, não outra conta** (Israel, 10/11/2026): tudo é postado no **@portalonenews**, inclusive vídeo para Reels (`create_video_post`).
- "Notícias que eu te mandar aqui você pode postar na hora, não precisa colocar em programação." Publique assim que preparar, **sem esperar o intervalo entre posts**. Se ele disser "só no site", vai só no site, também na hora; senão, Instagram e site. Continuam valendo as regras de moldura, título, foto e legenda.

## 🌧️ CHUVA EM BH E REGIÃO: ATENÇÃO MÁXIMA, PRINCIPALMENTE SANTA LUZIA (Israel, 05/11/2026)
- "Quero que você dê uma atenção muito grande nas chuvas em Belo Horizonte e região metropolitana, principalmente em Santa Luzia." **No começo de cada rodada e em cada espera entre posts**, procure chuva, temporal, granizo, alagamento, enxurrada, queda de árvore, deslizamento, falta de luz, alerta da Defesa Civil (radar e `WebSearch`).
- Notícia urgente de chuva **passa na frente da programação e sai na hora**, sem esperar o intervalo. Só publique o que tiver fonte: não invente estrago nem bairro.

## 🚀 CARGA TOTAL E FOCO EM SANTA LUZIA (pedido do Israel, 04/11/2026) — manda mais que as outras regras de volume e de tema
A redução provisória de 01/11 acabou. "Nós estamos falhando em Santa Luzia."
- **Volume**: **100 posts por dia no Instagram**: **6 posts por rodada** (a rodada roda de hora em hora, das 6h às 23h), um a cada 8 a 10 minutos. **Notícias só para o site: 8 por rodada.** Se o dia estiver atrasado, 7 posts.
- **Ordem de escolha das pautas** (campo `prio` do `dados/radar.json`; o radar imprime a contagem):
  1. **Santa Luzia (`prio` 1), sempre primeiro**: toda notícia real de Santa Luzia MG que aparecer entra (Instagram e/ou site), moldura `luziense`, até 2 dias para trás. Meta: **pelo menos 2 posts e 3 notícias de site por rodada**, quando houver notícia de verdade.
  2. **Vizinhas e Vetor Norte (`prio` 2)**: Vespasiano, São José da Lapa, Ribeirão das Neves, Sabará, Jaboticatubas, Taquaraçu de Minas, Nova União, Pedro Leopoldo, Matozinhos, Lagoa Santa, Confins, Caeté.
  3. **BH, em especial Venda Nova e seus bairros, região Norte, trânsito, metrô, estações de metrô e de ônibus, MOVE (`prio` 3)**.
  4. **Comércio** (abaixo). Depois política da região e de BH, e o resto. Mundo e Brasília só o que for muito importante.
- **Santa Luzia não pode ser inventada nem esticada**: o radar tira a maioria das homônimas (PB, MA, RO...), mas **abra e confira que é a de Minas**. Não havendo notícia real, não force: siga para a próxima prioridade. Procure também com `WebSearch` (bairros, Prefeitura, Câmara, PM, Bombeiros, Copasa, Cemig, MG-020, Terminal MOVE).
- **Notícia viral**: dê preferência ao que as pessoas comentam e compartilham: flagrante em vídeo, denúncia de moradores, problema de bairro, animal, salvamento, história curiosa, "viralizou nas redes", trânsito travado agora. Título chamativo, **mas sempre verdadeiro**: valem as regras de Título e Cuidados, sem exagero que engane, sem imagem chocante e sem expor menor.
- **Comércio: aumentar para 10 a 15 por dia** no Instagram (mais o site), preferindo Santa Luzia e a região. Falar com **lojistas e pequenos empreendedores**: vendas de fim de ano e Natal, Black Friday, 13º, temporários, economia para o pequeno negócio, benefícios e crédito para lojistas, MEI, Simples, cursos gratuitos (Sebrae, CDL, ACISL, Fecomércio). Notícia de comércio de outro estado só se servir de verdade ao comerciante daqui. Tem que ajudar o comerciante: diga o que ele pode fazer, até quando e onde.
- Continuam valendo: **Plantão Fake proibido**, menos Betim, esporte só time principal de Atlético e Cruzeiro, não repetir notícia, nunca post sem foto, concorrentes.
- Não mandar resumos nem avisos (nem do site, nem do Instagram).

## ⚠️ FOCO REGIONAL: VETOR NORTE EM PRIMEIRO, MENOS BETIM (Israel, 02/11/2026 ~19h35) — manda mais que as outras regras de região
- "Tá colocando Betim demais." **Menos notícias de Betim**: só quando for realmente forte, nunca como escolha fácil para cumprir a cota regional.
- Ordem de prioridade: **1º Santa Luzia (em primeiro lugar de tudo)**; depois **Vespasiano, Pedro Leopoldo, Jaboticatubas, Matozinhos e São José da Lapa**;
  depois **Belo Horizonte, principalmente região Norte e Venda Nova**; só então os outros temas e cidades.
- Antes de escolher pauta, procure primeiro por essas cidades no radar (e com busca própria, se o radar não trouxer). Cuidado: "Santa Luzia" no radar pode ser
  de outro estado (MA, PB, AL): confira que é a de Minas.
- **Esporte: mesclar Cruzeiro e Atlético** (a mensagem dele foi cortada em "cruzeiro e"; entendido como Cruzeiro e Atlético, como na regra de esporte abaixo).

## ⛔ ASSUNTO PROIBIDO: PLANTÃO FAKE EM SANTA LUZIA (Israel, 01/11/2026 22h)
- **Nada** sobre a Operação Plantão Fake / plantões "fantasmas" no Hospital Municipal Madalena Parrillo Calixto ou na
  UPA São Benedito de Santa Luzia: **nem no Instagram, nem no site**, de nenhuma fonte, nem atualização do caso.
- A matéria 231100_39 (20h11) foi tirada do `dados/site.json` a pedido dele. A trava `robo/repetido.py` agora bloqueia
  o assunto sozinha (coletar, site.py e links.py imprimem REPETIDO/PROIBIDO). Se aparecer no radar, pule.

## ⚠️ ESPORTE (bronca do Israel, 01/11/2026 16h20) — manda mais que as outras regras de esporte
- "Ninguém quer saber de futebol feminino, ninguém quer saber de sub-20." **Não publicar** futebol feminino, sub-20,
  base nem categorias menores (nem no Instagram nem no site), mesmo de Cruzeiro ou Atlético.
- O que importa: **time principal de Atlético e Cruzeiro** — jogos grandes (prévia no dia com horário e onde assistir,
  resultado logo depois do jogo), contratações, lesões de titulares, técnico, decisões. Equilibrar Atlético e Cruzeiro:
  não postar só de um. Ex.: Grêmio x Atlético, semifinal da Copa do Brasil, 01/11 18h30 (a prévia saiu em 31/10 22h49; falta o resultado depois do jogo, ~20h30).
- Se não houver notícia de esporte que interesse de verdade, **não poste esporte** (as vagas vão para outros temas).

## ⛔ NUNCA REPETIR FOTO NO FEED (Israel, 05/11/2026: "Não deixa acontecer novamente"; a mesma foto da fachada da ALMG saiu lado a lado, 07h03 e 07h13)
- **Não usar no Instagram foto já usada nos últimos 3 dias**, mesmo em notícia diferente e mesmo com outro corte. A trava `robo/foto_repetida.py` roda dentro do `links.py`: se ele imprimir **FOTO REPETIDA**, **não publique**: troque a foto, gere a arte de novo e faça o push.
- **Foto de arquivo do mesmo lugar também não**, ainda que seja outra foto (fachada da ALMG, Câmara, Prefeitura, Mineirão, sede de órgão): a trava só pega a mesma imagem, então confira a olho os **12 últimos posts** antes de escolher a foto. Se a matéria só tiver foto genérica já usada, busque outra (`coletar.py <URL> --anexar`) ou deixe a notícia só para o site.
- Vale também para rodadas diferentes e sessões manuais: antes de publicar, olhe `dados/historico.json` e as artes do dia.

## ⛔ NUNCA POST SEM MOLDURA (Israel, 04/11/2026 ~20h: "Postagem sem o padrão????", saiu a foto crua das pichações de Vespasiano)
- Vale para **qualquer sessão**, automática ou manual: no Instagram só vai a **arte de `artes/`** (moldura, etiqueta e título em 3 linhas), com o link do `links.py`.
  **Nunca** publicar imagem de `site/` ou `fotos/`: essas são fotos cruas, só para o site.

## ⚠️ NUNCA POST SEM FOTO (01/11/2026: o pendente das Cabulosas saiu com a moldura vazia)
- A foto de item pendente pode não estar no repositório (fotos/ não vai para o GitHub). **Nunca rode o artes.js de novo
  num pendente**: use a arte que já está no GitHub. O `artes.js` agora pula item sem foto e não refaz arte de pendente.
- **Antes de cada `create_image_post`, abra a arte (Read) e confira que tem foto.** Moldura vazia/cinza = não publique.


Este arquivo é a instrução de cada rodada automática. Quem roda é o Claude, numa sessão agendada.
Tudo o que o Israel vê é em português.

## Autorização
- O Israel autorizou em 31/10/2026 a publicação **automática** de até **100 posts por dia, todos os dias**, no Instagram
  **@portalonenews**, sem aprovação prévia, pelo Windsor.ai (conector `instagram`, conta `17841461417663539`, ação `create_image_post`).
  Vale a partir de 31/10/2026 (por volta das 19h).
- Passando de 100 posts no dia: **não publicar nada**. Encerrar a rodada. O contador zera à meia-noite (America/Sao_Paulo).

## Meta e ritmo
- Meta do dia: **100 posts** (ver CARGA TOTAL no topo), espaçados. Nunca vários de uma vez: **um post a cada 8 a 12 minutos** dentro da rodada.
- Horários: a grande maioria entre **6h e meia-noite** (5 posts por rodada; se o dia estiver atrasado, até 6).
  Madrugada (0h às 5h59): volume menor, **2 posts por rodada**, com intervalo maior (12 a 20 minutos) e legendas mais curtas.
- Distribuição das 100 do dia:
  - **Comércio: 5 a 10 posts** (bloco `comercio`), conforme houver notícia relevante. Se não houver, as vagas vão para outros temas.
  - **Pelo menos 30% de Santa Luzia + região** (bloco `rmbh`): Santa Luzia, Vespasiano, Pedro Leopoldo, Matozinhos,
    Jaboticatubas, Ribeirão das Neves e Venda Nova (e demais cidades da Grande BH).
    Dentro da cota regional, **Santa Luzia vem primeiro e com destaque** (Portal Luziense).
    Dar atenção a **transporte público** e **ocorrências policiais** da região.
  - Resto: Belo Horizonte (incluindo polícia e ocorrências), Minas Gerais, Brasil, **política (todo o Brasil)**, entretenimento,
    esporte (Atlético, Cruzeiro, América; **futebol de várzea e Copa Itatiaia** quando houver), saúde, educação, serviços
    e qualquer tema que tenha moldura.
  - Confira com `python3 robo/historico.py --resumo` e compense na rodada a cota que estiver abaixo.
- Priorizar matérias de impacto, interesse do público e potencial de visualizações.
- 28/10/2026: eleição da Mesa Diretora da Câmara Municipal de Santa Luzia. Se sair resultado em fonte confiável
  (site da Câmara, g1, O Tempo, Itatiaia, Estado de Minas), é prioridade. Se não achar, não inventar; o Israel faz à mão.

## Molduras (portal) e editoria
- `portal`: `luziense` para notícias de **Santa Luzia** (Portal Luziense); `politica` para **qualquer notícia política**
  (STF, Brasília, Congresso, Minas Gerais, prefeituras, câmaras, de qualquer lugar) (Portal Política);
  `comercio` para notícias de **comércio/empreendedorismo** (Portal Comércio, faixa laranja); `one` para todo o resto.
- Todas são publicadas no **@portalonenews** (a collab com os outros perfis o Israel coloca depois, à mão).
- `ed` (etiqueta em caixa alta): BELO HORIZONTE, REGIÃO METROPOLITANA, SANTA LUZIA, MINAS GERAIS, BRASIL, MUNDO,
  ENTRETENIMENTO, ESPORTE, ATLÉTICO, CRUZEIRO, AMÉRICA, SAÚDE, EDUCAÇÃO, LUTO, URGENTE, VIRALIZOU, POLÍTICA, COMÉRCIO,
  TRANSPORTE, POLÍCIA, VÁRZEA, COPA ITATIAIA,
  ou o nome da cidade da Grande BH (ex.: CONTAGEM, BETIM, VESPASIANO, LAGOA SANTA, SABARÁ).

## Portal Comércio (10 a 15 por dia, ver CARGA TOTAL no topo)
- Público: comerciantes, empreendedores e empresários. Preferência por **Santa Luzia** quando houver.
- Pautas: oportunidades para o comércio e o empreendedor; benefícios para empresários (crédito, programas, MEI, impostos);
  comércio no fim de ano (horários, 13º, Black Friday, Natal); datas festivas; expectativas de vendas; agenda do comércio
  (CDL, ACISL, Sebrae, Fecomércio, feiras).
- Moldura `comercio`, `ed` COMÉRCIO (ou SANTA LUZIA quando for de lá), `categoria` do site `comercio`, `bloco` `comercio`.
- Não publicar propaganda disfarçada de notícia: tem que ter fato e fonte.
- Moldura do feed (31/10/2026, pedido do Israel): a do Canva "Feed comercio" (canva.link/fbyi7bmmn43h5gm), já aplicada no
  `robo/modelo/moldura.html` (classe `.c`). A moldura de Reels não serve para post de imagem.

## Foto na arte (Israel, 01/11/2026: "nunca deixar borda" e "fazer o motivo da matéria aparecer")
- **Nunca deixar borda** na arte: a foto tem que preencher a moldura inteira. O `coletar.py` já roda o `robo/aparar.py`
  (tira faixas pretas, brancas ou cinza da foto); se ainda sobrar borda, faixa ou foto dentro de foto, aumente o `zoom` ou troque a foto.
- **O assunto da matéria tem que aparecer** (ex.: jacaré na Pampulha saiu só com o rabo, em 01/11). Foto deitada vira retrato
  e perde os lados: veja onde está o assunto e ajuste no item do `dados/lote.json`:
  - `foco`: posição do assunto, horizontal e vertical (ex.: `"85% 50%"` = assunto à direita; padrão `"center 25%"`);
  - `zoom`: 100 a 250 (aproxima mantendo a moldura cheia).
  Montagem/colagem de duas fotos lado a lado: escolha o lado com o assunto (`foco` `"0% 50%"` ou `"100% 50%"`) ou outra foto.
- Ao olhar as artes (passo 6), confira estes dois pontos em **todas**. Se não der para mostrar o assunto, não publique com aquela foto.

## ⚠️ ZOOM E CORTE: A IMAGEM TEM QUE FICAR BOA (Israel, 06/11/2026: "algumas imagens estão aparecendo borda... outras com um zoom muito alto")
- **Imagem com duas partes (uma pessoa de um lado, outra imagem do outro): priorize uma.** Nunca deixe o corte no meio das duas, partindo as partes principais. Use `foco` no lado escolhido (`"0% 50%"` ou `"100% 50%"`).
- **Nada de zoom alto**: não corte rosto, letreiro, logomarca nem o assunto principal. Se a foto deitada não cabe sem cortar o que importa, troque a foto. **Nada de borda** (faixa preta, branca ou cinza).
- Olhe cada arte antes de publicar e pense se ficou boa de ver, não só se a trava passou. Vale para o Instagram.

## Título (vai na arte)
- Sempre **exatamente 3 linhas** na moldura (cerca de 80 a 98 caracteres). O `robo/artes.js` mede: se não der 3, reescreva e gere de novo.
- Texto próprio, factual, sem exagero nem sensacionalismo enganoso. Nunca copiar o título original.
- Português do Brasil, sem palavras em inglês quando houver equivalente.

## Legenda
- Atrativa e informativa: 3 a 6 parágrafos curtos (madrugada: 2 a 3), abre com emoji + frase de impacto, depois os principais
  detalhes (quem, o quê, onde, quando, por quê) para o público entender o que aconteceu, sem copiar frases da fonte.
- Hashtags (3 a 5, sempre `#PortalONENews`) vêm **antes** do fecho. A legenda termina **sempre** exatamente assim:
  ```
  Fonte da foto: <crédito>
  Fonte da matéria: <veículo>
  Matéria completa no nosso portal: www.portalonenews.com.br
  ```
- Sem outra fonte confiável (pauta de concorrente), a linha `Fonte da matéria:` sai fora (ver Concorrentes).
- Não marcar perfis com @.

## Site (portalonenews.com.br, WordPress)
- Toda notícia publicada no Instagram também vai para o site. O site busca o arquivo `dados/site.json` sozinho a cada 15 minutos.
- Para cada item, preencha também no `dados/lote.json`:
  - `linha_fina`: uma frase de resumo (até 160 caracteres);
  - `categoria`: um destes: santa-luzia, belo-horizonte, regiao-metropolitana, contagem, betim, vespasiano, lagoa-santa,
    ribeirao-das-neves, sabara, pedro-leopoldo, matozinhos, jaboticatubas, venda-nova, comercio, minas-gerais, politica, brasil, mundo, esporte, atletico, cruzeiro, america, entretenimento,
    saude, educacao, luto;
  - `texto_site`: a matéria completa em HTML simples (`<p>...</p>`), 4 a 7 parágrafos, 250 a 450 palavras, com texto próprio
    (nunca copiar frases da fonte), na ordem: o fato principal, detalhes, o que disseram as autoridades/envolvidos, o que acontece agora;
  - `credito_site`: o crédito da foto (ex.: "Divulgação/PMMG");
  - `fonte_site`: o veículo de origem (mesma regra dos concorrentes: nunca citar os concorrentes).
- Depois rode `python3 robo/site.py <id> <id> ...` (antes do passo 7, para a foto do site subir junto).

## Notícias só para o site (pedido do Israel, 31/10/2026: "o máximo de notícias que conseguir, de todos os temas")
- Além dos posts do Instagram, cada rodada escreve notícias **só para o site** (não vão para o Instagram e não contam nos 100 do Instagram).
  Meta por rodada: **8** entre 6h e meia-noite; **3** na madrugada. Dá cerca de 160 por dia a mais no site, além das ~100 que também vão para o Instagram.
- Faça essas notícias **enquanto espera** os 8 a 12 minutos entre um post e outro do Instagram (não atrasa nada).
  Se o Instagram já bateu 100 no dia, a rodada faz só estas.
- Todos os temas, com a mesma divisão: pelo menos 30% Santa Luzia + região, Comércio (preferir Santa Luzia), política, BH, Minas, Brasil,
  mundo, esporte, educação, saúde, entretenimento. O site tem blocos próprios para Santa Luzia (Portal Luziense), Comércio e Política:
  manter esses três sempre abastecidos ao longo do dia.
- Mesmas regras de texto e de concorrentes da seção Site e Concorrentes (texto próprio, 250 a 450 palavras, nunca copiar frases,
  nunca citar concorrente, não inventar nada que a fonte não diga).
- **Foto: toda notícia do site tem foto (decisão do Israel, 31/10/2026).** Use a foto da matéria de origem e dê o crédito com o
  nome do veículo, por exemplo "Foto: João Silva/Itatiaia" ou "Foto: Reprodução/O Tempo" (campo `credito_site`).
  Prefira foto oficial ou de divulgação quando houver (Agência Brasil, Agência Minas, prefeituras, polícias, assessorias).
  **Nunca** usar foto dos concorrentes (Jornal Virou Notícia, Portal Impacto, Vetor Norte, RM Notícias).
  Se a matéria não tiver foto, busque outra matéria do mesmo fato com foto (`coletar.py <URL> --anexar`); se não achar, **não publique** essa notícia.
- Como fazer: colete com `python3 robo/coletar.py <n> ... --anexar`, marque cada item com `"so_site": true` e preencha `titulo`
  (não precisa caber em 3 linhas), `bloco`, `portal`, `ed`, `linha_fina`, `categoria`, `texto_site`, `credito_site`, `fonte_site`.
  Confira que todo item tem `foto` antes do site.py (o site.py avisa "SEM FOTO": nesse caso tire o item). Rode `python3 robo/site.py <ids>` e registre cada um com `python3 robo/historico.py <id> SITE` (assim o radar não repete a pauta).
  Itens com `"so_site": true` **nunca** passam pelo artes.js nem pelo Instagram.
- Commit + push a cada lote (o site busca o arquivo de 15 em 15 minutos).

## Concorrentes (regra do Israel)
- Concorrentes: **Jornal Virou Notícia / Virou Notícia**, **Portal Impacto**, qualquer **Vetor Norte**, **RM Notícias**.
- Pode usar a pauta deles, mas: **nunca citar como fonte**, reescrever o texto e **nunca usar a foto deles**.
- Antes de publicar pauta de concorrente, confirmar o fato em outra fonte (oficial ou veículo grande) e citar essa outra fonte.
  Se não houver outra fonte, a legenda sai **sem** a linha `Fonte da matéria:`. Se nem a confirmação existir, não publicar.
- O **Jornal Virou Notícia** nunca aparece em nenhuma legenda.

## Comunicação (regra do Israel, 31/10/2026)
- **Não mandar mensagens automáticas** (post publicado, rodada concluída, ação feita), nem na conversa do robô nem em outra.
  Nada de notificação. O Israel pergunta quando quiser saber. Erros ficam só em `dados/erros.txt`.
- No fim da rodada, a resposta da sessão é no máximo uma linha, sem lista de títulos.

## ⛔ DUAS RODADAS AO MESMO TEMPO (02/11/2026: Finados em Betim saiu 2 vezes, 14h32 e 14h38)
- A rodada pode começar atrasada e pegar a anterior ainda publicando. A pauta da outra só entrava no histórico depois do post.
- **No começo da rodada**: `git pull`; veja em `dados/site.json` os ids de hoje que ainda não estão em `dados/historico.json`:
  são pautas de outra rodada em andamento. **Não escolha o mesmo fato** (mesmo link ou mesma cidade e assunto).
- Se o último commit "Rodada" tiver menos de 15 minutos, **conte o intervalo a partir dele** antes de publicar.
- O `links.py` agora faz `git pull` antes de conferir a trava; **rode-o de novo logo antes de cada tentativa de publicar**,
  inclusive depois da espera do "Media ID".

## Nunca repetir notícia (Israel, 01/11/2026: "Post repetido. Não deixa isso acontecer mais")
- Em 01/11 à 1h saiu de novo o ônibus 402A que bateu num bar em Contagem (já publicado em 31/10 às 19h44), porque a matéria
  entrou por link avulso (`coletar.py <URL> --anexar`), que não passava pelo filtro do radar.
- Agora a trava `robo/repetido.py` compara cada item com tudo o que saiu nos últimos 10 dias (Instagram **e** site): mesmo link
  ou título parecido. Ela roda sozinha no `coletar.py` (descarta o item), no `site.py` (não manda) e no `links.py` (não dá link).
- Se o `links.py` imprimir **REPETIDO**, **não publique** de jeito nenhum, nem com o link de outra forma. Tire o item do lote.
- Antes de escolher pautas, confira também a olho o `dados/historico.json` do dia anterior e do dia: o mesmo fato com outra
  fonte ou outro título também é repetido. Só vale nova publicação se houver fato novo de verdade (ex.: resultado de jogo
  depois da prévia), e o título tem que deixar claro o que é novo.
- Pode testar à mão: `python3 robo/repetido.py <id> ...` ou `python3 robo/repetido.py --link <URL>`.

## Cuidados
- Polícia: não dizer que alguém é culpado; usar "suspeito", "segundo a polícia". Não expor menores de idade.
- Não publicar imagem chocante (sangue, corpos). Escolher outra foto ou não publicar a notícia.
- Não repetir pauta já publicada (o radar já filtra pelo `dados/historico.json`, mas confira).
- Política: tom neutro, só fatos com fonte.

## Passo a passo da rodada
1. `pip install --break-system-packages -q feedparser trafilatura googlenewsdecoder ftfy`
2. `python3 robo/historico.py --resumo` (quantos já saíram hoje e quanto de RMBH).
3. `python3 robo/radar.py 8` e leia `dados/radar.json`. Escolha as pautas mais fortes (sem repetir, respeitando a cota de RMBH).
4. `python3 robo/coletar.py <n> <n> ...` e leia o texto de cada uma em `dados/lote.json`.
5. Para cada item do lote, preencha no `dados/lote.json`: `portal` (one, luziense, politica ou comercio), `ed`, `titulo`, `legenda`, `bloco` (rmbh, comercio, minas, brasil...)
   (corrija o bloco se o radar errou). Item sem foto ou de concorrente: ache outra matéria do mesmo fato
   (`python3 robo/coletar.py <URL> --anexar`) e use a foto dela, ou descarte.
6. `python3 robo/colagem.py <ids do Instagram>` (montagem, faixa e pessoa no centro; ver regra do topo) e depois `node robo/artes.js`. Todos com 3 linhas. Olhe as artes (Read na imagem) antes de publicar: foto não pode estar
   cortando rosto de forma estranha, nem ter marca d'água de outro veículo grande no meio.
7. `python3 robo/site.py <ids>` (ver seção Site) e depois `git add -A && git commit && git push`
   (as imagens precisam estar no GitHub antes de publicar).
8. Para cada item, um de cada vez: rode `python3 robo/links.py <id>` (depois do push; se sair REPETIDO, não publique) e publique com `create_image_post`
   (`image_url` = **primeiro link** que ele imprimir, o do jsDelivr; `caption` = `legenda`),
   depois `python3 robo/historico.py <id> <media_id>`, e esperar 8 a 12 minutos antes do próximo
   (comando em segundo plano `sleep 540`).
   **Se der "Media ID is not available"** (é o Instagram que não terminou de processar a imagem; o post NÃO sai,
   o Israel confirmou em 28/10): espere **3 minutos** e tente de novo com o **segundo link** (raw.githubusercontent).
   Se falhar outra vez, espere mais 3 minutos e tente o primeiro link de novo. Depois de 3 falhas, registre em
   `dados/erros.txt` e passe para o próximo item; o que ficou pendente entra na próxima rodada.
   Isso não é erro de permissão/token, então não encerra a rodada.
   Item que não saiu: copie o objeto dele (com `legenda` e `arte`) para a lista em `dados/pendentes.json`.
   No começo de cada rodada, se `dados/pendentes.json` tiver itens de hoje, publique-os primeiro (a arte já está no GitHub;
   coloque o item de volta no `dados/lote.json` antes de rodar o `links.py`) e tire-os da lista depois de publicados.
9. No fim: `git add -A && git commit && git push` do histórico.
