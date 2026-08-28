# Requisitos do Sistema

## Requisitos funcionais

### Acesso e conta

- **RF001 — Exibir acesso público.** Disponibilizar login, cadastro, recuperação de senha e verificação de e-mail.
- **RF002 — Autenticar usuário.** Receber e-mail e senha, validar as credenciais cadastradas e iniciar uma sessão autenticada.
- **RF003 — Informar falhas de acesso.** Informar credenciais inválidas, campos obrigatórios e CSRF inválido sem revelar dados sensíveis.
- **RF004 — Encerrar sessão.** Permitir logout e impedir o acesso às áreas protegidas até um novo login.
- **RF005 — Direcionar por perfil.** Após o login, encaminhar o usuário ao painel de cliente, funcionário ou administrador.
- **RF006 — Restringir rotas por perfil.** Cliente não acessa áreas de funcionário ou admin; funcionário não acessa ações exclusivas de admin.
- **RF007 — Gerenciar perfil.** Permitir visualizar e editar os dados de perfil autorizados.

### Cliente e pets

- **RF008 — Exibir painel do cliente.** Mostrar resumo de pets, próximos agendamentos, atalhos e informações da conta.
- **RF009 — Listar pets do proprietário.** Mostrar somente pets vinculados à conta, com nome, espécie, raça, idade e imagem quando disponível.
- **RF010 — Cadastrar pet.** Permitir cadastrar um pet pelos dados exigidos no formulário.
- **RF011 — Alterar e excluir pet.** Permitir editar ou excluir apenas pets do próprio cliente, com confirmação quando aplicável.
- **RF012 — Consultar serviços.** Exibir serviços disponíveis, descrição, duração, preço e disponibilidade.

### Agendamentos

- **RF013 — Listar agendamentos do cliente.** Consultar os próprios agendamentos, incluindo o estado vazio quando não houver registros.
- **RF014 — Iniciar agendamento.** Iniciar um novo agendamento pelo painel ou pela área de agendamentos.
- **RF015 — Selecionar pet.** Permitir selecionar um pet pertencente ao cliente.
- **RF016 — Selecionar serviço.** Permitir escolher um serviço ativo do estabelecimento.
- **RF017 — Selecionar data e horário.** Apresentar datas e horários disponíveis, impedindo a seleção de horário indisponível.
- **RF018 — Confirmar agendamento.** Exibir resumo de pet, serviço, data, horário e valor; após confirmação, gravar e informar o resultado.
- **RF019 — Exibir situação.** Indicar status como pendente, confirmado, concluído ou cancelado com textos e indicadores visuais.

### Funcionário

- **RF020 — Exibir painel operacional.** Mostrar indicadores e visão resumida de agenda e atendimentos.
- **RF021 — Consultar agenda.** Visualizar agendamentos sob sua responsabilidade por data, horário e status.
- **RF022 — Consultar atendimentos.** Acessar a lista ou histórico de atendimentos relacionados às suas atividades.
- **RF023 — Consultar clientes e pets.** Localizar clientes e pets necessários ao atendimento, respeitando permissões.

### Administração

- **RF024 — Exibir painel administrativo.** Mostrar indicadores gerais de clientes, pets, serviços e agendamentos.
- **RF025 — Gerenciar clientes.** Listar e administrar os dados dos clientes.
- **RF026 — Gerenciar pets.** Consultar e administrar registros de pets.
- **RF027 — Gerenciar funcionários.** Listar, cadastrar, editar, ativar/inativar ou administrar funcionários.
- **RF028 — Gerenciar serviços.** Cadastrar, editar, ativar/inativar e consultar serviços, preço e duração.
- **RF029 — Gerenciar agendamentos.** Consultar agendamentos e acompanhar seus estados.
- **RF030 — Exibir relatórios e configurações.** Acessar relatórios operacionais e configurações do sistema.

### Integração e dados

- **RF031 — Persistir dados no PostgreSQL.** Ler e gravar usuários, pets, serviços e agendamentos no PostgreSQL do Supabase.
- **RF032 — Proteger vínculo dos dados.** Validar no servidor a relação entre o registro e o usuário autorizado antes de cada operação.
- **RF033 — Disponibilizar token para integração.** Fornecer JWT apenas para usuário autenticado, quando uma integração exigir esse recurso.

