<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

require_client();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('orders.php');
}

$id = (int) ($_POST['id_zakaz'] ?? 0);
$token = (string) ($_POST['csrf'] ?? '');
if ($id <= 0 || !hash_equals((string) ($_SESSION['csrf'] ?? ''), $token)) {
    flash_set('Некорректный запрос.', 'error');
    redirect('orders.php');
}

$uid = (int) (current_user()['id'] ?? 0);
$pdo = db();

$st = $pdo->prepare('SELECT * FROM STYLE_zakazy WHERE id_zakaz = ? AND klient = ? LIMIT 1');
$st->execute([$id, $uid]);
$z = $st->fetch();
if (!$z) {
    flash_set('Заказ не найден.', 'error');
    redirect('orders.php');
}

$status = (string) ($z['status'] ?? '');
$delivery = fetch_delivery_for_order($pdo, $id);
$canCancel = order_status_client_can_cancel($status)
    || order_status_client_can_cancel_pvz($z, $delivery);
if (!$canCancel) {
    flash_set('Этот заказ нельзя отменить на текущем этапе.', 'error');
    redirect('orders.php');
}

try {
    $pdo->beginTransaction();
    stock_restore_order($pdo, $id, $status);
    $pdo->prepare('UPDATE STYLE_zakazy SET status = ? WHERE id_zakaz = ?')
        ->execute([ORDER_STATUS_CANCELLED, $id]);
    delivery_mark_order_cancelled($pdo, $id);
    $pdo->commit();
    flash_set('Заказ №' . $id . ' отменён.', 'success');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash_set('Не удалось отменить заказ.', 'error');
}

redirect('orders.php');
