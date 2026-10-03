---
name: beleza-supabase-storage
description: Implementa ou investiga upload, recuperação, autorização e cache de imagens do Supabase Storage no Beleza Cão&Gato. Use quando o pedido envolver img-user, img-pets, PHP/cURL ou performance de imagens privadas.
---

# Storage e imagens privadas

Leia [fluxo de imagens](references/imagens.md) antes de alterar a integração. Os buckets `img-user` e `img-pets` já existem; não crie buckets automaticamente.

1. Mantenha a `SUPABASE_SERVICE_ROLE_KEY` somente no PHP. Identifique o usuário ou pet autorizado antes de obter ou remover qualquer objeto.
2. Reuse `SupabaseStorageService`: limite, MIME real, dimensões, extensão permitida e path aleatório são validados nele. Nunca use nome ou path enviado pelo cliente como destino confiável.
3. Para leitura no navegador, preserve proxy autenticado e `PrivateImageResponse` com `Cache-Control: private, no-cache, must-revalidate` e ETag. O código atual não possui cache físico local.
4. Teste com `tests/storage.php`; testes com serviço remoto só quando configuração e escopo permitirem. Trate indisponibilidade do Storage sem revelar credenciais.
