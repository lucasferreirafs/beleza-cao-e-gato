<?php
$e = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$getServiceIcon = static function (string $name): string {
    $lower = mb_strtolower($name, 'UTF-8');
    if (str_contains($lower, 'banho') && !str_contains($lower, 'tosa')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>';
    }
    if (str_contains($lower, 'tosa') && !str_contains($lower, 'banho')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/></svg>';
    }
    if (str_contains($lower, 'banho') && str_contains($lower, 'tosa')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>';
    }
    if (str_contains($lower, 'unha')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="4" r="2"/><circle cx="18" cy="8" r="2"/><circle cx="4" cy="8" r="2"/><path d="M12 10c-3.3 0-6 2.7-6 6 0 2.2 1.8 4 4 4h4c2.2 0 4-1.8 4-4 0-3.3-2.7-6-6-6z"/></svg>';
    }
    if (str_contains($lower, 'ouvido')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>';
    }
    if (str_contains($lower, 'dente') || str_contains($lower, 'escova')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';
    }
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>';
};
?>

<header class="page-heading">
    <div>
        <h1>Serviços Disponíveis</h1>
        <p>Selecione o serviço ideal e agende uma sessão para seu melhor amigo.</p>
    </div>
</header>

<div class="services-catalog-grid">
    <?php foreach ($services as $service): ?>
        <article class="service-catalog-card">
            <header class="service-catalog-header">
                <span class="service-catalog-icon"><?= $getServiceIcon($service['name']) ?></span>
                <span class="service-catalog-badge">Disponivel</span>
            </header>

            <h2 class="service-catalog-title"><?= $e($service['name']) ?></h2>
            <p class="service-catalog-desc"><?= $e($service['description']) ?></p>

            <div class="service-catalog-meta">
                <strong class="service-catalog-price">R$ <?= number_format((float) $service['price'], 2, ',', '.') ?></strong>
                <span class="service-catalog-duration">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <?= (int) $service['duration_minutes'] ?> min
                </span>
            </div>

            <a class="service-catalog-btn" href="/cliente/agendamento/1">Agendar Serviço</a>
        </article>
    <?php endforeach; ?>
</div>
