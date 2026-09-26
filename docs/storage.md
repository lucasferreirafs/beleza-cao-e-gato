# Supabase Storage — fase 0

Integração PHP/cURL com os buckets **existentes** `img-user` e `img-pets`.
Não cria buckets nem altera suas permissões. Nenhuma rota de upload foi exposta nesta fase.

## Configuração

Copie as três variáveis comentadas de `.env.example` para seu `.env` e preencha:

- `SUPABASE_URL`: URL HTTPS do projeto, por exemplo `https://seu-projeto.supabase.co`.
- `SUPABASE_ANON_KEY`: chave anon, reservada para futuras integrações com RLS.
- `SUPABASE_SERVICE_ROLE_KEY`: chave service_role usada exclusivamente no servidor.

A chave service_role tem acesso privilegiado; não deve chegar ao HTML/JavaScript,
às mensagens de erro nem aos logs. Não existe fallback para a chave anon.
O `.dockerignore` exclui `.env` e arquivos de sessão da imagem; Docker Compose injeta as variáveis via `env_file`.
As variáveis são lidas por `App\Core\Env`, como as configurações existentes.
O front controller já carrega `.env`; scripts CLI devem chamar `Env::load()` antes.

Após configurar, recrie a aplicação para aplicar as variáveis e os limites PHP:

```sh
docker compose up -d --build
```

O runtime precisa de cURL e Fileinfo; o Dockerfile verifica essas extensões no build.
`docker/uploads.ini` permite uploads de 5 MiB e POST de 6 MiB. Se o POST inteiro
ultrapassar `post_max_size`, o PHP esvazia `$_POST`/`$_FILES`; a futura rota deve
tratar esse caso antes da validação CSRF e mostrar uma mensagem de tamanho excedido.

## Uso no servidor

```php
use App\Services\SupabaseStorageService;

$storage = new SupabaseStorageService();

// Após autenticar, validar CSRF e autorizar acesso ao usuário/pet:
$image = $storage->upload(SupabaseStorageService::USER_BUCKET, $_FILES['photo'] ?? []);
// Para pets, use SupabaseStorageService::PET_BUCKET.
// Retorna: bucket, path, mime e size. Persistir bucket/path no registro autorizado.

// URL temporária (funciona também para bucket privado); não persisti-la no banco.
$url = $storage->signedUrl($image['bucket'], $image['path'], 3600);

// Alternativa somente se o bucket já for público:
$publicUrl = $storage->publicUrl($image['bucket'], $image['path']);

// Atualiza o arquivo existente mantendo seu nome aleatório e formato.
// $pathFromDatabase deve vir do registro cuja propriedade já foi verificada.
$updated = $storage->update('img-user', $pathFromDatabase, $_FILES['photo'] ?? []);
```

`uploadImage()` e `updateImage()` aceitam bytes já disponíveis no servidor e o nome
de origem, para importações e testes. Para requisições multipart, use `upload()` e
`update()`, que verificam também `is_uploaded_file()` e os erros de upload do PHP.

## Regras e integração das próximas fases

- Aceita JPG/JPEG, PNG e WebP de até 5 MiB, até 8192 pixels por lado e 25 megapixels.
  MIME detectado por Fileinfo e cabeçalho da imagem devem corresponder à extensão.
  SVG, arquivos vazios e extensões incompatíveis são rejeitados.
- Cada novo arquivo recebe 64 caracteres hexadecimais gerados por
  `bin2hex(random_bytes(32))`, preservando a extensão original, inclusive sua caixa.
  O nome original não é enviado ao Storage. Uploads usam POST com `x-upsert: false`;
  atualizar usa PUT no caminho aleatório existente.
- Para mudar o formato ou evitar cache de uma foto anterior, envie um novo arquivo
  e atualize o caminho do registro somente após o upload terminar com sucesso.
  Atualizações simultâneas no mesmo caminho têm comportamento de última gravação.
- A service_role ignora RLS: os controllers futuros devem validar o dono do usuário/pet
  antes de chamar o serviço, inclusive ao gerar URLs privadas. Bucket/path não devem
  ser aceitos diretamente do navegador como autorização.
- Persistência do caminho e limpeza de arquivos substituídos ou órfãos serão integradas
  aos fluxos de usuário/pet nas próximas fases. URLs públicas dependem da configuração
  atual do bucket; o serviço não muda sua visibilidade.
- Ao integrar imagens às telas, incluir o host de `SUPABASE_URL` no `img-src` da
  Content-Security-Policy em `docker/apache.conf` (hoje limitado a self/data).
- cURL usa HTTPS, valida certificado/host, não segue redirecionamentos e tem timeout
  de conexão de 10 s e total de 30 s. Falhas remotas geram mensagens genéricas em português.

## QA

```sh
php tests/storage.php
php tests/lint.php
php tests/run.php
```

`tests/storage.php` simula respostas HTTP e valida nomes únicos, buckets, métodos,
headers, conteúdo, URLs, limites, arquivos inválidos, erros PHP, rede e API, sem
acesso ao Storage e sem carregar as credenciais reais. A conexão real deve ser
validada após preencher as variáveis; os testes simulados não comprovam permissões
ou configuração dos buckets do projeto.

Referências: [uploads](https://supabase.com/docs/guides/storage/uploads/standard-uploads)
e [API de Storage](https://supabase.com/docs/reference/self-hosting-storage).
