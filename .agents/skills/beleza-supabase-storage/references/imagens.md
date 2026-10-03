# Fluxo atual de imagens

- `SupabaseStorageService` usa PHP/cURL e REST com `img-user` e `img-pets`; cria nome aleatório e valida extensão, MIME, dimensões e limite de 5 MB.
- `avatar_path` e `photo_path` guardam paths de objeto no banco. A chave privilegiada nunca é entregue ao navegador.
- Fotos privadas passam por controllers PHP e `PrivateImageResponse`, após autenticação e autorização do dono ou funcionário designado. O navegador recebe `Cache-Control: private, no-cache, must-revalidate` e ETag.
- Não existe, no código atual, mecanismo de cache físico em `public/assets/cache/`. Não documente ou implemente esse fluxo sem solicitação e análise de privacidade.
- Verifique comportamento com `tests/storage.php`; chamadas reais ao projeto Supabase dependem de ambiente autorizado e não fazem parte de teste local padrão.
