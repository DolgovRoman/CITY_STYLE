<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/staff_auth.php';
require __DIR__ . '/../../includes/staff_filters.php';

require_staff();
require_staff_kind('manager');

$pdo = db();
$filters = staff_list_filters_from_request();
$dolzhnosti = $pdo->query('SELECT id_dolzhnost, nazvanie FROM STYLE_dolzhnost ORDER BY nazvanie')->fetchAll();

$params = [];
$where = staff_sql_employees_where($filters, $params);

$sql = 'SELECT s.id_sotrudnik, s.familiya, s.imya, s.email, d.nazvanie AS dolzhnost
     FROM STYLE_sotrudniki s
     INNER JOIN STYLE_dolzhnost d ON s.dolzhnost = d.id_dolzhnost
     WHERE 1=1' . $where . ' ORDER BY s.id_sotrudnik DESC';
$st = $pdo->prepare($sql);
$st->execute($params);
$list = $st->fetchAll();

$pageTitle = 'Сотрудники — City Style';
$staffHome = '../';
require __DIR__ . '/../../includes/layout_staff_top.php';
require __DIR__ . '/../_nav.php';
?>

<main class="staff-main">
    <h1>Сотрудники</h1>
    <div class="staff-toolbar">
        <a class="btn btn-primary" href="employee_edit.php">Добавить сотрудника</a>
    </div>
    <?php staff_render_filter_panel('employees.php', $filters, 'employees', ['dolzhnosti' => $dolzhnosti]); ?>
    <div class="staff-table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>ФИО</th>
                <th>Email</th>
                <th>Должность</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($list as $s): ?>
                <tr>
                    <td><?= (int) $s['id_sotrudnik'] ?></td>
                    <td><?= h(trim(($s['familiya'] ?? '') . ' ' . ($s['imya'] ?? ''))) ?></td>
                    <td><?= h((string) ($s['email'] ?? '')) ?></td>
                    <td><?= h((string) ($s['dolzhnost'] ?? '')) ?></td>
                    <td>
                        <a class="btn btn-ghost btn-small" href="employee_edit.php?id=<?= (int) $s['id_sotrudnik'] ?>">Изменить</a>
                        <form method="post" action="employee_delete.php" style="display:inline" onsubmit="return confirm('Удалить сотрудника?');">
                            <input type="hidden" name="id_sotrudnik" value="<?= (int) $s['id_sotrudnik'] ?>">
                            <button type="submit" class="btn btn-ghost" style="padding:4px 8px;font-size:12px">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php if (!$list): ?>
        <p>Сотрудники не найдены.</p>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../../includes/layout_staff_bottom.php'; ?>
