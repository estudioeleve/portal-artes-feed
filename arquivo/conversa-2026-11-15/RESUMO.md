# Histórico do Portal ONE News: conversa até 15/11/2026

Cole este texto no começo de uma conversa nova, ou anexe o arquivo. Tudo o que foi feito está aqui.

---

## 1. Quem e o quê
- **Israel Fernandes**, jornalista e dono do **Portal ONE News** (site portalonenews.com.br, Instagram **@portalonenews**). Sub-portal: **Portal Luziense** (Santa Luzia).
- Responder sempre em português, de forma direta. Ele não é programador.
- Sempre dar link direto e passo a passo. Avisar o custo e se depende de serviço externo.
- Mandar sempre o **texto inteiro** atualizado, nunca "troque só tal item". Mandar **prévia em vídeo** antes de fazer o final.

## 2. Site (WordPress, tema próprio "portal-one")
- **Versão atual instalada: 2.14.7.**
- **Cópia completa do tema:** repositório GitHub **estudioeleve/portal-artes-feed**, pasta `arquivo/conversa-2026-11-15/`.
  - `tema/`: código-fonte.
  - `portal-one-v2.14.7.zip`: tema pronto para instalar.
- **Como atualizar o tema:**
  1. Aumentar a versão no `style.css`.
  2. Gerar o zip com a pasta `portal-one`.
  3. Instalar em https://portalonenews.com.br/wp-admin/theme-install.php?upload → **"Substituir o ativo pelo enviado"**.
- **Cuidado:** os zips têm todos o mesmo nome. Pedir ao Israel que apague os antigos do celular e conferir a versão depois de instalar:
  - `curl https://portalonenews.com.br/wp-content/themes/portal-one/style.css?nc=<número>`
- **Botão "Faça sua denúncia":** fica no topo do site e na gaveta do menu, laranja, em formato de pílula.
- **Página de denúncia** (portalonenews.com.br/denuncia):
  - Zoom travado no celular: viewport, campos com 16px, `touch-action` e um script que bloqueia o gesto de pinça.
  - O leitor pode mandar texto, áudio, foto e vídeo, anônimo ou com nome. Recebe um protocolo **OC-2026-xxxx**.
- **Cache (LiteSpeed):** as rotas `/wp-json/portalone/v1/` vão sem cache (no-cache) desde a versão 2.14.5.

## 3. Fluxo das denúncias (o mais importante)
**Painel:** wp-admin → **Denúncias**, que é o tipo de post `po_denuncia`.

**O que o Israel faz:**
1. Abre a denúncia.
2. Na caixa **"Publicar esta denúncia"**, coloca a foto e/ou o vídeo.
3. Clica em **"Aprovar e publicar agora"**.
   - Ele **não escreve título**: quem escreve é o robô.
   - Nada é publicado sem a aprovação dele.

**Regras de mídia:**
- **Foto + vídeo:** o vídeo vai para o Reels e a foto vai para o site.
- **Só foto:** a foto vai para o feed e para o site.
- **Vídeo nunca vai para o site.**

**O que o site faz quando recebe o título:**
- Gera as artes (feed, reels e site) em `uploads/denuncias/`. Feed sai em **JPG**; Reels com vídeo sai em **PNG transparente**.
- Publica a matéria na categoria "Denúncias", mais "santa-luzia" quando a moldura é a do Luziense.

**Matéria no site (desde a versão 2.14.7):**
- A capa é a **foto original, sem moldura e sem degradê**. Se a denúncia só tiver vídeo, a capa é a arte.
- O texto começa com **"Denúncia · Protocolo OC-…"** e, na linha de baixo, **"Enviada por (nome)"** ou **"Denúncia anônima"**.
- Depois vêm: "Onde", o relato na íntegra em citação, "O espaço está aberto para os citados se manifestarem." e o link "Faça sua denúncia".

**Artes do Instagram:**
- Moldura do Portal Luziense ou do ONE News, igual ao Canva.
- **Degradê PRETO**, que é o padrão do Canva. Nunca azul-marinho.
- Título feito pelo robô, editoria (ex.: "Denúncia · Santa Luzia"), selo "Denúncia anônima" ou "Denúncia feita por Nome", e protocolo.
- Fonte Open Sans (600/700/800), com as letras juntas.

**Situação na lista do painel:** Concluída / Aprovada – robô publicando / Aguardando sua aprovação / Falta foto ou vídeo.

**Rotas usadas pelo robô** (`?chave=` + a chave em **Denúncias → Configurações**):
https://portalonenews.com.br/wp-admin/edit.php?post_type=po_denuncia&page=po-denuncia-config

| Rota | O que faz |
|---|---|
| GET `/wp-json/portalone/v1/denuncias/para-titular` | Denúncias aprovadas e ainda sem título. Fica reservada por 10 min para um robô. |
| POST `/denuncias/{id}/titulo?titulo=&editoria=&moldura=luz\|one` | Gera as artes e publica a matéria. |
| GET `/denuncias` | Denúncias publicadas que ainda não foram para o Instagram. Fica reservada por **30 min** para um robô (trava contra post repetido). Traz `instagram_formato`, `arte_feed`, `arte_reels`, `video_url`, `materia_url` e outros campos. |
| POST `/denuncias/{id}/postada` | Marca a denúncia como postada. |

- **Chave do robô:** (fica só nos prompts das tarefas agendadas e em Denúncias → Configurações). Fica só nos prompts das tarefas agendadas, **nunca no repositório**, que é público.

## 4. Robôs (tarefas agendadas no Claude)
**1. "Robô de notícias do Portal ONE News (diário)"** (`trig_01VqZsqe1zh5tpXxHjZb5KBN`)
- Roda aos **7 minutos** de cada hora, das 6h às 23h.
- **Faz as denúncias ANTES de qualquer notícia.**
- Depois segue o REGRAS.md do repositório: cerca de 100 posts por dia e notícias só para o site.

