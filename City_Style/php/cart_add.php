<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/products.php';

require_client();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('catalog.php');
}

$id = (int) ($_POST['id_tovar'] ?? 0);
$qty = max(1, (int) ($_POST['qty'] ?? 1));
$target = (string) ($_POST['redirect_target'] ?? '');
$back = $target === 'checkout'
    ? 'checkout.php'
    : (string) ($_POST['redirect'] ?? 'cart.php');
if ($back === '' || str_starts_with($back, '//') || str_contains($back, '://')) {
    $back = 'cart.php';
}

$pdo = db();
$row = $id > 0 ? fetch_tovar_by_id($pdo, $id) : null;
if (!$row) {
    flash_set('Товар не найден.', 'error');
    redirect('catalog.php');
}

if (!stock_in_stock($pdo, $id)) {
    flash_set('Этот вариант сейчас отсутствует на складе.', 'error');
    redirect($back);
}

if ($target === 'checkout') {
    $cap = stock_cap_qty($pdo, $id, $qty);
    if ($qty > $cap) {
        flash_set('Нельзя заказать больше: недостаточно на складе.', 'error');
        redirect((string) ($_POST['redirect'] ?? 'product.php?id=' . $id));
    }
    cart_buy_now_set($id, $qty);
    redirect('checkout.php');
}

$inCart = (int) (cart_items()[$id] ?? 0);
$cap = stock_cap_qty($pdo, $id, $inCart + $qty);
if ($cap <= $inCart) {
    flash_set('Нельзя добавить больше: недостаточно на складе.', 'error');
    redirect($back);
}

cart_buy_now_clear();
cart_set_qty($id, $cap);
flash_set('Товар добавлен в корзину.', 'success');
redirect($back);
