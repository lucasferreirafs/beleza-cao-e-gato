# Contexto do projeto para agentes de IA

Este documento serve como guia de entrada para agentes que vão analisar ou desenvolver o **Beleza Cão&Gato**. Ele resume o propósito, a arquitetura, as tecnologias e as regras atuais que afetam mudanças no código.

> Fonte de verdade: código executado, `database/schema.sql`, configurações atuais e instruções em [`AGENTS.md`](../AGENTS.md). Se este documento ou outro material divergir deles, siga as fontes de verdade e registre a divergência quando for relevante.

## O projeto

O Beleza Cão&Gato é um sistema web de gestão de pet shop. Há áreas com permissões distintas para clientes, funcionários e administradores.

- **Cliente:** mantém perfil e pets, consulta serviços e cria/acompanha agendamentos.
- **Funcionário:** acompanha a própria agenda, visualiza dados dos pets e tutores e atualiza o andamento dos atendimentos.
- **Administrador:** gerencia cadastros e operações do estabelecimento.

O produto prioriza formulários e páginas renderizados no servidor, com JavaScript usado de forma pontual para melhorar a experiência — por exemplo, disponibilidade de horários, accordions e atualização assíncrona de status.

## Stack e ferramentas

| Camada | Tecnologias em uso |
| --- | --- |
| Backend | PHP 8.3, sem framework; Composer para autoload PSR-4 (`App\\` → `app/`) |
| Banco de dados | PostgreSQL via PDO (`pdo_pgsql`), parâmetros preparados e transações nos fluxos que precisam de consistência |
| Frontend | HTML/PHP templates, CSS e JavaScript Vanilla; não há etapa de build frontend nem jQuery |
| UI e fontes | Inter e Font Awesome carregados por CDN; GSAP nas páginas de autenticação e landing, ScrollTrigger e Lenis na landing page |
| Imagens | Supabase Storage via REST API e cURL no servidor PHP |
| Servidor | Apache com `mod_rewrite` e cabeçalhos de segurança |
| Ambiente local | Docker e Docker Compose; o Compose principal sobe a aplicação, não um PostgreSQL local |
| Verificações | Scripts PHP em `tests/`, mais verificações de sintaxe PHP/JavaScript |

O `composer.json` atual declara o autoload PSR-4 e não lista frameworks ou bibliotecas PHP de runtime. Consulte [`README.md`](../README.md) para pré-requisitos, variáveis de ambiente e comandos de execução.

## Como uma requisição percorre a aplicação

1. O Apache encaminha rotas da aplicação para `public/index.php`; arquivos públicos em `public/` são servidos como assets.
2. O front controller carrega o autoload do Composer e `.env`, define timezone e cabeçalhos HTTP e inicia a sessão.
3. `routes/web.php` declara método HTTP, rota, controller e middlewares. `Router` extrai parâmetros e executa os middlewares antes do controller.
4. Middlewares tratam autenticação, papel/permissão e CSRF conforme a rota.
5. Controllers coordenam a requisição. Regras de negócio ficam principalmente em `app/Services/`; consultas e persistência ficam em `app/Repositories/`, usando `app/Core/Database.php` e PDO.
6. `app/Core/View.php` renderiza o template em `resources/views/pages/` dentro de um layout compartilhado de `resources/views/layouts/`, com parciais de `resources/views/partials/`.
7. CSS e JavaScript ficam em `public/assets/`. `public/assets/css/main.css` importa folhas compartilhadas; o layout pode carregar CSS específico da página. Alguns templates carregam scripts próprios com `defer`.

O fluxo de interface é normalmente uma submissão tradicional GET/POST com resposta/redirect do PHP. `fetch()` é reservado a interações específicas; exemplos estão em `public/assets/js/app.js` e scripts `staff-*.js`. Mesmo quando há validação no navegador, a regra e a autorização precisam ser verificadas no backend.

## Mapa do repositório

