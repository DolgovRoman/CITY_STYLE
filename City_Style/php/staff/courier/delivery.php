<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/staff_auth.php';

require_staff();
require_staff_kind('courier');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$sid = (int) (current_user()['id'] ?? 0);
$pdo = db();

$st = $pdo->prepare(
    'SELECT d.*, z.status AS status_zakaza, z.tip_dostavki, z.adres, z.gorod, z.itogovaya_summa, z.data_sozdaniya,
            k.familiya, k.imya, k.email, k.otchestvo' . client_phone_select_sql($pdo) . '
     FROM STYLE_dostavka d
     INNER JOIN STYLE_zakazy z ON d.id_zakaz = z.id_zakaz
     INNER JOIN STYLE_klienti k ON z.klient = k.id_klient
     WHERE d.id_dostavka = ? AND d.id_sotrudnik_kurier = ? LIMIT 1'
);
$st->execute([$id, $sid]);
$row = $st->fetch();
if (!$row) {
    flash_set('Доставка не найдена.', 'error');
    redirect('deliveries.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_track'])) {
    $err = courier_save_track($pdo, $id, $sid, (string) ($_POST['trek_nomer'] ?? ''));
    if ($err !== null) {
        flash_set($err, 'error');
    } else {
        flash_set('Трек-номер сохранён. Теперь можно завершить доставку.', 'success');
    }
    redirect('delivery.php?id=' . $id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_delivered'])) {
    $err = courier_mark_delivered($pdo, $id, $sid);
    if ($err !== null) {
        flash_set($err, 'error');
    } else {
        $msg = delivery_order_is_pvz((string) ($row['tip_dostavki'] ?? ''))
            ? 'Заказ передан в ПВЗ. Статус заказа останется «В пути» до подтверждения клиентом.'
            : 'Заказ отмечен как доставленный.';
        flash_set($msg, 'success');
    }
    redirect('deliveries.php');
}

$itemSt = $pdo->prepare(
    order_sostav_select_sql($pdo)
);
$itemSt->execute([(int) $row['id_zakaz']]);
$items = $itemSt->fetchAll();

$isPvz = delivery_order_is_pvz((string) ($row['tip_dostavki'] ?? ''));
$track = delivery_get_track($row);
$isInTransit = (string) ($row['status_zakaza'] ?? '') === ORDER_STATUS_V_PUTI;
$isDone = (string) ($row['status_zakaza'] ?? '') === ORDER_STATUS_DONE;
$pvzAwaitingClient = delivery_pvz_awaiting_client(
    ['tip_dostavki' => $row['tip_dostavki'] ?? '', 'status' => $row['status_zakaza'] ?? ''],
    $row
);
$canComplete = $isInTransit && !$pvzAwaitingClient && (!$isPvz || ($track !== null && $track !== ''));

$pageTitle = 'Доставка №' . $id . ' — City Style';
$staffHome = '../';
require __DIR__ . '/../../includes/layout_staff_top.php';
require __DIR__ . '/../_nav.php';
?>

<main class="staff-main">
    <a class="btn btn-ghost btn-small staff-back" href="deliveries.php">← К списку доставок</a>
    <h1>Доставка №<?= $id ?></h1>
    <div class="panel">
        <h3>Заказ №<?= (int) $row['id_zakaz'] ?></h3>
        <p>Статус заказа: <strong><?= h((string) ($row['status_zakaza'] ?? '')) ?></strong></p>
        <p>Способ доставки: <strong><?= h(delivery_tip_label((string) ($row['tip_dostavki'] ?? ''))) ?></strong></p>
        <p><?= $isPvz ? 'ПВЗ:' : 'Адрес:' ?>
            <?= h((string) ($row['gorod'] ?? '')) ?>, <?= h((string) ($row['adres'] ?? '')) ?></p>
        <p>Сумма: <?= h(format_price((float) ($row['itogovaya_summa'] ?? 0))) ?></p>
        <?php if ($track): ?>
            <p><strong>Трек-номер:</strong> <?= h($track) ?></p>
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
                <li><?= h($lineName) ?> × <?= (int) $it['kolichestvo'] ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="panel">
        <h3>Клиент</h3>
        <p><?= h(trim(($row['familiya'] ?? '') . ' ' . ($row['imya'] ?? '') . ' ' . (string) ($row['otchestvo'] ?? ''))) ?></p>
        <p>Email: <a href="mailto:<?= h((string) ($row['email'] ?? '')) ?>"><?= h((string) ($row['email'] ?? '')) ?></a></p>
        <?php if (client_phone_enabled($pdo)): ?>
            <p>Телефон: <?= client_phone_html(client_phone_from_row($row, $pdo)) ?></p>
        <?php endif; ?>
    </div>

    <?php if ($isDone): ?>
        <div class="panel flash-info"><p>Заказ завершён клиентом.<?php if ($track): ?> Трек: <strong><?= h($track) ?></strong><?php endif; ?></p></div>
    <?php elseif ($pvzAwaitingClient): ?>
        <div class="panel flash-info">
            <p>Заказ передан в ПВЗ. Статус заказа: <strong><?= h(ORDER_STATUS_V_PUTI) ?></strong> — клиент должен подтвердить получение или отказаться.</p>
            <?php if ($track): ?>
                <p>Трек: <strong><?= h($track) ?></strong></p>
            <?php endif; ?>
        </div>
    <?php elseif ($isInTransit && $isPvz): ?>
        <div class="panel">
            <h3>Трек-номер для ПВЗ</h3>
            <p class="staff-hint">Укажите трек отправления в пункт выдачи. После кнопки «Заказ доставлен» заказ останется «В пути» до подтверждения клиентом.</p>
            <form method="post">
                <input type="hidden" name="save_track" value="1">
                <label>Трек-номер *
                    <input type="text" name="trek_nomer" required maxlength="64" value="<?= h($track ?? '') ?>" placeholder="Например: 12345678901234">
                </label>
                <button class="btn btn-primary" type="submit" style="margin-top:12px">Сохранить трек-номер</button>
            </form>
        </div>
        <div class="panel">
            <form method="post" onsubmit="return confirm('Подтвердить передачу заказа в ПВЗ? Клиент завершит заказ после получения.');">
                <input type="hidden" name="mark_delivered" value="1">
                <button class="btn btn-primary" type="submit" <?= $canComplete ? '' : 'disabled title="Сначала сохраните трек-номер"' ?>>Заказ доставлен в ПВЗ</button>
            </form>
            <?php if (!$canComplete): ?>
                <p class="staff-hint" style="margin-top:8px">Сначала сохраните трек-номер выше.</p>
            <?php endif; ?>
        </div>
    <?php elseif ($isInTransit): ?>
        <div class="panel">
            <form method="post" onsubmit="return confirm('Подтвердить доставку заказа клиенту?');">
                <input type="hidden" name="mark_delivered" value="1">
                <button class="btn btn-primary" type="submit">Заказ доставлен</button>
            </form>
        </div>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../../includes/layout_staff_bottom.php'; ?>
