CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY, name VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL CHECK (role IN ('client','staff','admin')),
    status VARCHAR(20) NOT NULL DEFAULT 'active', created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS pets (
    id BIGSERIAL PRIMARY KEY, owner_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(80) NOT NULL, species VARCHAR(40) NOT NULL, breed VARCHAR(100) NOT NULL,
    sex VARCHAR(10) NOT NULL, birth_date DATE, weight NUMERIC(6,2), allergies TEXT,
    neutered BOOLEAN NOT NULL DEFAULT false, behavior VARCHAR(100), special_needs TEXT, notes TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS services (
    id BIGSERIAL PRIMARY KEY, name VARCHAR(120) NOT NULL, description TEXT NOT NULL,
    duration_minutes INTEGER NOT NULL CHECK(duration_minutes > 0), price NUMERIC(10,2) NOT NULL CHECK(price >= 0), active BOOLEAN NOT NULL DEFAULT true
);

CREATE TABLE IF NOT EXISTS appointments (
    id BIGSERIAL PRIMARY KEY, pet_id BIGINT NOT NULL REFERENCES pets(id), service_id BIGINT NOT NULL REFERENCES services(id),
    staff_id BIGINT REFERENCES users(id), scheduled_at TIMESTAMPTZ NOT NULL, status VARCHAR(30) NOT NULL,
    price NUMERIC(10,2) NOT NULL, notes TEXT, created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_pets_owner ON pets(owner_id);
CREATE INDEX IF NOT EXISTS idx_appointments_pet_date ON appointments(pet_id, scheduled_at DESC);
CREATE INDEX IF NOT EXISTS idx_appointments_staff_date ON appointments(staff_id, scheduled_at);

INSERT INTO users(name,email,password_hash,role) VALUES
    ('Maria Eduarda Silva','cliente@demo.local','$2y$10$ubzpD6lTOIUyhtlufX5eBOAe8svE5edIY1Jo/a4RjZQpRVKuDym.2','client'),
    ('João Silva','staff@demo.local','$2y$10$SoWWh.1FdA9g5zMwqygaWuqMtzs9cO4VLmS4XHdvra9bxTK6LyujK','staff'),
    ('Administrador Geral','admin@demo.local','$2y$10$9cGIc4m3jK8Nay9//E6ioO/tNUVfhq3/bov5ZsDDysxT..vTDgprO','admin')
ON CONFLICT(email) DO NOTHING;

INSERT INTO services(name,description,duration_minutes,price) VALUES
    ('Banho Simples','Higienização completa, corte de unhas, limpeza de ouvidos e secagem profissional.',45,45),
    ('Tosa Estética','Tosa higiênica e padrão da raça com equipamentos premium.',60,55),
    ('Banho + Tosa Completa','Pacote completo de bem-estar com hidratação de pelos.',90,80),
    ('Corte de Unhas','Aparamento seguro e lixamento das patinhas.',15,25),
    ('Limpeza de Ouvidos','Prevenção de otite com produtos suaves.',15,30),
    ('Escovação de Dentes','Prevenção de tártaro e mau hálito.',20,20)
ON CONFLICT DO NOTHING;

INSERT INTO pets(owner_id,name,species,breed,sex,birth_date,weight,behavior)
SELECT u.id,v.name,v.species,v.breed,v.sex,v.birth_date::date,v.weight,v.behavior FROM users u CROSS JOIN (VALUES
    ('Luna','Cachorro','Golden Retriever','Fêmea','2023-08-15',28.0,'Dócil e Brincalhão'),
    ('Mel','Gato','Siamês','Fêmea','2025-02-01',4.2,'Curiosa'),
    ('Toddy','Cachorro','Shih Tzu','Macho','2024-05-20',6.1,'Tranquilo')) v(name,species,breed,sex,birth_date,weight,behavior)
WHERE u.email='cliente@demo.local' AND NOT EXISTS(SELECT 1 FROM pets p WHERE p.owner_id=u.id);
