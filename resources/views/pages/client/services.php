<?php
$e = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$icons = ['◌', '✂', '☆', '♢', '◉', '◇'];
?>

<header class="page-heading">
    <div>
        <h1>Serviços Disponíveis</h1>
        <p>Selecione o serviço ideal e agende uma sessão para seu melhor amigo.</p>
    </div>
</header>

<div class="stack">
    <?php foreach ($services as $index => $service): ?>
        <article class="panel service-card">
            <header>
                <span class="service-icon"><?= $icons[$index % count($icons)] ?></span>
                <span class="badge success">Disponível</span>
            </header>

            <h2><?= $e($service['name']) ?></h2>
            <p><?= $e($service['description']) ?></p>

            <div class="service-meta">
                <strong>R$ <?= number_format((float) $service['price'], 2, ',', '.') ?></strong>
                <span>◷ <?= (int) $service['duration_minutes'] ?> min</span>
            </div>

            <a class="button teal wide" href="/cliente/agendamento/1">Agendar Serviço</a>
        </article>
    <?php endforeach; ?>
</div>
