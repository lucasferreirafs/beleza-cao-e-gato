# Beleza Cão&Gato

Sistema web de gestão para um pet shop, com áreas de cliente, funcionário e administração. O cliente gerencia pets e perfil, consulta serviços e agenda atendimentos; a equipe acompanha a agenda e os administradores gerenciam usuários, serviços e agendamentos.

Este documento descreve o comportamento encontrado no código e no schema atuais. Configurações de ambiente e provedores externos variam entre desenvolvimento e produção.

## Índice

- [Funcionalidades](#funcionalidades)
- [Stack e arquitetura](#stack-e-arquitetura)
- [Pré-requisitos](#pré-requisitos)
- [Configuração e execução local](#configuração-e-execução-local)
- [Configuração de ambiente](#configuração-de-ambiente)
- [Banco de dados](#banco-de-dados)
- [Supabase Storage](#supabase-storage)
- [Regras de agendamento](#regras-de-agendamento)
- [Segurança](#segurança)
- [Testes e verificações](#testes-e-verificações)
- [Estrutura do repositório](#estrutura-do-repositório)
- [Operação e implantação](#operação-e-implantação)
- [Documentação de requisitos e backlog](#documentação-de-requisitos-e-backlog)

## Funcionalidades

- Cadastro de cliente, verificação de e-mail, login, recuperação de senha e opção “Lembrar de mim”.
- Perfil com edição de dados pessoais e foto.
- Cadastro, edição, exclusão e consulta dos pets do cliente, incluindo alergias, castração, cor do pelo e foto.
- Catálogo de serviços com descrição, duração e preço.
- Agendamento de um a dez serviços para um pet, com escolha de profissional, horário e observação.
- Consulta e cancelamento de agendamentos pelo cliente; atualização de status por funcionário e administrador.
- Painéis próprios para cliente, funcionário e administrador.

O aceite dos termos ocorre no cadastro. O login não pede novo aceite.

## Stack e arquitetura

- **Backend:** PHP 8.3 no container, sem framework; autoload PSR-4 configurado pelo Composer.
- **Frontend:** HTML, CSS e JavaScript vanilla. Não há jQuery nem etapa de compilação de assets.
- **Interface:** Inter, Font Awesome, GSAP/ScrollTrigger e Lenis em páginas que usam animação.
- **Servidor web:** Apache com `mod_rewrite` e cabeçalho de segurança.
- **Persistência:** PostgreSQL, acessado pelo PHP via PDO e `pdo_pgsql`. A configuração de exemplo aponta para PostgreSQL do Supabase e exige SSL.
- **Arquivos:** Supabase Storage, acessado pelo backend via REST API e cURL.
- **Execução local:** Docker e Docker Compose.

O fluxo segue uma organização MVC simples: `routes/` declara URLs e middleware; `app/Controllers/` coordena requisições; `app/Services/` concentra regras; `app/Repositories/` acessa dados; `resources/views/` contém templates PHP; `public/` é a raiz pública e guarda assets.

## Pré-requisitos

- Docker Desktop com Docker Compose.
- Projeto PostgreSQL acessível por SSL (o exemplo usa Supabase).
- Projeto Supabase Storage com os buckets `img-user` e `img-pets` já criados.
- Node.js somente para executar `node --check` nos scripts do frontend localmente.

O container instala PHP 8.3, Apache, `pdo_pgsql`, cURL e Fileinfo. Não há dependências de runtime PHP de terceiros declaradas no `composer.json` além do autoload do projeto.

## Configuração e execução local

Na raiz do repositório, crie o arquivo de ambiente a partir do modelo:

```powershell
Copy-Item .env.example .env
```

No macOS/Linux:

```sh
cp .env.example .env
```

Preencha as credenciais descritas em [Configuração de ambiente](#configuração-de-ambiente). Para desenvolvimento local, mantenha `APP_ENV=development`. O `AUTH_BYPASS=true` do exemplo cria uma sessão de desenvolvimento automaticamente; escolha o papel com `AUTH_BYPASS_ROLE=client`, `staff` ou `admin`. Esse atalho é exclusivo de desenvolvimento.

Suba o serviço web:

```sh
docker compose up -d --build
```

A aplicação fica em <http://localhost:8080>. Para acompanhar os logs:

```sh
docker compose logs -f app
```

O Compose não inicia um PostgreSQL local: a aplicação conecta ao banco configurado no `.env`.

### Banco novo e banco existente

Para inicializar um banco vazio com schema, índices e dados de demonstração:

```sh
docker compose exec app php bin/migrate.php
```

**Atenção:** `database/schema.sql`, chamado por `bin/migrate.php`, remove objetos do schema `public` e os recria. Isso apaga os dados existentes desse schema. Use somente em banco novo ou descartável.

Para atualizar um banco existente, não execute o reset. Use as migrações incrementais correspondentes, na raiz do projeto:

```sh
docker compose exec app php bin/migrate-phase1.php
docker compose exec app php bin/migrate-phase2.php
docker compose exec app php bin/migrate-phase4.php
```

Esses comandos aplicam as migrações 001, 002 e 003; a migração 003 também importa para `appointment_services` os snapshots versionados que ainda estejam em `appointments.notes`. A migração 004 adiciona a flag gerada de alergias: execute o conteúdo de `database/migrations/004_pet_allergy_flag.sql` no SQL Editor do Supabase ou por uma conexão PostgreSQL autorizada. Faça backup e confirme o ambiente antes de qualquer migração em produção.

## Configuração de ambiente

O arquivo `.env.example` lista as variáveis principais. `MAIL_FROM` é opcional e pode ser adicionado ao `.env` para habilitar o envio de e-mails. `.env` contém segredos e não deve ser versionado.

| Variável | Finalidade |
| --- | --- |
| `APP_ENV` | Ambiente. O atalho de autenticação só é aceito quando o valor é `development`. |
| `APP_DEBUG` | Modo de depuração da aplicação. Desative em produção. |
| `APP_URL` | URL-base pública usada, entre outros pontos, nos links de recuperação de senha. |
| `APP_TIMEZONE` | Fuso horário da conexão PostgreSQL e das datas da aplicação. Exemplo: `America/Sao_Paulo`. |
| `SESSION_SECURE` | Define cookie de sessão seguro; em produção HTTPS o código também exige `Secure`. |
| `SESSION_IDLE_TIMEOUT` | Limite de inatividade em segundos; o padrão no middleware é 7200. |
| `AUTH_BYPASS` | Sessão automática apenas em `development`; deve ser `false` em produção. |
| `AUTH_BYPASS_ROLE` | Papel usado pelo atalho local: `client`, `staff` ou `admin`. |
| `DB_CONNECTION` | Driver PostgreSQL (`pgsql`). |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE` | Endereço e nome do banco PostgreSQL. |
| `DB_USERNAME`, `DB_PASSWORD` | Credenciais do PostgreSQL. |
| `DB_SSLMODE` | Modo TLS do PDO; o exemplo usa `require`. |
| `SUPABASE_URL` | URL HTTPS do projeto Supabase, sem `/storage/v1`. |
| `SUPABASE_ANON_KEY` | Reservada para integrações que usem RLS; não é usada nos uploads atuais. |
| `SUPABASE_SERVICE_ROLE_KEY` | Chave privilegiada usada pelo PHP para operações Storage. Nunca exponha ao browser. |
| `MAIL_FROM` | Remetente usado por `mail()` para verificação e recuperação de senha. |

O serviço de e-mail usa a função nativa `mail()`. O ambiente precisa ter um MTA ou transporte de e-mail configurado; `MAIL_FROM` sozinho não configura a entrega.

## Banco de dados

O schema completo está em [`database/schema.sql`](../database/schema.sql). Tabelas principais:

| Tabela | Conteúdo |
| --- | --- |
| `users` | Clientes, funcionários e administradores; perfil, autenticação, status, aceite de termos e caminho da foto. |
| `pets` | Dados dos pets, castração (`neutered`), alergias em texto, flag gerada `has_allergies`, cor do pelo e caminho da foto. |
| `services` | Catálogo com preço, duração e estado ativo. |
| `appointments` | Pet, profissional atribuído, data, status, preço e duração total do atendimento. `service_id` mantém compatibilidade com o modelo anterior. |
| `appointment_services` | Serviços e snapshots de nome, preço, duração e ordem no agendamento. |
| `account_tokens` | Tokens de uso único para verificação de e-mail e redefinição de senha. |
| `remember_tokens` | Tokens revogáveis para “Lembrar de mim”; RLS ativado e acesso público revogado. |

Os caminhos `avatar_path` e `photo_path` guardam o nome aleatório do objeto, não URLs assinadas. A flag `pets.has_allergies` é calculada pelo PostgreSQL a partir de `allergies`, que guarda a descrição integral.

Migrações disponíveis:

1. `001_auth_profile.sql`: campos de termos, perfil e tokens persistentes.
2. `002_pet_photos.sql`: caminho da foto do pet.
3. `003_multi_service_booking.sql`: duração e itens de serviço em agendamentos, com importação dos registros existentes pelo runner da Fase 4.
4. `004_pet_allergy_flag.sql`: flag gerada para alergias.

Não há um registro central de versões de migração no momento; aplique os scripts adequados ao schema de origem e mantenha um registro operacional da atualização.

## Supabase Storage

O sistema usa os buckets existentes `img-user` e `img-pets`. A aplicação não cria buckets nem altera suas permissões.

- Upload, substituição, recuperação e exclusão são feitos no backend pelo serviço `SupabaseStorageService` usando cURL e a chave `service_role`.
- Cada novo objeto recebe 32 bytes aleatórios em hexadecimal (64 caracteres) mais a extensão original; o nome enviado pelo usuário não vira nome do objeto.
- Formatos aceitos: JPEG/JPG, PNG e WebP; limite de 5 MiB, 8192 pixels por lado e 25 megapixels.
- O servidor valida extensão, MIME e dimensões. Os arquivos são exibidos por rotas autenticadas que verificam o usuário ou o vínculo com o pet.
- Fotos de pets sem upload usam `public/assets/images/pet.webp` como fallback visual.
- A integração requer `SUPABASE_URL` e `SUPABASE_SERVICE_ROLE_KEY`. A chave anon não é usada como alternativa.

Detalhes de validação e respostas HTTP estão implementados em `app/Services/SupabaseStorageService.php`.

## Regras de agendamento

- Fluxo nomeado: `servico` → `escolha-pet` → `profissional` → `data-e-horario` → `confirmacao`. URLs numéricas antigas são normalizadas para as etapas nomeadas.
- Cada reserva aceita de 1 a 10 serviços ativos e distintos. O backend calcula preço e duração total; a confirmação compara os valores atuais com o resumo apresentado.
- Os horários começam a cada 30 minutos entre 08:00 e 17:30, e todos os serviços precisam terminar até 18:00. A disponibilidade é calculada diariamente; não há escala semanal por profissional.
- Existe um intervalo de 60 minutos ao redor da reserva entre atendimentos do mesmo profissional ou pet. O limite exato é considerado livre.
- A seleção de profissional precede data e horário. Na etapa do horário, profissionais ocupados continuam visíveis e desabilitados. “Sem preferência” seleciona no servidor um profissional ativo e livre.
- A disponibilidade é verificada novamente ao confirmar. A gravação usa transação, lock consultivo compartilhado com mudanças de status e locks de linha para reduzir colisões concorrentes.
- Agendamentos cancelados liberam a agenda e não podem ser reabertos diretamente.
- Em schemas antigos sem a tabela/coluna da Fase 4, `AppointmentStorage` guarda temporariamente snapshots dos serviços e a observação em JSON versionado no campo `notes`. Nenhuma requisição web altera o schema. A migração 003 converte esses registros para `appointment_services`.

Implementação central: `app/Services/BookingService.php`, `app/Repositories/AppointmentRepository.php` e `app/Repositories/AppointmentStorage.php`.

## Segurança

- Rotas são protegidas por middleware de sessão, papel e CSRF conforme a operação.
- O backend valida propriedade dos pets, estado dos serviços e disponibilidade dos profissionais; esconder um controle na interface não substitui autorização.
- Senhas são guardadas com `password_hash()` e verificadas com `password_verify()`.
- Tokens de e-mail, recuperação e “Lembrar de mim” são de uso limitado, guardados com proteção e/ou revogáveis conforme o fluxo.
- Saídas HTML são escapadas; consultas SQL usam PDO e parâmetros preparados.
- A chave `service_role` e as credenciais do banco ficam somente no ambiente do servidor.
- `AUTH_BYPASS=true` permite entrar sem autenticação real em `development`. Nunca use esse modo publicamente ou em produção.

## Testes e verificações

Dentro do container da aplicação:

```sh
docker compose exec app php tests/run.php
docker compose exec app php tests/storage.php
docker compose exec app php tests/lint.php
```

Com Node.js instalado no host:

```sh
node --check public/assets/js/app.js
```

A integração de autenticação e Storage em `tests/phase1.php` faz requisições reais e requer ambiente de desenvolvimento/teste com `AUTH_BYPASS=false`, banco preparado, credenciais do Storage e URL acessível pela variável `PHASE1_TEST_URL` quando necessário.

### Teste isolado do motor de agendamentos

O teste de agendamento reinicializa schemas e dados em um banco descartável. Use exclusivamente o Compose próprio de QA:

```sh
docker compose -f compose.booking-qa.yaml up -d --build --wait
docker compose -f compose.booking-qa.yaml exec -T app php tests/booking.php
docker compose -f compose.booking-qa.yaml exec -T app php tests/run.php
docker compose -f compose.booking-qa.yaml exec -T app php tests/lint.php
docker compose -f compose.booking-qa.yaml down -v
```

`tests/booking.php` exige simultaneamente `APP_ENV=testing`, `BOOKING_QA_ALLOW_RESET=1`, `DB_DATABASE=booking_qa` e `DB_HOST=beleza-booking-qa-db`; ele recusa o banco configurado para a aplicação normal. `down -v` remove o volume descartável do banco de QA.

## Estrutura do repositório

```text
app/                    Controladores, domínio, serviços, repositórios, segurança
bin/                    Migrações e utilitários de diagnóstico do banco
database/schema.sql     Schema completo para inicialização/reset
database/migrations/    Migrações incrementais
docs/                   Documentação do projeto
public/                 Front controller, assets e arquivos públicos
resources/views/        Templates PHP
routes/                 Registro de rotas e middleware
tests/                  Testes básicos, Storage, autenticação e agendamento
docker/                 Configuração do Apache e limites de upload
```

## Operação e implantação

Antes de publicar:

1. Defina `APP_ENV=production`, `APP_DEBUG=false`, `AUTH_BYPASS=false` e publique somente por HTTPS.
2. Use credenciais PostgreSQL com SSL e permissões mínimas necessárias.
3. Configure `SUPABASE_URL` e uma `SUPABASE_SERVICE_ROLE_KEY` no ambiente do servidor; confirme que os dois buckets existem.
4. Configure um MTA/transporte de e-mail para `mail()` e preencha `MAIL_FROM`.
5. Não armazene `.env` no Git; mantenha segredos somente no ambiente do servidor.
6. Faça backup do PostgreSQL antes de migrações e nunca rode o reset `bin/migrate.php` sobre dados que precisam ser preservados.
7. Revise os cabeçalhos e a Content Security Policy em `docker/apache.conf` se mudar os hosts externos usados pela interface.

Comandos úteis:

```sh
docker compose ps
docker compose logs -f app
docker compose down
```

## Documentação de requisitos e backlog

- [Levantamento de requisitos](docs/levantamento-de-requisitos.md): requisitos observados no código, regras de negócio, critérios transversais e questões pendentes de elicitação.
- [Backlog do projeto](docs/backlog.md): funcionalidades já representadas na aplicação e propostas futuras ainda sujeitas à priorização/aprovação.
