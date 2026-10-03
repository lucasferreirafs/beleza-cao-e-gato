# Levantamento de Requisitos — Beleza Cão&Gato

> Documento elaborado a partir da implementação presente no repositório. “Implementado” descreve comportamento observado no código; não representa, por si só, validação em produção. Pontos em aberto estão separados para não confundir decisões futuras com requisitos aprovados.

## 1. Objetivo e contexto

O Beleza Cão&Gato é uma aplicação web para gestão de banho e tosa, com áreas de cliente, funcionário e administração. Clientes mantêm seus dados e pets e solicitam agendamentos; funcionários acompanham a operação e atualizam atendimentos; administradores mantêm cadastros e configurações disponíveis no sistema.

## 2. Fontes e limites deste levantamento

O levantamento considera as rotas em `routes/web.php`, controladores e serviços PHP, views, JavaScript, `database/schema.sql` e configurações existentes. O estado do código é a fonte para descrever funcionalidades atuais. A documentação não presume requisitos de produto que não estejam explícitos na implementação.

### Atores identificados

| Ator | Capacidades observadas |
|---|---|
| Visitante | Acessar páginas públicas, cadastrar-se, entrar e iniciar fluxos de verificação/recuperação de conta. |
| Cliente | Gerir perfil e pets, consultar serviços e agendamentos, reservar ou cancelar um agendamento conforme as regras da aplicação. |
| Funcionário (`staff`) | Consultar páginas operacionais e atualizar status de agendamentos. |
| Administrador (`admin`) | Acessar páginas administrativas para gerenciar registros, status e configurações oferecidas pela aplicação. |

## 3. Requisitos funcionais observados

Os critérios abaixo descrevem o que a implementação deve permitir conforme as rotas e os fluxos atuais. IDs são referências úteis para discussão e backlog.

| ID | Requisito funcional | Estado no código |
|---|---|---|
| RF-01 | Permitir cadastro e autenticação de contas com papéis cliente, funcionário e administrador. | Implementado |
| RF-02 | Oferecer verificação de e-mail, recuperação e redefinição de senha. | Implementado |
| RF-03 | Proteger operações mutáveis com token CSRF e exigir autenticação/papel apropriado nas rotas privadas. | Implementado nas rotas POST/DELETE observadas |
| RF-04 | Manter perfil da conta, configurações pessoais e foto/avatar. | Implementado; foto integrada ao fluxo de armazenamento |
| RF-05 | Permitir ao cliente cadastrar, editar, visualizar e remover seus pets, com dados clínicos/cadastrais e foto. | Implementado |
| RF-06 | Usar foto padrão do pet quando não houver foto cadastrada. | Implementado nas telas de cliente observadas |
| RF-07 | Exibir o catálogo de serviços ativos com preço e duração. | Implementado |
| RF-08 | Permitir criar agendamento para um pet selecionando um ou mais serviços, profissional específico ou sem preferência, data/horário e observações. | Implementado |
| RF-09 | Consultar disponibilidade antes da confirmação e impedir conflitos incompatíveis de profissional/pet. | Implementado no fluxo de booking; disponibilidade é revalidada no servidor |
| RF-10 | Aplicar intervalo de segurança de 30 minutos entre atendimentos conforme as regras do motor de agendamento. | Implementado |
| RF-11 | Atribuir profissional livre no servidor quando a escolha for “sem preferência”. | Implementado |
| RF-12 | Exibir ao cliente seus agendamentos e permitir cancelamento conforme regras aplicáveis. | Implementado |
| RF-13 | Permitir à equipe acompanhar a operação e alterar status de atendimento. | Implementado |
| RF-14 | Permitir à administração gerenciar cadastros e status apresentados nas seções administrativas. | Implementado |
| RF-15 | Armazenar imagens de usuário e de pets nos buckets existentes `img-user` e `img-pets`, respectivamente. | Implementado via integração PHP/cURL; buckets são pré-requisito externo |
| RF-16 | Registrar a versão e a data de aceite dos termos no cadastro, sem exigir novo aceite no login. | Implementado |

## 4. Regras de negócio observadas

