<?php
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$singular = match ($section) {
    'clientes' => 'Cliente',
    'pets' => 'Pet',
    'funcionarios' => 'Funcionário',
    'servicos' => 'Serviço',
    'agendamentos' => 'Agendamento',
    default => null,
};
$appointmentTimezone = new DateTimeZone((string) \App\Core\Env::get('APP_TIMEZONE', 'America/Sao_Paulo'));
$formatDuration = static function (int $minutes): string {
    $hours = intdiv($minutes, 60);
    $remainingMinutes = $minutes % 60;
    if ($hours === 0) return $remainingMinutes . ' min';
    return $hours . ' h' . ($remainingMinutes > 0 ? ' ' . $remainingMinutes . ' min' : '');
};
?>

<header class="page-heading">
    <div>
        <h1><?= $e($title) ?></h1>
        <p>Consulte cadastros, vínculos e estados persistidos no banco.</p>
    </div>

    <?php if ($section === 'servicos'): ?>
        <a class="button primary" href="/admin/<?= $e($section) ?>/novo">
            ＋ Novo <?= $e($singular) ?>
        </a>
    <?php endif; ?>
</header>

<?php if ($section === 'configuracoes'): ?>
    <section class="panel">
        <h2>Configurações</h2>
        <p>Parâmetros sensíveis são administrados por variáveis de ambiente e não são expostos nesta tela.</p>
    </section>

