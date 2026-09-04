<?php
$pets = [
    ['Luna', 'Golden Retriever', '3 anos', 'pet-luna.png'],
    ['Mel', 'Siamês', '1 ano', 'pet-mel.png'],
    ['Toddy', 'Shih Tzu', '2 anos', 'pet-toddy.png']
];

?>

<header class="page-heading">
    <div>
        <h1>Olá, Maria 👋</h1>
        <p>Veja o que está acontecendo com seus pets hoje.</p>
    </div>
</header>

<section class="metric-grid three">
    <article class="metric-card featured">
        <div>
            <small>PRÓXIMO AGENDAMENTO</small>
            <strong>25/08 às 14:00</strong>
            <p>Luna • Banho + Tosa</p>
        </div>

        <span>
            <i class="fa-regular fa-calendar"></i>
        </span>
    </article>

    <article class="metric-card clear">
        <div>
            <small>TOTAL DE PETS</small>
            <strong>3 Pets</strong>
            <p>Luna, Mel e Toddy</p>
        </div>

        <span>
            <i class="fa-solid fa-paw"></i>
        </span>
    </article>

    <article class="metric-card clear">
        <div>
            <small>ATENDIMENTOS REALIZADOS</small>
            <strong>12 Atendimentos</strong>
            <p>Último em 10/07/2026</p>
        </div>

        <span>
            <i class="fa-solid fa-arrow-trend-up"></i>
        </span>
    </article>
</section>

<div class="content-grid">
    <section>
        <article class="panel appointment-highlight">

            <header>
                <h2>Destaque: Próximo Agendamento</h2>
                <span class="badge success">✓ Confirmado</span>
            </header>

            <div class="appointment-info"><img src="/assets/images/pet-luna.png" alt="Luna">
                <dl>
                    <div>
                        <dt>Pet</dt>
                        <dd>Luna</dd>
                    </div>

                    <div>
                        <dt>Serviço</dt>
                        <dd>Banho + Tosa Premium</dd>
                    </div>

                    <div>
                        <dt>Data e Horário</dt>
                        <dd>25/08/2026 às 14:00</dd>
                    </div>

                    <div>
                        <dt>Profissional</dt>
                        <dd>João Silva</dd>
                    </div>
                </dl>
            </div>

            <footer>
                <button class="button primary">Ver Detalhes</button>
                <button class="button secondary">Reagendar</button>
                <button class="button ghost danger">Cancelar</button>
            </footer>
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
                        <tr>
                            <td>Mel</td>
                            <td>Consulta Veterinária</td>
                            <td>10/07/2026</td>
                            <td>10:30</td>
                            <td><span class="badge success">Realizado</span></td>
                        </tr>
                        <tr>
                            <td>Luna</td>
                            <td>Banho Higiênico</td>
                            <td>28/06/2026</td>
                            <td>16:00</td>
                            <td><span class="badge success">Realizado</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <aside class="panel">
        <header>
            <h2>Meus Pets</h2>
            <a href="/cliente/pets">Ver Todos</a>
        </header>

        <?php foreach ($pets as $pet): ?>
            <div class="pet-row">
                <img src="/assets/images/<?= $pet[3] ?>" alt="<?= $pet[0] ?>">

                <span>
                    <strong><?= $pet[0] ?></strong>
                    <small>Cachorro • <?= $pet[1] ?><br><?= $pet[2] ?></small>
                </span>

                <a href="/cliente/pets" aria-label="Ver <?= $pet[0] ?>">◉</a>
            </div>
        <?php endforeach; ?>
    </aside>
</div>