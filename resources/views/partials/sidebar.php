<?php
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$role = $user['role'] ?? 'client';
$items = match ($role) {
    'admin' => [
        'dashboard'    => ['Dashboard',     '/admin',               '<i class="fa-solid fa-chart-pie"></i>'],
        'agendamentos' => ['Agendamentos',   '/admin/agendamentos',  '<i class="fa-regular fa-calendar-days"></i>'],
        'clientes'     => ['Clientes',       '/admin/clientes',      '<i class="fa-solid fa-users"></i>'],
        'pets'         => ['Pets',           '/admin/pets',          '<i class="fa-solid fa-paw"></i>'],
        'funcionarios' => ['Funcionários',   '/admin/funcionarios',  '<i class="fa-solid fa-users-line"></i>'],
        'servicos'     => ['Serviços',       '/admin/servicos',      '<i class="fa-solid fa-scissors"></i>'],
        'relatorios'   => ['Relatórios',     '/admin/relatorios',    '<i class="fa-solid fa-chart-simple"></i>'],
    ],
    'staff' => [
        'dashboard'    => ['Dashboard',     '/staff',               '<i class="fa-solid fa-chart-pie"></i>'],
        'agenda'       => ['Minha Agenda',  '/staff/agenda',        '<i class="fa-regular fa-calendar-days"></i>'],
        'atendimentos' => ['Atendimentos',  '/staff/atendimentos',  '<i class="fa-solid fa-dog"></i>'],
        'clientes'     => ['Clientes',      '/staff/clientes',      '<i class="fa-solid fa-users"></i>'],
        'pets'         => ['Pets',          '/staff/pets',          '<i class="fa-solid fa-paw"></i>'],
    ],
    default => [
        'dashboard'    => ['Início',        '/cliente',             '<i class="fa-solid fa-house"></i>'],
        'pets'         => ['Meus Pets',     '/cliente/pets',        '<i class="fa-solid fa-paw"></i>'],
        'appointments' => ['Agendamentos',  '/cliente/agendamentos','<i class="fa-regular fa-calendar-days"></i>'],
        'services'     => ['Serviços',      '/cliente/servicos',    '<i class="fa-solid fa-scissors"></i>'],
    ],
};
?>

<aside class="sidebar" id="app-sidebar" data-sidebar aria-label="Navegação principal">
    <button type="button" class="sidebar-close" data-sidebar-close aria-label="Fechar menu">
        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
    </button>

    <div class="sidebar-top">
        <a class="brand" href="/dashboard">
            <span class="brand-mark" aria-hidden="true">
                <i class="fa-solid fa-paw"></i>
            </span>
            <span>
                <strong>Beleza Cão&amp;Gato</strong>
                <small>PET SHOP &amp; CLÍNICA</small>
            </span>
        </a>

        <nav>
            <?php foreach ($items as $key => $item): ?>
                <a class="nav-item <?= ($active ?? '') === $key ? 'active' : '' ?>" href="<?= $e($item[1]) ?>">
                    <span><?= $item[2] ?></span>
                    <?= $e($item[0]) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="sidebar-footer">
        <form method="post" action="/logout">
            <?= \App\Security\Csrf::field() ?>
            <button class="logout" type="submit">
                <i class="fa-solid fa-right-from-bracket"></i>
                Sair da Conta
            </button>
        </form>
    </div>
</aside>