<?php elseif ($section === 'agendamentos'): ?>
    <section class="panel admin-appointments-panel" data-admin-appointments>
        <form method="get" action="/admin/agendamentos" class="admin-appointment-filters" role="search" aria-label="Filtros de agendamentos">
            <label class="admin-appointment-search">
                <span>Buscar agendamentos</span>
                <input type="search" name="q" value="<?= $e($search ?? '') ?>" maxlength="100" placeholder="Buscar por pet, tutor ou serviço...">
            </label>
            <label>
                <span>Data</span>
                <input type="date" name="date" value="<?= $e($dateFilter ?? '') ?>" aria-label="Filtrar por data do agendamento">
            </label>
            <label>
                <span>Status</span>
                <select name="status" aria-label="Filtrar por status">
                    <option value="">Todos os status</option>
                    <?php foreach (\App\Support\AppointmentStatus::editable() as $value => $label): ?>
                        <option value="<?= $e($value) ?>" <?= ($statusFilter ?? '') === $value ? 'selected' : '' ?>><?= $e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="button primary" type="submit">Filtrar</button>
        </form>

        <div class="admin-appointment-list" data-admin-appointment-list>
            <?php foreach ($rows as $row): ?>
                <?php
                $scheduledAt = (new DateTimeImmutable((string) $row['scheduled_at']))->setTimezone($appointmentTimezone);
                $detailsId = 'appointment-details-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $row['id']);
                ?>
                <article class="admin-appointment-card" data-admin-appointment-card
                    data-status="<?= $e($row['status']) ?>" data-date="<?= $e($scheduledAt->format('Y-m-d')) ?>">
                    <div class="admin-appointment-summary">
                        <div class="appointment-summary-field appointment-date-field">
                            <span>Data e horário</span>
                            <time datetime="<?= $e($scheduledAt->format(DateTimeInterface::ATOM)) ?>">
                                <?= $e($scheduledAt->format('d/m/Y')) ?> · <?= $e($scheduledAt->format('H:i')) ?>
                            </time>
                        </div>
                        <div class="appointment-summary-field">
                            <span>Pet</span>
                            <strong><?= $e($row['pet_name']) ?></strong>
                        </div>
                        <div class="appointment-summary-field">
                            <span>Tutor</span>
                            <strong><?= $e($row['client_name']) ?></strong>
                        </div>
                        <span class="badge <?= $e(\App\Support\StatusBadge::class($row['status'])) ?>">
                            <?= $e(\App\Support\AppointmentStatus::label($row['status'])) ?>
                        </span>
                        <button type="button" class="button ghost admin-appointment-details-toggle"
                            aria-expanded="false" aria-controls="<?= $e($detailsId) ?>" data-appointment-details-toggle>
                            <span>Ver detalhes do agendamento</span>
                            <i class="fa-solid fa-angle-down" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="admin-appointment-details" id="<?= $e($detailsId) ?>" data-appointment-details hidden>
                        <div class="appointment-detail-block">
                            <h3>Serviços contratados</h3>
                            <?php if ($row['services']): ?>
                                <ul class="appointment-services-list">
                                    <?php foreach ($row['services'] as $service): ?>
                                        <li>
                                            <span><?= $e($service['name']) ?></span>
                                            <span><?= $e($formatDuration((int) $service['duration_minutes'])) ?></span>
                                            <strong>R$ <?= $e(number_format((float) $service['price'], 2, ',', '.')) ?></strong>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>Os detalhes dos serviços não estão disponíveis.</p>
                            <?php endif; ?>
                        </div>

                        <dl class="appointment-meta-list">
                            <div><dt>Duração total</dt><dd><?= $e($formatDuration((int) $row['duration_minutes'])) ?></dd></div>
                            <div><dt>Valor total</dt><dd>R$ <?= $e(number_format((float) $row['price'], 2, ',', '.')) ?></dd></div>
                            <div><dt>Profissional</dt><dd><?= $e($row['staff_name'] ?? 'Sem profissional') ?></dd></div>
                            <?php if (is_string($row['notes']) && trim($row['notes']) !== ''): ?>
                                <div class="appointment-notes"><dt>Observações</dt><dd><?= nl2br($e($row['notes'])) ?></dd></div>
                            <?php endif; ?>
                        </dl>

                        <?php if ($row['status'] === 'pending'): ?>
                            <form method="post" class="appointment-cancel-form"
                                action="/admin/agendamentos/<?= $e(rawurlencode((string) $row['id'])) ?>/status"
                                data-confirm="Tem certeza que deseja cancelar o agendamento de <?= $e($row['pet_name']) ?>? Esta ação não poderá ser desfeita.">
                                <?= \App\Security\Csrf::field() ?>
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="button danger">
                                    <i class="fa-solid fa-ban" aria-hidden="true"></i> Cancelar agendamento
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <p class="admin-appointments-empty" data-admin-appointments-empty <?= $rows ? 'hidden' : '' ?>>
            <?= ($search ?? '') !== '' || ($dateFilter ?? '') !== '' || ($statusFilter ?? '') !== ''
                ? 'Nenhum agendamento encontrado para os filtros selecionados.'
                : 'Nenhum agendamento cadastrado.' ?>
        </p>
        <?php $pagination = ['path' => '/admin/agendamentos', 'query' => ['q' => $search ?? '', 'date' => $dateFilter ?? '', 'status' => $statusFilter ?? ''], 'current' => $page ?? 1, 'pages' => $pages ?? 1, 'total' => $total ?? 0, 'per_page' => $perPage ?? 10, 'item_label' => 'agendamentos', 'item_label_singular' => 'agendamento', 'aria_label' => 'Paginação de agendamentos']; ?>
        <?php require dirname(__DIR__, 2) . '/partials/pagination.php'; ?>
    </section>

