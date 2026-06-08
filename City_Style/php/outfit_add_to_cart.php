<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/products.php';

require_client();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('outfit-builder.php');
}

$pdo = db();
$slots = [
    'id_top' => 'Верх',
    'id_bottom' => 'Низ',
    'id_shoes' => 'Обувь',
];

$redirect = trim((string) ($_POST['redirect'] ?? 'cart.php'));
if ($redirect === '' || str_starts_with($redirect, '//') || str_contains($redirect, '://')) {
    $redirect = 'cart.php';
}
if (!in_array($redirect, ['cart.php', 'outfit-builder.php'], true)) {
    $redirect = 'cart.php';
}

$ids = [];
foreach (array_keys($slots) as $field) {
    $id = (int) ($_POST[$field] ?? 0);
    if ($id > 0) {
        $ids[$id] = ($ids[$id] ?? 0) + 1;
    }
}

if ($ids === []) {
    flash_set('Выберите товары в каруселях: верх, низ и обувь.', 'error');
    redirect('outfit-builder.php');
}

cart_buy_now_clear();

$added = 0;
$problems = [];

foreach ($ids as $id => $qty) {
    $row = fetch_tovar_by_id($pdo, $id);
    if (!$row) {
        $problems[] = 'товар №' . $id;
        continue;
    }
    if (!stock_in_stock($pdo, $id)) {
        $problems[] = (string) ($row['nazvanie'] ?? 'товар');
        continue;
    }
    $inCart = (int) (cart_items()[$id] ?? 0);
    $cap = stock_cap_qty($pdo, $id, $inCart + $qty);
    if ($cap <= $inCart) {
        $problems[] = (string) ($row['nazvanie'] ?? 'товар');
        continue;
    }
    cart_set_qty($id, $cap);
    $added++;
}

if ($added === 0) {
    flash_set('Не удалось добавить образ в корзину: проверьте наличие на складе.', 'error');
    redirect('outfit-builder.php');
}

if ($problems !== []) {
    flash_set(
        'В корзину добавлено позиций: ' . $added . '. Не добавлено: ' . implode(', ', array_unique($problems)) . '.',
        'success'
    );
} else {
    flash_set(
        $added === 1
            ? 'Товар из образа добавлен в корзину.'
            : 'Образ добавлен в корзину (' . $added . ' поз.).',
        'success'
    );
}

redirect($redirect);
