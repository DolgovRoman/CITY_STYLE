<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/staff_auth.php';
require __DIR__ . '/../../includes/staff_filters.php';

require_staff();
require_staff_kind('employee');

$pdo = db();
$filters = staff_list_filters_from_request();
$params = [];
$where = staff_sql_orders_where($filters, $params);
$hasDost = delivery_table_exists($pdo);

$sql = 'SELECT z.*, k.familiya, k.imya, k.email' . client_phone_select_sql($pdo);
if ($hasDost) {
    $sql .= ', d.id_dostavka, d.status AS status_dostavki, kur.familiya AS kur_fam, kur.imya AS kur_imya';
}
$sql .= ' FROM STYLE_zakazy z
     INNER JOIN STYLE_klienti k ON z.klient = k.id_klient';
if ($hasDost) {
    $sql .= ' LEFT JOIN STYLE_dostavka d ON d.id_zakaz = z.id_zakaz
              LEFT JOIN STYLE_sotrudniki kur ON d.id_sotrudnik_kurier = kur.id_sotrudnik';
}
$sql .= ' WHERE 1=1' . $where . ' ORDER BY z.data_sozdaniya DESC, z.id_zakaz DESC LIMIT 200';

$st = $pdo->prepare($sql);
$st->execute($params);
$list = $st->fetchAll();

$pageTitle = 'Заказы — сотрудник';
$staffHome = '../';
require __DIR__ . '/../../includes/layout_staff_top.php';
require __DIR__ . '/../_nav.php';
?>

<main class="staff-main">
    <h1>Обработка заказов</h1>
    <?php staff_render_filter_panel('orders.php', $filters, 'orders'); ?>
    <div class="staff-table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>№</th>
                <th>Дата</th>
                <th>Клиент</th>
                <th>Статус</th>
                <th>Доставка</th>
                <?php if ($hasDost): ?><th>Курьер</th><?php endif; ?>
                <th>Сумма</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($list as $z): ?>
                <tr>
                    <td><?= (int) $z['id_zakaz'] ?></td>
                    <td><?= h((string) ($z['data_sozdaniya'] ?? '')) ?></td>
                    <td><?= h(trim(($z['familiya'] ?? '') . ' ' . ($z['imya'] ?? ''))) ?></td>
                    <td><?= h((string) ($z['status'] ?? '')) ?></td>
                    <td><?= h(delivery_tip_label((string) ($z['tip_dostavki'] ?? ''))) ?></td>
                    <?php if ($hasDost): ?>
                        <td><?= !empty($z['kur_fam']) ? h(trim(($z['kur_fam'] ?? '') . ' ' . ($z['kur_imya'] ?? ''))) : '—' ?></td>
                    <?php endif; ?>
                    <td><?= h(format_price((float) ($z['itogovaya_summa'] ?? 0))) ?></td>
                    <td><a class="btn btn-ghost btn-small" href="order.php?id=<?= (int) $z['id_zakaz'] ?>">Открыть</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php if (!$list): ?>
        <p>Заказы не найдены. <a class="btn btn-ghost btn-small" href="orders.php">Сбросить фильтры</a></p>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../../includes/layout_staff_bottom.php'; ?>
