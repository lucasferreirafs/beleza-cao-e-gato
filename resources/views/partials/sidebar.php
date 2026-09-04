<?php
$role = $user['role'] ?? 'client';
$items = match ($role) {
    'admin' => [
        'dashboard' => ['Dashboard', '/admin', '<i class="fa-solid fa-chart-line"></i>'],
        'agendamentos' => ['Agendamentos', '/admin/agendamentos', '<i class="fa-regular fa-calendar-days"></i>'],
        'clientes' => ['Clientes', '/admin/clientes', '♧'],
        'pets' => ['Pets', '/admin/pets', '<i class="fa-solid fa-paw"></i>'],
        'funcionarios' => ['Funcionários', '/admin/funcionarios', '▣'],
        'servicos' => ['Serviços', '/admin/servicos', '<i class="fa-solid fa-clipboard-list"></i>'],
        'relatorios' => ['Relatórios', '/admin/relatorios', '▥'],
        'configuracoes' => ['Configurações', '/admin/configuracoes', '⌘']
    ],
    'staff' => [
        'dashboard' => ['Dashboard', '/staff', '<i class="fa-solid fa-chart-line"></i>'],
        'agenda' => ['Minha Agenda', '/staff/agenda', '<i class="fa-regular fa-calendar-days"></i>'],
        'atendimentos' => ['Atendimentos', '/staff/atendimentos', '♧'],
        'clientes' => ['Clientes', '/staff/clientes', '♧'],
        'pets' => ['Pets', '/staff/pets', '<i class="fa-solid fa-paw"></i>'],
        'historico' => ['Histórico', '/staff/historico', '<i class="fa-solid fa-clock-rotate-left"></i>'],
        'profile' => ['Meu Perfil', '/staff/perfil', '<i class="fa-solid fa-user"></i>']
    ],
    default => [
        'dashboard' => ['Dashboard', '/cliente', '<i class="fa-solid fa-chart-line"></i>'],
        'pets' => ['Meus Pets', '/cliente/pets', '<i class="fa-solid fa-paw"></i>'],
        'appointments' => ['Agendamentos', '/cliente/agendamentos', '<i class="fa-regular fa-calendar-days"></i>'],
        'services' => ['Serviços', '/cliente/servicos', '<i class="fa-solid fa-clipboard-list"></i>'],
        'history' => ['Histórico', '/cliente/agendamentos', '<i class="fa-solid fa-clock-rotate-left"></i>'],
        'profile' => ['Meu Perfil', '/cliente/perfil', '<i class="fa-solid fa-user"></i>']
    ]
};

?>
<aside class="sidebar" data-sidebar>
    <div>
        <a class="sidebar-brand" href="/dashboard">
            <div class="brand-mark" aria-hidden="true">
                <i class="fa-solid fa-dog"></i>
                <span class="brand-mark-sep"></span>
                <i class="fa-solid fa-cat"></i>
            </div>
            <span class="brand-text">
                <strong>Beleza <span class="brand-name-accent">Cão&amp;Gato</span></strong>
                <small>SISTEMA DE GESTÃO</small>
            </span>
        </a>

        <nav>
            <?php foreach ($items as $key => $item): ?>
                <a class="nav-item <?= ($active ?? '') === $key ? 'active' : '' ?>" href="<?= $e($item[1]) ?>">
                    <span>
                        <?= $item[2] ?>
                    </span>
                    <?= $e($item[0]) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <form method="post" action="/logout">
        <?= \App\Security\Csrf::field() ?>
        <button class="logout" type="submit">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <?= $e($role === 'admin' ? 'Sair do Painel' : 'Sair do Sistema') ?>
        </button>
    </form>
</aside>