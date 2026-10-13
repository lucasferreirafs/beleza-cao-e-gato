# Massa de teste: 60 usuarios

Arquivo: `seed-teste-60-usuarios.sql`. Validado no booking_qa e executado no
projeto principal Supabase configurado pelo sistema, apos autorizacao explicita.
Requer schema atual e os seis servicos ativos de demonstracao; nao insere servicos.
Nao e migracao nem substituto para schema.sql. Acrescenta registros, nao apaga dados.

## Quantidades

- 10 funcionarios, 50 clientes, tres pets por cliente: 150 pets.
- Dez agendamentos por pet: 1500.
- 1230 completed: maio 180, junho 195, julho 205, agosto 215, setembro 225,
  outubro 210. Datas passadas, alinhadas ao grafico que inclui o mes atual.
- 250 cancelled: datas passadas de maio a outubro.
- 20 pending: 14/10/2026, dois por funcionario, com 20 pets distintos sorteados.

O grafico atual conta todos os status, portanto os cancelados e pendentes tambem
contribuem para as barras. Os totais mensais acima sao somente dos concluidos.
Os timestamps created_at/updated_at usam defaults do banco; scheduled_at e
completed_at descrevem o historico ficticio e nao a data de insercao da carga.

## Contas de teste

Senha padrao para todos os 60 usuarios (50 clientes e 10 funcionarios): `SeedPets2026!`.
O SQL insere somente um hash bcrypt gerado por password_hash() no PHP na coluna
password_hash; a senha em texto esta documentada para facilitar o acesso de teste.
Emails: `funcionario01@seed-beleza.example.test` a
`funcionario10@seed-beleza.example.test`, e `cliente01@seed-beleza.example.test`
a `cliente50@seed-beleza.example.test`. Contas ativas e verificadas, sem envio de email.
CPFs sinteticos com digitos verificadores e telefones distintos: dados de teste,
nao contato de pessoas reais. Uma colisao existente aborta toda a transacao.

## Regras e protecoes

- A guarda permite booking_qa por padrao. A carga principal autorizada usou
  SET LOCAL beleza.seed_authorized_project com a referencia exata do projeto
  conferido, dentro da transacao. Nao executar novamente: a carga ja existe.
- Datas ancoradas em outubro/2026; o script aceita somente 01-13/10/2026 e exige
  dias passados suficientes no mes. Fora desse periodo, e necessario revisar datas.
- Usa BEGIN/COMMIT, lock compartilhado com booking e checagens antes de confirmar.
- Cada reserva tem 1 a 3 servicos distintos dos seis existentes, dentro do limite
  permitido de 1 a 10. Preco/duracao/nome sao snapshots dos servicos da carga.
- Exige conjuntos com no maximo 120 minutos para manter slots e intervalo de
  30 minutos; duracoes diferentes que violem esse limite abortam a carga.
- Nao ha coluna porte: sao preenchidos raca, especie, peso e nascimento.
- Bloqueia reexecucao pelo dominio de email; nao sobrescreve dados anteriores.
- SELECTs finais mostram contagens apenas da carga antes do COMMIT.
- Clientes SQL devem parar no primeiro erro; no psql usar ON_ERROR_STOP=1.

## Verificacao realizada

Revisao com schema/migracoes e motor de reservas, conferencias aritmeticas e
validacao do hash com PHP. A primeira execucao descartavel excedeu o tempo limite
na distribuicao de horarios; a selecao foi otimizada com slots temporarios e
indices, sem alterar o schema permanente. A segunda execucao passou e confirmou
as quantidades e invariantes antes do COMMIT.

A execucao principal no Supabase tambem confirmou a carga. Consultas independentes
apos o COMMIT verificaram 10 funcionarios, 50 clientes, 150 pets, 1500 agendamentos
e 1950 snapshots. Status: 1230 completed, 250 cancelled, 20 pending em 14/10/2026.
Nenhum pet tem quantidade diferente de dez; nenhum funcionario tem quantidade
de pending diferente de dois. Os fingerprints e contagens dos registros anteriores
permaneceram identicos (8 usuarios, 6 pets, 5 agendamentos). Totais finais do banco:
68 usuarios, 156 pets, 1505 agendamentos. Nao foram feitas migracoes nem deploy.
