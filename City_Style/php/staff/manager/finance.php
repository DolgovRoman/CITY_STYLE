<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/staff_auth.php';

require_staff();
require_staff_kind('manager');

$pdo = db();

$sum = (float) $pdo->query(
    "SELECT COALESCE(SUM(itogovaya_summa), 0) FROM STYLE_zakazy WHERE status = 'Завершен'"
)->fetchColumn();

$cntDone = (int) $pdo->query(
    "SELECT COUNT(*) FROM STYLE_zakazy WHERE status = 'Завершен'"
)->fetchColumn();

$cntAll = (int) $pdo->query('SELECT COUNT(*) FROM STYLE_zakazy')->fetchColumn();

$sumActive = (float) $pdo->query(
    "SELECT COALESCE(SUM(itogovaya_summa), 0) FROM STYLE_zakazy WHERE status NOT IN ('Отменён', 'Завершен')"
)->fetchColumn();

$pageTitle = 'Финансовые показатели — City Style';
$staffHome = '../';
require __DIR__ . '/../../includes/layout_staff_top.php';
require __DIR__ . '/../_nav.php';
?>

<main class="staff-main">
    <h1>Финансовые показатели</h1>
    <p class="staff-intro">Сводка по всем заказам в системе.</p>
    <div class="staff-stat-grid">
        <div class="staff-stat-card">
            <p class="staff-stat-label">Выручка (завершённые)</p>
            <p class="staff-stat-value"><?= h(format_price($sum)) ?></p>
            <p class="staff-stat-meta">Заказов: <?= $cntDone ?></p>
        </div>
        <div class="staff-stat-card">
            <p class="staff-stat-label">В работе (сумма)</p>
            <p class="staff-stat-value"><?= h(format_price($sumActive)) ?></p>
            <p class="staff-stat-meta">Не завершены и не отменены</p>
        </div>
        <div class="staff-stat-card">
            <p class="staff-stat-label">Всего заказов</p>
            <p class="staff-stat-value"><?= $cntAll ?></p>
            <p class="staff-stat-meta">Все статусы</p>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../../includes/layout_staff_bottom.php'; ?>
