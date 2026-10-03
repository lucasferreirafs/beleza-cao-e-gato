# Beleza Cão&Gato — instruções do repositório

- Use o código executado, `database/schema.sql` e as configurações atuais como fonte de verdade. Registre divergências da documentação sem inventar novas regras.
- Preserve a stack: PHP puro com Composer/PSR-4, PostgreSQL via PDO, HTML/CSS/JavaScript Vanilla e Supabase Storage via PHP/cURL. Não introduza frameworks por conveniência.
- Preserve alterações existentes no workspace. Não execute `database/schema.sql` nem `bin/migrate.php` em banco com dados: ambos reinicializam objetos do schema `public`.
- Mantenha segredos no servidor. Verifique autenticação, autorização, CSRF e encoding de saída conforme a operação; mudanças de status e reservas exigem validação no backend.
- No booking atual, o intervalo operacional é de 30 minutos (`BookingService::BUFFER_MINUTES`); o cancelamento automático de `pending` usa prazo separado de 30 minutos (`AppointmentLifecycleService`). `confirmed` é somente referência a dados legados em migrações, testes e documentação.
- Escolha somente as Skills de `.agents/skills/` pertinentes à tarefa. Elas contêm os procedimentos especializados; este arquivo não substitui a leitura da Skill aplicável.
- Testes que reinicializam o banco devem rodar apenas no ambiente descartável `compose.booking-qa.yaml`, com as guardas de ambiente dos próprios testes. Nunca afirme que um teste passou sem executá-lo.
