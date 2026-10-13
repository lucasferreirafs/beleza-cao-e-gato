-- Beleza Cao&Gato: massa ficticia. Executada no Supabase apos autorizacao explicita.
-- Acrescenta 60 usuarios / 150 pets / 1500 agendamentos, sem apagar dados.
-- 1230 completed (maio-outubro/2026), 250 cancelled, 20 pending em 14/10/2026.
-- Cada pet tem dez agendamentos; cada funcionario tem dois pending.
-- Os IDs sao novos UUIDs. Contagens abaixo abrangem somente esta carga.
-- Senha das 60 contas: SeedPets2026! (bcrypt gerado com password_hash no PHP).
-- Nao cria servicos: exige os seis nomes do schema, ativos e sem duplicidade.
-- Nao executar schema.sql/bin/migrate.php para aplicar esta carga.
-- Usar cliente SQL que pare no primeiro erro (no psql: ON_ERROR_STOP=1).
-- Por padrao exige booking_qa. Na execucao principal autorizada, foi definido
-- SET LOCAL beleza.seed_authorized_project='gfyzolwacxbfhrmeuvpl' apos BEGIN,
-- com o projeto de destino previamente confirmado. Nao reexecutar a carga.

BEGIN;
SET LOCAL TIME ZONE 'America/Sao_Paulo';
SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '60s';

DO $$
BEGIN
    IF current_database() <> 'booking_qa' AND NOT (
        current_database()='postgres'
        AND coalesce(current_setting('beleza.seed_authorized_project',true),'')='gfyzolwacxbfhrmeuvpl'
    ) THEN
        RAISE EXCEPTION 'Destino nao autorizado: usar booking_qa ou a autorizacao explicita do projeto Supabase conferido.';
    END IF;
    IF CURRENT_DATE < DATE '2026-10-01' OR CURRENT_DATE >= DATE '2026-10-14' THEN
        RAISE EXCEPTION 'Carga ancorada em outubro/2026: revisar datas antes de executar fora de 01-13/10/2026.';
    END IF;
END;
$$;

-- Serializa com o booking e protege snapshots/contagens durante a transacao.
SELECT pg_advisory_xact_lock(4242, 4);
LOCK TABLE public.users, public.pets, public.services, public.appointments,
    public.appointment_services IN SHARE ROW EXCLUSIVE MODE;

CREATE TEMP TABLE seed_services ON COMMIT DROP AS
SELECT expected.n, s.id, s.name, s.price, s.duration_minutes
FROM (VALUES
    (1, 'Banho Simples'), (2, 'Tosa Estética'), (3, 'Banho + Tosa Completa'),
    (4, 'Corte de Unhas'), (5, 'Limpeza de Ouvidos'), (6, 'Escovação de Dentes')
) AS expected(n, name)
JOIN public.services s ON s.name = expected.name AND s.active;

DO $$
BEGIN
    IF (SELECT count(*) FROM seed_services) <> 6
       OR (SELECT count(DISTINCT n) FROM seed_services) <> 6 THEN
        RAISE EXCEPTION 'Exigidos exatamente seis servicos ativos, um por nome esperado.';
    END IF;
    -- Todos os conjuntos escolhidos abaixo devem caber em blocos de 150 minutos.
    IF EXISTS (SELECT 1 FROM seed_services WHERE duration_minutes > 120)
       OR (SELECT sum(duration_minutes) FROM seed_services WHERE n IN (4,5,6)) > 120 THEN
        RAISE EXCEPTION 'Duracoes atuais incompativeis com a agenda de teste (maximo 120 minutos por conjunto).';
    END IF;
    IF EXISTS (SELECT 1 FROM public.users WHERE email LIKE '%@seed-beleza.example.test') THEN
        RAISE EXCEPTION 'Esta carga ja existe ou o dominio de teste esta ocupado; nenhum registro sera sobrescrito.';
    END IF;
END;
$$;