<?php elseif ($section === 'clientes'): ?>
    <section class="panel admin-clients-panel">
        <form method="get" action="/admin/clientes" class="admin-client-search" role="search">
            <label for="admin-client-q">Buscar clientes</label>
            <div><input id="admin-client-q" type="search" name="q" value="<?= $e($search ?? '') ?>" maxlength="100" placeholder="Buscar por nome, e-mail ou telefone..."><button class="button primary" type="submit">Buscar</button></div>
        </form>

        <?php if (!$clients): ?><div class="empty-state"><p><?= ($search ?? '') !== '' ? 'Nenhum cliente corresponde à busca.' : 'Nenhum cliente cadastrado.' ?></p></div>
        <?php else: ?><div class="admin-client-list">
            <?php foreach ($clients as $client): ?>
                <?php $statusLabel = match ($client['status']) { 'active' => 'Ativo', 'blocked' => 'Bloqueado', 'pending_verification' => 'Aguardando verificação', default => 'Inativo' }; ?>
                <?php $actionsId = 'admin-client-actions-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $client['id']); ?>
                <article class="admin-client-card" data-admin-client-card>
                    <div class="admin-client-summary" role="button" tabindex="0" aria-label="Mostrar ou ocultar ações do cliente <?= $e($client['name']) ?>" aria-expanded="false" aria-controls="<?= $e($actionsId) ?>" data-admin-client-toggle>
                        <div class="admin-client-field admin-client-name"><span>Nome</span><strong><?= $e($client['name']) ?></strong></div>
                        <div class="admin-client-field admin-client-email"><span>E-mail</span><span><?= $e($client['email']) ?></span></div>
                        <div class="admin-client-field admin-client-phone"><span>Telefone</span><span><?= $e($client['phone'] ?: 'Não informado') ?></span></div>
                        <div class="admin-client-field admin-client-pet-count"><span>Pets</span><strong><i class="fa-solid fa-paw" aria-hidden="true"></i> <?= (int) $client['pets_count'] ?></strong></div>
                        <div class="admin-client-field admin-client-status"><span>Status</span><span class="badge <?= $e(\App\Support\StatusBadge::class($client['status'])) ?>"><?= $e($statusLabel) ?></span></div>
                        <span class="admin-client-actions-toggle" aria-hidden="true"><i class="fa-solid fa-angle-down"></i></span>
                    </div>
                    <div class="admin-client-actions" id="<?= $e($actionsId) ?>" data-admin-client-actions hidden>
                        <a class="button secondary" href="/admin/clientes/<?= $e(rawurlencode((string) $client['id'])) ?>">Ver detalhes</a>
                        <a class="button ghost" href="/admin/clientes/<?= $e(rawurlencode((string) $client['id'])) ?>/editar">Editar</a>
                        <?php if ($client['status'] === 'active'): ?>
                            <form method="post" action="/admin/clientes/<?= $e(rawurlencode((string) $client['id'])) ?>/promover" data-admin-password-required data-password-title="Promover cliente" data-password-message="Confirme sua senha para alterar esta conta para funcionário.">
                                <?= \App\Security\Csrf::field() ?><input type="password" name="admin_password" hidden disabled data-admin-password-value>
                                <noscript><label>Senha do administrador<input type="password" name="admin_password" autocomplete="current-password" required></label></noscript>
                                <button class="button ghost" type="submit">Promover a funcionário</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="/admin/clientes/<?= $e(rawurlencode((string) $client['id'])) ?>/excluir" data-admin-password-required data-password-title="Excluir cliente" data-password-message="Esta exclusão não pode ser desfeita. Contas com histórico de agendamentos não podem ser excluídas.">
                            <?= \App\Security\Csrf::field() ?><input type="password" name="admin_password" hidden disabled data-admin-password-value>
                            <noscript><label>Senha do administrador<input type="password" name="admin_password" autocomplete="current-password" required></label></noscript>
                            <button class="button danger" type="submit">Excluir</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div><?php endif; ?>

        <?php $pagination = ['path' => '/admin/clientes', 'query' => ['q' => $search ?? ''], 'current' => $page ?? 1, 'pages' => $pages ?? 1, 'total' => $total ?? 0, 'per_page' => $perPage ?? 10, 'item_label' => 'clientes', 'item_label_singular' => 'cliente', 'aria_label' => 'Paginação de clientes']; ?>
        <?php require dirname(__DIR__, 2) . '/partials/pagination.php'; ?>
    </section>

