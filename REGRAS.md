# Robô de notícias do Portal ONE News: regras de cada rodada

Este arquivo é a instrução de cada rodada automática. Quem roda é o Claude, numa sessão agendada.
Tudo o que o Israel vê é em português.

## Autorização
- O Israel autorizou em 28/10/2026 a publicação **automática** de até **100 posts nesse dia** no Instagram **@portalonenews**,
  sem aprovação prévia, pelo Windsor.ai (conector `instagram`, conta `17841461417663539`, ação `create_image_post`).
- Fora dessa data ou passando de 100 posts no dia: **não publicar nada**. Encerrar a rodada.

## Meta e ritmo
- Meta do dia: **100 posts**, espaçados. Nunca vários de uma vez: **um post a cada 8 a 12 minutos** dentro da rodada.
- Quantidade por rodada: das 0h às 5h59, **2 posts**; das 6h às 23h59, **5 posts**. Se o dia estiver atrasado, até 6.
- **30% dos posts do dia devem ser de Santa Luzia + Região Metropolitana de BH** (bloco `rmbh`).
  Confira com `python3 robo/historico.py --resumo` e compense na rodada se estiver abaixo.
- Resto: Minas Gerais, Brasil, Mundo, Entretenimento, Esporte (Atlético, Cruzeiro, América), Saúde, Educação, serviços.
- 28/10/2026: eleição da Mesa Diretora da Câmara Municipal de Santa Luzia. Se sair resultado em fonte confiável
  (site da Câmara, g1, O Tempo, Itatiaia, Estado de Minas), é prioridade. Se não achar, não inventar; o Israel faz à mão.

## Molduras (portal) e editoria
- `portal`: `luziense` para notícias de **Santa Luzia**; `politica` para **política**; `one` para todo o resto.
- Todas são publicadas no **@portalonenews** (a collab com os outros perfis o Israel coloca depois, à mão).
- `ed` (etiqueta em caixa alta): BELO HORIZONTE, REGIÃO METROPOLITANA, SANTA LUZIA, MINAS GERAIS, BRASIL, MUNDO,
  ENTRETENIMENTO, ESPORTE, ATLÉTICO, CRUZEIRO, AMÉRICA, SAÚDE, EDUCAÇÃO, LUTO, URGENTE, VIRALIZOU, POLÍTICA,
  ou o nome da cidade da Grande BH (ex.: CONTAGEM, BETIM, VESPASIANO, LAGOA SANTA, SABARÁ).

## Título (vai na arte)
- Sempre **exatamente 3 linhas** na moldura (cerca de 80 a 98 caracteres). O `robo/artes.js` mede: se não der 3, reescreva e gere de novo.
- Texto próprio, factual, sem exagero nem sensacionalismo enganoso. Nunca copiar o título original.
- Português do Brasil, sem palavras em inglês quando houver equivalente.

## Legenda
- 3 a 6 parágrafos curtos: abre com emoji + frase de impacto, depois os fatos (quem, o quê, onde, quando), sem copiar frases da fonte.
- Termina com `📸 Foto: <crédito>`, `Fonte: <veículo>` e 3 a 5 hashtags (sempre `#PortalONENews`).
- Não marcar perfis com @.

## Site (portalonenews.com.br, WordPress)
- Toda notícia publicada no Instagram também vai para o site. O site busca o arquivo `dados/site.json` sozinho a cada 15 minutos.
- Para cada item, preencha também no `dados/lote.json`:
  - `linha_fina`: uma frase de resumo (até 160 caracteres);
  - `categoria`: um destes: santa-luzia, belo-horizonte, regiao-metropolitana, contagem, betim, vespasiano, lagoa-santa,
    ribeirao-das-neves, sabara, minas-gerais, politica, brasil, mundo, esporte, atletico, cruzeiro, america, entretenimento,
    saude, educacao, luto;
  - `texto_site`: a matéria completa em HTML simples (`<p>...</p>`), 4 a 7 parágrafos, 250 a 450 palavras, com texto próprio
    (nunca copiar frases da fonte), na ordem: o fato principal, detalhes, o que disseram as autoridades/envolvidos, o que acontece agora;
  - `credito_site`: o crédito da foto (ex.: "Divulgação/PMMG");
  - `fonte_site`: o veículo de origem (mesma regra dos concorrentes: nunca citar os concorrentes).
- Depois rode `python3 robo/site.py <id> <id> ...` (antes do passo 7, para a foto do site subir junto).

## Concorrentes (regra do Israel)
- Concorrentes: **Jornal Virou Notícia / Virou Notícia**, **Portal Impacto**, qualquer **Vetor Norte**, **RM Notícias**.
- Pode usar a pauta deles, mas: **nunca citar como fonte**, reescrever o texto e **nunca usar a foto deles**.
- Antes de publicar pauta de concorrente, confirmar o fato em outra fonte (oficial ou veículo grande) e citar essa outra fonte.
  Se não houver outra fonte, a legenda sai **sem** a linha `Fonte:`. Se nem a confirmação existir, não publicar.
- O **Jornal Virou Notícia** nunca aparece em nenhuma legenda.

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
5. Para cada item do lote, preencha no `dados/lote.json`: `portal`, `ed`, `titulo`, `legenda`, `bloco`
   (corrija o bloco se o radar errou). Item sem foto ou de concorrente: ache outra matéria do mesmo fato
   (`python3 robo/coletar.py <URL> --anexar`) e use a foto dela, ou descarte.
6. `node robo/artes.js`. Todos com 3 linhas. Olhe as artes (Read na imagem) antes de publicar: foto não pode estar
   cortando rosto de forma estranha, nem ter marca d'água de outro veículo grande no meio.
7. `python3 robo/site.py <ids>` (ver seção Site) e depois `git add -A && git commit && git push`
   (as imagens precisam estar no GitHub antes de publicar).
8. Para cada item, um de cada vez: rode `python3 robo/links.py <id>` (depois do push) e publique com `create_image_post`
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
