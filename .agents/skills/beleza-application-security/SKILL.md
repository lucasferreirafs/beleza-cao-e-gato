---
name: beleza-application-security
description: Implementa ou audita controles de autenticação, sessão, autorização, CSRF, XSS e privacidade no Beleza Cão&Gato. Use em mudanças sensíveis ou auditorias de segurança; revisões gerais de código pertencem a beleza-code-review.
---

# Segurança da aplicação

Antes de uma mudança sensível, leia [fronteiras de confiança](references/fronteiras.md) para o fluxo afetado.

1. Identifique ator, ativo, entrada e fronteira de confiança. Verifique autorização no servidor para cada objeto, não apenas o papel da rota ou a visibilidade de um botão.
2. Preserve `Session::login()` com regeneração do ID, cookies `HttpOnly`/`Secure`/`SameSite`, expiração e `RememberMeService` revogável. Senhas usam `password_hash()`/`password_verify()`, nunca hash simples.
3. Mutações HTTP exigem CSRF conforme `routes/web.php` e `CsrfMiddleware`; GET não deve executar ações destrutivas. Valide entrada, use SQL parametrizado e encode a saída para seu contexto.
4. Evite segredos e PII desnecessária em HTML, JS, URL, logs ou exceções públicas. Verifique upload e imagens privadas com `beleza-supabase-storage` quando o risco estiver ali.
5. Reporte o que foi testado e as limitações, sem alegar segurança absoluta.