-- 1. Usuarios: nomes distintos, email/telefone exclusivos e CPF calculado.
CREATE TEMP TABLE seed_users (
    n integer PRIMARY KEY, id uuid NOT NULL, role text NOT NULL,
    name text NOT NULL, email text NOT NULL, phone text NOT NULL, cpf text NOT NULL
) ON COMMIT DROP;

DO $$
DECLARE
    i integer;
    j integer;
    base text;
    digits text;
    total integer;
    digit integer;
    first_names text[] := ARRAY['Ana','Bruno','Carla','Daniel','Elisa','Felipe','Gabriela','Henrique','Isabela','Joao'];
    last_names text[] := ARRAY['Silva','Oliveira','Santos','Costa','Almeida','Pereira'];
BEGIN
    FOR i IN 1..60 LOOP
        base := (710000000 + i)::text;
        digits := base;
        total := 0;
        FOR j IN 1..9 LOOP total := total + substring(digits,j,1)::integer * (11-j); END LOOP;
        digit := (total * 10) % 11;
        digits := digits || CASE WHEN digit=10 THEN '0' ELSE digit::text END;
        total := 0;
        FOR j IN 1..10 LOOP total := total + substring(digits,j,1)::integer * (12-j); END LOOP;
        digit := (total * 10) % 11;
        digits := digits || CASE WHEN digit=10 THEN '0' ELSE digit::text END;
        INSERT INTO seed_users VALUES (
            i, gen_random_uuid(), CASE WHEN i<=10 THEN 'staff' ELSE 'client' END,
            first_names[((i-1)%10)+1] || ' ' || last_names[((i-1)/10)+1],
            CASE WHEN i<=10 THEN 'funcionario' ELSE 'cliente' END
                || lpad((CASE WHEN i<=10 THEN i ELSE i-10 END)::text,2,'0') || '@seed-beleza.example.test',
            '8199' || lpad(i::text,7,'0'), digits
        );
    END LOOP;
    IF EXISTS (SELECT 1 FROM public.users u JOIN seed_users s
        ON lower(u.email)=s.email OR regexp_replace(coalesce(u.cpf,''),'[^0-9]','','g')=s.cpf
        OR regexp_replace(coalesce(u.phone,''),'[^0-9]','','g')=s.phone) THEN
        RAISE EXCEPTION 'Colisao com email, CPF ou telefone existente; carga abortada.';
    END IF;
END;
$$;

INSERT INTO public.users (id,name,email,password_hash,role,status,phone,cpf,birth_date,
    email_verified_at,terms_version,terms_accepted_at)
SELECT id,name,email,'$2y$10$.yB18RFGirjnPgQaEQVlie2J98ZQH6807x5/dwzP8aGdr6syizeCy',
    role,'active',phone,cpf,DATE '1968-01-01' + n*211,
    clock_timestamp(),'1.0',clock_timestamp()
FROM seed_users;

-- 2. Pets: tres por tutor. Nao existe coluna porte; peso/raca/especie sao usados.
-- has_allergies e gerado pelo banco; timestamps usam defaults/triggers existentes.
CREATE TEMP TABLE seed_pets ON COMMIT DROP AS
SELECT row_number() OVER (ORDER BY u.n,p.n)::integer AS n,
    gen_random_uuid() AS id, u.id AS owner_id, p.n AS pet_number,
    (ARRAY['Luna','Mel','Thor','Nina','Simba','Bento','Amora','Theo','Maya','Toby','Lola','Chico','Kira','Pipoca','Lilo'])
        [(((u.n-11)*3+p.n-1)%15)+1] AS name,
    CASE WHEN p.n=3 THEN 'Gato' ELSE 'Cachorro' END AS species,
    CASE WHEN p.n=3 THEN (ARRAY['Siames','Persa','Maine Coon','SRD','Angora'])[((u.n-11)%5)+1]
        ELSE (ARRAY['Golden Retriever','Shih Tzu','Poodle','Labrador','Beagle','SRD'])[((u.n+p.n)%6)+1] END AS breed,
    CASE WHEN (u.n+p.n)%2=0 THEN 'Fêmea' ELSE 'Macho' END AS sex,
    DATE '2019-01-01' + ((u.n*29+p.n*137)%2000) AS birth_date,
    CASE WHEN p.n=3 THEN (3.0+(u.n%30)/10.0)::numeric(6,2)
        ELSE (4.0+((u.n+p.n)%28))::numeric(6,2) END AS weight,
    (u.n+p.n)%2=0 AS neutered,
    CASE WHEN (u.n+p.n)%9=0 THEN 'Sensibilidade a shampoo perfumado' ELSE NULL END AS allergies,
    (ARRAY['Docil','Brincalhao','Timido','Energetico'])[((u.n+p.n)%4)+1] AS behavior,
    CASE WHEN (u.n+p.n)%13=0 THEN 'Atendimento tranquilo, com pausas' ELSE NULL END AS special_needs,
    (ARRAY['Branco','Preto','Caramelo','Cinza','Malhado'])[((u.n+p.n)%5)+1] AS coat_color