<?php elseif ($section === 'funcionarios'): ?>
    <section class="panel admin-clients-panel">
        <form method="get" action="/admin/funcionarios" class="admin-client-search" role="search">
            <label for="admin-staff-q">Buscar funcionários</label>
            <div><input id="admin-staff-q" type="search" name="q" value="<?= $e($search ?? '') ?>" maxlength="100" placeholder="Buscar por nome, e-mail ou telefone..."><button class="button primary" type="submit">Buscar</button></div>
        </form>

        <?php if (!$employees): ?><div class="empty-state"><p><?= ($search ?? '') !== '' ? 'Nenhum funcionário corresponde à busca.' : 'Nenhum funcionário cadastrado.' ?></p></div>
        <?php else: ?><div class="admin-client-list">
            <?php foreach ($employees as $employee): ?>
                <?php $statusLabel = match ($employee['status']) { 'active' => 'Ativo', 'blocked' => 'Bloqueado', 'pending_verification' => 'Aguardando verificação', default => 'Inativo' }; ?>
                <?php $actionsId = 'admin-staff-actions-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $employee['id']); ?>
                <article class="admin-client-card admin-staff-card" data-admin-client-card>
                    <div class="admin-client-summary admin-staff-summary" role="button" tabindex="0" aria-label="Mostrar ou ocultar ações do funcionário <?= $e($employee['name']) ?>" aria-expanded="false" aria-controls="<?= $e($actionsId) ?>" data-admin-client-toggle>
                        <div class="admin-client-field admin-staff-name"><span>Nome</span><strong><?= $e($employee['name']) ?></strong></div>
                        <div class="admin-client-field admin-staff-email"><span>E-mail</span><span><?= $e($employee['email']) ?></span></div>
                        <div class="admin-client-field admin-staff-phone"><span>Telefone</span><span><?= $e($employee['phone'] ?: 'Não informado') ?></span></div>
                        <div class="admin-client-field admin-staff-status"><span>Status</span><span class="badge <?= $e(\App\Support\StatusBadge::class($employee['status'])) ?>"><?= $e($statusLabel) ?></span></div>
                        <span class="admin-client-actions-toggle" aria-hidden="true"><i class="fa-solid fa-angle-down"></i></span>
                    </div>
                    <div class="admin-client-actions" id="<?= $e($actionsId) ?>" data-admin-client-actions hidden>
                        <a class="button secondary" href="/admin/funcionarios/<?= $e(rawurlencode((string) $employee['id'])) ?>">Ver perfil</a>
                        <a class="button ghost" href="/admin/funcionarios/<?= $e(rawurlencode((string) $employee['id'])) ?>/editar">Editar</a>
                        <?php if ($employee['status'] === 'active'): ?>
                            <form method="post" action="/admin/funcionarios/<?= $e(rawurlencode((string) $employee['id'])) ?>/promover-admin" data-admin-password-required data-password-title="Promover funcionário a administrador" data-password-message="Confirme sua senha para conceder acesso administrativo a esta conta.">
                                <?= \App\Security\Csrf::field() ?><input type="password" name="admin_password" hidden disabled data-admin-password-value>
                                <noscript><label>Senha do administrador<input type="password" name="admin_password" autocomplete="current-password" required></label></noscript>
                                <button class="button ghost" type="submit">Promover a administrador</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="/admin/funcionarios/<?= $e(rawurlencode((string) $employee['id'])) ?>/rebaixar-cliente" data-admin-password-required data-password-title="Rebaixar funcionário para cliente" data-password-message="Confirme sua senha. A ação será bloqueada se houver atendimentos pendentes ou em andamento para este funcionário.">
                            <?= \App\Security\Csrf::field() ?><input type="password" name="admin_password" hidden disabled data-admin-password-value>
                            <noscript><label>Senha do administrador<input type="password" name="admin_password" autocomplete="current-password" required></label></noscript>
                            <button class="button danger" type="submit"><i class="fa-solid fa-user-minus" aria-hidden="true"></i> Rebaixar para cliente</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div><?php endif; ?>

        <?php $pagination = ['path' => '/admin/funcionarios', 'query' => ['q' => $search ?? ''], 'current' => $page ?? 1, 'pages' => $pages ?? 1, 'total' => $total ?? 0, 'per_page' => $perPage ?? 10, 'item_label' => 'funcionários', 'item_label_singular' => 'funcionário', 'aria_label' => 'Paginação de funcionários']; ?>
        <?php require dirname(__DIR__, 2) . '/partials/pagination.php'; ?>
    </section>
    <?php if (is_string($demotionModalMessage ?? null) && $demotionModalMessage !== ''): ?>
        <dialog class="admin-password-dialog" data-staff-demotion-dialog aria-labelledby="staff-demotion-title" aria-describedby="staff-demotion-message" role="alertdialog">
            <form method="dialog" class="admin-password-dialog__surface">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                <h2 id="staff-demotion-title">Não foi possível rebaixar o funcionário</h2>
                <p id="staff-demotion-message"><?= $e($demotionModalMessage) ?></p>
                <div class="admin-password-dialog__actions">
                    <button class="button primary" type="submit" autofocus>Entendi</button>
                </div>
            </form>
        </dialog>
    <?php endif; ?>

