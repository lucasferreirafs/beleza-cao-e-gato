# Fase 4 — agendamento e compatibilidade de banco

## Diagnóstico do incidente

Em 26/09/2026, os logs do Apache mostraram `POST /login 302`, seguido de
`GET /dashboard 302` e `GET /cliente 500`, com `PDOException code=42P01`.
O dashboard chamava `AppointmentRepository::allForUser()`, que fazia JOIN em
`appointment_services` mesmo antes da migração. Também referenciava
`appointments.duration_minutes`. `COALESCE` trata valores nulos, não nomes de
tabelas/colunas ausentes. A consulta do dashboard de funcionário ainda continha
barras invertidas indevidas antes de aspas SQL. Verificar apenas `php -l` não
executou essas consultas e não detectou os erros.

## Compatibilidade sem executar schema.sql

`AppointmentStorage` consulta o catálogo do PostgreSQL antes de usar a tabela
de itens ou a coluna de duração. A leitura de agendamentos usa somente colunas
do schema anterior e depois monta os serviços e a duração efetiva em PHP.
Dashboards, listagens e disponibilidade usam essa mesma leitura.

Sem `appointment_services`, uma reserva continua sendo **uma única linha** em
`appointments`, com o preço total em `price`, o primeiro serviço em `service_id`
e um envelope JSON versionado no campo TEXT `notes`. O envelope contém os
snapshots de todos os serviços e a observação original. A camada de leitura
decodifica o envelope antes de entregá-lo às telas. Consumidores externos que
consultem `notes` diretamente devem considerar esse formato temporário.

Com a tabela de itens disponível, as linhas de `appointment_services` guardam
os snapshots e `notes` guarda o texto normal. Notas que comecem pelo prefixo
reservado são encapsuladas para não serem interpretadas como metadados.
Também há suporte a migrações parciais: apenas a coluna ou apenas a tabela.

Nenhuma requisição web cria/altera o schema. Quando a migração for desejada,
execute `php bin/migrate-phase4.php` no ambiente alvo. O script é aditivo,
transacional e idempotente; importa também os snapshots dos envelopes antigos.
Não execute `database/schema.sql` sobre dados que deseja manter: ele é um reset.

## Regras

- Etapas: `servico`, `escolha-pet`, `profissional`, `data-e-horario`, `confirmacao`.
  Os endereços numéricos antigos redirecionam para os nomes.
- De 1 a 10 serviços distintos e ativos; preço e duração calculados no servidor.
- Slots de 30 minutos, entre 08h e 18h, com todos os serviços terminando até 18h.
  Mantido o funcionamento diário já existente; não há escala semanal por funcionário.
- Intervalo mínimo de 60 minutos entre o término de uma reserva e o início da
  próxima para o mesmo profissional ou pet. O limite exato de 60 minutos é livre.
- A interface só oferece horários com algum profissional livre e pet disponível;
  o profissional preferido ocupado permanece visível e desabilitado. Outro
  profissional ou “Sem preferência” pode ser escolhido nessa etapa.
- Sem preferência atribui uma pessoa ativa e livre no servidor; nunca grava NULL.
- Confirmação usa transação READ COMMITTED e trava consultiva transacional
  `(4242,4)`, compartilhada com mudanças de status, antes de consultar novamente
  a disponibilidade. Pet, catálogo e profissionais também têm travas de linha.
  Isso serializa confirmações da aplicação; integrações SQL externas devem seguir
  o mesmo protocolo. Há timeout de trava e rollback em falhas.
- Cancelados liberam a agenda e não podem ser reabertos diretamente; é preciso
  criar outra reserva e passar pela validação de disponibilidade.
- Alteração de preço/duração/nome depois do resumo exige nova revisão.
- POSTs mantêm CSRF e autorização por dono do pet. Texto é escapado nas views.
- Consulta de disponibilidade com erro falha de forma fechada e mostra uma
  mensagem; não oferece todos os horários como se estivessem livres.

## QA reproduzível

Na raiz do projeto, no terminal local com Docker disponível:

```powershell
docker compose -f compose.booking-qa.yaml up -d --build --wait
docker compose -f compose.booking-qa.yaml exec -T app php tests/booking.php
docker compose -f compose.booking-qa.yaml exec -T app php tests/run.php
docker compose -f compose.booking-qa.yaml exec -T app php tests/lint.php
docker compose -f compose.booking-qa.yaml down -v
```

O banco é exclusivamente local e descartável. A suíte recusa o banco configurado
normalmente no `.env`: exige APP_ENV=testing, BOOKING_QA_ALLOW_RESET=1,
DB_DATABASE=booking_qa e DB_HOST=beleza-booking-qa-db. Ela reinicializa apenas esse
banco de teste. A senha pública do compose é uma credencial de fixture local.

A suíte cobre os schemas antigo, novo e duas migrações parciais; login e os três
dashboards; navegação e CSRF; múltiplos serviços; notas com HTML e “0”; propriedade
do pet; horários inválidos; limites de duração/buffer; preço adulterado;
profissional ocupado; atribuição automática; cancelamento; reenvio; alteração do
catálogo; migração idempotente; concorrência entre processos PHP independentes
disputando um profissional, a capacidade sem preferência e o mesmo pet.
