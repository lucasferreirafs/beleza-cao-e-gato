-- ATENÇÃO: este script apaga e recria os objetos do schema public.
-- Execute somente quando for apropriado reinicializar o banco.

-- 1. Remove views, rotinas, índices, tabelas e tipos existentes.
DO $$
DECLARE
   object_record RECORD;
BEGIN
   FOR object_record IN
      SELECT schemaname, viewname AS object_name
      FROM pg_views
      WHERE schemaname = 'public'
   LOOP
      EXECUTE format('DROP VIEW IF EXISTS %I.%I CASCADE', object_record.schemaname, object_record.object_name);
   END LOOP;

   FOR object_record IN
      SELECT schemaname, matviewname AS object_name
      FROM pg_matviews
      WHERE schemaname = 'public'
   LOOP
      EXECUTE format('DROP MATERIALIZED VIEW IF EXISTS %I.%I CASCADE', object_record.schemaname, object_record.object_name);
   END LOOP;

   FOR object_record IN
      SELECT
         n.nspname AS schema_name,
         p.proname AS routine_name,
         p.prokind AS routine_kind,
         pg_get_function_identity_arguments(p.oid) AS identity_arguments
      FROM pg_proc p
      JOIN pg_namespace n ON n.oid = p.pronamespace
      WHERE n.nspname = 'public'
         AND p.prokind IN ('f', 'p')
   LOOP
      EXECUTE format(
         'DROP %s IF EXISTS %I.%I(%s) CASCADE',
         CASE WHEN object_record.routine_kind = 'p' THEN 'PROCEDURE' ELSE 'FUNCTION' END,
         object_record.schema_name,
         object_record.routine_name,
         object_record.identity_arguments
      );
   END LOOP;

   FOR object_record IN
      SELECT n.nspname AS schema_name, index_class.relname AS index_name
      FROM pg_index i
      JOIN pg_class index_class ON index_class.oid = i.indexrelid
      JOIN pg_class table_class ON table_class.oid = i.indrelid
      JOIN pg_namespace n ON n.oid = table_class.relnamespace
      WHERE n.nspname = 'public'
         AND NOT EXISTS (SELECT 1 FROM pg_constraint c WHERE c.conindid = i.indexrelid)
   LOOP
      EXECUTE format('DROP INDEX IF EXISTS %I.%I CASCADE', object_record.schema_name, object_record.index_name);
   END LOOP;

   FOR object_record IN
      SELECT tablename AS object_name
      FROM pg_tables
      WHERE schemaname = 'public'
   LOOP
      EXECUTE format('DROP TABLE IF EXISTS public.%I CASCADE', object_record.object_name);
   END LOOP;

   FOR object_record IN
      SELECT t.typname AS object_name
      FROM pg_type t
      JOIN pg_namespace n ON n.oid = t.typnamespace
      WHERE n.nspname = 'public'
         AND t.typtype IN ('c', 'd', 'e', 'r', 'm')
   LOOP
      EXECUTE format('DROP TYPE IF EXISTS public.%I CASCADE', object_record.object_name);
   END LOOP;
END;
$$;

