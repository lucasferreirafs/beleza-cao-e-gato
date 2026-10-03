-- Fase 1: migração incremental. Não remove tabelas nem dados existentes.
ALTER TABLE public.users
   ADD COLUMN IF NOT EXISTS terms_version VARCHAR(30),
   ADD COLUMN IF NOT EXISTS terms_accepted_at TIMESTAMPTZ,
   ADD COLUMN IF NOT EXISTS avatar_path VARCHAR(100),
   ADD COLUMN IF NOT EXISTS auth_version INTEGER NOT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS public.remember_tokens (
   selector VARCHAR(32) PRIMARY KEY,
   user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
   token_hash VARCHAR(64) NOT NULL,
   previous_hash VARCHAR(64),
   previous_valid_until TIMESTAMPTZ,
   expires_at TIMESTAMPTZ NOT NULL,
   created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_remember_tokens_user ON public.remember_tokens (user_id);
CREATE INDEX IF NOT EXISTS idx_remember_tokens_expiry ON public.remember_tokens (expires_at);
ALTER TABLE public.remember_tokens ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON public.remember_tokens FROM PUBLIC;
-- PHP conecta pelo proprietário do banco; nenhum token é exposto pela API pública.
DO $$
BEGIN
   IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'anon') THEN
      REVOKE ALL ON public.remember_tokens FROM anon;
   END IF;
   IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'authenticated') THEN
      REVOKE ALL ON public.remember_tokens FROM authenticated;
   END IF;
END;
$$;
