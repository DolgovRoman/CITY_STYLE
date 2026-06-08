<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/staff_auth.php';
require __DIR__ . '/../../includes/staff_filters.php';

require_staff();
require_staff_kind('manager');

$pdo = db();
$filters = staff_list_filters_from_request();
$hasSklad = table_exists($pdo, 'STYLE_sklad_ostatok');
$hasPost = table_exists($pdo, 'STYLE_postupleniya');

/**
 * @return string|null Ошибка для flash или null
 */
function warehouse_log_movement(
    PDO $pdo,
    int $tid,
    int $qty,
    string $dt,
    string $prim,
    bool $inbound
): ?string {
    if ($tid <= 0 || $qty <= 0) {
        return 'Проверьте товар и количество.';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dt)) {
        return 'Укажите дату в формате ГГГГ-ММ-ДД.';
    }
    if (!$inbound && trim($prim) === '') {
        return 'Укажите причину списания (брак, возврат и т.д.).';
    }

    $signedQty = $inbound ? $qty : -$qty;
    $note = $prim === '' ? null : $prim;
    if (!$inbound && $note !== null && !str_starts_with(mb_strtolower($note), 'списание')) {
        $note = 'Списание: ' . $note;
    }

    try {
        $pdo->beginTransaction();
        if ($inbound) {
            $err = stock_receive($pdo, $tid, $qty);
        } else {
            $err = stock_write_off($pdo, $tid, $qty);
        }
        if ($err !== null) {
            $pdo->rollBack();

            return $err;
        }
        $pdo->prepare(
            'INSERT INTO STYLE_postupleniya (data_postupleniya, id_tovar, kolichestvo, primechanie)
             VALUES (?, ?, ?, ?)'
        )->execute([$dt, $tid, $signedQty, $note]);
        $pdo->commit();

        return null;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return 'Ошибка записи операции на складе.';
    }
}

if ($hasSklad && $hasPost && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tid = (int) ($_POST['id_tovar'] ?? 0);
    $kol = (int) ($_POST['kolichestvo'] ?? 0);
    $dt = trim((string) ($_POST['data_postupleniya'] ?? ''));
    $prim = trim((string) ($_POST['primechanie'] ?? ''));

    if (isset($_POST['postuplenie'])) {
        $err = warehouse_log_movement($pdo, $tid, $kol, $dt, $prim, true);
        flash_set($err ?? 'Поступление учтено, остаток увеличен.', $err ? 'error' : 'success');
    } elseif (isset($_POST['spisanie'])) {
        $err = warehouse_log_movement($pdo, $tid, $kol, $dt, $prim, false);
        flash_set($err ?? 'Списание учтено, остаток уменьшен.', $err ? 'error' : 'success');
    }
    redirect('warehouse.php');
}

$ostatki = [];
$dvizheniya = [];
if ($hasSklad) {
    $params = [];
    $where = staff_sql_warehouse_where($filters, $params);
    $sql = 'SELECT o.id_tovar, o.kolichestvo, t.nazvanie, t.articul
         FROM STYLE_sklad_ostatok o
         INNER JOIN STYLE_tovary t ON t.id_tovar = o.id_tovar
         WHERE 1=1' . $where . ' ORDER BY t.nazvanie';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $ostatki = $st->fetchAll();
}

if ($hasPost) {
    $dvSt = $pdo->query(
        'SELECT p.id_postuplenie, p.data_postupleniya, p.kolichestvo, p.primechanie,
                t.nazvanie, t.articul
         FROM STYLE_postupleniya p
         INNER JOIN STYLE_tovary t ON t.id_tovar = p.id_tovar
         ORDER BY p.data_postupleniya DESC, p.id_postuplenie DESC
         LIMIT 40'
    );
    $dvizheniya = $dvSt->fetchAll();
}

$tovaryParams = [];
$tovaryWhere = staff_sql_warehouse_where($filters, $tovaryParams);
$tovarySql = 'SELECT id_tovar, nazvanie, articul FROM STYLE_tovary t WHERE 1=1' . $tovaryWhere . ' ORDER BY nazvanie, id_tovar LIMIT 500';
$stT = $pdo->prepare($tovarySql);
$stT->execute($tovaryParams);
$tovaryList = $stT->fetchAll();

