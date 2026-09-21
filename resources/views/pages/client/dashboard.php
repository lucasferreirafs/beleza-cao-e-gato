<?php
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$photos = ['Luna' => 'pet-luna.png', 'Mel' => 'pet-mel.png', 'Toddy' => 'pet-toddy.png'];
$firstName = explode(' ', trim((string) ($user['name'] ?? 'Cliente')))[0];
?>

<header class="page-heading">
    <div>
        <h1>Olá, <?= $e($firstName) ?> 👋</h1>
        <p>Veja o que está acontecendo com seus pets hoje.</p>
    </div>
</header>

<section class="metric-grid three">
    <article class="metric-card featured">
        <div>
            <small>PRÓXIMO AGENDAMENTO</small>
            <strong>
                <?= $upcoming ? date('d/m \à\s H:i', strtotime($upcoming['scheduled_at'])) : 'Nenhum agendado' ?>
            </strong>

            <p>
                <?= $upcoming ? $e($upcoming['pet_name'] . ' • ' . $upcoming['service_name']) : 'Escolha um serviço e reserve um horário' ?>
            </p>
        </div>

        <span>
            <i class="fa-regular fa-calendar"></i>
        </span>
    </article>

    <article class="metric-card">
        <div>
            <small>TOTAL DE PETS</small>
            <strong>
                <?= count($pets) ?>
                <?= count($pets) === 1 ? 'Pet' : 'Pets' ?>
            </strong>
            <p>
                <?= $pets ? $e(implode(', ', array_column($pets, 'name'))) : 'Cadastre seu primeiro pet' ?>
            </p>
        </div>

        <span>
            <i class="fa-solid fa-dog"></i>
        </span>
    </article>

    <article class="metric-card">
        <div>
            <small>ATENDIMENTOS REALIZADOS</small>
            <strong>
                <?= count($history) ?> Atendimentos
            </strong>
            <p>
                <?= $history ? 'Último em ' . date('d/m/Y', strtotime($history[0]['scheduled_at'])) : 'Sem histórico concluído' ?>
            </p>
        </div>

        <span>
            <i class="fa-solid fa-users-line"></i>
        </span>
    </article>
</section>

<div class="content-grid">
    <section>
        <article class="panel appointment-highlight">
            <header>
                <h2>Próximo Agendamento</h2>
                <?php if ($upcoming): ?>
                    <span class="badge <?= \App\Support\StatusBadge::class($upcoming['status']) ?>"><?= $e(\App\Support\AppointmentStatus::label($upcoming['status'])) ?></span>
                <?php endif; ?>
            </header>

            <?php if ($upcoming): ?>
                <div class="appointment-info">
                    <img src="/assets/images/<?= $e($photos[$upcoming['pet_name']] ?? 'pet-luna.png') ?>"
                        alt="<?= $e($upcoming['pet_name']) ?>">

                    <dl>
                        <div>
                            <dt>Pet</dt>
                            <dd><?= $e($upcoming['pet_name']) ?></dd>
                        </div>
                        <div>
                            <dt>Serviço</dt>
                            <dd><?= $e($upcoming['service_name']) ?></dd>
                        </div>
                        <div>
                            <dt>Data e Horário</dt>
                            <dd><?= date('d/m/Y \à\s H:i', strtotime($upcoming['scheduled_at'])) ?></dd>
                        </div>
                        <div>
                            <dt>Profissional</dt>
                            <dd><?= $e($upcoming['staff_name'] ?? 'A definir') ?></dd>
                        </div>
                    </dl>
                </div>

                <footer>
                    <a class="button primary" href="/cliente/agendamentos">Ver Detalhes</a>
                    <form method="post" action="/cliente/agendamentos/<?= (int) $upcoming['id'] ?>/cancelar"
                        data-confirm="Cancelar este agendamento?">
                        <?= \App\Security\Csrf::field() ?>
                        <button class="button ghost danger">Cancelar</button>
                    </form>
                </footer>
            <?php else: ?>
                <div class="empty-state">
                    <p>Nenhum agendamento futuro.</p>
                    <a class="button primary" href="/cliente/agendamento/1">Agendar agora</a>
                </div>
            <?php endif; ?>
        </article>

        <article class="panel">
            <h2>Histórico Recente de Agendamentos</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Pet</th>
                            <th>Serviço</th>
                            <th>Data</th>
                            <th>Horário</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($history, 0, 5) as $item): ?>
                            <tr>
                                <td><?= $e($item['pet_name']) ?></td>
                                <td><?= $e($item['service_name']) ?></td>
                                <td><?= date('d/m/Y', strtotime($item['scheduled_at'])) ?></td>
                                <td><?= date('H:i', strtotime($item['scheduled_at'])) ?></td>
                                <td><span class="badge <?= \App\Support\StatusBadge::class($item['status']) ?>"><?= $e(\App\Support\AppointmentStatus::label($item['status'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!$history): ?>
                <div class="empty-state">
                    <p>O histórico aparecerá após o primeiro atendimento concluído.</p>
                </div>
            <?php endif; ?>
        </article>
    </section>

    <aside class="panel pets-mini">
        <header>
            <h2>Meus Pets</h2>
            <a href="/cliente/pets">Ver Todos</a>
        </header>

        <?php foreach (array_slice($pets, 0, 4) as $pet): ?>
            <div class="pet-row">
                <img src="/assets/images/<?= $e($photos[$pet['name']] ?? 'pet-luna.png') ?>" alt="<?= $e($pet['name']) ?>">

                <span>
                    <strong>
                        <?= $e($pet['name']) ?>
                        <?php if ($e($pet['sex']) === 'Macho'): ?>
                            <i class="fa-solid fa-venus pet-macho"></i>
                        <?php else: ?>
                            <i class="fa-solid fa-mars pet-femea"></i>
                        <?php endif; ?>
                    </strong>

                    <small><?= $e($pet['species']) ?> • <?= $e($pet['breed']) ?></small>
                </span>

                <a href="/cliente/pets/<?= (int) $pet['id'] ?>" aria-label="Ver <?= $e($pet['name']) ?>">
                    <i class="fa-solid fa-eye"></i>
                </a>
            </div>
        <?php endforeach; ?>

        <?php if (!$pets): ?>
            <div class="empty-state">
                <a class="button primary" href="/cliente/pets/novo">Cadastrar pet</a>
            </div>
        <?php endif; ?>
    </aside>
</div>
