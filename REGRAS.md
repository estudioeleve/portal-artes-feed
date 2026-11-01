# Robô de notícias do Portal ONE News: regras de cada rodada

## ⚠️ REDUÇÃO PROVISÓRIA (pedido do Israel, 01/11/2026 ~13h50) — vale ATÉ ELE AVISAR que aumentou o plano
Motivo: economizar o uso do plano do Claude. Esta seção manda mais que as outras abaixo.
- **Metade dos posts**: Instagram com meta de **50 por dia** (não 100): **2 a 3 posts por rodada** entre 6h e meia-noite
  e **1 post** na madrugada. Notícias só para o site: **4 por rodada** de dia e **1 a 2** na madrugada.
- Rodada mais enxuta: colete só o necessário (poucos itens no `coletar.py`), sem pesquisas extras.
- **Prioridade máxima**: Santa Luzia e Região Metropolitana; depois Belo Horizonte (crimes/polícia, ocorrências, transporte)
  e **política da região e de BH** (Câmara, prefeituras). Política de Brasília só os assuntos importantes.
- **Política: não diminuir tanto** (manter presença, foco em região/BH e o que for importante em Brasília).
- **Diminuir bastante**: Comércio (Portal Comércio: **2 a 4 por dia** no total, preferindo Santa Luzia) e **Mundo** (no máximo 1 a 2 por dia).
- Entretenimento: diminuir um pouco.
- Não mandar resumos nem avisos (nem do site, nem do Instagram).


Este arquivo é a instrução de cada rodada automática. Quem roda é o Claude, numa sessão agendada.
Tudo o que o Israel vê é em português.

## Autorização
- O Israel autorizou em 31/10/2026 a publicação **automática** de até **100 posts por dia, todos os dias**, no Instagram
  **@portalonenews**, sem aprovação prévia, pelo Windsor.ai (conector `instagram`, conta `17841461417663539`, ação `create_image_post`).
  Vale a partir de 31/10/2026 (por volta das 19h).
- Passando de 100 posts no dia: **não publicar nada**. Encerrar a rodada. O contador zera à meia-noite (America/Sao_Paulo).

## Meta e ritmo
- Meta do dia: **100 posts**, espaçados. Nunca vários de uma vez: **um post a cada 8 a 12 minutos** dentro da rodada.
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

## Portal Comércio (5 a 10 por dia)
- Público: comerciantes, empreendedores e empresários. Preferência por **Santa Luzia** quando houver.
- Pautas: oportunidades para o comércio e o empreendedor; benefícios para empresários (crédito, programas, MEI, impostos);
  comércio no fim de ano (horários, 13º, Black Friday, Natal); datas festivas; expectativas de vendas; agenda do comércio
  (CDL, ACISL, Sebrae, Fecomércio, feiras).
- Moldura `comercio`, `ed` COMÉRCIO (ou SANTA LUZIA quando for de lá), `categoria` do site `comercio`, `bloco` `comercio`.
- Não publicar propaganda disfarçada de notícia: tem que ter fato e fonte.
- Moldura do feed (31/10/2026, pedido do Israel): a do Canva "Feed comercio" (canva.link/fbyi7bmmn43h5gm), já aplicada no
  `robo/modelo/moldura.html` (classe `.c`). A moldura de Reels não serve para post de imagem.

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
6. `node robo/artes.js`. Todos com 3 linhas. Olhe as artes (Read na imagem) antes de publicar: foto não pode estar
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
