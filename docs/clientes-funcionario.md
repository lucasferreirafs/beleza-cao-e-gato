# Diretórios de clientes e pets do funcionário

## Escopo e autorização

O diretório é somente leitura e está disponível exclusivamente ao papel `staff`, com sessão autenticada. Exibe todos os usuários com papel `client`, inclusive clientes sem agendamentos e contas bloqueadas. O bloqueio da conta é identificado visualmente e não impede sua consulta pelo funcionário.

Não há edição de cadastro, mudanças de status ou histórico de atendimentos nestas telas. Não foi necessária alteração no schema.

## Rotas e fluxo

- `/staff/clientes`: busca por nome, e-mail ou telefone e paginação de dez clientes, ordenados por nome e UUID.
- `/staff/clientes/{clientId}`: contato, nascimento, data de cadastro e todos os pets do tutor.
- `/staff/clientes/{clientId}/pets/{petId}`: informações cadastrais, cor do pelo, castração, alergias, comportamento, necessidades especiais e observações do pet.
- As rotas de perfil acima recebem o sufixo `/foto` para recuperar a respectiva imagem autenticada.
- `/staff/pets`: diretório de todos os pets vinculados a clientes, com busca por nome, raça ou tutor e paginação de dez registros.
- `/staff/pets/{petId}`: reutiliza a mesma view de perfil do pet. A foto é recuperada em `/staff/pets/{petId}/foto`, e o retorno ao diretório preserva os filtros.
- `/staff/pets`: diretório de todos os pets vinculados a clientes, com busca por nome, raça ou tutor e paginação de dez registros.
- `/staff/pets/{petId}`: reutiliza a mesma view de perfil do pet e o endpoint protegido de foto, com retorno ao diretório preservando busca e página.

A busca usa parâmetros GET `q` e `page`; funciona sem JavaScript. Os links de retorno dos perfis preservam esses parâmetros. A comparação de telefone ignora pontuação quando a busca tem formato telefônico. Caracteres `%` e `_` são tratados literalmente, não como curingas fornecidos pelo usuário.

## Arquitetura e privacidade

`StaffController` coordena as respostas. `StaffClientService` e `StaffPetService` validam identificadores e o vínculo tutor–pet; `StaffClientRepository` e `StaffPetRepository` executam consultas preparadas com projeção explícita de colunas. CPF e dados de autenticação dos clientes não são selecionados pelos diretórios nem enviados às suas views.

As rotas dos diretórios e perfis passam pelos middlewares de autenticação e papel. Identificadores inválidos, usuários que não sejam clientes e pets sem tutor cliente retornam 404. A validação também ocorre nas rotas de imagem, antes de qualquer resposta condicional 304.

Fotos passam pelo proxy PHP existente de Supabase Storage e pela política de cache privado de `PrivateImageResponse`; nenhuma chave é enviada ao navegador. Ausência ou falha de imagem usa iniciais para o tutor e `pet.webp` para o pet.

As views ficam em `resources/views/pages/staff/`, com o card de pet compartilhado em `partials/client-pet-card.php`. O CSS dedicado `public/assets/css/staff/clients.css` usa o escopo `.staff-clients`; a tabela se transforma em cards no mobile. O tratamento de falha das imagens reutiliza o JavaScript existente.

## Verificação

**Atenção:** `tests/staff-clients.php` reinicializa o banco. Execute somente no compose descartável, nunca na conexão real do `.env`:

```sh
docker compose -f compose.booking-qa.yaml up -d --build --wait
docker compose -f compose.booking-qa.yaml exec -T app php tests/staff-clients.php
```

A suíte cobre busca, paginação, entradas inválidas, projeção sem CPF, autorização, vínculo tutor–pet, encoding HTML e resposta condicional de imagens. O teste não verifica transferência real de imagens pela rede do Supabase.

Após essa suíte, `node tests/staff-clients-layout.cjs` usa suas fixtures em `http://127.0.0.1:18080`. Requer Playwright disponível no ambiente e um navegador compatível; `QA_BROWSER_CHANNEL=msedge` seleciona o Edge instalado. Não adicione essas dependências ao runtime PHP. Execute antes de outras suítes que reinicializem o QA.

O teste de interface verifica três telas em oito larguras (320 a 1440 px), busca, paginação, retorno, foco e erros de JavaScript. As capturas são gravadas no diretório temporário do sistema. Isso não substitui testes em aparelhos físicos.

Ao terminar, encerre o ambiente criado para os testes:

```sh
docker compose -f compose.booking-qa.yaml down
```
