-- Apply inside a transaction; preserve existing appointments and their services.
SELECT pg_advisory_xact_lock(4242, 4);
LOCK TABLE public.appointments IN SHARE ROW EXCLUSIVE MODE;

ALTER TABLE public.appointments DROP CONSTRAINT IF EXISTS appointments_status_check;

UPDATE public.appointments
SET status = CASE status
   WHEN 'confirmed' THEN 'pending'
   WHEN 'scheduled' THEN 'pending'
   WHEN 'done' THEN 'completed'
   WHEN 'canceled' THEN 'cancelled'
   ELSE status
END,
   updated_at = clock_timestamp()
WHERE status IN ('confirmed', 'scheduled', 'done', 'canceled');

-- Unknown values abort the transaction instead of silently corrupting their meaning.
ALTER TABLE public.appointments
   ALTER COLUMN status SET DEFAULT 'pending',
   ADD CONSTRAINT appointments_status_check
      CHECK (status IN ('pending', 'in_progress', 'completed', 'cancelled'));

UPDATE public.appointments
SET status = 'cancelled', updated_at = clock_timestamp()
WHERE status = 'pending'
   AND scheduled_at < clock_timestamp() - INTERVAL '30 minutes';

DROP INDEX IF EXISTS public.appointments_staff_schedule_active_idx;
CREATE INDEX appointments_staff_schedule_active_idx
   ON public.appointments (staff_id, scheduled_at)
   WHERE staff_id IS NOT NULL AND status <> 'cancelled';

DROP INDEX IF EXISTS public.appointments_unassigned_schedule_active_idx;
CREATE INDEX appointments_unassigned_schedule_active_idx
   ON public.appointments (scheduled_at)
   WHERE staff_id IS NULL AND status <> 'cancelled';

CREATE INDEX IF NOT EXISTS appointments_pending_expiry_idx
   ON public.appointments (scheduled_at)
   WHERE status = 'pending';
