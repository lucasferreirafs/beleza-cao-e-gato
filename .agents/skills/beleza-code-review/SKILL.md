---
name: beleza-code-review
description: Revisa alterações ou código existente do Beleza Cão&Gato e relata findings priorizados com evidência. Use quando o usuário pedir revisão ou auditoria de código; não modifica arquivos numa revisão somente de leitura.
---

# Revisão de código

1. Determine diff, objetivo e consumidores. Leia o código executado, o schema e testes necessários para entender o comportamento.
2. Priorize corretude e regra de negócio; depois segurança/autorização, manutenção, performance, acessibilidade e testes. Não invente problema a partir de preferência estilística.
3. Classifique findings como Critical, High, Medium, Low ou Improvement. Para cada um, informe arquivo/linha, condição de ocorrência, impacto e correção objetiva.
4. Diferencie problema comprovado de risco que requer teste. Se nenhum finding for encontrado, declare o escopo verificado e a lacuna de testes, sem prometer ausência total de bugs.
5. Altere código somente se o pedido também solicitar correção.
