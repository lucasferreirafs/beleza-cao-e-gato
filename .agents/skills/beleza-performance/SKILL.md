---
name: beleza-performance
description: Diagnostica ou otimiza lentidão de páginas, queries, imagens e requisições do Beleza Cão&Gato. Use quando houver problema mensurável de tempo, peso ou estabilidade visual; não ative para ajustes estéticos comuns.
---

# Performance orientada por evidência

1. Defina cenário, ambiente e métrica antes da mudança: TTFB, LCP, INP, CLS, tempo SQL ou tempo de download de imagem.
2. Localize o gargalo na cadeia navegador → PHP → PostgreSQL/Supabase. Use medidas repetíveis e não confunda latência externa com execução de PHP.
3. Priorize query cara, N+1, resultados sem limite, imagem pesada, asset bloqueante ou requisições redundantes conforme a evidência.
4. Preserve autorização e privacidade ao alterar cache. Compare antes/depois no mesmo cenário e verifique regressões visuais e funcionais.
5. Reporte ganho medido e condições; não declare melhoria sem medição.