| Caminho | Responsabilidade |
| --- | --- |
| `app/Core/` | Request, Router, View, Session, Env, Response e conexão PDO |
| `app/Controllers/` | Entrada HTTP e coordenação dos fluxos por área |
| `app/Services/` | Regras de autenticação, agendamento, ciclo de status, Storage e agenda |
| `app/Repositories/` | Persistência e leitura PostgreSQL |
| `app/Middleware/`, `app/Security/` | Autenticação, papéis, CSRF, rate limiting e proteções correlatas |
| `app/Support/`, `app/Validation/` | Status, UUID, navegação, imagens, validação e helpers |
| `routes/web.php` | Rotas HTTP e middlewares aplicados |
| `resources/views/` | Layouts, páginas PHP e componentes/parciais |
| `public/assets/` | CSS, JavaScript, imagens, ícones e vídeos públicos |
| `database/schema.sql` | Schema completo usado para inicialização/reset |
| `database/migrations/` | Migrações incrementais disponíveis |
| `bin/` | Runner de migração e utilitários de diagnóstico/operação |
| `tests/` | Testes PHP, fixtures e verificações de layout |
| `docs/` | Documentação de requisitos, backlog, banco e regras operacionais |
| `.agents/skills/` | Procedimentos especializados do projeto; leia a skill pertinente à tarefa |

## Contratos importantes do domínio

### Identidade, login e segurança

- A autenticação de aplicação usa **sessões PHP**, não JWT. O cookie de sessão é `HttpOnly`, `SameSite=Lax`, com `Secure` conforme ambiente/configuração; os dados de sessão ficam em `storage/sessions` no servidor.
- “Lembrar de mim” é separado da sessão: há tokens revogáveis em `remember_tokens`, restaurados pelo backend. Não trate o cookie persistente como uma sessão longa sem expiração.
- O cadastro aceita os termos; o login não solicita um novo aceite.
- Formulários mutáveis usam CSRF. As rotas também devem verificar papel, vínculo/posse do recurso e transições permitidas no servidor.
- Senhas usam `password_hash()`/`password_verify()`. Saídas em HTML devem ser escapadas; SQL deve usar parâmetros preparados.
- A recuperação de senha sempre apresenta uma confirmação genérica para evitar revelar se um e-mail tem conta; o endereço informado fica na sessão e é escapado ao exibir.
- Os links de redefinição identificam o token com um seletor UUID e um segredo aleatório; somente o hash do segredo fica em `account_tokens`. O token expira em 30 minutos, o reenvio protegido por CSRF invalida o anterior e o `GET` não o consome (para tolerar scanners de e-mail). A senha só muda no `POST` válido, na mesma transação que consome o token; então `auth_version` e tokens “Lembrar de mim” são revogados.
- `APP_URL` monta o link: `http://localhost:8080` no desenvolvimento local e `https://beleza-cao-e-gato.onrender.com` na produção. Em produção, somente HTTPS é aceito. A página com token define `Cache-Control: no-store` e `Referrer-Policy: no-referrer`.
- `AUTH_BYPASS` só deve ser usado em `APP_ENV=development`; nunca habilite em ambiente público ou de produção.

### Agendamentos e status

- Status válidos no fluxo atual: `pending`, `in_progress`, `completed` e `cancelled`. `confirmed` é legado, não reintroduza como status de aplicação.
- A reserva aceita de 1 a 10 serviços ativos e distintos. Preço e duração são calculados/conferidos no backend; snapshots dos serviços ficam associados ao agendamento.
- Inícios disponíveis ocorrem em intervalos de **30 minutos**; o horário de trabalho e a duração total precisam respeitar o fechamento às 18:00.
- `BookingService::BUFFER_MINUTES` define um intervalo operacional de **30 minutos** entre compromissos incompatíveis de profissional/pet. Disponibilidade deve ser revalidada na confirmação e concorrência tratada no backend.
- O cancelamento automático de `pending` após **30 minutos do horário marcado** é outra regra, separada do intervalo operacional; veja `AppointmentLifecycleService`.
- “Sem preferência” deve ser resolvido no servidor para um profissional livre; o frontend não é fonte autoritativa de disponibilidade.

### PostgreSQL e datas

