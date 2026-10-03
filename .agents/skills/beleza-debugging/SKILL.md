---
name: beleza-debugging
description: Investiga falhas reproduzíveis do Beleza Cão&Gato e corrige a causa raiz quando autorizado. Use para erro 500, quebra visual, falha de upload ou comportamento inesperado; não use para revisão preventiva sem defeito relatado.
---

# Investigação de defeitos

1. Reproduza no menor cenário seguro; anote entrada, estado, resposta e ambiente. Consulte logs sem divulgar dados sensíveis.
2. Localize a camada: rota/middleware, controller/service, SQL/Storage ou HTML/CSS/JS. Forme hipótese e teste-a antes de alterar.
3. Corrija a causa raiz com mudança mínima. Se o pedido for apenas diagnóstico, pare na explicação fundamentada.
4. Acrescente teste de regressão quando o defeito for significativo e repetível. Verifique o fluxo original e os consumidores relacionados.
5. Reporte causa comprovada, correção, evidência de teste e limites da investigação.
