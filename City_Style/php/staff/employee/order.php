<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/staff_auth.php';
require __DIR__ . '/../../includes/staff_filters.php';

require_staff();
require_staff_kind('employee');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$pdo = db();

$st = $pdo->prepare(
    'SELECT z.*, k.familiya, k.imya, k.email, k.otchestvo' . client_phone_select_sql($pdo) . '
     FROM STYLE_zakazy z
     INNER JOIN STYLE_klienti k ON z.klient = k.id_klient
     WHERE z.id_zakaz = ? LIMIT 1'
);
$st->execute([$id]);
$z = $st->fetch();
if (!$z) {
    flash_set('Заказ не найден.', 'error');
    redirect('orders.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_courier'])) {
        $courierId = (int) ($_POST['id_sotrudnik_kurier'] ?? 0);
        if ($courierId <= 0) {
            flash_set('Выберите курьера.', 'error');
        } else {
            $err = assign_courier_to_order($pdo, $id, $courierId);
            if ($err !== null) {
                flash_set($err, 'error');
            } else {
                flash_set('Курьер назначен. Статус заказа: «' . ORDER_STATUS_V_PUTI . '».', 'success');
            }
        }
        redirect('order.php?id=' . $id);
    }

    $status = trim((string) ($_POST['status'] ?? ''));
    if (in_array($status, order_statuses_employee(), true)) {
        $prevStatus = (string) ($z['status'] ?? '');
        if ($status === ORDER_STATUS_CANCELLED) {
            stock_restore_order($pdo, $id, $prevStatus);
        }
        $pdo->prepare('UPDATE STYLE_zakazy SET status = ? WHERE id_zakaz = ?')->execute([$status, $id]);
        flash_set('Статус заказа обновлён.', 'success');
    }
    redirect('order.php?id=' . $id);
}

$itemSt = $pdo->prepare(order_sostav_select_sql($pdo));
$itemSt->execute([$id]);
$items = $itemSt->fetchAll();

$couriers = fetch_couriers($pdo);
$delivery = fetch_delivery_for_order($pdo, $id);
$canAssignCourier = !in_array((string) ($z['status'] ?? ''), [ORDER_STATUS_DONE, ORDER_STATUS_CANCELLED], true);

$body = 'Здравствуйте, ' . trim(($z['familiya'] ?? '') . ' ' . ($z['imya'] ?? '')) . ', по заказу №' . $id;
$mailtoBody = rawurlencode($body);

$pageTitle = 'Заказ №' . $id . ' — City Style';
$staffHome = '../';
require __DIR__ . '/../../includes/layout_staff_top.php';
require __DIR__ . '/../_nav.php';
?>

<main class="staff-main">
    <a class="btn btn-ghost btn-small staff-back" href="orders.php">← К списку заказов</a>
    <h1>Заказ №<?= $id ?></h1>
    <div class="panel">
        <p><strong>Клиент:</strong> <?= h(trim(($z['familiya'] ?? '') . ' ' . ($z['imya'] ?? '') . ' ' . (string) ($z['otchestvo'] ?? ''))) ?></p>
        <p><strong>Email:</strong> <a href="mailto:<?= h((string) ($z['email'] ?? '')) ?>?subject=<?= rawurlencode('Заказ №' . $id) ?>&body=<?= $mailtoBody ?>"><?= h((string) ($z['email'] ?? '')) ?></a></p>
        <?php if (client_phone_enabled($pdo)): ?>
            <p><strong>Телефон:</strong> <?= client_phone_html(client_phone_from_row($z, $pdo)) ?></p>
        <?php endif; ?>
        <p><strong>Адрес:</strong> <?= h((string) ($z['gorod'] ?? '')) ?>, <?= h((string) ($z['adres'] ?? '')) ?></p>
        <p><strong>Способ доставки:</strong> <?= h(delivery_tip_label((string) ($z['tip_dostavki'] ?? ''))) ?></p>
        <p><strong>Статус:</strong> <?= h((string) ($z['status'] ?? '')) ?></p>
        <p><strong>Сумма:</strong> <?= h(format_price((float) ($z['itogovaya_summa'] ?? 0))) ?></p>
        <?php if ($delivery): ?>
            <p><strong>Курьер:</strong>
                <?= h(trim(($delivery['kur_fam'] ?? '') . ' ' . ($delivery['kur_imya'] ?? ''))) ?>
                (доставка №<?= (int) ($delivery['id_dostavka'] ?? 0) ?>)
            </p>
            <?php $empTrack = delivery_get_track($delivery); ?>
            <?php if ($empTrack && delivery_order_is_pvz((string) ($z['tip_dostavki'] ?? ''))): ?>
                <p><strong>Трек-номер:</strong> <?= h($empTrack) ?></p>
            <?php endif; ?>
        <?php endif; ?>
        <h4>Состав</h4>
        <ul class="simple-list">
            <?php foreach ($items as $it): ?>
                <?php
                $lineName = trim((string) ($it['nazvanie'] ?? ''));
                if ($lineName === '') {
                    $lineName = 'Товар (снят с каталога)';
                }
                ?>
                <li><?= h($lineName) ?> (<?= h((string) ($it['articul'] ?? '')) ?>) × <?= (int) $it['kolichestvo'] ?> — <?= h(format_price((float) ($it['tsena'] ?? 0) * (int) $it['kolichestvo'])) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php if ($canAssignCourier && $couriers): ?>
        <div class="panel">
            <h3>Назначить курьера</h3>
            <p class="staff-hint">После назначения заказ появится у курьера, статус станет «<?= h(ORDER_STATUS_V_PUTI) ?>».</p>
            <form method="post">
                <input type="hidden" name="assign_courier" value="1">
                <label>Курьер
                    <select name="id_sotrudnik_kurier" required>
                        <option value="">— выберите —</option>
                        <?php foreach ($couriers as $c): ?>
                            <option value="<?= (int) $c['id_sotrudnik'] ?>" <?= (int) ($delivery['id_sotrudnik_kurier'] ?? 0) === (int) $c['id_sotrudnik'] ? 'selected' : '' ?>>
                                <?= h(trim(($c['familiya'] ?? '') . ' ' . ($c['imya'] ?? ''))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="btn btn-primary" type="submit" style="margin-top:12px"><?= $delivery ? 'Сменить курьера' : 'Назначить курьера' ?></button>
            </form>
        </div>
    <?php elseif ($canAssignCourier && !$couriers): ?>
        <div class="panel flash-info"><p>В системе нет сотрудников с должностью «курьер».</p></div>
    <?php endif; ?>

    <?php if ((string) ($z['status'] ?? '') !== ORDER_STATUS_V_PUTI && (string) ($z['status'] ?? '') !== ORDER_STATUS_DONE): ?>
    <div class="panel">
        <h3>Статус обработки</h3>
        <form method="post">
            <label>Статус
                <select name="status">
                    <?php foreach (order_statuses_employee() as $opt): ?>
                        <option value="<?= h($opt) ?>" <?= (($z['status'] ?? '') === $opt) ? 'selected' : '' ?>><?= h($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn btn-ghost" type="submit" style="margin-top:12px">Сохранить статус</button>
        </form>
    </div>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../../includes/layout_staff_bottom.php'; ?>
