---
name: beleza-qa-release
description: Faz verificação funcional e de regressão antes de entregar uma mudança relevante no Beleza Cão&Gato. Use para QA final ou preparação de release; para escrever testes novos use beleza-testing.
---

# QA final

Leia [matriz de verificação](references/matriz.md) e selecione apenas cenários alcançados pela mudança.

1. Confira que a aplicação e rotas afetadas carregam sem fatal error; valide autenticação, autorização, CSRF e persistência nos fluxos alterados.
2. Cubra happy path, entradas inválidas, estados vazios, concorrência quando envolver booking, erros externos quando envolver Storage e regressões dos consumidores compartilhados.
3. Para frontend, confira mobile, tablet, desktop, teclado, foco, erros de console e comportamento de backdrop/modal. Respeite a preferência por movimento reduzido.
4. Distinga teste executado de revisão estática. Não trate `php -l` como teste de integração nem declare cobertura inexistente.
5. Entregue resultado e riscos remanescentes proporcionais ao escopo. Não execute migração ou release em produção como parte implícita da revisão.
