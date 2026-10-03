# Backlog do Projeto — Beleza Cão&Gato

Este backlog separa funcionalidades já visíveis no código de oportunidades ainda não aprovadas. “Concluído” significa encontrado na implementação atual; não é promessa de disponibilidade em produção. As prioridades dos itens futuros são sugestões iniciais, sujeitas à elicitação e aprovação do responsável pelo produto.

## Convenções

- **Concluído:** comportamento observado no código atual.
- **A decidir:** depende de resposta de negócio/operação antes de estimativa ou compromisso.
- **Proposta:** oportunidade recomendada; não consta como requisito aprovado.
- **P0/P1/P2:** sugestão de prioridade (bloqueador/alta/média), não prazo.

## Entregas já representadas na aplicação

| ID | Épico | Item / resultado | Estado |
|---|---|---|---|
| BGC-001 | Conta e acesso | Cadastro, login, papéis de acesso, verificação de e-mail, recuperação/redefinição de senha e “lembrar de mim”. | Concluído |
| BGC-002 | Conta e acesso | Proteção CSRF nas operações mutáveis e autorização de rotas por autenticação/papel. | Concluído |
| BGC-003 | Perfil | Edição de dados do perfil/configurações e foto de usuário. | Concluído |
| BGC-004 | Pets | Cadastro, edição, perfil, listagem e remoção de pets do cliente; campos de alergia, castração e cor do pelo. | Concluído |
| BGC-005 | Pets | Upload/recuperação de fotos no bucket `img-pets` e fallback `pet.webp`. | Concluído |
| BGC-006 | Catálogo | Consulta de serviços com preço e duração. | Concluído |
| BGC-007 | Agendamento | Fluxo nomeado por etapas, seleção de pet e múltiplos serviços, escolha de profissional/preferência, horário e observação. | Concluído |
| BGC-008 | Agendamento | Consulta de disponibilidade, intervalo de 30 minutos, autoatribuição de profissional e revalidação no backend ao confirmar. | Concluído |
| BGC-009 | Agendamento | Persistência do total e composição de serviços no agendamento; consulta e cancelamento pelo cliente. | Concluído |
| BGC-010 | Operação | Área de funcionário para consultar páginas operacionais e atualizar status de atendimento. | Concluído |
| BGC-011 | Administração | Seções administrativas para manutenção dos registros e status previstos no sistema. | Concluído |
| BGC-012 | Infraestrutura | Aplicação PHP/Apache em Docker, persistência PostgreSQL e integração cURL com Supabase Storage existente. | Concluído |

## Backlog candidato — depende de elicitação

Os itens desta seção são propostas para discussão, não uma afirmação de que o produto já os exige. Ordenação inicial sugerida: primeiro fechar regras operacionais que afetam disponibilidade e segurança; depois definir integrações e observabilidade.

| ID | Épico | História / resultado esperado | Prioridade sugerida | Estado / dependência | Critério inicial de aceite |
|---|---|---|---|---|---|
| BGC-101 | Agenda operacional | Como responsável pela operação, quero cadastrar expediente, pausas e exceções por profissional para que a disponibilidade reflita a escala real. | P0 | A decidir: escalas, pausas, antecedência e permissões (Q-01). | A consulta de disponibilidade respeita expediente e exceções; horário fora da escala não pode ser confirmado por envio manual. |
| BGC-102 | Agenda operacional | Como administrador, quero bloquear datas/horários fechados e feriados. | P0 | A decidir: quem bloqueia, abrangência e regras de recorrência (Q-02). | Bloqueios ativos somem da disponibilidade e são revalidados na confirmação. |
| BGC-103 | Agendamento | Como cliente/equipe, quero uma política explícita de cancelamento e remarcação apresentada antes da confirmação. | P1 | A decidir: prazos, penalidades e permissões (Q-03). | Regras são mostradas no fluxo e aplicadas uniformemente no servidor. |
| BGC-104 | Comunicação | Como cliente, quero receber confirmações e atualizações pelos canais definidos pelo negócio. | P1 | A decidir: canais, eventos, consentimento e provedor (Q-04). | Eventos acordados geram mensagem rastreável; falha de envio não apaga nem duplica o agendamento. |
| BGC-105 | Relatórios | Como gestor, quero indicadores e exportações que respondam às perguntas operacionais acordadas. | P2 | A decidir: métricas, filtros, formato e permissões (Q-05). | Cada métrica tem definição, período/fuso claros e totais conferíveis com os registros de origem. |
| BGC-106 | Segurança e privacidade | Como responsável pelos dados, quero uma matriz de permissões para dados de conta e informações clínicas dos pets. | P1 | A decidir: papéis/campos e finalidade (Q-06). | Testes de autorização cobrem cada papel e acesso direto a registros de outro cliente. |
| BGC-107 | Operação/continuidade | Como operador, quero política testada de backup e restauração do PostgreSQL e objetos relevantes. | P1 | A decidir: retenção, RPO/RTO e responsabilidade (Q-07). | Restauração em ambiente isolado documentada e validada periodicamente contra metas aprovadas. |
| BGC-108 | Qualidade/acessibilidade | Como usuário, quero concluir fluxos essenciais por teclado e em dispositivos suportados. | P2 | A decidir: navegadores, dispositivos e nível de acessibilidade (Q-08). | Matriz acordada passa por testes de viewport, teclado, foco, contraste e leitor de tela. |
| BGC-109 | Entrega/produção | Como equipe técnica, quero pipeline de deploy e gestão de segredos/ambientes para publicar alterações com segurança. | P1 | A decidir: provedor, ambientes, revisão e rollback (Q-09). | Segredos não entram no repositório; deploy e rollback são repetíveis e há verificação pós-publicação. |
| BGC-110 | Banco de dados | Como equipe técnica, quero migrações incrementais rastreáveis em vez de depender apenas do script destrutivo de schema. | P1 | Proposta técnica a aprovar; confirmar estratégia de deploy existente. | Migrações versionadas aplicam-se uma única vez em base com dados sem apagar o schema inteiro e têm plano de rollback/recuperação. |
| BGC-111 | Observabilidade | Como equipe de suporte, quero logs estruturados e alertas para erros de login, booking, e-mail e Storage sem expor dados sensíveis. | P2 | Proposta; definir ferramentas e retenção. | Falhas críticas são correlacionáveis por identificador de requisição; tokens, senhas e chaves não aparecem nos logs. |

## Sequenciamento recomendado para refinamento

1. Responder Q-01, Q-02 e Q-03; então detalhar e estimar BGC-101 a BGC-103, pois afetam regras centrais de agenda.
2. Responder Q-04 a Q-07 e estabelecer escopo operacional/privacidade antes de integrar notificações ou ampliar relatórios.
3. Aprovar ambiente de entrega e metas não funcionais (Q-08/Q-09); priorizar migrações, backup e observabilidade conforme risco real.
4. Para cada proposta aprovada, dividir em tarefas de backend, interface, migração, QA e documentação; registrar estimativa e responsável somente após refinamento.

## Referências

- [Levantamento de requisitos](levantamento-de-requisitos.md): atores, requisitos observados, regras, critérios transversais e perguntas em aberto.
- [README principal](../README.md): arquitetura, execução e detalhes técnicos do projeto.
