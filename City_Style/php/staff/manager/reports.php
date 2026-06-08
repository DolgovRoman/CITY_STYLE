<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/staff_auth.php';

require_staff();
require_staff_kind('manager');

$pdo = db();

$byMonthRows = $pdo->query(
    "SELECT DATE_FORMAT(data_sozdaniya, '%Y-%m') AS ym, COUNT(*) AS cnt, COALESCE(SUM(itogovaya_summa),0) AS summa
     FROM STYLE_zakazy
     WHERE (status IS NULL OR status <> 'Отменён')
     GROUP BY ym
     ORDER BY ym DESC
     LIMIT 36"
)->fetchAll();

$topSql = order_sostav_has_snapshot($pdo)
    ? 'SELECT COALESCE(sz.nazvanie, t.nazvanie) AS nazvanie,
              SUM(sz.kolichestvo) AS qty,
              SUM(sz.kolichestvo * COALESCE(sz.tsena, t.tsena)) AS vyruchka
       FROM STYLE_sostav_zakaza sz
       LEFT JOIN STYLE_tovary t ON sz.id_tovar = t.id_tovar
       INNER JOIN STYLE_zakazy z ON sz.id_zakaz = z.id_zakaz
       WHERE (z.status IS NULL OR z.status <> \'Отменён\')
       GROUP BY nazvanie
       ORDER BY qty DESC
       LIMIT 15'
    : 'SELECT t.nazvanie, SUM(sz.kolichestvo) AS qty, SUM(sz.kolichestvo * t.tsena) AS vyruchka
       FROM STYLE_sostav_zakaza sz
       INNER JOIN STYLE_tovary t ON sz.id_tovar = t.id_tovar
       INNER JOIN STYLE_zakazy z ON sz.id_zakaz = z.id_zakaz
       WHERE (z.status IS NULL OR z.status <> \'Отменён\')
       GROUP BY t.id_tovar, t.nazvanie
       ORDER BY qty DESC
       LIMIT 15';
$topRows = $pdo->query($topSql)->fetchAll();

$pageTitle = 'Отчёты о продажах — City Style';
$staffHome = '../';
require __DIR__ . '/../../includes/layout_staff_top.php';
require __DIR__ . '/../_nav.php';
?>

<main class="staff-main">
    <h1>Отчёты о продажах</h1>
    <div class="staff-toolbar">
        <a class="btn btn-primary" href="reports_export.php">Сохранить отчёт в Word</a>
    </div>
    <div class="panel">
        <h3>По месяцам</h3>
        <div class="staff-table-wrap">
        <table class="data-table">
            <thead><tr><th>Месяц</th><th>Заказов</th><th>Выручка</th></tr></thead>
            <tbody>
                <?php foreach ($byMonthRows as $r): ?>
                    <tr>
                        <td><?= h((string) ($r['ym'] ?? '')) ?></td>
                        <td><?= (int) ($r['cnt'] ?? 0) ?></td>
                        <td><?= h(format_price((float) ($r['summa'] ?? 0))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <div class="panel">
        <h3>Топ товаров</h3>
        <div class="staff-table-wrap">
        <table class="data-table">
            <thead><tr><th>Товар</th><th>Шт.</th><th>Выручка</th></tr></thead>
            <tbody>
                <?php foreach ($topRows as $r): ?>
                    <tr>
                        <td><?= h((string) ($r['nazvanie'] ?? '')) ?></td>
                        <td><?= (int) ($r['qty'] ?? 0) ?></td>
                        <td><?= h(format_price((float) ($r['vyruchka'] ?? 0))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php if (!$topRows): ?>
            <p>Нет данных.</p>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/../../includes/layout_staff_bottom.php'; ?>