FROM seed_users u CROSS JOIN generate_series(1,3) AS p(n)
WHERE u.role='client';

INSERT INTO public.pets (id,owner_id,name,species,breed,sex,birth_date,weight,neutered,
    allergies,behavior,special_needs,coat_color,notes)
SELECT id,owner_id,name,species,breed,sex,birth_date,weight,neutered,
    allergies,behavior,special_needs,coat_color,'Massa ficticia: seed-60-usuarios-outubro-2026'
FROM seed_pets;

-- 3. Distribuicao: oito completed por pet (1200); vinte pets sorteados ganham
-- um pending. Entre os outros 280 registros, sorteamos 30 completed e 250 cancelled.
CREATE TEMP TABLE seed_pending_pets ON COMMIT DROP AS
SELECT id, row_number() OVER (ORDER BY random())::integer AS position FROM seed_pets;

CREATE TEMP TABLE seed_plan ON COMMIT DROP AS
WITH extra AS (
    SELECT p.id AS pet_id,j.n AS visit,
        row_number() OVER (ORDER BY random()) AS rank
    FROM seed_pets p CROSS JOIN generate_series(9,10) j(n)
    JOIN seed_pending_pets pp ON pp.id=p.id
    WHERE NOT (j.n=10 AND pp.position<=20)
)
SELECT gen_random_uuid() AS id,p.id AS pet_id,j.n AS visit,'completed'::text AS status
FROM seed_pets p CROSS JOIN generate_series(1,8) j(n)
UNION ALL
SELECT gen_random_uuid(),pet_id,visit,CASE WHEN rank<=30 THEN 'completed' ELSE 'cancelled' END FROM extra
UNION ALL
SELECT gen_random_uuid(),id,10,'pending' FROM seed_pending_pets WHERE position<=20;

CREATE UNIQUE INDEX ON seed_plan (id);

-- Selecoes distintas de 1 a 3 dos seis servicos (limite operacional 1 a 10).
-- O primeiro item tambem ocupa appointments.service_id para compatibilidade.
CREATE TEMP TABLE seed_items ON COMMIT DROP AS
SELECT plan.id AS appointment_id,s.id AS service_id,selected.position::smallint,
    s.name AS service_name,s.price,s.duration_minutes
FROM seed_plan plan
CROSS JOIN LATERAL unnest(CASE (plan.visit-1)%8
    WHEN 0 THEN ARRAY[1] WHEN 1 THEN ARRAY[2] WHEN 2 THEN ARRAY[3]
    WHEN 3 THEN ARRAY[4] WHEN 4 THEN ARRAY[5] WHEN 5 THEN ARRAY[6]
    WHEN 6 THEN ARRAY[4,5] ELSE ARRAY[4,5,6] END)
    WITH ORDINALITY AS selected(service_n,position)
JOIN seed_services s ON s.n=selected.service_n;

