<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/staff_auth.php';

require_staff();
require_staff_kind('employee');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('products.php');
}

$id = (int) ($_POST['id_tovar'] ?? 0);
if ($id <= 0) {
    redirect('products.php');
}

$pdo = db();

try {
    if (product_delete_from_catalog($pdo, $id)) {
        flash_set('Товар удалён из каталога. Позиции в существующих заказах сохранены для доставки.', 'success');
    } else {
        flash_set('Товар не найден или уже удалён.', 'error');
    }
} catch (Throwable $e) {
    $hint = '';
    if (!order_sostav_has_snapshot($pdo) || !order_sostav_id_tovar_nullable($pdo)) {
        $hint = ' Выполните SQL из файла php/sql/schema_order_item_snapshot.sql в phpMyAdmin.';
    }
    flash_set('Ошибка удаления (связи в БД).' . $hint, 'error');
}

redirect('products.php');
