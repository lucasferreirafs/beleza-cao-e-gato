---
name: beleza-ui-implementation
description: Constrói ou refatora uma tela do Beleza Cão&Gato a partir de requisito visual, mockup ou fluxo aprovado. Use para implementação visual completa; avaliações sem alteração pertencem a beleza-ux-review ou beleza-accessibility-review.
---

# Implementação de telas

1. Inspecione mockup, views próximas, tokens e componentes existentes. Mapeie conteúdo real e estados: vazio, carregando, erro, sucesso, disabled e status de negócio.
2. Construa a hierarquia HTML e CSS coerente com as telas atuais. Mantenha lógica de negócio e autorização no PHP; deixe a view modular quando houver reuso concreto.
3. Faça layout fluido desde larguras estreitas. Tabelas complexas precisam de alternativa legível, como cards, se a informação não couber. Modais e menus devem bloquear interação indevida no fundo.
4. Garanta teclado, foco, contraste, rótulos e toque confortável. Interações JS devem ter feedback e fallback razoável.
5. Confira a tela em mobile, tablet e desktop, com nomes e observações longos, e execute os testes do fluxo afetado.
