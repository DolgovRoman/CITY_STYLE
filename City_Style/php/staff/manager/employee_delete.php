<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/staff_auth.php';

require_staff();
require_staff_kind('manager');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('employees.php');
}

$id = (int) ($_POST['id_sotrudnik'] ?? 0);
if ($id <= 0) {
    redirect('employees.php');
}

$pdo = db();
$chk = $pdo->prepare('SELECT COUNT(*) FROM STYLE_dostavka WHERE id_sotrudnik_kurier = ?');
$chk->execute([$id]);
if ((int) $chk->fetchColumn() > 0) {
    flash_set('Нельзя удалить: сотрудник указан в доставках.', 'error');
    redirect('employees.php');
}

try {
    $pdo->prepare('DELETE FROM STYLE_sotrudniki WHERE id_sotrudnik = ?')->execute([$id]);
    flash_set('Сотрудник удалён.', 'success');
} catch (Throwable $e) {
    flash_set('Ошибка удаления.', 'error');
}

redirect('employees.php');
