---
name: beleza-documentation
description: Cria ou atualiza documentação técnica do Beleza Cão&Gato em /docs/ com base no código atual. Use para documentação solicitada ou para registrar decisão duradoura alterada; não copie documentação histórica sem validar.
---

# Documentação do projeto

1. Verifique comportamento no código, depois schema/configuração, e só então compare com README e `/docs/`.
2. Documente decisões, fluxos operacionais e procedimentos não óbvios; prefira link para fonte estável a copiar trechos longos de código.
3. Comandos de banco devem distinguir inicialização destrutiva (`database/schema.sql`, `bin/migrate.php`) de migrações incrementais. Nunca descreva como executado algo apenas planejado.
4. Corrija contradições quando a tarefa autorizar documentação, sem mudar a regra funcional silenciosamente. Faça os exemplos conferirem com nomes reais de arquivos, rotas e variáveis.
