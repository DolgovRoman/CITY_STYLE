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

$err = client_confirm_pvz_received($pdo, $id, $uid);
if ($err !== null) {
    flash_set($err, 'error');
} else {
    flash_set('Заказ №' . $id . ' завершён. Спасибо за покупку!', 'success');
}

redirect('orders.php');
