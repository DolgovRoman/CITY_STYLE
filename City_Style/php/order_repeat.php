<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/products.php';

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

$st = $pdo->prepare('SELECT id_zakaz FROM STYLE_zakazy WHERE id_zakaz = ? AND klient = ? LIMIT 1');
$st->execute([$id, $uid]);
if (!$st->fetch()) {
    flash_set('Заказ не найден.', 'error');
    redirect('orders.php');
}

$lines = $pdo->prepare(
    'SELECT id_tovar, kolichestvo FROM STYLE_sostav_zakaza WHERE id_zakaz = ?'
);
$lines->execute([$id]);
$rows = $lines->fetchAll();

if (!$rows) {
    flash_set('В заказе нет позиций для переноса.', 'error');
    redirect('orders.php');
}

$added = 0;
$skipped = 0;
foreach ($rows as $row) {
    $tid = (int) ($row['id_tovar'] ?? 0);
    $qty = max(1, (int) ($row['kolichestvo'] ?? 1));
    if ($tid <= 0) {
        $skipped++;
        continue;
    }
    if (!fetch_tovar_by_id($pdo, $tid)) {
        $skipped++;
        continue;
    }
    if (!stock_in_stock($pdo, $tid)) {
        $skipped++;
        continue;
    }
    $inCart = (int) (cart_items()[$tid] ?? 0);
    $cap = stock_cap_qty($pdo, $tid, $inCart + $qty);
    if ($cap <= $inCart) {
        $skipped++;
        continue;
    }
    cart_set_qty($tid, $cap);
    $added++;
}

if ($added === 0) {
    flash_set('Не удалось добавить товары: позиции отсутствуют в каталоге.', 'error');
    redirect('orders.php');
}

$msg = 'Товары из заказа №' . $id . ' добавлены в корзину (позиций: ' . $added . ').';
if ($skipped > 0) {
    $msg .= ' Не перенесено (нет в каталоге или нет на складе): ' . $skipped . '.';
}
flash_set($msg, 'success');
redirect('cart.php');