<?php elseif ($section === 'servicos'): ?>
    <section class="panel admin-services-panel">
        <form method="get" action="/admin/servicos" class="admin-service-filters" role="search">
            <label for="admin-service-q">Buscar serviços</label>
            <div class="admin-service-filter-controls">
                <input id="admin-service-q" type="search" name="q" value="<?= $e($search ?? '') ?>" maxlength="100" placeholder="Buscar por nome ou descrição...">
                <div class="admin-service-status-filter">
                    <label for="admin-service-status">Status</label>
                    <select id="admin-service-status" name="status">
                        <option value="" <?= ($statusFilter ?? '') === '' ? 'selected' : '' ?>>Todos os status</option>
                        <option value="active" <?= ($statusFilter ?? '') === 'active' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inactive" <?= ($statusFilter ?? '') === 'inactive' ? 'selected' : '' ?>>Desativado</option>
                    </select>
                </div>
                <button class="button primary" type="submit">Buscar</button>
            </div>
        </form>

        <?php if (!$services): ?><div class="empty-state"><p><?= ($search ?? '') !== '' || ($statusFilter ?? '') !== '' ? 'Nenhum serviço corresponde aos filtros.' : 'Nenhum serviço cadastrado.' ?></p></div>
        <?php else: ?><div class="admin-service-list">
            <?php foreach ($services as $service): ?>
                <?php $isActive = $service['active'] === true || in_array(strtolower((string) $service['active']), ['1', 't', 'true', 'yes', 'on'], true); ?>
                <?php $nextStatus = $isActive ? 'inactive' : 'active'; ?>
                <article class="admin-service-card">
                    <div class="admin-service-heading">
                        <h2><?= $e($service['name']) ?></h2>
                        <span class="badge <?= $isActive ? 'status-success' : 'status-muted' ?>"><?= $isActive ? 'Ativo' : 'Desativado' ?></span>
                    </div>
                    <p class="admin-service-description"><?= $e($service['description']) ?></p>
                    <dl class="admin-service-facts">
                        <div><dt>Duração</dt><dd><?= $e($formatDuration((int) $service['duration_minutes'])) ?></dd></div>
                        <div><dt>Valor</dt><dd><?= $e(\App\Support\PetHelper::formatPrice($service['price'])) ?></dd></div>
                        <div><dt>Agendamentos</dt><dd><?= (int) $service['appointment_count'] ?></dd></div>
                    </dl>
                    <footer class="admin-service-actions">
                        <a class="button secondary admin-service-edit" href="/admin/servicos/<?= $e(rawurlencode((string) $service['id'])) ?>/editar"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Editar</a>
                        <form method="post" action="/admin/servicos/<?= $e(rawurlencode((string) $service['id'])) ?>/status" data-admin-password-required data-password-title="<?= $isActive ? 'Desativar serviço' : 'Ativar serviço' ?>" data-password-message="Confirme sua senha de administrador para <?= $isActive ? 'desativar' : 'ativar' ?> este serviço.">
                            <?= \App\Security\Csrf::field() ?>
                            <input type="hidden" name="status" value="<?= $e($nextStatus) ?>">
                            <input type="password" name="admin_password" hidden disabled data-admin-password-value>
                            <noscript><label>Senha do administrador<input type="password" name="admin_password" autocomplete="current-password" required></label></noscript>
                            <button class="button <?= $isActive ? 'danger' : 'secondary' ?>" type="submit"><i class="fa-solid <?= $isActive ? 'fa-ban' : 'fa-circle-check' ?>" aria-hidden="true"></i> <?= $isActive ? 'Desativar' : 'Ativar' ?></button>
                        </form>
                    </footer>
                </article>
            <?php endforeach; ?>
        </div><?php endif; ?>

        <?php $pagination = ['path' => '/admin/servicos', 'query' => ['q' => $search ?? '', 'status' => $statusFilter ?? ''], 'current' => $page ?? 1, 'pages' => $pages ?? 1, 'total' => $total ?? 0, 'per_page' => $perPage ?? 6, 'item_label' => 'serviços', 'item_label_singular' => 'serviço', 'aria_label' => 'Paginação de serviços']; ?>
        <?php require dirname(__DIR__, 2) . '/partials/pagination.php'; ?>
    </section>

