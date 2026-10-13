<?php
$config = is_array($pagination ?? null) ? $pagination : [];
$escape = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$basePath = (string) ($config['path'] ?? '');
$query = is_array($config['query'] ?? null) ? $config['query'] : [];
$page = max(1, (int) ($config['current'] ?? 1));
$pages = max(1, (int) ($config['pages'] ?? 1));
$total = max(0, (int) ($config['total'] ?? 0));
$perPage = max(1, (int) ($config['per_page'] ?? 10));
$itemLabel = (string) ($config['item_label'] ?? 'registros');
$itemLabelSingular = (string) ($config['item_label_singular'] ?? rtrim($itemLabel, 's'));
$ariaLabel = (string) ($config['aria_label'] ?? 'Paginação');
$page = min($page, $pages);
$start = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
$end = min($total, $start + $perPage - 1);
$rangeLabel = $start === $end ? (string) $start : $start . '–' . $end;
$pageUrl = static function (int $number) use ($basePath, $query): string {
    $params = array_filter($query, static fn(mixed $value): bool => $value !== null && $value !== '');
    $params['page'] = $number;
    return $basePath . '?' . http_build_query($params);
};
$visiblePages = array_values(array_unique(array_filter([1, $page - 1, $page, $page + 1, $pages], static fn(int $number): bool => $number >= 1 && $number <= $pages)));
sort($visiblePages);
$pageItems = [];
$previousPage = null;
foreach ($visiblePages as $visiblePage) {
    if ($previousPage !== null && $visiblePage - $previousPage > 1) $pageItems[] = null;
    $pageItems[] = $visiblePage;
    $previousPage = $visiblePage;
}
?>
<div class="pagination-component" data-pagination-component>
    <p class="pagination-component__summary" role="status" aria-live="polite" aria-atomic="true">
        <?= $total > 0 ? 'Mostrando ' . $rangeLabel . ' de ' . $total . ' ' . $escape($total === 1 ? $itemLabelSingular : $itemLabel) : 'Nenhum registro encontrado' ?>
    </p>
    <?php if ($total > 0): ?>
    <nav class="pagination-component__nav" aria-label="<?= $escape($ariaLabel) ?>">
        <span class="pagination-component__controls">
            <?php if ($page > 1): ?>
                <a class="pagination-component__step" href="<?= $escape($pageUrl($page - 1)) ?>" rel="prev" aria-label="Página anterior"><i class="fa-solid fa-angle-left" aria-hidden="true"></i><span>Anterior</span></a>
            <?php else: ?>
                <span class="pagination-component__step is-disabled" aria-disabled="true"><i class="fa-solid fa-angle-left" aria-hidden="true"></i><span>Anterior</span></span>
            <?php endif; ?>

            <?php foreach ($pageItems as $item): ?>
                <?php if ($item === null): ?>
                    <span class="pagination-component__ellipsis" aria-hidden="true">…</span>
                <?php elseif ($item === $page): ?>
                    <span class="pagination-component__number" aria-current="page" aria-label="Página <?= $item ?>"><?= $item ?></span>
                <?php else: ?>
                    <a class="pagination-component__number" href="<?= $escape($pageUrl($item)) ?>" aria-label="Página <?= $item ?>"><?= $item ?></a>
                <?php endif; ?>
            <?php endforeach; ?>

            <span class="pagination-component__page-indicator" role="status" aria-label="Página <?= $page ?> de <?= $pages ?>"><?= $page ?> / <?= $pages ?></span>

            <?php if ($page < $pages): ?>
                <a class="pagination-component__step" href="<?= $escape($pageUrl($page + 1)) ?>" rel="next" aria-label="Próxima página"><span>Próxima</span><i class="fa-solid fa-angle-right" aria-hidden="true"></i></a>
            <?php else: ?>
                <span class="pagination-component__step is-disabled" aria-disabled="true"><span>Próxima</span><i class="fa-solid fa-angle-right" aria-hidden="true"></i></span>
            <?php endif; ?>
        </span>
    </nav>
    <?php endif; ?>
</div>
