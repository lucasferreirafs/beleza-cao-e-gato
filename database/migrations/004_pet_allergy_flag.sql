-- Flag derivada das alergias cadastradas; permanece sincronizada com a lista/texto.
ALTER TABLE public.pets
   ADD COLUMN IF NOT EXISTS has_allergies BOOLEAN
   GENERATED ALWAYS AS (NULLIF(btrim(allergies), '') IS NOT NULL) STORED;
