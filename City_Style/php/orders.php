<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

require_client();

$uid = (int) (current_user()['id'] ?? 0);
$pdo = db();

if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

$hasDost = delivery_table_exists($pdo);
$hasTrackCol = $hasDost && delivery_track_column_exists($pdo);
$sql = 'SELECT z.*';
if ($hasDost) {
    $sql .= ', d.status AS status_dostavki';
    if ($hasTrackCol) {
        $sql .= ', d.trek_nomer AS dost_trek';
    }
}
$sql .= ' FROM STYLE_zakazy z';
if ($hasDost) {
    $sql .= ' LEFT JOIN STYLE_dostavka d ON d.id_zakaz = z.id_zakaz';
}
$sql .= ' WHERE z.klient = ? ORDER BY z.data_sozdaniya DESC, z.id_zakaz DESC';
$st = $pdo->prepare($sql);
$st->execute([$uid]);
$zakazy = $st->fetchAll();

$itemSt = $pdo->prepare(order_sostav_select_sql($pdo));

$pageTitle = 'Мои заказы — City Style';
require __DIR__ . '/includes/header.php';
?>

<main class="container section">
    <h1>Мои заказы</h1>
    <p class="page-back"><a class="btn btn-ghost btn-small" href="account.php">← Личный кабинет</a></p>
    <div class="orders-list">
        <?php foreach ($zakazy as $z): ?>
            <article class="panel">
                <h3>Заказ №<?= (int) $z['id_zakaz'] ?></h3>
                <?php
                $deliveryRow = null;
                if ($hasDost) {
                    $deliveryRow = [
                        'status' => $z['status_dostavki'] ?? null,
                        'trek_nomer' => $z['dost_trek'] ?? null,
                    ];
                }
                $statusLabel = order_status_client_label(
                    (string) ($z['status'] ?? ''),
                    (string) ($z['tip_dostavki'] ?? ''),
                    $deliveryRow
                );
                $pvzAwaiting = delivery_pvz_awaiting_client($z, $deliveryRow);
                ?>
                <p style="font-size:14px;color:var(--muted)">
                    <?= h((string) ($z['data_sozdaniya'] ?? '')) ?> ·
                    <?= h($statusLabel) ?> ·
                    <?= h(delivery_tip_label((string) ($z['tip_dostavki'] ?? ''))) ?>
                </p>
                <p><?= h((string) ($z['gorod'] ?? '')) ?>, <?= h((string) ($z['adres'] ?? '')) ?></p>
                <?php
                $orderTrack = null;
                if ($hasDost) {
                    $orderTrack = delivery_get_track([
                        'trek_nomer' => $z['dost_trek'] ?? null,
                        'status' => $z['status_dostavki'] ?? null,
                    ]);
                }
                ?>
                <?php if ($orderTrack && delivery_order_is_pvz((string) ($z['tip_dostavki'] ?? ''))): ?>
                    <p><strong>Трек-номер:</strong> <span class="order-track"><?= h($orderTrack) ?></span></p>
                <?php elseif (delivery_order_is_pvz((string) ($z['tip_dostavki'] ?? '')) && (string) ($z['status'] ?? '') === ORDER_STATUS_V_PUTI && !$pvzAwaiting): ?>
                    <p style="font-size:14px;color:var(--muted)">Трек-номер появится после передачи заказа в пункт выдачи.</p>
                <?php endif; ?>
                <?php if ($pvzAwaiting): ?>
                    <p class="flash-info" style="margin:12px 0;padding:12px;border-radius:10px;font-size:14px">
                        Нажмите «Товар получен», когда заберёте заказ, или «Отменить заказ», если отказываетесь.
                    </p>
                <?php endif; ?>
                <p><strong>Сумма: <?= h(format_price((float) ($z['itogovaya_summa'] ?? 0))) ?></strong></p>
                <?php
                $itemSt->execute([(int) $z['id_zakaz']]);
                $rows = $itemSt->fetchAll();
                ?>
                <ul class="simple-list">
                    <?php foreach ($rows as $r): ?>
                        <li>
                            <?= h((string) ($r['nazvanie'] ?? '') !== '' ? (string) $r['nazvanie'] : 'Товар №' . (int) ($r['id_tovar'] ?? 0) . ' (нет в каталоге)') ?>
                            × <?= (int) $r['kolichestvo'] ?>
                            — <?= h(format_price((float) ($r['tsena'] ?? 0) * (int) $r['kolichestvo'])) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php
                $canCancel = order_status_client_can_cancel((string) ($z['status'] ?? ''))
                    || order_status_client_can_cancel_pvz($z, $deliveryRow);
                $showActions = $rows || $canCancel || $pvzAwaiting;
                ?>
                <?php if ($showActions): ?>
                <div class="order-actions">
                    <?php if ($rows): ?>
                        <form method="post" action="order_repeat.php" class="order-action-form">
                            <input type="hidden" name="id_zakaz" value="<?= (int) $z['id_zakaz'] ?>">
                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                            <button class="btn btn-primary" type="submit">Повторить заказ</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($pvzAwaiting): ?>
                        <form method="post" action="order_pvz_confirm.php" class="order-action-form" onsubmit="return confirm('Подтвердить, что вы получили заказ №<?= (int) $z['id_zakaz'] ?> в ПВЗ?');">
                            <input type="hidden" name="id_zakaz" value="<?= (int) $z['id_zakaz'] ?>">
                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                            <button class="btn btn-primary" type="submit">Товар получен</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($canCancel): ?>
                        <form method="post" action="order_cancel.php" class="order-action-form" onsubmit="return confirm('Отменить заказ №<?= (int) $z['id_zakaz'] ?>?');">
                            <input type="hidden" name="id_zakaz" value="<?= (int) $z['id_zakaz'] ?>">
                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                            <button class="btn btn-ghost" type="submit">Отменить заказ</button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
        <?php if (!$zakazy): ?>
            <p>Заказов пока нет. <a class="btn btn-primary btn-small" href="catalog.php">Перейти в каталог</a></p>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
