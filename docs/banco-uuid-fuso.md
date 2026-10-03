# UUID e fuso horário no PostgreSQL

## Identificadores

`database/schema.sql` cria UUIDs com `gen_random_uuid()` como chaves primárias de `users`, `pets`, `services`, `appointments` e `account_tokens`. As respectivas chaves estrangeiras também são UUID. `appointment_services` conserva a chave composta `(appointment_id, service_id)`; `remember_tokens` conserva o seletor aleatório como chave primária, pois ele é o identificador revogável do cookie.

As rotas, sessões, formulários, repositories e o motor de agendamento tratam os identificadores como strings. Entradas de URL e formulários são validadas em `app/Support/Uuid.php`; não converta um UUID para `int`.

As migrações incrementais antigas não transformam IDs BIGINT existentes em UUID. A troca exige o reset explícito do schema da aplicação ou uma migração de dados com mapeamento de todas as chaves e referências. **Não execute `bin/migrate.php` em um banco cujos dados devam ser preservados.** O script remove as tabelas da aplicação em `public` e recria dados de demonstração; não limpa objetos do Supabase Storage.

## Horário brasileiro

Os campos de evento continuam como `TIMESTAMPTZ`: o PostgreSQL armazena o instante absoluto, e o fuso da conexão determina sua apresentação. `public.brazil_timestamp()` fornece o relógio real para defaults e `public.update_timestamp_brazil()` atualiza `updated_at` via trigger. O reset em `bin/migrate.php` configura `America/Sao_Paulo` como padrão do banco e da role de conexão naquele banco; `app/Core/Database.php` também define o fuso em cada conexão PDO. A role é configurada porque um pooler pode iniciar sessões sem herdar apenas o padrão do banco.

Não aplique `-03:00` manualmente a valores `TIMESTAMPTZ`. Para uma entrada de agendamento local, o PHP constrói o horário em `APP_TIMEZONE` e envia um instante com offset ao PostgreSQL. Consultas como `CURRENT_DATE` e `scheduled_at::date` dependem do fuso da sessão; por isso a configuração da conexão é parte da regra de negócio.

Verificação em um banco autorizado, após o reset:

```sql
SHOW TimeZone;
SELECT id, created_at FROM users LIMIT 1;
SELECT public.brazil_timestamp();
```
