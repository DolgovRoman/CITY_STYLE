<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/staff_auth.php';
require __DIR__ . '/../../includes/staff_filters.php';

require_staff();
require_staff_kind('courier');

$pdo = db();
$sid = (int) (current_user()['id'] ?? 0);
$filters = staff_list_filters_from_request();
$params = [$sid];
$where = staff_sql_deliveries_where($filters, $params);

$sql = 'SELECT d.id_dostavka, d.id_zakaz, d.status AS status_dostavki, d.id_sotrudnik_kurier,
        z.status AS status_zakaza, z.tip_dostavki, z.adres, z.gorod, z.itogovaya_summa, z.data_sozdaniya,
        k.familiya, k.imya, k.email
        FROM STYLE_dostavka d
        INNER JOIN STYLE_zakazy z ON d.id_zakaz = z.id_zakaz
        INNER JOIN STYLE_klienti k ON z.klient = k.id_klient
        WHERE d.id_sotrudnik_kurier = ?' . $where . '
        ORDER BY d.id_dostavka DESC';

$rows = [];
try {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();
} catch (Throwable $e) {
    $rows = [];
}

$pageTitle = 'Мои доставки — City Style';
$staffHome = '../';
require __DIR__ . '/../../includes/layout_staff_top.php';
require __DIR__ . '/../_nav.php';
?>

<main class="staff-main">
    <h1>Мои доставки</h1>
    <?php staff_render_filter_panel('deliveries.php', $filters, 'deliveries'); ?>
    <div class="staff-table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>№ доставки</th>
                <th>Заказ</th>
                <th>Клиент</th>
                <th>Способ</th>
                <th>Адрес</th>
                <th>Статус</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= (int) $r['id_dostavka'] ?></td>
                    <td>№<?= (int) $r['id_zakaz'] ?> (<?= h((string) ($r['status_zakaza'] ?? '')) ?>)</td>
                    <td><?= h(trim(($r['familiya'] ?? '') . ' ' . ($r['imya'] ?? ''))) ?></td>
                    <td><?= h(delivery_tip_label((string) ($r['tip_dostavki'] ?? ''))) ?></td>
                    <td><?= h((string) ($r['gorod'] ?? '')) ?>, <?= h((string) ($r['adres'] ?? '')) ?></td>
                    <td>
                        <?php
                        $t = delivery_get_track(['status' => $r['status_dostavki'] ?? '', 'trek_nomer' => $r['trek_nomer'] ?? null]);
                        echo h(delivery_status_base((string) ($r['status_dostavki'] ?? '')));
                        if ($t) {
                            echo '<br><span style="font-size:12px;color:var(--muted)">трек: ' . h($t) . '</span>';
                        }
                        ?>
                    </td>
                    <td><a class="btn btn-ghost btn-small" href="delivery.php?id=<?= (int) $r['id_dostavka'] ?>">Открыть</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php if (!$rows): ?>
        <p>Назначенных доставок нет<?= delivery_table_exists($pdo) ? '' : ' (создайте таблицу STYLE_dostavka)' ?>.</p>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../../includes/layout_staff_bottom.php'; ?>
