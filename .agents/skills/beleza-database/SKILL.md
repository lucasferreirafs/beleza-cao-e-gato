---
name: beleza-database
description: Analisa ou modifica consultas PostgreSQL/PDO, schema, índices e migrações do Beleza Cão&Gato. Use quando uma tarefa muda persistência ou desempenho SQL; para disponibilidade e reservas combine com beleza-booking-engine.
---

# PostgreSQL e PDO

1. Confira `database/schema.sql`, migrações em `database/migrations/`, chamadas do repository e compatibilidade com bancos existentes. Leia [migrações seguras](references/migracoes.md) antes de alterar estrutura ou dados.
2. Use PDO prepared statements para dados externos. Identificadores SQL dinâmicos só podem vir de allowlist interna. Trate `NULL` explicitamente e proteja operações atômicas com transação e rollback.
3. Fundamente índices no `WHERE`, `JOIN` e ordenação reais; evite N+1 e resultados sem limite quando crescerem. Preserve chaves estrangeiras e constraints que protegem invariantes.
4. Teste no banco descartável `booking_qa`; não use `schema.sql` em banco existente. Descreva migração, rollback e impacto em dados antes de propor execução real.
