---
name: beleza-testing
description: Cria, atualiza ou executa testes automatizados do Beleza Cão&Gato para PHP, PostgreSQL, Storage ou interface. Use quando a tarefa principal for testes; validação final ampla pertence a beleza-qa-release.
---

# Testes automatizados

Leia [mapa de testes](references/suites.md) para escolher a suíte e o ambiente. Não presuma PHPUnit ou Playwright instalado no checkout.

1. Teste comportamento, não texto interno da implementação. Cubra sucesso, erro, autorização e limites pertinentes.
2. Mantenha fixtures determinísticas e isoladas. Suites que executam `schema.sql` só podem rodar no `booking_qa` após verificar as guardas de ambiente.
3. Para booking, inclua conflito e concorrência quando houver alteração da regra. Para UI, verifique layout e interação em larguras representativas.
4. Execute o teste criado e as regressões compartilhadas. Informe comandos, resultados e qualquer teste que não foi possível executar.
