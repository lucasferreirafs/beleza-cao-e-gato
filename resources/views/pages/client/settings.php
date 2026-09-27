<?php $e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<header class="page-heading"><div><h1>Configurações</h1><p>Gerencie a segurança e os acessos à sua conta.</p></div></header>
<section class="panel account-settings">
    <h2>Acessos lembrados</h2>
    <p>Ao entrar com “Lembrar de login, seu acesso permanece disponível por até 30 dias neste navegador.</p>
    <form method="post" action="/conta/revogar-acessos" data-confirm="Revogar os acessos lembrados e encerrar as outras sessões?">
        <?= \App\Security\Csrf::field() ?><button class="button secondary" type="submit">Revogar outros acessos</button>
    </form>
    <h2>Dados e senha</h2>
    <p><a href="<?= $e(\App\Support\Navigation::home($user['role'])) ?>/perfil">Editar meus dados e minha foto</a></p>
    <p><a href="/recuperar-senha">Redefinir minha senha por e-mail</a></p>
    <h2>Termos de uso</h2>
    <p>Versão aceita: <?= $e($user['terms_version'] ?? 'Ainda não registrada') ?>
        <?= !empty($user['terms_accepted_at']) ? ' · ' . date('d/m/Y', strtotime($user['terms_accepted_at'])) : '' ?>.</p>
    <a href="/termos">Ler termos de uso</a>
</section>
