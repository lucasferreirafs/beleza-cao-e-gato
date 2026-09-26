# Fase 1 — autenticação e perfil

## Banco de dados

Em uma instalação existente, execute na raiz do projeto, no terminal local:

```sh
docker compose up -d --build app
docker compose exec app php bin/migrate-phase1.php
```

A migração é aditiva e preserva os dados. Não execute `schema.sql` nem o reset geral para atualizar uma instalação existente.

## Comportamentos

- Lembrar de mim: validade absoluta de 30 dias, token aleatório armazenado como hash, rotação e revogação no logout.
- Cadastro e login exigem aceite explícito da versão atual dos termos; o banco registra versão e data.
- CPF e e-mail começam bloqueados. A alteração exige senha atual; novo e-mail exige confirmação e encerra acessos anteriores.
- Fotos usam exclusivamente `img-user`, via cURL no servidor, com nome aleatório. JPEG, PNG e WebP até 5 MiB; limites adicionais de dimensões são validados no servidor.
- A imagem é servida por rota autenticada, sem expor a chave do Supabase. Nenhum bucket é criado.
- Menus e confirmação usam isolamento de foco/background; os campos de perfil seguem o foco visual do login.

Em produção, configure HTTPS, envio de e-mail e as variáveis Supabase. Não exponha `SUPABASE_SERVICE_ROLE_KEY` ao navegador.

## QA

```sh
php tests/run.php
php tests/storage.php
php tests/lint.php
node --check public/assets/js/app.js
docker compose exec app php tests/phase1.php
```

O teste de integração exige `APP_ENV=development` (ou test/testing), `AUTH_BYPASS=false`, banco migrado e credenciais Storage. Cria contas próprias e imagens temporárias em `img-user`, removendo-as ao terminar. Para executar fora do container do app, defina `PHASE1_TEST_URL` com a URL acessível nesse ambiente. O teste não comprova entrega de e-mail por um provedor real nem substitui a inspeção visual em dispositivos móveis.
