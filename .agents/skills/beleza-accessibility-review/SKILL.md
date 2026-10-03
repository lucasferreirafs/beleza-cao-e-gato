---
name: beleza-accessibility-review
description: Audita acessibilidade de telas e componentes do Beleza Cão&Gato, incluindo teclado, foco, nomes acessíveis, modais e responsividade. Use quando o pedido for avaliação de acessibilidade ou quando uma interação complexa exigir revisão específica.
---

# Revisão de acessibilidade

1. Use WCAG 2.2 AA como referência aplicável, sem tratar a revisão como certificação. Teste o fluxo com teclado: ordem, foco visível, abertura/fechamento e retorno de foco.
2. Inspecione semântica antes de ARIA: landmarks, headings, botões, links, labels, tabelas, `details` e diálogos. ARIA complementa a marcação correta.
3. Confira contraste, conteúdo em zoom, texto longo, alternativas de imagem, erros de formulário e touch targets. Respeite reduced motion.
4. Para cada problema, registre elemento, efeito para o usuário e correção verificável. Se a tarefa for apenas auditoria, não altere código.
