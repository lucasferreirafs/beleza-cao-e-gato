<?php
$role = $user['role'] ?? 'client';
$items = match ($role) {
    'admin' => [
        'dashboard' => ['Dashboard', '/admin', '<i class="fa-solid fa-chart-pie"></i>'],
        'agendamentos' => ['Agendamentos', '/admin/agendamentos', '<i class="fa-regular fa-calendar-days"></i>'],
        'clientes' => ['Clientes', '/admin/clientes', '<i class="fa-solid fa-users"></i>'],
        'pets' => ['Pets', '/admin/pets', '<i class="fa-solid fa-paw"></i>'],
        'funcionarios' => ['Funcionários', '/admin/funcionarios', '<i class="fa-solid fa-users-line"></i>'],
        'servicos' => ['Serviços', '/admin/servicos', '<i class="fa-solid fa-bone"></i>'],
        'relatorios' => ['Relatórios', '/admin/relatorios', '<i class="fa-solid fa-chart-simple"></i>'],
        'configuracoes' => ['Configurações', '/admin/configuracoes', '<i class="fa-solid fa-gear"></i>'],
    ],
    'staff' => [
        'dashboard' => ['Dashboard', '/staff', '<i class="fa-solid fa-chart-pie"></i>'],
        'agenda' => ['Minha Agenda', '/staff/agenda', '<i class="fa-regular fa-calendar-days"></i>'],
        'atendimentos' => ['Atendimentos', '/staff/atendimentos', '<i class="fa-solid fa-dog"></i>'],
        'clientes' => ['Clientes', '/staff/clientes', '<i class="fa-solid fa-users"></i>'],
        'pets' => ['Pets', '/staff/pets', '<i class="fa-solid fa-paw"></i>'],
        'historico' => ['Histórico', '/staff/historico', '<i class="fa-solid fa-clock-rotate-left"></i>'],
        'profile' => ['Meu Perfil', '/staff/perfil', '<i class="fa-solid fa-user"></i>'],
    ],
    default => [
        'dashboard' => ['Inicio', '/cliente', '<i class="fa-solid fa-house"></i>'],
        'pets' => ['Meus Pets', '/cliente/pets', '<i class="fa-solid fa-paw"></i>'],
        'appointments' => ['Agendamentos', '/cliente/agendamentos', '<i class="fa-regular fa-calendar-days"></i>'],
        'services' => ['Serviços', '/cliente/servicos', '<i class="fa-solid fa-bone"></i>'],
        'profile' => ['Meu Perfil', '/cliente/perfil', '<i class="fa-solid fa-user"></i>'],
    ],
};
?>

<aside class="sidebar" data-sidebar>
    <div>
        <a class="brand" href="/dashboard">
            <span class="brand-mark" aria-hidden="true">
                <i class="fa-solid fa-paw"></i>
            </span>
            <span>
                <strong>Beleza Cão&amp;Gato</strong>
                <small>
                    <?= $e(match ($role) {
                        'admin' => 'SISTEMA ADMINISTRATIVO',
                        'staff' => 'ÁREA DO COLABORADOR',
                        default => 'ÁREA DO CLIENTE'
                    }) ?>
                </small>
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

    <form method="post" action="/logout">
        <?= \App\Security\Csrf::field() ?>
        <button class="logout" type="submit">
            <i class="fa-solid fa-right-from-bracket"></i>
            <?= $e($role === 'admin' ? 'Sair do Painel' : 'Sair do Sistema') ?>
        </button>
    </form>
</aside>