## Requisitos não funcionais

### Arquitetura e manutenção

- **RNF001 — Tecnologia.** Usar PHP 8.2 ou superior, Composer e autoload PSR-4.
- **RNF002 — Organização.** Adotar MVC, separando rotas, controllers, regras de domínio/repositórios, views e recursos públicos.
- **RNF003 — Configuração externa.** Ler credenciais e configurações por variáveis de ambiente; `.env` não deve ser versionado.
- **RNF004 — Código sustentável.** Manter responsabilidades claras, tipagem quando aplicável e documentação suficiente para manutenção manual.

### Banco, execução e confiabilidade

- **RNF005 — Banco remoto.** Conectar ao PostgreSQL do Supabase por PDO, sem depender de container PostgreSQL local.
- **RNF006 — Conexão segura.** Exigir TLS/SSL por `DB_SSLMODE=require` ou configuração equivalente aprovada pelo provedor.
- **RNF007 — Docker.** Iniciar por Docker Compose com o serviço da aplicação consumindo as variáveis do `.env`.
- **RNF008 — Integridade.** Manter chaves, relações, restrições e validações que evitem registros órfãos ou inválidos.
- **RNF009 — Tratamento de erro.** Gerar resposta compreensível e registro técnico seguro, sem expor senha, token ou detalhes internos.

### Segurança

- **RNF010 — Senhas.** Armazenar senhas somente com hash seguro (`password_hash`) e validá-las com `password_verify`.
- **RNF011 — Sessão.** Usar sessão PHP com ID renovado após login, expiração por inatividade e cookies `HttpOnly` e `SameSite`.
- **RNF012 — CSRF.** Validar token CSRF em toda rota que altera dados por POST, PUT, PATCH ou DELETE.
- **RNF013 — XSS.** Escapar dados antes da renderização HTML.
- **RNF014 — SQL Injection.** Usar PDO e parâmetros preparados; nunca concatenar valores externos diretamente em SQL.
- **RNF015 — Autorização no servidor.** Validar sessão, papel e propriedade do dado no backend, independentemente do menu visível.
- **RNF016 — Segredos.** Não expor senhas do banco, chaves JWT ou tokens em código, logs, documentação pública ou Git.

### Interface e acessibilidade

- **RNF017 — Fidelidade visual.** Seguir o design do Figma: sidebar teal, cabeçalho, fundo claro, Inter, laranja de destaque, cards, tabelas, badges, modais e stepper.
- **RNF018 — Responsividade.** Manter uso adequado em desktop, tablet e celular, adaptando navegação, tabelas, cartões e formulários.
- **RNF019 — Consistência.** Reutilizar o mesmo design system para botões, inputs, mensagens, badges, espaçamentos e raios.
- **RNF020 — Acessibilidade básica.** Usar rótulos associados, foco visível, teclado, contraste adequado e textos alternativos em imagens relevantes.
- **RNF021 — Feedback.** Mostrar estados de sucesso, erro, confirmação, lista vazia e indisponibilidade de dados de forma clara.

### Desempenho e compatibilidade

- **RNF022 — Desempenho percebido.** Listagens e telas principais devem responder sem bloqueios perceptíveis; consultas devem buscar somente os dados necessários.
- **RNF023 — Compatibilidade.** Funcionar nas versões atuais dos principais navegadores modernos.
- **RNF024 — Qualidade verificável.** Possuir lint e verificações para banco, autenticação e fluxos críticos.

## Critérios de aceitação prioritários

1. Um usuário válido faz login e é redirecionado ao painel do seu perfil.
2. Um cliente só visualiza e altera seus próprios pets e agendamentos.
3. Uma tentativa de acessar rota de outro perfil resulta em bloqueio ou redirecionamento seguro.
4. O cadastro de pet permanece salvo no PostgreSQL do Supabase.
5. Um agendamento confirmado possui pet, serviço, data, horário, status e vínculo com o cliente.
6. Todas as mutações rejeitam requisições sem CSRF válido.
7. O sistema inicia via Docker sem banco local e conecta ao Supabase por SSL.
