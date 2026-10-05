# Ciclo de vida dos agendamentos

## Status e transições

O status inicial é `pending`. O banco aceita exclusivamente:

| Status | Exibição | Próximos estados permitidos |
| --- | --- | --- |
| `pending` | Pendente | `in_progress` ou `cancelled` |
| `in_progress` | Em andamento | `completed` |
| `completed` | Concluído | Nenhum |
| `cancelled` | Cancelado | Nenhum |

Enviar novamente o status atual é uma operação idempotente: não altera timestamps.
Para utilizar novamente uma vaga cancelada, crie um novo agendamento. Não há reabertura de estados terminais nem conclusão direta de um pendente.

O funcionário somente altera os atendimentos atribuídos a ele. O tutor somente cancela seus próprios agendamentos pendentes **antes do horário marcado**, preservando a regra anterior da aplicação. Funcionário e administrador também podem cancelar um pendente; nenhum desses caminhos cancela um serviço já iniciado.

## Cancelamento após 30 minutos

`App\Services\AppointmentLifecycleService::cancelExpiredPending()` cancela agendamentos ainda pendentes quando a hora atual ultrapassa estritamente o horário marcado mais 30 minutos. No limite exato de 30 minutos, o agendamento ainda não está vencido.

As consultas de agendamentos e disponibilidade executam essa manutenção antes de devolver os dados. Nas leituras comuns, ela é executada uma vez por conexão em cada requisição HTTP: `public/index.php` delimita esse escopo, e somente uma manutenção concluída com commit é reutilizada. Leituras seguintes ainda consultam os dados, mas não repetem o sweep; um vencimento ocorrido depois da primeira manutenção será tratado na próxima requisição ou ação crítica.

Dentro de uma transação, a manutenção nunca é pulada nem registrada nesse cache, pois a operação pode sofrer rollback. O início de um serviço também verifica novamente o prazo após adquirir os bloqueios. Um início tardio é recusado e seu cancelamento fica persistido. Chamadas diretas a `cancelExpiredPending()` e comandos CLI continuam executando a manutenção a cada chamada; não há cache persistente entre requisições.

A decisão utiliza o relógio do PostgreSQL após aguardar os bloqueios. A manutenção, a reserva e a mudança manual de status compartilham o bloqueio transacional já utilizado pelo motor de agendamentos. O cancelamento nunca atinge `in_progress` ou `completed` e não altera `completed_at`.

**Não confundir:** os 30 minutos após o horário marcado são o prazo para iniciar o atendimento. O intervalo operacional entre reservas também é de 30 minutos, mas é uma regra distinta.

## Atualizar um banco existente sem apagar dados

Faça backup e suspenda temporariamente o acesso à aplicação durante a implantação. Não execute `database/schema.sql`: ele apaga e recria o schema.

O Compose principal copia `bin/` e `database/` na imagem, portanto reconstrua-a antes de executar os novos comandos:

```sh
docker compose up -d --build
docker compose exec -T app php bin/migrate-appointment-statuses.php
```

A migração `005_appointment_status_lifecycle.sql` é transacional e pode ser reaplicada. Ela converte `confirmed` e `scheduled` para `pending`, `done` para `completed` e `canceled` para `cancelled`; cancela pendentes já vencidos; cria o default, a constraint e os índices adequados. Valores desconhecidos impedem a aplicação e provocam rollback para revisão manual, em vez de terem seu significado inventado.

O arquivo SQL deve ser executado **dentro de uma transação**, como faz o script PHP. Migrações antigas permanecem como histórico; os nomes antigos somente são relevantes para essa conversão de dados legados.

## Executar sem visitas ao sistema

PHP não executa tarefas em segundo plano automaticamente. Para cancelar mesmo sem navegação, configure um agendador externo (cron, Agendador de Tarefas do Windows ou agendador da hospedagem) para executar, por exemplo, a cada minuto:

```sh
docker compose exec -T app php bin/cancel-expired-appointments.php
```

Execute no diretório do projeto ou informe o caminho absoluto do Compose. Com uma periodicidade de um minuto, registros podem permanecer pendentes até a próxima execução; consultas e ações continuam verificando o prazo independentemente disso. Nenhuma tarefa recorrente foi instalada automaticamente.

## Testes em ambiente isolado

```sh
docker compose -f compose.booking-qa.yaml up -d --build
docker compose -f compose.booking-qa.yaml exec -T app php tests/booking.php
docker compose -f compose.booking-qa.yaml exec -T app php tests/appointment-lifecycle.php
docker compose -f compose.booking-qa.yaml exec -T app php tests/lint.php
```

As duas suítes de banco recriam fixtures somente no PostgreSQL descartável `booking_qa`, mediante as proteções de ambiente. Não as execute contra o banco real. A suíte de ciclo de vida cobre limite exato e fusos, autorização, transições ilegais, timestamps, manutenção por leitura, workers concorrentes, conversão de dados legados, repetição e rollback da migração.

## Dashboard do funcionário — Fase 2

O topo apresenta o próximo atendimento pendente atribuído ao funcionário, com todos os serviços e duração total. Pendentes atrasados dentro da tolerância têm prioridade sobre os futuros. O contador é atualizado no navegador; ao ultrapassar o prazo de início, oferece a atualização da agenda para consultar o status atual no servidor.

O filtro GET `period=today|week` afeta os quatro indicadores. Hoje é o padrão; a semana compreende segunda-feira até domingo no fuso `APP_TIMEZONE`. Os limites são inclusivos no início e exclusivos no fim. Valores inválidos retornam ao período de hoje. O total inclui atendimentos em andamento; estes não entram na contagem de pendentes.

O destaque independe do filtro de métricas. A tabela continua exibindo a agenda de hoje. O filtro funciona também sem JavaScript, por meio do botão Aplicar.

Validação específica: `docker compose -f compose.booking-qa.yaml exec -T app php tests/staff-dashboard.php`. Esse teste também utiliza exclusivamente o banco descartável de QA.

As ações rápidas, a tabela filtrada, o resumo do pet e os cartões de agendamento mobile já estão representados no dashboard do funcionário.
