# Migrações e banco existente

- `database/schema.sql` começa removendo objetos de `public`; `bin/migrate.php` executa esse arquivo. Ambos são somente para banco novo ou descartável.
- Há migrações `001` a `005` em `database/migrations/` e scripts incrementais em `bin/`. Confirme quais já foram aplicadas antes de sugerir ordem ou reexecução.
- Antes de alterar estrutura: localize queries e views afetadas; avalie `NULL`, dados existentes, lock, duração, compatibilidade da aplicação e rollback.
- Testes com reset exigem `APP_ENV=testing`, `BOOKING_QA_ALLOW_RESET=1`, `DB_DATABASE=booking_qa` e `DB_HOST=beleza-booking-qa-db`. Confira a guarda no script específico.
- O projeto usa PostgreSQL via PDO; não assuma Data API ou políticas RLS para as consultas PHP. Revise políticas RLS apenas quando a superfície Supabase exposta estiver no escopo.
