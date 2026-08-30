-- ============================================================
-- BANCO DE DADOS - SISTEMA DE AGENDAMENTO PET
-- PostgreSQL / Supabase
-- ============================================================

CREATE EXTENSION IF NOT EXISTS pgcrypto;


-- ============================================================
-- TIPOS
-- ============================================================

DO $$
BEGIN

    IF NOT EXISTS (
        SELECT 1
        FROM pg_type
        WHERE typname = 'tipo_usuario'
    ) THEN

        CREATE TYPE tipo_usuario AS ENUM (
            'cliente',
            'funcionario',
            'administrador'
        );

    END IF;


    IF NOT EXISTS (
        SELECT 1
        FROM pg_type
        WHERE typname = 'status_agendamento'
    ) THEN

        CREATE TYPE status_agendamento AS ENUM (
            'pendente',
            'confirmado',
            'concluido',
            'cancelado'
        );

    END IF;

END
$$;


-- ============================================================
-- TABELA: USUARIOS
-- ============================================================

CREATE TABLE usuarios (

    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    nome VARCHAR(120) NOT NULL,

    email VARCHAR(255) NOT NULL UNIQUE,

    senha VARCHAR(255) NOT NULL,

    tipo tipo_usuario NOT NULL DEFAULT 'cliente',

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    email_verificado BOOLEAN NOT NULL DEFAULT FALSE,

    criado_em TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    atualizado_em TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT usuario_nome_valido
        CHECK (length(trim(nome)) >= 2),

    CONSTRAINT usuario_email_valido
        CHECK (length(trim(email)) > 5)

);


-- ============================================================
-- TABELA: ANIMAIS
-- ============================================================

CREATE TABLE animais (

    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    usuario_id UUID NOT NULL,

    nome VARCHAR(100) NOT NULL,

    especie VARCHAR(50) NOT NULL,

    raca VARCHAR(100),

    data_nascimento DATE,

    imagem TEXT,

    criado_em TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    atualizado_em TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT animal_usuario_fk
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE,

    CONSTRAINT animal_nome_valido
        CHECK (length(trim(nome)) >= 1),

    CONSTRAINT animal_data_nascimento_valida
        CHECK (
            data_nascimento IS NULL
            OR data_nascimento <= CURRENT_DATE
        )

);


-- ============================================================
-- TABELA: SERVICOS
-- ============================================================

CREATE TABLE servicos (

    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    nome VARCHAR(120) NOT NULL UNIQUE,

    descricao TEXT,

    duracao_minutos INTEGER NOT NULL,

    preco NUMERIC(10,2) NOT NULL,

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    criado_em TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    atualizado_em TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT servico_duracao_valida
        CHECK (duracao_minutos > 0),

    CONSTRAINT servico_preco_valido
        CHECK (preco >= 0)

);


-- ============================================================
-- TABELA: HORARIOS_FUNCIONAMENTO
-- ============================================================

CREATE TABLE horarios_funcionamento (

    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    dia_semana SMALLINT NOT NULL,

    horario_abertura TIME,

    horario_fechamento TIME,

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT dia_semana_valido
        CHECK (dia_semana BETWEEN 0 AND 6),

    CONSTRAINT horario_valido
        CHECK (
            (
                horario_abertura IS NULL
                AND horario_fechamento IS NULL
            )
            OR
            (
                horario_abertura IS NOT NULL
                AND horario_fechamento IS NOT NULL
                AND horario_fechamento > horario_abertura
            )
        ),

    CONSTRAINT dia_semana_unico
        UNIQUE (dia_semana)

);


-- ============================================================
-- TABELA: AGENDAMENTOS
-- ============================================================

CREATE TABLE agendamentos (

    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    usuario_id UUID NOT NULL,

    animal_id UUID NOT NULL,

    servico_id UUID NOT NULL,

    funcionario_id UUID,

    data_agendamento DATE NOT NULL,

    horario_agendamento TIME NOT NULL,

    status status_agendamento NOT NULL DEFAULT 'pendente',

    valor_agendamento NUMERIC(10,2) NOT NULL,

    observacoes TEXT,

    criado_em TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    atualizado_em TIMESTAMPTZ NOT NULL DEFAULT NOW(),


    CONSTRAINT agendamento_usuario_fk
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE RESTRICT,


    CONSTRAINT agendamento_animal_fk
        FOREIGN KEY (animal_id)
        REFERENCES animais(id)
        ON DELETE RESTRICT,


    CONSTRAINT agendamento_servico_fk
        FOREIGN KEY (servico_id)
        REFERENCES servicos(id)
        ON DELETE RESTRICT,


    CONSTRAINT agendamento_funcionario_fk
        FOREIGN KEY (funcionario_id)
        REFERENCES usuarios(id)
        ON DELETE SET NULL,


    CONSTRAINT agendamento_valor_valido
        CHECK (valor_agendamento >= 0)

);


