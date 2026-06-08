<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/staff_auth.php';
require __DIR__ . '/../../includes/staff_filters.php';

require_staff();
require_staff_kind('manager');

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_status'])) {
    $zid = (int) ($_POST['id_zakaz'] ?? 0);
    $st = trim((string) ($_POST['new_status'] ?? ''));
    if ($zid > 0 && in_array($st, order_statuses_manager(), true)) {
        $prevSt = $pdo->prepare('SELECT status FROM STYLE_zakazy WHERE id_zakaz = ? LIMIT 1');
        $prevSt->execute([$zid]);
        $prevRow = $prevSt->fetch();
        $prevStatus = (string) ($prevRow['status'] ?? '');
        if ($st === ORDER_STATUS_CANCELLED) {
            stock_restore_order($pdo, $zid, $prevStatus);
        }
        $pdo->prepare('UPDATE STYLE_zakazy SET status = ? WHERE id_zakaz = ?')->execute([$st, $zid]);
        flash_set('Статус заказа №' . $zid . ' обновлён.', 'success');
    }
    redirect('orders.php?' . http_build_query(array_filter($_GET)));
}

$filters = staff_list_filters_from_request();
$params = [];
$where = staff_sql_orders_where($filters, $params);

$sql = 'SELECT z.*, k.familiya, k.imya, k.email' . client_phone_select_sql($pdo) . ' FROM STYLE_zakazy z
     INNER JOIN STYLE_klienti k ON z.klient = k.id_klient
     WHERE 1=1' . $where . ' ORDER BY z.id_zakaz DESC LIMIT 150';
$st = $pdo->prepare($sql);
$st->execute($params);
$list = $st->fetchAll();

$pageTitle = 'Контроль заказов — City Style';
$staffHome = '../';
require __DIR__ . '/../../includes/layout_staff_top.php';
require __DIR__ . '/../_nav.php';
?>

<main class="staff-main">
    <h1>Контроль выполнения заказов</h1>
    <?php staff_render_filter_panel('orders.php', $filters, 'orders'); ?>
    <div class="staff-table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>№</th>
                <th>Клиент</th>
                <?php if (client_phone_enabled($pdo)): ?><th>Телефон</th><?php endif; ?>
                <th>Статус</th>
                <th>Доставка</th>
                <th>Сумма</th>
                <th>Дата</th>
                <th>Новый статус</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($list as $z): ?>
                <tr>
                    <td><?= (int) $z['id_zakaz'] ?></td>
                    <td><?= h(trim(($z['familiya'] ?? '') . ' ' . ($z['imya'] ?? ''))) ?></td>
                    <?php if (client_phone_enabled($pdo)): ?>
                        <td><?= client_phone_html(client_phone_from_row($z, $pdo)) ?></td>
                    <?php endif; ?>
                    <td><?= h((string) ($z['status'] ?? '')) ?></td>
                    <td><?= h(delivery_tip_label((string) ($z['tip_dostavki'] ?? ''))) ?></td>
                    <td><?= h(format_price((float) ($z['itogovaya_summa'] ?? 0))) ?></td>
                    <td><?= h((string) ($z['data_sozdaniya'] ?? '')) ?></td>
                    <td>
                        <form method="post" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
                            <input type="hidden" name="id_zakaz" value="<?= (int) $z['id_zakaz'] ?>">
                            <select name="new_status" style="max-width:160px">
                                <?php foreach (order_statuses_manager() as $opt): ?>
                                    <option value="<?= h($opt) ?>"><?= h($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-small btn-primary">OK</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php if (!$list): ?>
        <p>Заказы не найдены.</p>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../../includes/layout_staff_bottom.php'; ?>