-- Maio a outubro: o grafico usa o mes atual e os cinco anteriores.
-- As datas historicas ficam antes de hoje; apenas segundas a sabados.
CREATE TEMP TABLE seed_months ON COMMIT DROP AS
SELECT m.n,m.total,
    sum(m.total) OVER (ORDER BY m.n)-m.total AS offset,
    array_agg(d.day::date ORDER BY d.day) AS days
FROM (VALUES (1,180),(2,195),(3,205),(4,215),(5,225),(6,210)) m(n,total)
CROSS JOIN LATERAL generate_series(
    DATE '2026-05-01' + (m.n-1)*INTERVAL '1 month',
    LEAST(DATE '2026-05-01' + m.n*INTERVAL '1 month' - INTERVAL '1 day',
        CURRENT_DATE - INTERVAL '1 day'),INTERVAL '1 day') d(day)
WHERE extract(isodow FROM d.day)<>7
GROUP BY m.n,m.total;

DO $$
BEGIN
    IF (SELECT count(*) FROM seed_months)<>6 OR EXISTS (
        SELECT 1 FROM seed_months WHERE cardinality(days)<ceil(total/40.0)
    ) THEN
        RAISE EXCEPTION 'Dias passados insuficientes para distribuir historico sem conflitos; revisar data de execucao.';
    END IF;
END;
$$;

CREATE TEMP TABLE seed_schedule ON COMMIT DROP AS
WITH cancelled AS (
    SELECT p.*,row_number() OVER (ORDER BY random()) AS rank FROM seed_plan p WHERE status='cancelled'
), pending AS (
    SELECT p.*,pp.position AS rank FROM seed_plan p JOIN seed_pending_pets pp ON pp.id=p.pet_id WHERE p.status='pending'
)
SELECT c.id,c.pet_id,c.status,((c.rank-1)%10+1)::integer AS staff_n,
    (m.days[1+(((c.rank-1)/6)::integer % cardinality(m.days))] + TIME '08:30'
        + ((c.rank-1)%4)::integer * INTERVAL '150 minutes') AT TIME ZONE 'America/Sao_Paulo' AS scheduled_at
FROM cancelled c JOIN seed_months m ON m.n=((c.rank-1)%6+1)
UNION ALL
SELECT p.id,p.pet_id,p.status,((p.rank-1)%10+1)::integer,
    (DATE '2026-10-14' + TIME '08:00'
        + (((p.rank-1)/10)::integer*4 + ((p.rank-1)%10)::integer%4)*INTERVAL '60 minutes')
        AT TIME ZONE 'America/Sao_Paulo'
FROM pending p;

-- Escolha dos horarios historicos com nova checagem de pet e profissional.
-- Preferimos dias espalhados no mes, depois outros slots livres desse mes.
CREATE INDEX ON seed_schedule (scheduled_at,pet_id);
CREATE TEMP TABLE seed_slots ON COMMIT DROP AS
SELECT row_number() OVER () AS id,m.n AS month_n,d.position AS day_position,u.n AS staff_n,
    (d.day + TIME '08:00' + slot.n*INTERVAL '150 minutes')
        AT TIME ZONE 'America/Sao_Paulo' AS scheduled_at,false AS used
FROM seed_months m CROSS JOIN LATERAL unnest(m.days) WITH ORDINALITY d(day,position)
CROSS JOIN generate_series(0,3) slot(n) CROSS JOIN seed_users u WHERE u.role='staff';
CREATE UNIQUE INDEX ON seed_slots (id);
CREATE INDEX ON seed_slots (month_n,used,scheduled_at,staff_n);
ANALYZE seed_slots;
ANALYZE seed_plan;
DO $$
DECLARE
    booking record;
    selected_slot record;
