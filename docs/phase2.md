# Fase 2 — gestão de pets e fotos

Depois de atualizar a imagem da aplicação, aplique a migração aditiva para adicionar pets.photo_path sem alterar os pets existentes:

    docker compose up -d --build app
    docker compose exec app php bin/migrate-phase2.php

O formulário aceita JPEG, PNG e WebP de até 5 MiB, validando conteúdo e dimensões no servidor. Os arquivos são enviados ao bucket existente img-pets usando a chave de serviço apenas no PHP; o nome salvo é aleatório. A visualização passa por rota autenticada que confirma a propriedade do pet. Substituição e exclusão tentam remover as imagens antigas do Storage.

Rotas POST seguem protegidas pelo middleware CSRF. Também verificam o dono do pet em cada operação.