**2. "Denúncias do leitor — Portal ONE News (na hora)"** (`trig_01QLtVXYnkdDn4DUPkeqcB9w`)
- Roda aos **37 minutos** de cada hora, das 6h às 23h, e faz só as denúncias.
- Com os dois robôs, a denúncia aprovada sai em até cerca de 30 minutos.

**3. "Atualizar painel Instagram"** (`trig_01CzJk3WQP66KK6wYdqgi4un`): roda todo dia às 6h07.

**Regras dos robôs:**
- **Convite em todo post de feed** (notícia ou denúncia): vira carrossel, com a arte primeiro e o convite como **segunda imagem**:
  https://portalonenews.com.br/wp-content/themes/portal-one/assets/denuncia/convite-denuncia.jpg
- **Reels não levam o convite.**
- **Instagram só aceita JPG.**
- **Reels de denúncia:**
  1. Juntar o vídeo e a arte com ffmpeg (1080x1920, 30 fps, arte por cima do começo ao fim).
  2. Salvar em `dados/reels/den-<id>.mp4` no repositório.
  3. Publicar pelo endereço **cdn.jsdelivr.net/gh/estudioeleve/portal-artes-feed@main/dados/reels/…**. O raw.githubusercontent o Instagram recusa.
- **Legenda da denúncia, nesta ordem:**
  - "📢 DENÚNCIA DO LEITOR | bairro"
  - resumo neutro ("segundo o leitor…")
  - "Relato do leitor:" e o relato na íntegra entre aspas (até 1.500 caracteres; passando disso, "(...)" e o link)
  - "Denúncia feita por Nome", só se não for anônima
  - "Protocolo …"
  - "O espaço está aberto…"
  - "Viu algo errado no seu bairro? Faça sua denúncia pelo link na bio ou em portalonenews.com.br/denuncia"
  - hashtags
- **Cuidado:** se o relato tiver telefone, placa, endereço exato, nome de acusado ou menor de idade, o robô não posta. Anota em `dados/erros.txt` para o Israel decidir.
- **Nunca avisar o Israel** de que publicou.
- **Proteções contra post repetido:** sem cache, trava de 30 min e conferência no histórico. Em 15/11 saiu um post repetido (o do buraco), antes dessas travas existirem. O Israel apagou.
- **Instagram:** conector Windsor.ai, conta **17841461417663539**. Ações: create_carousel_post, create_image_post, create_video_post. O plano é gratuito e **não dá para apagar posts** por ele.

## 5. Artes e vídeos feitos nesta conversa
Fontes no repositório: `arquivo/conversa-2026-11-15/`.
- **Imagem de convite** ("Que tal mandar a sua denúncia ou sugestão de pauta para a gente?") — **APROVADA**:
  - Logo azul sem moldura, celular mostrando a home com o botão em destaque.
  - Pílula "Link na bio" + "portalonenews.com.br/denuncia", e WhatsApp (31) 99237-1702.
  - Fonte: `convite/convite.html`, renderizada com `r.cjs` (Playwright).
  - Está no tema em `assets/denuncia/convite-denuncia.jpg` (também em .png).
- **Encerramento dos Reels (4,6 s, sem som)** — **APROVADO**, mas **a regra ainda não foi ativada no robô** porque o Israel pediu para ir por partes:
  - Pedaços da logo voam em 3D e se encaixam, passa um brilho, pisca em branco e aparece a logo com o slogan.
  - Depois: pílula "portalonenews.com.br", "Faça sua denúncia: link na bio" e "@portalonenews".
  - Arquivo: `assets/denuncia/fim-reels.mp4` no tema. Fonte: `videos/FimReels.tsx` (Remotion).
- **Vídeo explicando a página de denúncia** (celular 3D em tema claro, mouse, narração do Israel) — entregue. Fonte: `videos/DenunciaDemo.tsx`.
- **Vídeo de voo 3D pelos destaques do dia** (vertical, sem som) — entregue. Fonte: `videos/Fly2.tsx` + `flydata.json`.
- **Legenda do post do convite:** entregue (sugestão: postar às 19h).
- **Projeto Remotion:** baseado na skill **padrao-portal-one**.
  - Render com concurrency 2.
  - Prévias em vídeo, com `--scale=0.5`.

## 6. Pendências
1. **Ativar o encerramento em todo Reels:** quando o Israel pedir, mandar o texto completo do robô com a regra de emendar `fim-reels.mp4` no fim de todo Reels.
2. **3 posts fixados no topo do perfil ("Do bairro ao Brasil"):** ideia proposta, aguardando resposta e fotos dele.
   - Panorama em 3 partes (1080x1440 cada): Matriz de Santa Luzia → Igreja da Pampulha → Congresso Nacional.
   - Fotos com o mesmo tom azul, linhas de luz atravessando as 3 imagens e faixa com os números do Portal.
3. **Denúncia "na hora do clique" (opcional):** o próprio site postaria sozinho. Precisa de:
   - chave da API do Claude para o título (cerca de R$ 30 de crédito inicial; menos de R$ 0,05 por denúncia);
   - ligação direta do site com o Instagram pela Meta (R$ 0, configuração única).
   - O Israel ainda não decidiu.
4. **Vídeo narrado com as ~6 principais notícias do dia:** o roteiro já foi enviado. Falta o áudio do Israel. A matéria dos aviões precisava estar no site.
5. **As 3 denúncias de 15/11** (buraco Imperial/Petrópolis, farmácia São Benedito, mato alto) saíram no Instagram com o degradê azul antigo. O Israel pode apagar e pedir para repostar em preto, se quiser.