BEGIN
    FOR booking IN
        WITH ranked AS (
            SELECT p.*,row_number() OVER (ORDER BY random()) AS rank
            FROM seed_plan p WHERE status='completed'
        )
        SELECT r.*,m.n AS month_n,m.days,
            1+floor(((r.rank-m.offset-1)/40)::numeric * (cardinality(m.days)-1)
                / (ceil(m.total/40.0)-1))::integer AS preferred_day
        FROM ranked r JOIN seed_months m ON r.rank>m.offset AND r.rank<=m.offset+m.total
        ORDER BY r.rank
    LOOP
        SELECT candidate.id,candidate.staff_n,candidate.scheduled_at INTO selected_slot
        FROM seed_slots candidate
        WHERE candidate.month_n=booking.month_n AND NOT candidate.used AND NOT EXISTS (
            SELECT 1 FROM seed_schedule existing
            WHERE existing.status<>'cancelled' AND existing.pet_id=booking.pet_id
                -- Slots are 150 minutes apart, exceeding service duration plus buffer.
                AND existing.scheduled_at=candidate.scheduled_at
        )
        ORDER BY (candidate.day_position=booking.preferred_day) DESC,
            candidate.day_position,candidate.scheduled_at,candidate.staff_n
        LIMIT 1;
        IF NOT FOUND THEN
            RAISE EXCEPTION 'Nao ha horario historico livre no mes; nenhuma carga sera confirmada.';
        END IF;
        UPDATE seed_slots SET used=true WHERE id=selected_slot.id;
        INSERT INTO seed_schedule VALUES (booking.id,booking.pet_id,booking.status,selected_slot.staff_n,selected_slot.scheduled_at);
    END LOOP;
END;
$$;

INSERT INTO public.appointments (id,pet_id,service_id,staff_id,scheduled_at,status,
    price,duration_minutes,completed_at,notes)
SELECT p.id,p.pet_id,first_item.service_id,u.id,s.scheduled_at,p.status,
    sums.price,sums.duration,
    CASE WHEN p.status='completed' THEN s.scheduled_at+sums.duration*INTERVAL '1 minute' ELSE NULL END,
    'Massa ficticia: seed-60-usuarios-outubro-2026'
FROM seed_plan p JOIN seed_schedule s ON s.id=p.id
JOIN seed_users u ON u.n=s.staff_n AND u.role='staff'
JOIN seed_items first_item ON first_item.appointment_id=p.id AND first_item.position=1
JOIN (SELECT appointment_id,sum(price) AS price,sum(duration_minutes)::integer AS duration
    FROM seed_items GROUP BY appointment_id) sums ON sums.appointment_id=p.id;

-- 4. Snapshots: nome, preco, duracao e posicao, sem servicos repetidos.
INSERT INTO public.appointment_services (appointment_id,service_id,position,service_name,price,duration_minutes)
SELECT appointment_id,service_id,position,service_name,price,duration_minutes FROM seed_items;

