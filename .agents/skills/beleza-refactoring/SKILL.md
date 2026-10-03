---
name: beleza-refactoring
description: Reorganiza código existente do Beleza Cão&Gato preservando comportamento e contratos. Use para limpeza, extração ou simplificação sem nova regra; se houver funcionalidade nova use beleza-feature-development.
---

# Refatoração incremental

1. Estabeleça comportamento atual, consumidores e testes antes de editar. Registre qualquer divergência conhecida para não confundi-la com regressão.
2. Mude em passos pequenos; evite reescrever uma área inteira quando uma extração basta. Não crie abstração para similaridade superficial.
3. Preserve rotas, payloads, status, permissões, mensagens essenciais e compatibilidade de dados, salvo instrução explícita contrária.
4. Compare antes/depois com testes e inspeção dos fluxos relacionados. Separe correção funcional descoberta da refatoração quando o escopo exigir decisão do usuário.