-- 2. Cria tabelas.
CREATE TABLE users (
   id BIGSERIAL PRIMARY KEY,
   name VARCHAR(120) NOT NULL,
   email VARCHAR(190) NOT NULL UNIQUE,
   password_hash VARCHAR(255) NOT NULL,
   role VARCHAR(20) NOT NULL CHECK (role IN ('client', 'staff', 'admin')),
   status VARCHAR(30) NOT NULL DEFAULT 'active',
   phone VARCHAR(30),
   cpf VARCHAR(14),
   birth_date DATE,
   email_verified_at TIMESTAMPTZ,
   terms_version VARCHAR(30),
   terms_accepted_at TIMESTAMPTZ,
   avatar_path VARCHAR(100),
   auth_version INTEGER NOT NULL DEFAULT 1,
   created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
   updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE pets (
   id BIGSERIAL PRIMARY KEY,
   owner_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
   name VARCHAR(80) NOT NULL,
   species VARCHAR(40) NOT NULL,
   breed VARCHAR(100) NOT NULL,
   sex VARCHAR(10) NOT NULL,
   birth_date DATE,
   weight NUMERIC(6, 2),
   allergies TEXT,
   has_allergies BOOLEAN GENERATED ALWAYS AS (NULLIF(btrim(allergies), '') IS NOT NULL) STORED,
   neutered BOOLEAN NOT NULL DEFAULT false,
   behavior VARCHAR(100),
   special_needs TEXT,
   notes TEXT,
   coat_color VARCHAR(60),
   photo_path VARCHAR(100),
   created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
   updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE services (
   id BIGSERIAL PRIMARY KEY,
   name VARCHAR(120) NOT NULL,
   description TEXT NOT NULL,
   duration_minutes INTEGER NOT NULL CHECK (duration_minutes > 0),
   price NUMERIC(10, 2) NOT NULL CHECK (price >= 0),
   active BOOLEAN NOT NULL DEFAULT true,
   created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
   updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE appointments (
   id BIGSERIAL PRIMARY KEY,
   pet_id BIGINT NOT NULL REFERENCES pets(id),
   service_id BIGINT NOT NULL REFERENCES services(id),
   staff_id BIGINT REFERENCES users(id),
   scheduled_at TIMESTAMPTZ NOT NULL,
   status VARCHAR(30) NOT NULL DEFAULT 'pending'
      CONSTRAINT appointments_status_check CHECK (status IN ('pending', 'in_progress', 'completed', 'cancelled')),
   price NUMERIC(10, 2) NOT NULL,
   notes TEXT,
   duration_minutes INTEGER NOT NULL CHECK (duration_minutes > 0),
   completed_at TIMESTAMPTZ,
   created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
   updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE appointment_services (
   appointment_id BIGINT NOT NULL REFERENCES appointments(id) ON DELETE CASCADE,
   service_id BIGINT NOT NULL REFERENCES services(id),
   position SMALLINT NOT NULL CHECK (position > 0),
   service_name VARCHAR(120) NOT NULL,
   price NUMERIC(10, 2) NOT NULL CHECK (price >= 0),
   duration_minutes INTEGER NOT NULL CHECK (duration_minutes > 0),
   PRIMARY KEY (appointment_id, service_id),
   UNIQUE (appointment_id, position)
);

CREATE TABLE account_tokens (
   id BIGSERIAL PRIMARY KEY,
   user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
   purpose VARCHAR(30) NOT NULL CHECK (purpose IN ('verify_email', 'reset_password')),
   token_hash VARCHAR(255) NOT NULL,
   expires_at TIMESTAMPTZ NOT NULL,
   consumed_at TIMESTAMPTZ,
   created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- 3. Cria tabelas de autenticação persistente.
CREATE TABLE remember_tokens (
   selector VARCHAR(32) PRIMARY KEY,
   user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
   token_hash VARCHAR(64) NOT NULL,
   previous_hash VARCHAR(64),
   previous_valid_until TIMESTAMPTZ,
   expires_at TIMESTAMPTZ NOT NULL,
   created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
ALTER TABLE remember_tokens ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON remember_tokens FROM PUBLIC;
DO $$
BEGIN
   IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'anon') THEN
      REVOKE ALL ON remember_tokens FROM anon;
   END IF;
   IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'authenticated') THEN
      REVOKE ALL ON remember_tokens FROM authenticated;
   END IF;
END;
$$;

-- 4. Cria índices para consultas, disponibilidade e unicidade.
CREATE INDEX idx_remember_tokens_user ON remember_tokens (user_id);
CREATE INDEX idx_remember_tokens_expiry ON remember_tokens (expires_at);
CREATE INDEX idx_pets_owner ON pets (owner_id);
CREATE INDEX idx_appointments_pet_date ON appointments (pet_id, scheduled_at DESC);
CREATE INDEX idx_appointments_staff_date ON appointments (staff_id, scheduled_at);
CREATE INDEX idx_appointments_status_date ON appointments (status, scheduled_at);
CREATE INDEX appointments_staff_schedule_active_idx
   ON appointments (staff_id, scheduled_at)
   WHERE staff_id IS NOT NULL AND status <> 'cancelled';
CREATE INDEX appointments_unassigned_schedule_active_idx
   ON appointments (scheduled_at)
   WHERE staff_id IS NULL AND status <> 'cancelled';
CREATE INDEX appointments_pending_expiry_idx
   ON appointments (scheduled_at)
   WHERE status = 'pending';
CREATE INDEX idx_account_tokens_lookup ON account_tokens (user_id, purpose, expires_at DESC);
CREATE UNIQUE INDEX uq_users_email_lower ON users (lower(email));
CREATE UNIQUE INDEX uq_users_cpf ON users (cpf) WHERE cpf IS NOT NULL;

-- 5. Cria dados iniciais de demonstração.
INSERT INTO users (name, email, password_hash, role)
VALUES
   ('Maria Eduarda Silva', 'cliente@demo.local', '$2y$10$ubzpD6lTOIUyhtlufX5eBOAe8svE5edIY1Jo/a4RjZQpRVKuDym.2', 'client'),
   ('João Silva', 'staff@demo.local', '$2y$10$SoWWh.1FdA9g5zMwqygaWuqMtzs9cO4VLmS4XHdvra9bxTK6LyujK', 'staff'),
   ('Administrador Geral', 'admin@demo.local', '$2y$10$9cGIc4m3jK8Nay9//E6ioO/tNUVfhq3/bov5ZsDDysxT..vTDgprO', 'admin');

UPDATE users
SET email_verified_at = COALESCE(email_verified_at, now())
WHERE email IN ('cliente@demo.local', 'staff@demo.local', 'admin@demo.local');

INSERT INTO services (name, description, duration_minutes, price)
VALUES
   ('Banho Simples', 'Higienização completa, corte de unhas, limpeza de ouvidos e secagem profissional.', 45, 45),
   ('Tosa Estética', 'Tosa higiênica e padrão da raça com equipamentos premium.', 60, 55),
   ('Banho + Tosa Completa', 'Pacote completo de bem-estar com hidratação de pelos.', 90, 80),
   ('Corte de Unhas', 'Aparamento seguro e lixamento das patinhas.', 15, 25),
   ('Limpeza de Ouvidos', 'Prevenção de otite com produtos suaves.', 15, 30),
   ('Escovação de Dentes', 'Prevenção de tártaro e mau hálito.', 20, 20);

INSERT INTO pets (owner_id, name, species, breed, sex, birth_date, weight, behavior)
SELECT u.id, pet.name, pet.species, pet.breed, pet.sex, pet.birth_date::date, pet.weight, pet.behavior
FROM users u
CROSS JOIN (VALUES
   ('Luna', 'Cachorro', 'Golden Retriever', 'Fêmea', '2023-08-15', 28.0, 'Dócil e Brincalhão'),
   ('Mel', 'Gato', 'Siamês', 'Fêmea', '2025-02-01', 4.2, 'Curiosa'),
   ('Toddy', 'Cachorro', 'Shih Tzu', 'Macho', '2024-05-20', 6.1, 'Tranquilo')
) AS pet(name, species, breed, sex, birth_date, weight, behavior)
WHERE u.email = 'cliente@demo.local';
