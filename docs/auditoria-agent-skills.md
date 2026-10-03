# Auditoria inicial das Agent Skills — 2026-10-03

Este registro documenta a aplicação inicial das 18 Skills de `.agents/skills/` ao código disponível nesta data. É uma revisão delimitada, não uma certificação de qualidade, segurança ou acessibilidade de todas as telas. O workspace já continha alterações não commitadas da página de atendimentos; a auditoria não as descartou nem as incorporou a outro trabalho.

## Fonte de verdade e correções

- `BookingService::BUFFER_MINUTES` e `AppointmentLifecycleService::PENDING_TOLERANCE_MINUTES` têm valor 30, para regras diferentes. O código não foi alterado. README, requisitos, backlog e documentação de status foram corrigidos onde ainda diziam 60 minutos.
- `confirmed` aparece na migração `005` e em testes de conversão/rejeição legada. Não é aceito como status operacional; o schema e `AppointmentStatus` aceitam apenas `pending`, `in_progress`, `completed` e `cancelled`.
- Imagens privadas usam `SupabaseStorageService` via PHP/cURL e `PrivateImageResponse` com cache HTTP privado e ETag. Não há cache físico de imagens em `public/assets/cache/`.

## Aplicação por especialidade

| Skills | Verificação realizada |
| --- | --- |
| `beleza-feature-development`, `beleza-php-backend`, `beleza-refactoring` | Conferidas rota → controller → service/repository → view, autoload PSR-4 e preservação das mudanças pré-existentes. Nenhuma funcionalidade ou refatoração de produção foi iniciada nesta missão. |
| `beleza-database`, `beleza-booking-engine` | Conferidos schema, migrações, status, intervalo e bloqueio transacional; executadas suites de booking e ciclo de vida no banco descartável. |
| `beleza-application-security`, `beleza-supabase-storage` | Conferidos middleware de papel, autorização por registro, sessão, CSRF, hash de senha, proxy privado de imagens e testes do transporte Storage. Não foi realizado teste remoto no projeto Supabase. |
| `beleza-frontend`, `beleza-ui-implementation`, `beleza-accessibility-review`, `beleza-ux-review` | Revisados padrões de layout, assets, foco, navegação, overlay e mecanismos mobile existentes. A sintaxe dos nove arquivos JS foi verificada; esta auditoria não substitui inspeção manual de cada tela com teclado e leitor de tela. |
| `beleza-performance`, `beleza-observability` | Identificados os caminhos de cache HTTP, consultas compartilhadas, tratamento de erro e logging mínimo. Não houve medição de Core Web Vitals, perfil SQL nem tráfego real de Storage; não se afirma ganho de performance. |
| `beleza-testing`, `beleza-qa-release` | Executados testes básicos, Storage simulado, lint PHP, booking e ciclo de vida; contêineres de QA foram encerrados após os testes. |
| `beleza-code-review`, `beleza-debugging`, `beleza-documentation` | Comparadas afirmações da documentação ao código; corrigidas divergências comprovadas. Não houve defeito novo reproduzido que justificasse patch funcional. |

## Evidência executada

- `php tests/run.php`: passou.
- `php tests/storage.php`: 198 verificações passaram, sem rede ou credenciais reais.
- `php tests/lint.php`: passou.
- `node --check` nos nove arquivos de `public/assets/js/`: passou.
- `php tests/booking.php` em `compose.booking-qa.yaml`: 650 verificações passaram, inclusive concorrência real em schemas legado e moderno.
- `php tests/appointment-lifecycle.php` no mesmo QA: 45 verificações passaram.
- Validação local dos 18 `SKILL.md`: front matter, nomes, descrições, links e ausência de placeholders passaram. O `quick_validate.py` do `skill-creator` não pôde iniciar porque o Python disponível não contém PyYAML; não foi instalada dependência para contornar isso.

## Evals de ativação

| Solicitação simulada | Skills primárias esperadas |
| --- | --- |
| Corrija conflito de dois agendamentos simultâneos | `beleza-debugging`, `beleza-booking-engine`, `beleza-testing` |
| Crie CRUD de funcionários | `beleza-feature-development`, `beleza-php-backend`, `beleza-database` |
| Audite segurança do login | `beleza-application-security`, `beleza-code-review` |
| Analise uma tela no celular sem alterar código | `beleza-ux-review`, `beleza-accessibility-review` |
| Implemente um mockup mobile | `beleza-ui-implementation`, `beleza-frontend` |
| Investigue carregamento lento de fotos | `beleza-performance`, `beleza-supabase-storage` |
| Atualize o README após mudar a regra de status | `beleza-documentation`, `beleza-booking-engine` |

## Itens futuros fora desta missão

- Não há PHPUnit ou PHPStan em `composer.json`; avaliar incorporação gradual quando houver necessidade e orçamento de manutenção.
- Playwright é usado por testes `.cjs`, mas seu runtime/navegador não é declarado como dependência reproduzível do repositório.
- Uma revisão completa de acessibilidade, performance e segurança em ambiente de produção exige ambiente, dados e critérios próprios; os testes acima não fornecem essa garantia.
