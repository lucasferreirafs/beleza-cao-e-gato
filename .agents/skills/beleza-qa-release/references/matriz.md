# Escolha de verificações por mudança

| Mudança | Verificações mínimas pertinentes |
| --- | --- |
| PHP/rotas | `php -l`, rota real, sucesso/erro, papel e CSRF |
| Schema/queries | banco descartável, compatibilidade e consumidores |
| Booking/status | conflitos, lock/concorrência, prazo, transições e timezone |
| Storage | validação de upload, acesso privado, cache e falha externa |
| UI | mobile/tablet/desktop, conteúdo longo, teclado, foco, modal e console |
| Documentação/Skills | paths, comandos, gatilhos e contradições com código |

Não execute todas as suítes indiscriminadamente. Rode as regressões ligadas aos arquivos compartilhados e descreva os limites dos testes manuais.
