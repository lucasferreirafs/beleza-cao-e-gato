---
name: beleza-php-backend
description: Implementa ou revisa lógica backend PHP puro no Beleza Cão&Gato, incluindo controllers, services, repositories, middleware e helpers. Use quando o trabalho principal for código PHP; não substitui a Skill específica do motor de agendamentos.
---

# PHP do projeto

1. Leia `composer.json`, o ponto de entrada em `public/index.php` e as classes vizinhas da mudança. O namespace `App\` mapeia para `app/`; rotas são declaradas em `routes/web.php`.
2. Mantenha fronteiras: controller recebe e responde; service coordena regra; repository persiste; view apresenta. Reuse abstrações existentes somente quando reduzirem duplicação real.
3. Prefira `strict_types`, assinaturas e retornos explícitos quando compatíveis com a área alterada. Faça validação nas entradas, use early return quando clarificar o fluxo, e trate falhas sem expor SQL, paths ou segredos ao usuário.
4. Não confie em `Request::input()` como valor escalar: campos podem chegar como arrays. Escape saída no contexto da view. Evite estado global novo e padrões artificiais.
5. Rode `php -l` nos arquivos modificados e o teste de fluxo pertinente. Antes de editar uma API compartilhada, localize seus consumidores com `rg`.
