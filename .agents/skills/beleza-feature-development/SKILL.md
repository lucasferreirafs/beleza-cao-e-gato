---
name: beleza-feature-development
description: Planeja e implementa uma nova funcionalidade do Beleza Cão&Gato que atravesse rotas, PHP, banco ou interface. Use para features novas; para correção pontual use beleza-debugging e para reorganização sem mudança funcional use beleza-refactoring.
---

# Desenvolvimento de funcionalidade

1. Traduza o pedido em comportamento observável, permissões e estados de erro. Localize rota, controller, service, repository, view e assets afetados; confirme nomes no código.
2. Verifique schema, contratos existentes, componentes reutilizáveis e riscos de concorrência, privacidade e regressão. Apresente um plano quando o usuário solicitar planejamento ou houver escolha de produto ainda aberta. Uma autorização explícita de implementação já satisfaz a aprovação do trabalho pedido.
3. Implemente o menor fluxo completo. Preserve fallback sem JavaScript quando viável e valide regras críticas no servidor.
4. Verifique caminhos de sucesso, erro, permissão e mobile proporcionais ao risco; atualize `/docs/` quando uma decisão duradoura mudar.
5. Entregue resultado, verificações executadas e limitações reais. Não acrescente funcionalidade fora do escopo por iniciativa própria.