- IDs das entidades principais são UUIDs (`gen_random_uuid()`); referências relacionadas também usam UUID. `appointment_services` usa chave composta.
- O schema usa `TIMESTAMPTZ`. A conexão PHP aplica `APP_TIMEZONE` (normalmente `America/Sao_Paulo`); funções e triggers do schema cuidam dos timestamps de criação/atualização.
- Antes de mudar persistência, confira `database/schema.sql`, as migrações, repositórios e consumidores PHP. Migrações incrementais existentes não substituem automaticamente IDs inteiros legados por UUID.
- `database/schema.sql` e `php bin/migrate.php` são destrutivos para os objetos/dados da aplicação no schema `public`. Não os execute em banco com dados que devam ser preservados.
- Testes que reinicializam banco devem rodar somente com o ambiente descartável e as guardas de `compose.booking-qa.yaml`/testes. Nunca use a conexão normal do `.env` para esse propósito.

### Supabase Storage

- Os buckets existentes são `img-user` e `img-pets`; a aplicação não cria buckets.
- O backend usa `SupabaseStorageService` e cURL com `SUPABASE_URL` e `SUPABASE_SERVICE_ROLE_KEY`. A chave privilegiada nunca vai para HTML, JavaScript ou logs.
- O nome do objeto é aleatório (bytes criptograficamente seguros em hexadecimal) e preserva a extensão validada; o nome original enviado não é usado como nome do arquivo.
- Caminhos são guardados no banco. Fotos privadas são servidas por rotas autenticadas que verificam autorização; não transforme essa arquitetura em URL pública sem decisão explícita de segurança.
- Após a autorização, `PrivateImageResponse::isNotModified()` persiste e fecha a sessão antes da revalidação/transferência. Isso libera outras requisições do mesmo usuário durante o download. Os controllers de foto não devem escrever na sessão depois desse ponto, inclusive em erros. O cache continua `private, no-cache, must-revalidate`; uma transferência já autorizada pode terminar após logout concorrente, mas não reabre/restaura a sessão. Novas requisições continuam passando pela autenticação. Teste isolado: `php tests/image-session-lock.php` (sem banco ou Storage remoto).
- A configuração documentada está no `.env.example`; valores reais pertencem somente ao `.env`/ambiente do servidor e não devem ser copiados para documentação ou resposta.

## Orientações para agentes que forem trabalhar no repositório

1. Leia este guia e `AGENTS.md`; depois inspecione o código atual do escopo. Código/schema/configuração prevalecem sobre documentação antiga ou descrição de tarefa anterior.
2. Identifique e leia a skill pertinente em `.agents/skills/` antes de atuar. Use somente skills que se aplicam ao pedido.
3. Preserve mudanças locais existentes. Confira `git status` e o diff antes de editar; não descarte nem inclua alterações alheias à tarefa.
4. Mantenha PHP puro, PostgreSQL via PDO, HTML/CSS/JavaScript Vanilla e Supabase Storage por cURL. Não adicione framework ou dependência por conveniência.
5. Em alterações visuais, verifique os assets realmente carregados, layouts compartilhados, breakpoints, conteúdo extenso, teclado/foco e `prefers-reduced-motion` quando houver animação.
6. Em mudanças de backend, valide autenticação, autorização, CSRF, encoding, erros e consistência transacional conforme o fluxo afetado.
7. Execute apenas verificações pertinentes e informe exatamente o que foi executado. Não alegue teste visual, integração ou cobertura que não ocorreu.
8. Não faça commit, reset, migração destrutiva, alteração de dados ou ação externa sem que isso faça parte do pedido autorizado.

## Referências para aprofundamento

- [`README.md`](../README.md): instalação, ambiente, comandos, banco, testes e operação.
- [`docs/levantamento-de-requisitos.md`](levantamento-de-requisitos.md): requisitos levantados e pendências.
- [`docs/backlog.md`](backlog.md): funcionalidades atuais e itens futuros/propostos.
- [`docs/agendamentos-status.md`](agendamentos-status.md): ciclo de vida e migração de status.
- [`docs/banco-uuid-fuso.md`](banco-uuid-fuso.md): decisões relacionadas a UUID e timezone.
- `database/schema.sql`: definição efetiva do banco.
- `routes/web.php`: catálogo atual de rotas.
- `app/Services/BookingService.php`, `app/Services/AppointmentLifecycleService.php`: regras centrais de agendamento e expiração.
- `app/Services/SupabaseStorageService.php`: contrato de Storage, validação e requests.
