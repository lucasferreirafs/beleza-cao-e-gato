# Suítes existentes

- `tests/run.php`: validações básicas de email, CPF, senha, CSRF e registro de rotas.
- `tests/lint.php`: sintaxe PHP; não verifica comportamento.
- `tests/storage.php`: integração Storage com transporte simulado, sem serviço remoto.
- `tests/booking.php`, `tests/appointment-lifecycle.php`, `tests/staff-dashboard.php`, `tests/staff-agenda.php`, `tests/staff-appointments.php`, `tests/phase1.php`: testes HTTP/banco; confira as guardas antes de executar. Alguns reinicializam `database/schema.sql`.
- `tests/*-layout.cjs`: testes visuais com Playwright. A dependência não consta de `composer.json`; requer runtime Node e navegador disponíveis no ambiente.
- `compose.booking-qa.yaml` define `booking_qa`, separado do Compose principal. Use-o para testes destrutivos e encerre somente os contêineres de QA iniciados para a validação.