<?php elseif ($section === 'pets'): ?>
    <?php require dirname(__DIR__) . '/staff/staff-pets.php'; ?>

<?php else: ?>
    <section class="panel">
        <div class="filters">
            <input type="search" placeholder="Filtrar nesta página..." data-table-filter>
            <select data-status-filter>
                <option value="">Todos os status</option>
                <option value="active">Ativo</option>
                <option value="blocked">Bloqueado</option>
                <option value="pending">Pendente</option>
            </select>
        </div>

        <div class="table-wrap">
            <table data-filter-table>
                <thead>
                    <tr>
                        <th><?= strtoupper($e($singular ?? 'ITEM')) ?></th>
                        <th>CONTATO / DETALHE</th>
                        <th>VÍNCULOS</th>
                        <th>STATUS</th>
                        <th>AÇÕES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr data-status="<?= $e($row['status']) ?>">
                            <td><strong><?= $e($row['label']) ?></strong></td>
                            <td><?= $e($row['detail']) ?></td>
                            <td><?= $e($row['links']) ?></td>
                            <td>
                                <span class="badge <?= \App\Support\StatusBadge::class($row['status']) ?>">
                                    <?= $e($section === 'agendamentos'
                                        ? \App\Support\AppointmentStatus::label($row['status'])
                                        : $row['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (in_array($section, ['clientes', 'funcionarios'], true)): ?>
                                    <a class="button ghost"
                                        href="/admin/<?= $e($section) ?>/<?= $e($row['id']) ?>/editar">Editar</a>
                                    <form method="post" action="/admin/<?= $e($section) ?>/<?= $e($row['id']) ?>/status">
                                        <?= \App\Security\Csrf::field() ?>
                                        <input type="hidden" name="status"
                                            value="<?= $row['status'] === 'active' ? 'blocked' : 'active' ?>">
                                        <button
                                            class="button ghost"><?= $row['status'] === 'active' ? 'Bloquear' : 'Ativar' ?></button>
                                    </form>

                                <?php elseif ($section === 'pets'): ?>
                                    <a class="button ghost" href="/admin/pets/<?= $e($row['id']) ?>/editar">Editar</a>

                                <?php elseif ($section === 'agendamentos'): ?>
                                    <form method="post" action="/admin/agendamentos/<?= $e($row['id']) ?>/status">
                                        <?= \App\Security\Csrf::field() ?>
                                        <select name="status" aria-label="Novo status">
                                            <?php foreach (\App\Support\AppointmentStatus::optionsFor($row['status']) as $value => $label): ?>
                                                <option value="<?= $e($value) ?>" <?= $row['status'] === $value ? 'selected' : '' ?>><?= $e($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="button ghost">Salvar</button>
                                    </form>

                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (!$rows): ?>
            <div class="empty-state">
                <p>Nenhum registro encontrado.</p>
            </div>
        <?php endif; ?>

        <footer class="pagination">
            <span>Mostrando <?= count($rows) ?> registros</span>
        </footer>
    </section>
<?php endif; ?>

<?php if ($section === 'agendamentos'): ?>
    <script src="/assets/js/admin-appointments.js?v=<?= hash_file('sha256', dirname(__DIR__, 4) . '/public/assets/js/admin-appointments.js') ?>" defer></script>
<?php endif; ?>
