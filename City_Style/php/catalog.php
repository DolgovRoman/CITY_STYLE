<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/products.php';

$pageTitle = 'Каталог — City Style';
require __DIR__ . '/includes/header.php';

$pdo = db();
$filters = catalog_filters_from_request();
$groups = fetch_catalog_groups($pdo, $filters['kategoriya'] > 0 ? $filters['kategoriya'] : null, null, $filters);
$kategorii = fetch_kategorii($pdo);
$razmera = fetch_razmer($pdo);
$tsveta = fetch_tsveta($pdo);

$queryBase = [];
foreach (['q', 'kategoriya', 'razmer', 'tsvet'] as $key) {
    if (!empty($filters[$key])) {
        $queryBase[$key] = (string) $filters[$key];
    }
}
?>

<main class="container">
    <section class="page-title">
        <h1>Каталог</h1>
    </section>
    <section class="section two-col">
        <aside class="panel filters">
            <h3>Поиск и фильтры</h3>
            <form method="get" action="catalog.php" class="filter-form">
                <label class="filter-label">Поиск
                    <input type="search" name="q" value="<?= h($filters['q']) ?>" placeholder="Название или артикул">
                </label>
                <label class="filter-label">Категория
                    <select name="kategoriya">
                        <option value="">Все</option>
                        <?php foreach ($kategorii as $k): ?>
                            <option value="<?= (int) $k['id_kategoriya'] ?>" <?= $filters['kategoriya'] === (int) $k['id_kategoriya'] ? 'selected' : '' ?>><?= h($k['nazvanie']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="filter-label">Размер
                    <select name="razmer">
                        <option value="">Любой</option>
                        <?php foreach ($razmera as $r): ?>
                            <option value="<?= (int) $r['id_razmer'] ?>" <?= $filters['razmer'] === (int) $r['id_razmer'] ? 'selected' : '' ?>><?= h($r['nazvanie']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="filter-label">Цвет
                    <select name="tsvet">
                        <option value="">Любой</option>
                        <?php foreach ($tsveta as $c): ?>
                            <option value="<?= (int) $c['id_tcvet'] ?>" <?= $filters['tsvet'] === (int) $c['id_tcvet'] ? 'selected' : '' ?>><?= h($c['nazvanie']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="filter-actions">
                    <button class="btn btn-primary" type="submit">Применить</button>
                    <a class="btn btn-ghost" href="catalog.php">Сбросить</a>
                </div>
            </form>
        </aside>
        <div>
            <div class="product-grid-3">
                <?php foreach ($groups as $g): ?>
                    <?php
                    $img = product_image_url($g['izobrazhenie'] ?? null);
                    $pid = (int) $g['id_tovar'];
                    $minP = (float) $g['min_tsena'];
                    $maxP = (float) $g['max_tsena'];
                    $priceLabel = $minP === $maxP ? format_price($minP) : ('от ' . format_price($minP));
                    ?>
                    <?php
                    $backParam = $queryBase ? ('catalog.php?' . http_build_query($queryBase)) : 'catalog.php';
                    $productHref = 'product.php?id=' . $pid . '&back=' . rawurlencode($backParam);
                    ?>
                    <article class="product-card">
                        <div class="media-frame media-frame--card<?= $img ? '' : ' is-empty' ?>">
                            <?php if ($img): ?>
                                <img class="media-frame__img product-card-img" src="<?= h($img) ?>" alt="<?= h($g['nazvanie']) ?>">
                            <?php else: ?>
                                <img class="media-frame__img product-card-img" alt="" hidden>
                            <?php endif; ?>
                            <div class="media-frame__empty">Нет фото</div>
                        </div>
                        <h3><?= h($g['nazvanie']) ?></h3>
                        <p><?= h($priceLabel) ?></p>
                        <?php if (!empty($g['kategoriya_nazv'])): ?>
                            <p class="product-card-meta"><?= h($g['kategoriya_nazv']) ?></p>
                        <?php endif; ?>
                        <a class="btn btn-small btn-primary" href="<?= h($productHref) ?>">Подробнее</a>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if (!$groups): ?>
                <p class="catalog-empty">По выбранным условиям товаров нет. <a class="btn btn-ghost btn-small" href="catalog.php">Сбросить фильтры</a></p>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
