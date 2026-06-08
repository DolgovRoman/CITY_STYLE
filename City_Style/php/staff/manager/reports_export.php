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

$filename = 'otchet_prodazhi_' . date('Y-m-d_His') . '.doc';
header('Content-Type: application/msword; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

echo '<html><head><meta charset="UTF-8"></head><body>';
echo '<h1>Отчёт о продажах — City Style</h1>';
echo '<p>Дата формирования: ' . h(date('d.m.Y H:i')) . '</p>';

echo '<h2>Продажи по месяцам</h2>';
echo '<table border="1" cellpadding="6" cellspacing="0">';
echo '<tr><th>Месяц</th><th>Заказов</th><th>Выручка</th></tr>';
foreach ($byMonthRows as $r) {
    echo '<tr><td>' . h((string) ($r['ym'] ?? '')) . '</td>';
    echo '<td>' . (int) ($r['cnt'] ?? 0) . '</td>';
    echo '<td>' . h(format_price((float) ($r['summa'] ?? 0))) . '</td></tr>';
}
echo '</table>';

echo '<h2>Топ товаров</h2>';
echo '<table border="1" cellpadding="6" cellspacing="0">';
echo '<tr><th>Товар</th><th>Шт.</th><th>Выручка</th></tr>';
foreach ($topRows as $r) {
    echo '<tr><td>' . h((string) ($r['nazvanie'] ?? '')) . '</td>';
    echo '<td>' . (int) ($r['qty'] ?? 0) . '</td>';
    echo '<td>' . h(format_price((float) ($r['vyruchka'] ?? 0))) . '</td></tr>';
}
if (!$topRows) {
    echo '<tr><td colspan="3">Нет данных</td></tr>';
}
echo '</table></body></html>';
exit;