$pageTitle = 'Склад — City Style';
$staffHome = '../';
require __DIR__ . '/../../includes/layout_staff_top.php';
require __DIR__ . '/../_nav.php';
?>

<main class="staff-main">
    <h1>Склад и поступления</h1>
    <?php if (!$hasSklad || !$hasPost): ?>
        <div class="panel flash-info">
            <p>Таблицы склада не созданы. Выполните SQL из файла <code>php/sql/schema_cart_stock.sql</code> в phpMyAdmin, затем обновите страницу.</p>
        </div>
    <?php else: ?>
        <?php staff_render_filter_panel('warehouse.php', $filters, 'warehouse'); ?>
        <div class="warehouse-forms">
            <div class="panel">
                <h3>Учёт поступления</h3>
                <p class="staff-hint">Увеличивает остаток на складе.</p>
                <form method="post" class="warehouse-form">
                    <input type="hidden" name="postuplenie" value="1">
                    <label>Товар
                        <select name="id_tovar" required>
                            <?php foreach ($tovaryList as $t): ?>
                                <option value="<?= (int) $t['id_tovar'] ?>"><?= h($t['nazvanie']) ?> (<?= h((string) ($t['articul'] ?? '')) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Количество *<input type="number" name="kolichestvo" required min="1" value="1"></label>
                    <label>Дата *<input type="date" name="data_postupleniya" required value="<?= h(date('Y-m-d')) ?>"></label>
                    <label>Примечание<input name="primechanie" placeholder="Например: поставка от поставщика"></label>
                    <button class="btn btn-primary" type="submit">Записать поступление</button>
                </form>
            </div>
            <div class="panel panel--warn">
                <h3>Списание / возврат</h3>
                <p class="staff-hint">Уменьшает остаток: брак, возврат поставщику, утеря при приёмке.</p>
                <form method="post" class="warehouse-form">
                    <input type="hidden" name="spisanie" value="1">
                    <label>Товар
                        <select name="id_tovar" required>
                            <?php foreach ($tovaryList as $t): ?>
                                <option value="<?= (int) $t['id_tovar'] ?>"><?= h($t['nazvanie']) ?> (<?= h((string) ($t['articul'] ?? '')) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Количество *<input type="number" name="kolichestvo" required min="1" value="1"></label>
                    <label>Дата *<input type="date" name="data_postupleniya" required value="<?= h(date('Y-m-d')) ?>"></label>
                    <label>Причина списания *
                        <input name="primechanie" required placeholder="Брак, возврат поставщику…">
                    </label>
                    <button class="btn btn-primary" type="submit">Списать со склада</button>
                </form>
            </div>
        </div>
        <div class="panel">
            <h3>Остатки по SKU</h3>
            <div class="staff-table-wrap">
            <table class="data-table">
                <thead><tr><th>Товар</th><th>Артикул</th><th>Остаток</th></tr></thead>
                <tbody>
                    <?php foreach ($ostatki as $o): ?>
                        <tr>
                            <td><?= h((string) ($o['nazvanie'] ?? '')) ?></td>
                            <td><?= h((string) ($o['articul'] ?? '')) ?></td>
                            <td><?= (int) ($o['kolichestvo'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php if (!$ostatki): ?>
                <p>Остатков пока нет — добавьте поступление.</p>
            <?php endif; ?>
        </div>
        <div class="panel">
            <h3>Журнал движений</h3>
            <p class="staff-hint">Все операции в таблице поступлений: «+» приход, «−» списание.</p>
            <div class="staff-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Товар</th>
                        <th>Кол-во</th>
                        <th>Примечание</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dvizheniya as $d): ?>
                        <?php $q = (int) ($d['kolichestvo'] ?? 0); ?>
                        <tr>
                            <td><?= h((string) ($d['data_postupleniya'] ?? '')) ?></td>
                            <td><?= h((string) ($d['nazvanie'] ?? '')) ?> <span class="muted">(<?= h((string) ($d['articul'] ?? '')) ?>)</span></td>
                            <td class="<?= $q >= 0 ? 'warehouse-qty--in' : 'warehouse-qty--out' ?>">
                                <?= $q >= 0 ? '+' : '' ?><?= $q ?>
                            </td>
                            <td><?= h((string) ($d['primechanie'] ?? '—')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php if (!$dvizheniya): ?>
                <p>Движений пока нет.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../../includes/layout_staff_bottom.php'; ?>
