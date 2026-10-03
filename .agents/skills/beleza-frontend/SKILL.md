---
name: beleza-frontend
description: Implementa mudanças pontuais em HTML, CSS Vanilla ou JavaScript Vanilla do Beleza Cão&Gato. Use para formulários, componentes e interações frontend; para uma tela completa guiada por mockup use beleza-ui-implementation.
---

# Frontend existente

1. Localize a view PHP, stylesheet e script que o layout realmente carrega; confira `resources/views/layouts/` e `public/assets/css/main.css` antes de criar um asset.
2. Use HTML semântico: `a` navega, `button` executa ação, `label` identifica controle. Preserve valores de formulário com encoding adequado e estados `readonly`/`disabled` corretos.
3. Em CSS, prefira escopo por página/componente, tokens existentes, Grid/Flexbox e foco visível. Evite seletor global novo que altere outras telas. Verifique texto longo e larguras pequenas.
4. Em JavaScript, use Vanilla JS, `const` por padrão, delegação quando útil e `textContent` para dados não confiáveis. Trate erro de `fetch`, cliques repetidos e restauração da interface. Regras críticas continuam no PHP.
5. Reuse o overlay temático em `resources/views/partials/request-loading.php` quando a operação exigir bloqueio; respeite `prefers-reduced-motion`.
