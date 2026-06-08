<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/staff_auth.php';
require __DIR__ . '/../../includes/products.php';

require_staff();
require_staff_kind('employee');

$pdo = db();
$filters = catalog_filters_from_request();
$list = fetch_staff_products($pdo, $filters);
$kategorii = fetch_kategorii($pdo);
$razmera = fetch_razmer($pdo);
$tsveta = fetch_tsveta($pdo);
$hasStock = stock_enabled($pdo);

$pageTitle = 'Товары — сотрудник';
$staffHome = '../';
require __DIR__ . '/../../includes/layout_staff_top.php';
require __DIR__ . '/../_nav.php';
?>

<main class="staff-main">
    <h1>Управление товарами</h1>
    <div class="staff-toolbar">
        <a class="btn btn-primary" href="product_edit.php">Добавить товар</a>
    </div>

    <div class="panel filter-form staff-filter-panel">
        <h3>Поиск и фильтры</h3>
        <form method="get" action="products.php" class="staff-filter-grid">
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
                <button class="btn btn-primary" type="submit">Найти</button>
                <a class="btn btn-ghost" href="products.php">Сбросить</a>
            </div>
        </form>
    </div>

    <div class="staff-table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Артикул</th>
                <th>Цена</th>
                <th>Категория</th>
                <th>Цвет / размер</th>
                <?php if ($hasStock): ?><th>Остаток</th><?php endif; ?>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($list as $t): ?>
                <?php $ost = (int) ($t['ostatok'] ?? 0); ?>
                <tr>
                    <td><?= (int) $t['id_tovar'] ?></td>
                    <td><?= h($t['nazvanie']) ?></td>
                    <td><?= h((string) ($t['articul'] ?? '')) ?></td>
                    <td><?= h(format_price($t['tsena'])) ?></td>
                    <td><?= h((string) ($t['kat'] ?? '')) ?></td>
                    <td><?= h((string) ($t['cvet'] ?? '')) ?> / <?= h((string) ($t['razm'] ?? '')) ?></td>
                    <?php if ($hasStock): ?>
                        <td><?php if ($ost > 0): ?><?= $ost ?><?php else: ?><span class="stock-zero">0</span><?php endif; ?></td>
                    <?php endif; ?>
                    <td>
                        <a class="btn btn-ghost btn-small" href="product_edit.php?id=<?= (int) $t['id_tovar'] ?>">Изменить</a>
                        <form method="post" action="product_delete.php" style="display:inline" onsubmit="return confirm('Удалить SKU?');">
                            <input type="hidden" name="id_tovar" value="<?= (int) $t['id_tovar'] ?>">
                            <button type="submit" class="btn btn-ghost" style="padding:4px 8px;font-size:12px">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php if (!$list): ?>
        <p>Товары не найдены. <a class="btn btn-ghost btn-small" href="products.php">Сбросить фильтры</a></p>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../../includes/layout_staff_bottom.php'; ?>
