---
name: beleza-observability
description: Projeta ou revisa logs, mensagens de erro e diagnóstico operacional do Beleza Cão&Gato. Use quando o pedido envolver observabilidade, investigação de falhas recorrentes ou trilhas de auditoria; bugs isolados pertencem a beleza-debugging.
---

# Logs e diagnóstico

1. Defina o evento operacional a observar e os dados mínimos para correlacioná-lo. Separe mensagem pública segura de detalhe técnico em log.
2. Nunca registre senha, sessão, token, chave Supabase, Authorization header ou PII desnecessária. Não mostre SQL, stack trace ou path interno na resposta HTML.
3. Reuse tratamento existente em `public/index.php` e erros de serviço. Se propor formato estruturado ou PSR-3, confirme necessidade e dependência antes de introduzi-los.
4. Teste falha controlada, verificando informação suficiente para diagnóstico sem vazamento. Defina retenção ou rotação apenas se o ambiente de execução suportar isso.
