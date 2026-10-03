# Invariantes atuais do booking

- Passos atuais em `BookingService::STEPS`: serviço, escolha-pet, data-e-horario e confirmação. A seleção do profissional integra data e horário.
- De 1 a 10 serviços ativos e distintos. Duração total e valor são somas dos snapshots; `AppointmentStorage` mantém compatibilidade com bancos legados.
- Inícios em `08:00`–`17:30`, em incrementos de 30 minutos, devem terminar até `18:00`. `BookingService::BUFFER_MINUTES = 30` separa reservas conflitantes.
- Preferência de profissional é opcional; o backend atribui um livre após obter lock transacional e revalidar disponibilidade do pet/profissional.
- `AppointmentRepository::create()` e `changeStatus()` compartilham advisory lock. Concorrência e cancelamento automático devem manter a mesma ordem de bloqueio.
- Status válidos: `pending → in_progress → completed`; `pending → cancelled`. Repetir o status atual é idempotente; estados terminais não reabrem. `AppointmentLifecycleService::PENDING_TOLERANCE_MINUTES = 30` cancela `pending` vencido; é independente do intervalo entre reservas.
- `confirmed` só aparece na migração/testes de legado. Não é estado operacional.
- Casos mínimos ao mudar regra: slots limites, duração longa, dois serviços, profissional ocupado, sem preferência, mesmo pet, fuso, cancelamento, início após tolerância, requisições simultâneas e schema compatível.
