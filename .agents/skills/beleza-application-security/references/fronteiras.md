# Fronteiras e ativos

- Navegador → PHP: sessão `beleza_session`, remember cookie separado, CSRF em mutações, entrada validada e saída codificada no contexto.
- PHP → PostgreSQL: credenciais no ambiente, PDO prepared statements; `auth_version` e status da conta são consultados pelo middleware de autenticação.
- PHP → Supabase Storage: `service_role` somente no servidor; objetos de imagem são retornados ao navegador via endpoints autenticados com autorização por conta/atendimento.
- Perfis: client, staff e admin. `RoleMiddleware` protege a família de rota, mas o controller/repository ainda deve autorizar o registro individual.
- Ativos sensíveis: senha, sessão, remember token, foto, CPF, contato, histórico de pet e agendamentos. Evite cópias em logs, URLs e cache compartilhado.
- Para alteração sensível, pense em IDOR, XSS persistido, CSRF, SQL injection, upload indevido, brute force e concorrência. Priorize ameaças alcançáveis no fluxo concreto.