-- ============================================================
-- TABELA: VERIFICACOES_EMAIL
-- ============================================================

CREATE TABLE verificacoes_email (
usuarios(id)
        ON DELETE CASCADE

);


-- ============================================================
-- INDICES
-- ============================================================

CREATE INDEX indice_usuarios_email
    ON usuarios(email);

CREATE INDEX indice_usuarios_tipo
    ON usuarios(tipo);

CREATE INDEX indice_usuarios_ativo
    ON usuarios(ativo);


CREATE INDEX indice_animais_usuario
    ON animais(usuario_id);


CREATE INDEX indice_animais_especie
    ON animais(especie);


CREATE INDEX indice_servicos_ativo
    ON servicos(ativo);


CREATE INDEX indice_agendamentos_usuario
    ON agendamentos(usuario_id);

CREATE INDEX indice_agendamentos_animal
    ON agendamentos(animal_id);

CREATE INDEX indice_agendamentos_servico
    ON agendamentos(servico_id);

CREATE INDEX indice_agendamentos_funcionario
    ON agendamentos(funcionario_id);

CREATE INDEX indice_agendamentos_data
    ON agendamentos(data_agendamento);

CREATE INDEX indice_agendamentos_status
    ON agendamentos(status);


-- ============================================================
-- FUNÇÃO PARA ATUALIZAR "ATUALIZADO_EM"
-- ============================================================

CREATE OR REPLACE FUNCTION atualizar_data_modificacao()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN

    NEW.atualizado_em = NOW();

    RETURN NEW;

END;
$$;


-- ============================================================
-- TRIGGERS
-- ============================================================

CREATE TRIGGER atualizar_usuario
BEFORE UPDATE ON usuarios
FOR EACH ROW
EXECUTE FUNCTION atualizar_data_modificacao();


CREATE TRIGGER atualizar_animal
BEFORE UPDATE ON animais
FOR EACH ROW
EXECUTE FUNCTION atualizar_data_modificacao();


CREATE TRIGGER atualizar_servico
BEFORE UPDATE ON servicos
FOR EACH ROW
EXECUTE FUNCTION atualizar_data_modificacao();


CREATE TRIGGER atualizar_agendamento
BEFORE UPDATE ON agendamentos
FOR EACH ROW
EXECUTE FUNCTION atualizar_data_modificacao();


-- ============================================================
-- HORARIOS DE FUNCIONAMENTO INICIAIS
-- ============================================================

INSERT INTO horarios_funcionamento
(
    dia_semana,
    horario_abertura,
    horario_fechamento,
    ativo
)

VALUES

-- Domingo
(0, NULL, NULL, FALSE),

-- Segunda
(1, '08:00', '18:00', TRUE),

-- Terça
(2, '08:00', '18:00', TRUE),

-- Quarta
(3, '08:00', '18:00', TRUE),

-- Quinta
(4, '08:00', '18:00', TRUE),

-- Sexta
(5, '08:00', '18:00', TRUE),

-- Sábado
(6, '08:00', '14:00', TRUE);


-- ============================================================
-- SERVICOS INICIAIS
-- ============================================================

INSERT INTO servicos
(
    nome,
    descricao,
    duracao_minutos,
    preco,
    ativo
)

VALUES

(
    'Banho',
    'Banho completo para o animal.',
    60,
    50.00,
    TRUE
),

(
    'Tosa',
    'Tosa completa para o animal.',
    90,
    70.00,
    TRUE
),

(
    'Banho e Tosa',
    'Serviço completo de banho e tosa.',
    120,
    100.00,
    TRUE
);
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    usuario_id UUID NOT NULL,

    token VARCHAR(255) NOT NULL UNIQUE,

    expira_em TIMESTAMPTZ NOT NULL,

    utilizado_em TIMESTAMPTZ,

    criado_em TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT verificacao_email_usuario_fk
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE

);


-- ============================================================
-- TABELA: RECUPERACOES_SENHA
-- ============================================================

CREATE TABLE recuperacoes_senha (

    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    usuario_id UUID NOT NULL,

    token VARCHAR(255) NOT NULL UNIQUE,

    expira_em TIMESTAMPTZ NOT NULL,

    utilizado_em TIMESTAMPTZ,

    criado_em TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT recuperacao_senha_usuario_fk
        FOREIGN KEY (usuario_id)
        REFERENCES 