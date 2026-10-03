---
name: beleza-booking-engine
description: Altera ou audita disponibilidade, reserva, múltiplos serviços, atribuição de profissional, conflitos e ciclo de vida de agendamentos do Beleza Cão&Gato. Use para qualquer mudança na regra de booking; não ative para mera estilização de cards.
---

# Motor de agendamentos

Leia [invariantes e cenários](references/invariantes.md) antes de editar `BookingService`, `AppointmentRepository`, `AppointmentStorage`, `AppointmentLifecycleService` ou as transições de status.

1. Trace os passos do formulário até a gravação. Separe a validação de prévia da confirmação transacional.
2. Confirme duração total, profissional específico ou sem preferência, pet, fuso, horário comercial e intervalo de 30 minutos. Não confunda esse intervalo com o prazo de 30 minutos para cancelar um `pending` vencido.
3. Preserve o bloqueio transacional e a nova checagem após obtê-lo. Não adicione caminho de escrita que permita double booking ou transição ilegal.
4. Valide com `tests/booking.php` e `tests/appointment-lifecycle.php` no `booking_qa` quando a mudança envolver regras ou persistência; inclua cenário de concorrência quando aplicável.
