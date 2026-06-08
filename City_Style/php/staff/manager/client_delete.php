<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/staff_auth.php';

require_staff();
require_staff_kind('manager');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('clients.php');
}

$id = (int) ($_POST['id_klient'] ?? 0);
if ($id <= 0) {
    redirect('clients.php');
}

$pdo = db();
$chk = $pdo->prepare('SELECT COUNT(*) FROM STYLE_zakazy WHERE klient = ?');
$chk->execute([$id]);
if ((int) $chk->fetchColumn() > 0) {
    flash_set('Нельзя удалить: у клиента есть заказы.', 'error');
    redirect('clients.php');
}

try {
    $pdo->prepare('DELETE FROM STYLE_klienti WHERE id_klient = ?')->execute([$id]);
    flash_set('Клиент удалён.', 'success');
} catch (Throwable $e) {
    flash_set('Ошибка удаления.', 'error');
}

redirect('clients.php');
