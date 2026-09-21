CREATE TABLE IF NOT EXISTS users (
 id BIGSERIAL PRIMARY KEY, name VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL CHECK (role IN ('client','staff','admin')),
 status VARCHAR(30) NOT NULL DEFAULT 'active', phone VARCHAR(30), cpf VARCHAR(14), birth_date DATE,
 email_verified_at TIMESTAMPTZ, created_at TIMESTAMPTZ NOT NULL DEFAULT now(), updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(30);
ALTER TABLE users ADD COLUMN IF NOT EXISTS cpf VARCHAR(14);
ALTER TABLE users ADD COLUMN IF NOT EXISTS birth_date DATE;
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified_at TIMESTAMPTZ;
ALTER TABLE users ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ NOT NULL DEFAULT now();
CREATE TABLE IF NOT EXISTS pets (
 id BIGSERIAL PRIMARY KEY, owner_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
 name VARCHAR(80) NOT NULL, species VARCHAR(40) NOT NULL, breed VARCHAR(100) NOT NULL,
 sex VARCHAR(10) NOT NULL, birth_date DATE, weight NUMERIC(6,2), allergies TEXT,
 neutered BOOLEAN NOT NULL DEFAULT false, behavior VARCHAR(100), special_needs TEXT, notes TEXT,
 created_at TIMESTAMPTZ NOT NULL DEFAULT now(), updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
ALTER TABLE pets ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ NOT NULL DEFAULT now();
ALTER TABLE pets ADD COLUMN IF NOT EXISTS coat_color VARCHAR(60);
CREATE TABLE IF NOT EXISTS services (
 id BIGSERIAL PRIMARY KEY, name VARCHAR(120) NOT NULL, description TEXT NOT NULL,
 duration_minutes INTEGER NOT NULL CHECK(duration_minutes > 0), price NUMERIC(10,2) NOT NULL CHECK(price >= 0),
 active BOOLEAN NOT NULL DEFAULT true, created_at TIMESTAMPTZ NOT NULL DEFAULT now(), updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
ALTER TABLE services ADD COLUMN IF NOT EXISTS created_at TIMESTAMPTZ NOT NULL DEFAULT now();
ALTER TABLE services ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ NOT NULL DEFAULT now();
CREATE TABLE IF NOT EXISTS appointments (
 id BIGSERIAL PRIMARY KEY, pet_id BIGINT NOT NULL REFERENCES pets(id), service_id BIGINT NOT NULL REFERENCES services(id),
 staff_id BIGINT REFERENCES users(id), scheduled_at TIMESTAMPTZ NOT NULL, status VARCHAR(30) NOT NULL,
 price NUMERIC(10,2) NOT NULL, notes TEXT, completed_at TIMESTAMPTZ,
 created_at TIMESTAMPTZ NOT NULL DEFAULT now(), updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
ALTER TABLE appointments ADD COLUMN IF NOT EXISTS completed_at TIMESTAMPTZ;
ALTER TABLE appointments ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ NOT NULL DEFAULT now();

CREATE TABLE IF NOT EXISTS account_tokens (
 id BIGSERIAL PRIMARY KEY,
 user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
 purpose VARCHAR(30) NOT NULL CHECK (purpose IN ('verify_email','reset_password')),
 token_hash VARCHAR(255) NOT NULL,
 expires_at TIMESTAMPTZ NOT NULL,
 consumed_at TIMESTAMPTZ,
 created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_pets_owner ON pets(owner_id);
CREATE INDEX IF NOT EXISTS idx_appointments_pet_date ON appointments(pet_id, scheduled_at DESC);
CREATE INDEX IF NOT EXISTS idx_appointments_staff_date ON appointments(staff_id, scheduled_at);
CREATE INDEX IF NOT EXISTS idx_appointments_status_date ON appointments(status, scheduled_at);
CREATE INDEX IF NOT EXISTS idx_account_tokens_lookup ON account_tokens(user_id, purpose, expires_at DESC);
CREATE UNIQUE INDEX IF NOT EXISTS uq_users_email_lower ON users(lower(email));
CREATE UNIQUE INDEX IF NOT EXISTS uq_users_cpf ON users(cpf) WHERE cpf IS NOT NULL;

INSERT INTO users(name,email,password_hash,role) VALUES
 ('Maria Eduarda Silva','cliente@demo.local','$2y$10$ubzpD6lTOIUyhtlufX5eBOAe8svE5edIY1Jo/a4RjZQpRVKuDym.2','client'),
 ('João Silva','staff@demo.local','$2y$10$SoWWh.1FdA9g5zMwqygaWuqMtzs9cO4VLmS4XHdvra9bxTK6LyujK','staff'),
 ('Administrador Geral','admin@demo.local','$2y$10$9cGIc4m3jK8Nay9//E6ioO/tNUVfhq3/bov5ZsDDysxT..vTDgprO','admin')
ON CONFLICT(email) DO NOTHING;
UPDATE users SET email_verified_at = COALESCE(email_verified_at, now())
WHERE email IN ('cliente@demo.local','staff@demo.local','admin@demo.local');
INSERT INTO services(name,description,duration_minutes,price)
SELECT v.name,v.description,v.duration_minutes,v.price
FROM (VALUES
 ('Banho Simples','Higienização completa, corte de unhas, limpeza de ouvidos e secagem profissional.',45,45::numeric),
 ('Tosa Estética','Tosa higiênica e padrão da raça com equipamentos premium.',60,55::numeric),
 ('Banho + Tosa Completa','Pacote completo de bem-estar com hidratação de pelos.',90,80::numeric),
 ('Corte de Unhas','Aparamento seguro e lixamento das patinhas.',15,25::numeric),
 ('Limpeza de Ouvidos','Prevenção de otite com produtos suaves.',15,30::numeric),
 ('Escovação de Dentes','Prevenção de tártaro e mau hálito.',20,20::numeric)
) v(name,description,duration_minutes,price)
WHERE NOT EXISTS (SELECT 1 FROM services s WHERE lower(s.name)=lower(v.name));
INSERT INTO pets(owner_id,name,species,breed,sex,birth_date,weight,behavior)
SELECT u.id,v.name,v.species,v.breed,v.sex,v.birth_date::date,v.weight,v.behavior FROM users u CROSS JOIN (VALUES
 ('Luna','Cachorro','Golden Retriever','Fêmea','2023-08-15',28.0,'Dócil e Brincalhão'),
 ('Mel','Gato','Siamês','Fêmea','2025-02-01',4.2,'Curiosa'),
 ('Toddy','Cachorro','Shih Tzu','Macho','2024-05-20',6.1,'Tranquilo')) v(name,species,breed,sex,birth_date,weight,behavior)
WHERE u.email='cliente@demo.local' AND NOT EXISTS(SELECT 1 FROM pets p WHERE p.owner_id=u.id);