-- Abortamos antes do COMMIT se qualquer quantidade/invariante estiver errada.
DO $$
BEGIN
    IF (SELECT count(*) FROM public.users u JOIN seed_users s ON s.id=u.id)<>60
       OR (SELECT count(*) FROM seed_users WHERE role='staff')<>10
       OR (SELECT count(*) FROM seed_users WHERE role='client')<>50
       OR (SELECT count(*) FROM public.pets p JOIN seed_pets s ON s.id=p.id)<>150
       OR (SELECT count(*) FROM public.appointments a JOIN seed_plan s ON s.id=a.id)<>1500
       OR (SELECT count(*) FROM seed_plan WHERE status='completed')<>1230
       OR (SELECT count(*) FROM seed_plan WHERE status='cancelled')<>250
       OR (SELECT count(*) FROM seed_plan WHERE status='pending')<>20 THEN
        RAISE EXCEPTION 'Contagens divergentes da carga aprovada.';
    END IF;
    IF EXISTS (SELECT 1 FROM seed_users u LEFT JOIN public.pets p ON p.owner_id=u.id
        WHERE u.role='client' GROUP BY u.id HAVING count(p.id)<>3)
       OR EXISTS (SELECT 1 FROM seed_pets p LEFT JOIN public.appointments a ON a.pet_id=p.id
        GROUP BY p.id HAVING count(a.id)<>10)
       OR EXISTS (SELECT 1 FROM seed_users u LEFT JOIN public.appointments a
        ON a.staff_id=u.id AND a.status='pending' WHERE u.role='staff'
        GROUP BY u.id HAVING count(a.id)<>2) THEN
        RAISE EXCEPTION 'Distribuicao por tutor/pet/funcionario divergente.';
    END IF;
    IF EXISTS (SELECT 1 FROM public.appointments a JOIN seed_plan s ON s.id=a.id
        WHERE extract(minute FROM a.scheduled_at) NOT IN (0,30)
        OR a.scheduled_at::time<TIME '08:00'
        OR (a.scheduled_at+a.duration_minutes*INTERVAL '1 minute')::date<>a.scheduled_at::date
        OR (a.scheduled_at+a.duration_minutes*INTERVAL '1 minute')::time>TIME '18:00'
        OR (a.status='pending' AND (a.scheduled_at::date<>DATE '2026-10-14' OR a.scheduled_at<=clock_timestamp()))
        OR (a.status<>'pending' AND (a.scheduled_at::date>=CURRENT_DATE
            OR a.scheduled_at< TIMESTAMPTZ '2026-05-01 00:00:00 America/Sao_Paulo'))) THEN
        RAISE EXCEPTION 'Data ou horario operacional invalido.';
    END IF;
    IF EXISTS (SELECT 1 FROM public.appointments a JOIN seed_plan s ON s.id=a.id
        JOIN public.appointments b ON a.id<>b.id AND b.status<>'cancelled'
        AND (a.staff_id=b.staff_id OR a.pet_id=b.pet_id OR b.staff_id IS NULL)
        AND b.scheduled_at-INTERVAL '30 minutes'<a.scheduled_at+a.duration_minutes*INTERVAL '1 minute'
        AND b.scheduled_at+(b.duration_minutes+30)*INTERVAL '1 minute'>a.scheduled_at
        WHERE a.status<>'cancelled') THEN
        RAISE EXCEPTION 'Conflito de profissional/pet ou intervalo operacional insuficiente.';
    END IF;
    IF EXISTS (SELECT 1 FROM public.appointments a JOIN seed_plan s ON s.id=a.id
        JOIN public.appointment_services i ON i.appointment_id=a.id
        GROUP BY a.id,a.price,a.duration_minutes
        HAVING count(*) NOT BETWEEN 1 AND 10 OR sum(i.price)<>a.price
            OR sum(i.duration_minutes)<>a.duration_minutes) THEN
        RAISE EXCEPTION 'Snapshots ou totais inconsistentes.';
    END IF;
END;
$$;

-- Verificacoes da carga (na mesma transacao; temporarias sao removidas no COMMIT).
SELECT u.role,count(*) AS usuarios_inseridos FROM public.users u
JOIN seed_users s ON s.id=u.id GROUP BY u.role ORDER BY u.role;

SELECT u.email,count(p.id) AS pets_por_cliente FROM seed_users u
JOIN public.pets p ON p.owner_id=u.id WHERE u.role='client' GROUP BY u.email ORDER BY u.email;

SELECT p.id,p.name,count(a.id) AS agendamentos_por_pet FROM seed_pets p
JOIN public.appointments a ON a.pet_id=p.id GROUP BY p.id,p.name ORDER BY p.name,p.id;

SELECT a.status,count(*) AS agendamentos_inseridos FROM public.appointments a
JOIN seed_plan s ON s.id=a.id GROUP BY a.status ORDER BY a.status;

SELECT u.email,a.scheduled_at::date AS dia,a.status,count(*) AS atendimentos
FROM public.appointments a JOIN seed_plan s ON s.id=a.id JOIN seed_users u ON u.id=a.staff_id
WHERE a.scheduled_at::date=DATE '2026-10-14'
GROUP BY u.email,a.scheduled_at::date,a.status ORDER BY u.email;

SELECT date_trunc('month',a.scheduled_at)::date AS mes,a.status,count(*) AS quantidade
FROM public.appointments a JOIN seed_plan s ON s.id=a.id
GROUP BY mes,a.status ORDER BY mes,a.status;

COMMIT;
