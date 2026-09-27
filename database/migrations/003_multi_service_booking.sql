ALTER TABLE public.appointments
   ADD COLUMN IF NOT EXISTS duration_minutes INTEGER;

UPDATE public.appointments appointment
SET duration_minutes=service.duration_minutes
FROM public.services service
WHERE appointment.service_id=service.id
   AND appointment.duration_minutes IS NULL;

ALTER TABLE public.appointments
   ALTER COLUMN duration_minutes SET NOT NULL;

DO $$
BEGIN
   IF NOT EXISTS (
      SELECT 1 FROM pg_constraint
      WHERE conrelid='public.appointments'::regclass
         AND conname='appointments_duration_minutes_positive'
   ) THEN
      ALTER TABLE public.appointments
         ADD CONSTRAINT appointments_duration_minutes_positive
         CHECK (duration_minutes > 0);
   END IF;
END;
$$;

CREATE TABLE IF NOT EXISTS public.appointment_services (
   appointment_id BIGINT NOT NULL REFERENCES public.appointments(id) ON DELETE CASCADE,
   service_id BIGINT NOT NULL REFERENCES public.services(id),
   position SMALLINT NOT NULL CHECK (position > 0),
   service_name VARCHAR(120) NOT NULL,
   price NUMERIC(10, 2) NOT NULL CHECK (price >= 0),
   duration_minutes INTEGER NOT NULL CHECK (duration_minutes > 0),
   PRIMARY KEY (appointment_id, service_id),
   UNIQUE (appointment_id, position)
);

INSERT INTO public.appointment_services
   (appointment_id,service_id,position,service_name,price,duration_minutes)
SELECT appointment.id,service.id,1,service.name,appointment.price,appointment.duration_minutes
FROM public.appointments appointment
JOIN public.services service ON service.id=appointment.service_id
ON CONFLICT (appointment_id,service_id) DO NOTHING;

CREATE INDEX IF NOT EXISTS appointments_staff_schedule_active_idx
   ON public.appointments (staff_id,scheduled_at)
   WHERE staff_id IS NOT NULL AND status NOT IN ('cancelled','canceled');

CREATE INDEX IF NOT EXISTS appointments_unassigned_schedule_active_idx
   ON public.appointments (scheduled_at)
   WHERE staff_id IS NULL AND status NOT IN ('cancelled','canceled');