- O agendamento percorre etapas nomeadas: `servico`, `escolha-pet`, `profissional`, `data-e-horario` e `confirmacao`.
- Um agendamento aceita até 10 serviços. O banco armazena a composição em `appointment_services`, preservando nome, preço e duração associados ao agendamento.
- A duração total é derivada dos serviços selecionados; as operações de disponibilidade e confirmação verificam a duração e o intervalo de 30 minutos.
- A grade de horários observada usa intervalos de 30 minutos, início entre 08:00 e 17:30 e término do atendimento até 18:00.
- A confirmação revalida disponibilidade no servidor em transação; não deve confiar apenas no horário exibido anteriormente no navegador.
- A opção “sem preferência” não deixa o profissional indefinido: a atribuição ocorre no backend, condicionada à disponibilidade.
- A estrutura atual não expressa escalas individuais de trabalho, feriados ou bloqueios operacionais recorrentes por profissional. Não inferir disponibilidade trabalhista a partir da grade padrão.
- A foto do pet tem fallback visual para `public/assets/images/pet.webp`.
- Os nomes de arquivo enviados ao Storage são gerados pela aplicação, preservando a extensão permitida, e não reutilizam o nome original enviado pelo navegador.

## 5. Requisitos não funcionais e restrições técnicas observados

- **Interface:** PHP renderizado no servidor, HTML, CSS e JavaScript sem framework front-end obrigatório; layouts devem permanecer usáveis em telas móveis.
- **Servidor:** PHP com autoload Composer/PSR-4, Apache no ambiente Docker e acesso PostgreSQL via PDO.
- **Persistência:** PostgreSQL; `database/schema.sql` é um script de recriação destrutiva do schema público e deve ser executado somente em ambiente/ocasião apropriados.
- **Armazenamento:** Supabase Storage REST via cURL; `img-user` e `img-pets` precisam existir previamente. A chave de serviço deve permanecer no servidor e nunca ser exposta no HTML/JS.
- **Segurança:** sessão/autorização por papel, CSRF para operações mutáveis, validações no servidor e consultas parametrizadas nos caminhos que usam PDO.
- **Uploads:** validação de tipo/tamanho/dimensões definida no serviço de Storage; confirmar limites atuais no código/configuração antes de alterar políticas.

## 6. Critérios de aceitação transversais

1. Rotas privadas recusam visitantes e papéis não autorizados.
2. Requisições de escrita sem CSRF válido não alteram dados.
3. Dados exibidos e enviados são validados também no servidor; falhas produzem resposta/mensagem recuperável, sem deixar estado parcial.
4. Dois pedidos concorrentes para o mesmo profissional ou pet não podem confirmar intervalos conflitantes.
5. Falta de imagem não quebra o layout nem gera imagem quebrada nas páginas de cliente.
6. Fluxos essenciais permanecem utilizáveis em viewport estreita, com modais/menus sem interação acidental com o conteúdo de fundo.
7. Credenciais e erros internos não são revelados ao usuário final.

## 7. Questões para elicitação / decisões pendentes

Os pontos abaixo não são afirmados como falhas definitivas: precisam de resposta do responsável pelo produto/operação antes de serem transformados em requisito comprometido.

| ID | Pergunta | Por que importa |
|---|---|---|
| Q-01 | Qual é a escala semanal de cada profissional, incluindo pausas, férias e exceções? | A grade padrão de horários não substitui a escala real de trabalho. |
| Q-02 | Quem pode bloquear horários, datas fechadas e feriados, e com quanta antecedência? | Define uma fonte operacional de indisponibilidade. |
| Q-03 | Qual política de cancelamento/remarcação vale para cliente e equipe (prazo, multa, limites)? | A rota de cancelamento existe, mas a política deve ser confirmada com o negócio. |
| Q-04 | Quais notificações são obrigatórias, por quais canais e em quais eventos? | E-mail/SMS/WhatsApp dependem de provedor, consentimento e regras de entrega. |
| Q-05 | Quais métricas e exportações os relatórios administrativos devem fornecer? | Evita tratar uma página administrativa como especificação de analytics. |
| Q-06 | Quais papéis podem editar cada campo e ver dados pessoais/clínicos? | Permite fechar uma matriz de acesso além da proteção geral por papel. |
| Q-07 | Quais política de backup, retenção e recuperação de desastre são exigidas? | Define RPO/RTO, retenção de dados e responsabilidades operacionais. |
| Q-08 | Quais navegadores/dispositivos e metas de acessibilidade devem ser oficialmente suportados? | Converte responsividade atual em critérios mensuráveis de compatibilidade. |
| Q-09 | Qual ambiente e processo de deploy serão usados em produção? | Determina gestão de segredos, TLS, observabilidade e migrações. |

## 8. Fora do escopo confirmado

Não se considera confirmado, sem decisão explícita: cobrança online, emissão fiscal, integração com WhatsApp, aplicativo nativo, programa de fidelidade, múltiplas unidades, escalas avançadas, notificações garantidas por provedor, relatórios exportáveis e SLA de produção.
